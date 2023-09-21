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
	 * Parse Day of the Week
	 * shift left for 7 times
	 *
	 * @access private
	 *
	 * @param integer $num
	 *
	 * @return array $dotw
	 */
	private function parse_dotw( $num ) {
		$week = array( '月', '火', '水', '木', '金', '土', '日' );
		$dotw = array();

		for ( $i = 0; $i < 7; $i++ ) {
			if ( $num & 1 << $i ) {
				array_push( $dotw, $week[ $i ] );
			}
		}

		return $dotw;
	}

	/**
	 * Calc Schedule Time
	 *
	 * @access private
	 *
	 * @param string $duration ISO 8601 duration format. extend Week [W].
	 * @param \DateTimeImmutable $now
	 * @param string $post_date
	 *
	 * @return string | boolean
	 */
	private function calc_next_schedule( $duration, $now ) {
		preg_match( '/^(P.*?)(?:([0-9]{1})W)?([^W]*?)$/', $duration, $matches, PREG_OFFSET_CAPTURE );

		$interval = new \DateInterval( $matches[1][0] . $matches[3][0] );
		$month    = (int) $interval->m;
		$week     = (int) $matches[2][0];
		$day      = (int) $interval->d;
		$hour     = (int) $interval->h;
		$minute   = (int) $interval->i;

		$now_minute = (int) $now->format( 'i' );
		$now_hour   = (int) $now->format( 'H' );
		$now_month  = (int) $now->format( 'm' );
		$do_time    = $now->format( 'Y-m-d' );

		if ( 0 === ( $month + $week + $day + $hour ) && 0 < $minute ) {
			// for minute
			for ( $do = $minute; $do <= 60; $do += $minute ) {
				if ( $now_minute < $do ) {
					if ( 60 === $do ) {
						$do = 0;
						++$now_hour;
					}

					$do = sprintf( '%02d', $do );

					return "{$do_time} {$now_hour}:{$do}";
				}
			}

			return false;
		}

		if ( 0 < $month || 0 < $week ) {
			// for month, week
			$dotw         = $this->parse_dotw( $week );
			$dotw_arr     = array( '日', '月', '火', '水', '木', '金', '土' );
			$current_dotw = $dotw_arr[ date( 'w' ) ];

			if ( ! in_array( $current_dotw, $dotw, true ) || $hour !== $now_hour || ( 0 < $month && 0 !== $now_month % $month ) ) {
				return false;
			}
		} else {
			// for day, hour
			if ( 0 !== $now_hour % $hour ) {
				return false;
			}
		}

		// for month, week, day, hour
		if ( $now_minute <= $minute && $minute < $now_minute + $this->minute_span ) {
			return "{$do_time} {$now_hour}:{$minute}";
		}

		return false;
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
		$timezone = new \DateTimeZone( get_option( 'timezone_string' ) );
		$now      = new \DateTimeImmutable( 'now', $timezone );
		$response = array();
		$posts    = get_posts(
			array(
				'author'         => $user_id,
				'post_type'      => 'task',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		date_default_timezone_set( $timezone->getName() );

		foreach ( $posts as $post ) {
			$task_obj  = json_decode( $post->post_content );
			$schedules = get_post_meta( $post->ID, '_schedule_time' );

			$do_times = array();
			foreach ( $schedules as $schedule ) {
				$next = $this->calc_next_schedule( $schedule['format'], $now );
				if ( false === $next ) {
					continue;
				}
				array_push( $do_times, $next );
			}

			if ( true === empty( $do_times ) ) {
				continue;
			}
			$do_times = array_unique( $do_times );
			array_push(
				$response,
				array(
					'id'       => $post->ID,
					'name'     => $task_obj->name,
					'command'  => $task_obj->command,
					'schedule' => $do_times,
				)
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
		$args = array(
			'ID'          => $task_id,
			'post_status' => 'draft',
		);
		wp_update_post( $args );
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

		$args  = array(
			'author'         => $user_id,
			'post_type'      => 'result',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$result_obj = json_decode( $post->post_content );
			$schedules  = get_post_meta( $result_obj->id, '_schedule_time' );
			array_push(
				$response,
				array(
					'id'       => $result_obj->id,
					'name'     => $result_obj->name,
					'command'  => $result_obj->command,
					'start'    => $result_obj->start,
					'end'      => $result_obj->end,
					'next'     => $result_obj->next,
					'status'   => $result_obj->status,
					'schedule' => $schedules,
				)
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
		$json = file_get_contents( 'php://input' );
		$arr  = json_decode( $json, true );
		$now  = date( 'Ymd_His' );

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

		$post_array = array(
			'post_title'   => $user_id . '_' . $now,
			'post_type'    => 'result',
			'post_content' => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
			'post_status'  => 'publish',
			'post_author'  => $user_id,
		);

		$post_id = wp_insert_post( $post_array );

		return $post_id;
	}
}
