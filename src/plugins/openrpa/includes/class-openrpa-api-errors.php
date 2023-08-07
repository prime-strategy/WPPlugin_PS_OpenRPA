<?php
/**
 * PS OpenRPA API Error
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class PS_OpenRPA_API_Error {

	/**
	 * 400 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_400() {
		return new WP_Error( 400, 'Bad Request', array( 'status' => 400 ) );
	}

	/**
	 * 401 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_401() {
		return new WP_Error( 401, 'Unauthorized', array( 'status' => 401 ) );
	}

	/**
	 * 403 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_403() {
		return new WP_Error( 403, 'Forbidden', array( 'status' => 403 ) );
	}

	/**
	 * 404 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_404() {
		return new WP_Error( 404, 'Not Found', array( 'status' => 404 ) );
	}

	/**
	 * 405 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_405() {
		return new WP_Error( 405, 'Not Allowed', array( 'status' => 405 ) );
	}

	/**
	 * 500 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_500() {
		return new WP_Error( 500, 'Unknown Error', array( 'status' => 500 ) );
	}

	/**
	 * 502 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_502() {
		return new WP_Error( 502, 'Bad Gateway', array( 'status' => 502 ) );
	}

	/**
	 * 503 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_503() {
		return new WP_Error( 503, 'Temporary Unavailable', array( 'status' => 503 ) );
	}

	/**
	 * 504 Error
	 *
	 * @access public
	 *
	 * @return object WP_Error
	 */
	public function Error_504() {
		return new WP_Error( 504, 'Gateway Timeout', array( 'status' => 504 ) );
	}
}
