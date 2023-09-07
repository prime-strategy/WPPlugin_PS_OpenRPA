<?php
/**
 * PS OpenRPA API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'PS_OpenRPA_API_Method' ) ) {
	include_once PS_OPENRPA_PATH . 'includes/class-openrpa-api-functions.php';
}

if ( ! class_exists( 'PS_OpenRPA_API_Error' ) ) {
	include_once PS_OPENRPA_PATH . 'includes/class-openrpa-api-errors.php';
}

/**
 * PS OpenRPA API Class
 */
class PS_OpenRPA_API {
	/**
	 * PS OpenRPA Version
	 *
	 * @var string
	 */
	private $API_VERSION = 'v1';

	/**
	 * PS OpenRPA Endpoint Prefix
	 *
	 * @var string
	 */
	private $API_ENDPOINT_PREFIX_NAME = 'openrpa';

	/**
	 * PS OpenRPA Method Variable
	 *
	 * @var null|object
	 */
	private $Method = null;

	/**
	 * PS OpenRPA Error Variable
	 *
	 * @var null|object
	 */
	private $Error = null;

	/**
	 * Initialize Constants And API Hooks
	 */
	public function __construct() {
		$this->Method = new PS_OpenRPA_API_Method();
		$this->Error  = new PS_OpenRPA_API_Error();
		$this->define_constants();
		add_action( 'rest_api_init', array( $this, 'enqueue_endpoint' ) );
	}

	/**
	 * Define PS OpenRPA API Constants
	 *
	 * @access private
	 */
	private function define_constants() {
		$this->define( 'PS_OPENRPA_API_ENDPOINT', $this->API_ENDPOINT_PREFIX_NAME . '/' . $this->API_VERSION );
		$this->define( 'PS_OPENRPA_API_ALLOW_USERROLE', 'administrator' );
	}

