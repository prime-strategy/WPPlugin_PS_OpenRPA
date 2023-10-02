<?php
/**
 * PS OpenRPA API Method
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'PS_OpenRPA_API_Error' ) ) {
	include_once PS_OPENRPA_PATH . 'includes/class-openrpa-api-errors.php';
}

require_once PS_OPENRPA_PATH . 'includes/DateIntervalExtend.php';

/**
 * PS OpenRPA API Method Class
 *
 * Using From PS_OpenRPA_API Class
 */
final class PS_OpenRPA_API_Method {
	/**
	 * Task 実行 JSON データフォーマットの保存バージョン
	 *
	 * @var string
	 */
	private const PS_OPENRPA_COMPLETE_TASK_JSON_VERSION = '1.0';

	/**
	 * Save Base Token Path
	 */
	private string $base_token_path = '/tmp';

	/**
	 * End Name Of Token File
	 */
	private string $token_end_name = '_session.txt';

	// /**
	//  * In Schedule, Allowed Minute Span
	//  */
	// private int $minute_span = 5;

	/**
	 * Get Request Method
	 *
	 * @return string $method Request Method
	 */
	public function get_request_method(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
	}

	/**
	 * Create Token
	 *
	 * @param $user_id
	 */
	public function create_token( string $user_id ): string {
		do {
			$token      = bin2hex( random_bytes( 32 ) );
			$token_file = $this->get_token_filepath( $token );
		} while ( file_exists( $token_file ) );

		$fp = fopen( $token_file, 'w' );
		fwrite( $fp, $user_id );
		fclose( $fp );

		return $token;
	}

	private function get_token_filepath( string $token ): string {
		return $this->base_token_path . '/' . $token . $this->token_end_name;
	}

	/**
	 * Check Token Is Correct Or InCorrect Or Expired
	 *
	 * @param $token
	 * @param $user_id
	 */
	public function check_token( string $token, string $user_id ): bool {
		$token_file = $this->get_token_filepath( $token );

		if ( is_readable( $token_file ) ) {
			$fp = fopen( $token_file, 'r' );
			$id = fgets( $fp );
			fclose( $fp );

			return $id === $user_id;
		}

		return false;
	}

