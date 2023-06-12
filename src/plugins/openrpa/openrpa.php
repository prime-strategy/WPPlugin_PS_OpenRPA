<?php
/**
 * Plugin Name: PS OpenRPA
 * Description: OpenRPA連携プラグイン
 * Version: 1.0.0
 * Author: Prime Strategy Co.,Ltd.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! defined( 'PS_OPENRPA_FILE' ) ) {
	define( 'PS_OPENRPA_FILE', __FILE__ );
}

if ( ! class_exists( 'PS_OpenRPA_API' ) ) {
	require_once plugin_dir_path( PS_OPENRPA_FILE ) . 'includes/class-openrpa.php';
}
