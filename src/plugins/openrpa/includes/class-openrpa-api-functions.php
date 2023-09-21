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

include_once PS_OPENRPA_PATH . 'includes/DateIntervalExtend.php';

/**
 * PS OpenRPA API Method Class
 *
 * Using From PS_OpenRPA_API Class
 */
class PS_OpenRPA_API_Method {
	/**
	 * Task 実行 JSON データフォーマットの保存バージョン
	 */
	const PS_OPENRPA_COMPLETE_TASK_JSON_VERSION = 1.0;

	/**
	 * Save Base Token Path
	 *
	 * @var string
	 */
	private $base_token_path = '/tmp';

	/**
	 * End Name Of Token File
	 *
	 * @var string
	 */
	private $token_end_name = '_session.txt';

	/**
	 * In Schedule, Allowed Minute Span
	 *
	 * @var integer
	 */
	private $minute_span = 5;

	/**
	 * Get Request Method
	 *
	 * @access public
	 *
	 * @return string $method Request Method
	 */
	public function get_request_method() {
		return sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
	}

	/**
	 * Create Token
	 *
	 * @access public
	 *
	 * @param string $user_id
	 *
	 * @return string $token
	 */
	public function create_token( $user_id ) {
		do {
			$token      = bin2hex( random_bytes( 32 ) );
			$token_file = $this->get_token_filepath( $token );
		} while ( file_exists( $token_file ) );

		$fp = fopen( $token_file, 'w' );
		fwrite( $fp, $user_id );
		fclose( $fp );

		return $token;
	}

	private function get_token_filepath( $token ) {
		return "{$this->base_token_path}/{$token}{$this->token_end_name}";
	}

