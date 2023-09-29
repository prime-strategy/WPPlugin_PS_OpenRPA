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
	 */
	public function Error_400(): WP_Error {
		return new WP_Error( 400, 'Bad Request', array( 'status' => 400 ) );
	}

	/**
	 * 401 Error
	 */
	public function Error_401(): WP_Error {
		return new WP_Error( 401, 'Unauthorized', array( 'status' => 401 ) );
	}

	/**
	 * 403 Error
	 */
	public function Error_403(): WP_Error {
		return new WP_Error( 403, 'Forbidden', array( 'status' => 403 ) );
	}

	/**
	 * 404 Error
	 */
	public function Error_404(): WP_Error {
		return new WP_Error( 404, 'Not Found', array( 'status' => 404 ) );
	}

	/**
	 * 405 Error
	 */
	public function Error_405(): WP_Error {
		return new WP_Error( 405, 'Not Allowed', array( 'status' => 405 ) );
	}

	/**
	 * 500 Error
	 */
	public function Error_500(): WP_Error {
		return new WP_Error( 500, 'Unknown Error', array( 'status' => 500 ) );
	}

	/**
	 * 502 Error
	 */
	public function Error_502(): WP_Error {
		return new WP_Error( 502, 'Bad Gateway', array( 'status' => 502 ) );
	}

	/**
	 * 503 Error
	 */
	public function Error_503(): WP_Error {
		return new WP_Error( 503, 'Temporary Unavailable', array( 'status' => 503 ) );
	}

	/**
	 * 504 Error
	 */
	public function Error_504(): WP_Error {
		return new WP_Error( 504, 'Gateway Timeout', array( 'status' => 504 ) );
	}
}