	/**
	 * Check Header Args
	 *
	 * @param string[] $args In Header Args
	 */
	public function check_header( array $args ): bool {
		foreach ( $args as $arg ) {
			if ( ! isset( $_SERVER[ 'HTTP_' . $arg ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get Token In Header
	 */
	public function get_token_in_header(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_TOKEN'] ?? '' ) );
	}

	/**
	 * Check Logged In And Application Key
	 */
	public function check_user_and_key(): bool {
		$username = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USERNAME'] ?? '' ) );
		$key      = sanitize_text_field( wp_unslash( $_SERVER['HTTP_APPLICATIONKEY'] ?? '' ) );

		// remove space
		$key = str_replace( ' ', '', $key );

		// check user exists
		$user_obj = get_user_by( 'login', $username );

		if ( ! $user_obj ) {
			return false;
		}

		// check appkey is correct
		$user_app_keys = WP_Application_Passwords::get_user_application_passwords( $user_obj->ID );

		foreach ( $user_app_keys as $user_app_key ) {
			if ( wp_check_password( $key, $user_app_key['password'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Calc Schedule Time
	 *
	 * @param $duration ISO 8601 duration format. extend Weekdays [A].
	 * @param $post_date_gmt
	 */
	private function calc_next_schedule( string $duration, \DateTimeImmutable $post_date_gmt ): string {
		$interval = new \PrimeStrategy\WP_Plugin\PS_OpenRPA\DateIntervalExtend( $duration );
		$timezone = new \DateTimeZone( 'UTC' );
		$now      = new \DateTimeImmutable( 'now', $timezone );
		$format   = 'Y-m-d H:i';

		// for minutely
		if ( 0 === ( $interval->m + $interval->a + $interval->d + $interval->h ) && 0 < $interval->i ) {
			$diff_seconds  = $now->getTimestamp() - $post_date_gmt->getTimestamp();
			$diff_minutes  = ceil( $diff_seconds / 60 / $interval->i ) * $interval->i;
			$interval_add  = new DateInterval( "PT%{$diff_minutes}M" );
			$next_datetime = $post_date_gmt->add( $interval_add );

			return $next_datetime->format( $format );
		}

		// $minute = (int) $now->format( 'i' );
		// if ( $minute > $interval->i && $interval->i >= $minute + $this->minute_span ) {
		//  return '';
		// }

		// for hourly
		if ( 0 === ( $interval->m + $interval->a + $interval->d ) && 0 < $interval->h ) {
			$hour          = (int) $now->format( 'H' );
			$interval_add  = new DateInterval( "PT{$interval->h}H" );
			$next_datetime = $now->setTime( $hour, $interval->i )->add( $interval_add );

			return $next_datetime->format( $format );
		}

		// for monthly, weekly, daily
		if ( ( 0 === $interval->d && 0 < $interval->m && 0 < $interval->a ) || ( 0 === ( $interval->m + $interval->d ) && 0 < $interval->a ) || ( 0 === ( $interval->m + $interval->a ) && 0 < $interval->d ) ) {
			$diff_days = $interval->d;

			// weekly to daily
			if ( 0 < $interval->a ) {
				$weekday   = (int) $now->format( 'w' ); // 0 (Sun) ... 6 (Sat)
				$diff_days = $interval->next_bitflag( $weekday + 1, $interval->a, 7 );
			}

			$interval_add  = new DateInterval( "P{$interval->m}M{$diff_days}D" );
			$next_datetime = $now->setTime( $interval->h, $interval->i )->add( $interval_add );

			return $next_datetime->format( $format );
		}

		return '';
	}

	/**
	 * Get User Task
	 *
	 * @param $user_id Requested User Id
	 *
	 * @return mixed[]
	 */
	public function get_user_task( string $user_id ): array {
		$response = array();
		$posts    = get_posts(
			array(
				'author'         => $user_id,
				'post_type'      => 'task',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);
		$timezone = new \DateTimeZone( 'UTC' );

		foreach ( $posts as $post ) {
			$post_date_gmt = new \DateTimeImmutable( $post->post_date_gmt, $timezone );
			$task_obj      = json_decode( $post->post_content, null, 512, JSON_THROW_ON_ERROR );
			$schedules     = get_post_meta( $post->ID, '_schedule_time' );
			$do_times      = array();

			foreach ( $schedules as $schedule ) {
				$next = $this->calc_next_schedule( $schedule['format'], $post_date_gmt );

				if ( '' !== $next ) {
					$do_times[] = $next;
				}
			}

			if ( array() !== $do_times ) {
				$response[] = array(
					'id'       => $post->ID,
					'name'     => $task_obj->name,
					'command'  => $task_obj->command,
					'schedule' => array_unique( $do_times ),
				);
			}
		}

		return $response;
	}

	/**
	 * Add User Task
	 */
	public function add_user_task(): WP_Error {
		// Not yet implemented
		$error = new PS_OpenRPA_API_Error();

		return $error->Error_503();
	}


	/**
	 * Update User Task
	 */
	public function update_user_task(): WP_Error {
		// Not yet implemented
		$error = new PS_OpenRPA_API_Error();

		return $error->Error_503();
	}

	/**
	 * Delete User Task
	 *
	 * @param $task_id Requested Task Id
	 */
	public function delete_user_task( string $task_id ) {
		wp_update_post(
			array(
				'ID'          => $task_id,
				'post_status' => 'draft',
			)
		);
	}

	/**
	 * Get Completed Task
	 *
	 * @param $user_id Requested User Id
	 *
	 * @return mixed[]
	 */
	public function get_complete_task( string $user_id ): array {
		$response = array();
		$posts    = get_posts(
			array(
				'author'         => $user_id,
				'post_type'      => 'result',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		foreach ( $posts as $post ) {
			$result_obj = json_decode( $post->post_content, null, 512, JSON_THROW_ON_ERROR );
			$schedules  = get_post_meta( $result_obj->id, '_schedule_time' );
			$response[] = array(
				'id'       => $result_obj->id,
				'name'     => $result_obj->name,
				'command'  => $result_obj->command,
				'start'    => $result_obj->start,
				'end'      => $result_obj->end,
				'next'     => $result_obj->next,
				'status'   => $result_obj->status,
				'schedule' => $schedules,
			);
		}

		return $response;
	}

	/**
	 * Add Completed Task
	 *
	 * @param $user_id Requested User Id
	 *
	 * @return mixed[]
	 */
	public function add_complete_task( string $user_id ): array {
		$timezone = new \DateTimeZone( 'UTC' );
		$now      = new \DateTimeImmutable( 'now', $timezone );
		$json     = file_get_contents( 'php://input' );
		$arr      = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );

		$data = array(
			'version' => self::PS_OPENRPA_COMPLETE_TASK_JSON_VERSION,
			'id'      => $arr['id'] ?? 0,
			'name'    => $arr['name'] ?? '',
			'command' => $arr['command'] ?? '',
			'start'   => $arr['start'] ?? '',
			'end'     => $arr['end'] ?? '',
			'next'    => $arr['next'] ?? '',
			'status'  => $arr['status'] ?? '',
		);

		return wp_insert_post(
			array(
				'post_title'   => $user_id . $now->format( '_Ymd_His' ),
				'post_type'    => 'result',
				'post_content' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
				'post_status'  => 'publish',
				'post_author'  => $user_id,
			)
		);
	}
}