	/**
	 * Define Constants Only If Not Set
	 *
	 * @access private
	 *
	 * @param string $name Constant Name
	 * @param string|bool $value Constant Value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Enqueue Needed EndPoint
	 *
	 * @access public
	 */
	public function enqueue_endpoint() {
		// Login EndPoint => GET
		register_rest_route(
			PS_OPENRPA_API_ENDPOINT,
			'/login',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'login' ),
			),
		);

		// User Task GET POST EndPoint => GET, POST
		register_rest_route(
			PS_OPENRPA_API_ENDPOINT,
			'/user/(?P<UserId>[\d]+)/task',
			array(
				'methods'  => array( 'GET', 'POST' ),
				'callback' => array( $this, 'task_router' ),
				//'permission_callback' => array( $this, 'rest_permission' ),
			),
		);

		// User Task PUT DELETE EndPoint => PUT, POST
		/*
		register_rest_route(
			PS_OPENRPA_API_ENDPOINT,
			'/user/(?P<UserId>[\d]+)/task/(?P<TaskId>[\d]+)',
			array(
				'methods' => array( 'PUT', 'DELETE' ),
				'callback' => array( $this, 'modify_router' ),
				//'permission_callback' => array( $this, 'rest_permission' ),
			),
		);
		*/
		// Completed Task GET POST EndPoint => GET, POST
		register_rest_route(
			PS_OPENRPA_API_ENDPOINT,
			'/task/(?P<UserId>[\d]+)',
			array(
				'methods'  => array( 'GET', 'POST' ),
				'callback' => array( $this, 'complete_router' ),
				//'permission_callback' => array( $this, 'rest_permission' ),
			)
		);
	}

	/**
	 * Only Allow PS_OPENRPA_API_ALLOW_USERROLE
	 *
	 * @access public
	 */
	public function rest_permission() {
		return current_user_can( PS_OPENRPA_API_ALLOW_USERROLE );
	}

	/**
	 * Login
	 *
	 * @access public
	 *
	 * @param string $username In Header
	 * @param string $application_key In Header
	 *
	 * @return object $userId,$token|$message Json Or Failed Message
	 */
	public function login( $request ) {
		$method = $this->Method->get_request_method();

		// method is not GET ... Maybe Unneeded
		if ( $method !== 'GET' ) {
			return $this->Error->Error_405();
		}

		// username and application key are not in header
		if ( ! $this->Method->check_header( array( 'USERNAME', 'APPLICATIONKEY' ) ) ) {
			return $this->Error->Error_400();
		}

		// if user not found
		if ( ! $this->Method->check_user_and_key() ) {
			return $this->Error->Error_404();
		}

		$user_obj = get_user_by( 'login', esc_html( $_SERVER['HTTP_USERNAME'] ?? '' ) );
		$user_id  = $user_obj->ID;
		$token    = $this->Method->create_token( $user_id );

		return array( 'userId' => $user_id, 'token' => $token );
	}

	/**
	 * For Get User Task
	 *
	 * @access public
	 *
	 * @param object $request
	 * -- @param string $token In Header --
	 * -- @param string $userId In Path   --
	 *
	 * @return array $message Success Or Failed Message
	 */
	public function task_router( $request ) {
		$method  = $this->Method->get_request_method();
		$user_id = $request['UserId'];

		// method is not GET or POST ... Maybe Unneeded
		if ( $method !== 'GET' && $method !== 'POST' ) {
			return $this->Error->Error_405();
		}

		// get token
		$token = $this->Method->get_token_in_header();
		if ( ! $token ) {
			return $this->Error->Error_400();
		}

		// check token is correct or not expired
		if ( ! $this->Method->check_token( $token, $user_id ) ) {
			return $this->Error->Error_401();
		}

		// do process, switching by method
		switch ( $method ) {
			case 'GET':
				return $this->Method->get_user_task( $user_id );
			case 'POST':
				return $this->Method->add_user_task( $user_id );
			default:
				return $this->Error->Error_500();
		}

	}

	/**
	 * For Update User Task
	 *
	 * @access public
	 *
	 * @param object $request
	 * -- @param string $token In Header --
	 * -- @param string $userId In Path   --
	 * -- @param string $taskId In Path   --
	 *
	 * @return array $message Success Or Failed Message
	 */
	public function modify_router( $request ) {
		$method  = $this->Method->get_request_method();
		$user_id = $request['UserId'];
		$task_id = $request['TaskId'];

		// method is not PUT or DELETE ... Maybe Unneeded
		if ( $method !== 'PUT' && $method !== 'DELETE' ) {
			return $this->Error->Error_405();
		}

		// get token
		$token = $this->Method->get_token_in_header();
		if ( ! $token ) {
			return $this->Error->Error_400();
		}

		// check token is correct or not expired
		if ( ! $this->Method->check_token( $token, $user_id ) ) {
			return $this->Error->Error_401();
		}

		// do process, switching by method
		switch ( $method ) {
			case 'PUT':
				return $this->Method->update_user_task( $user_id, $task_id );
			case 'DELETE':
				return $this->Method->delete_user_task( $user_id, $task_id );
			default:
				return $this->Error->Error_500();
		}
	}

	/**
	 * For Completed Task
	 *
	 * @access public
	 *
	 * @param object $request
	 * -- @param string $token In Header --
	 * -- @param string $userId In Path   --
	 *
	 * @return array $message Success Or Failed Message
	 */
	public function complete_router( $request ) {
		$method  = $this->Method->get_request_method();
		$user_id = $request['UserId'];

		// method is not GET or POST ... Maybe Unneeded
		if ( $method !== 'GET' && $method !== 'POST' ) {
			return $this->Error->Error_405();
		}

		// get token
		$token = $this->Method->get_token_in_header();

		if ( ! $token ) {
			return $this->Error->Error_400();
		}

		// check token is correct or not expired
		if ( ! $this->Method->check_token( $token, $user_id ) ) {
			return $this->Error->Error_401();
		}

		// do process, switching by method
		switch ( $method ) {
			case 'GET':
				return $this->Method->get_complete_task( $user_id );
			case 'POST':
				return $this->Method->add_complete_task( $user_id );
			default:
				return $this->Error->Error_500();
		}
	}


}

new PS_OpenRPA_API();