	/**
	 * Check Token Is Correct Or InCorrect Or Expired
	 *
	 * @access public
	 *
	 * @param string $token
	 * @param string $user_id
	 *
	 * @return boolean
	 */
	public function check_token( $token, $user_id ) {
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
	 * @access public
	 *
	 * @param array $args In Header Args
	 *
	 * @return boolean
	 */
	public function check_header( $args ) {
		foreach ( $args as $arg ) {
			if ( ! array_key_exists( "HTTP_{$arg}", $_SERVER ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get Token In Header
	 *
	 * @access public
	 *
	 * @return boolean|string $token if not set return false
	 */
	public function get_token_in_header() {
		$token = sanitize_text_field( wp_unslash( $_SERVER['HTTP_TOKEN'] ?? '' ) );

		if ( '' === $token ) {
			return false;
		}

		return $token;
	}

	/**
	 * Check Logged In And Application Key
	 *
	 * @access public
	 *
	 * @return boolean
	 */
	public function check_user_and_key() {
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
	 * @access private
	 *
	 * @param string $duration ISO 8601 duration format. extend Week [W].
	 * @param \DateTimeImmutable $post_date_gmt
	 *
	 * @return string
	 */
	private function calc_next_schedule( string $duration, \DateTimeImmutable $post_date_gmt ) {
		$interval = new \PrimeStrategy\WP_Plugin\PS_OpenRPA\DateIntervalExtend( $duration );
		$timezone = new \DateTimeZone( \DateTimeZone::UTC );
		$now      = new \DateTimeImmutable( 'now', $timezone );
		$format   = 'Y-m-d H:i:s';

		// for minutely
		if ( 0 === ( $interval->m + $interval->w + $interval->d + $interval->h ) && 0 < $interval->i ) {
			$diff          = $post_date_gmt->diff( $now );
			$diff_unit     = ( ( ( $diff->format( '%r%a' ) * 24 + $diff->format( '%r%h' ) ) * 60 + $diff->format( '%r%i' ) ) * 60 + $diff->format( '%r%s' ) ) / 60;
			$interval_add  = new DateInterval( 'PT' . ( ceil( $diff_unit / $interval->i ) * $interval->i ) . 'M' );
			$next_datetime = $post_date_gmt->add( $interval_add );

			return $next_datetime->format( $format );
		}

		// for hourly
		if ( 0 === ( $interval->m + $interval->w + $interval->d ) && 0 < $interval->h ) {
			$hour          = (int) $now->format( 'H' );
			$interval_add  = new DateInterval( "PT{$interval->h}H" );
			$next_datetime = $now->setTime( $hour, $interval->i )->add( $interval_add );

			return $next_datetime->format( $format );
		}

		// for monthly, weekly, daily
		if ( ( 0 === $interval->d && 0 < $interval->m && 0 < $interval->w ) || ( 0 === ( $interval->m + $interval->d ) && 0 < $interval->w ) || ( 0 === ( $interval->m + $interval->w ) && 0 < $interval->d ) ) {
			$add_day = $interval->d;

			// weekly to daily
			if ( 0 < $interval->w ) {
				$weekday      = (int) $now->format( 'w' );	// 0 (Sun) ... 6 (Mon)
				$weekly_shift = ( $interval->w << ( 7 - $weekday ) & 0b1111111 ) | $interval->w >> $weekday;

				for ( $i = 0; $i < 7; ++$i ) {
					if ( 1 === ( $weekly_shift & ( 1 << $i ) ) ) {
						$add_day = $i + 1;
						break;
					}
				}
			}

			$interval_add  = new DateInterval( "P{$interval->m}M{$add_day}D" );
			$next_datetime = $now->setTime( $interval->h, $interval->i )->add( $interval_add );

			return $next_datetime->format( $format );
		}

		return '';
	}

	/**
	 * Get User Task
	 *
	 * @access public
	 *
	 * @param string $user_id Requested User Id
	 *
	 * @return array
	 */
	public function get_user_task( $user_id ) {
		$response = array();
		$posts    = get_posts(
			array(
				'author'         => $user_id,
				'post_type'      => 'task',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		$timezone      = new \DateTimeZone( \DateTimeZone::UTC );
		$post_date_gmt = new \DateTimeImmutable( $post->post_date_gmt, $timezone );

		foreach ( $posts as $post ) {
			$task_obj  = json_decode( $post->post_content );
			$schedules = get_post_meta( $post->ID, '_schedule_time' );
			$do_times  = array();

			foreach ( $schedules as $schedule ) {
				$next = $this->calc_next_schedule( $schedule['format'], $post_date_gmt );

				if ( '' !== $next ) {
					$do_times[] = $next;
				}
			}

			if ( array() === $do_times ) {
				continue;
			}

			$response[] = array(
				'id'       => $post->ID,
				'name'     => $task_obj->name,
				'command'  => $task_obj->command,
				'schedule' => array_unique( $do_times ),
			);
		}

		return $response;
	}

	/**
	 * Add User Task
	 *
	 * @access public
	 *
	 * @param string $user_id Requested User Id
	 *
	 * @return array
	 */
	public function add_user_task( $user_id ) {
		// Not yet implemented
		$error = new PS_OpenRPA_API_Error();

		return $error->Error_503();
	}


	/**
	 * Update User Task
	 *
	 * @access public
	 *
	 * @param string $user_id Requested User Id
	 * @param string $task_id Requested Task Id
	 *
	 * @return array
	 */
	public function update_user_task( $user_id, $task_id ) {
		// Not yet implemented
		$error = new PS_OpenRPA_API_Error();

		return $error->Error_503();
	}

	/**
	 * Delete User Task
	 *
	 * @access public
	 *
	 * @param string $user_id Requested User Id
	 * @param string $task_id Requested Task Id
	 *
	 * @return array
	 */
	public function delete_user_task( $user_id, $task_id ) {
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
	 * @access public
	 *
	 * @param string $user_id Requested User Id
	 *
	 * @return array
	 */
	public function get_complete_task( $user_id ) {
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
			$result_obj = json_decode( $post->post_content );
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
	 * @access public
	 *
	 * @param string $user_id Requested User Id
	 *
	 * @return array
	 */
	public function add_complete_task( $user_id ) {
		$timezone = new \DateTimeZone( \DateTimeZone::UTC );
		$now      = new \DateTimeImmutable( 'now', $timezone );
		$json     = file_get_contents( 'php://input' );
		$arr      = json_decode( $json, true );

		$data = array(
			'version' => PS_OPENRPA_COMPLETE_TASK_JSON_VERSION,
			'id'      => $arr['id'] ?? 0,
			'name'    => $arr['name'] ?? '',
			'command' => $arr['command'] ?? '',
			'start'   => $arr['start'] ?? '',
			'end'     => $arr['end'] ?? '',
			'next'    => $arr['next'] ?? '',
			'status'  => $arr['status'] ?? '',
		);

		$post_id = wp_insert_post(
			array(
				'post_title'   => $user_id . $now->format( '_Ymd_His' ),
				'post_type'    => 'result',
				'post_content' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
				'post_status'  => 'publish',
				'post_author'  => $user_id,
			)
		);

		return $post_id;
	}
}
