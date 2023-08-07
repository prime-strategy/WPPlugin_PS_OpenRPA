<?php
/**
 * PS OpenRPA setup.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Main PS OpenRPA class
 */
class PS_OpenRPA {
	/**
	 * PS OpenRPA Version
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Class Constructor
	 */
	public function __construct() {
		$this->define_constants();
		$this->include_classes();
	}

	/**
	 * Define PS OpenRPA Constants
	 *
	 * @access private
	 */
	private function define_constants() {
		$this->define( 'PS_OPENRPA_BASENAME', plugin_basename( PS_OPENRPA_FILE ) );
		$this->define( 'PS_OPENRPA_PATH', plugin_dir_path( PS_OPENRPA_FILE ) );
		$this->define( 'PS_OPENRPA_VERSION', $this->version );
	}

	/**
	 * Define Constant Only If Not Set
	 *
	 * @access private
	 *
	 * @param string $name Constant Name
	 * @param string $value Constant Value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include Core Files Used In Front
	 *
	 * @access private
	 */
	private function include_classes() {
		$this->include( 'PS_OpenRPA_API', 'includes/class-openrpa-api.php' );
		$this->include( 'PS_OpenRPA_Admin', 'admin/class-openrpa-admin.php' );
		//$this->include( 'PS_OpenRPA_Time_Scheduler', 'includes/class-openrpa-scheduler.php' );
	}

	/**
	 * Include Classes Only If Not Set
	 *
	 * @param string $name Class Name
	 * @param string $path Class File Path
	 *
	 * @access private
	 */
	private function include( $name, $path ) {
		if ( ! class_exists( $name ) ) {
			include_once PS_OPENRPA_PATH . $path;
		}
	}
}

new PS_OpenRPA();
