<?php
/**
 * Plugin Name: Property Manager
 * Plugin URI: https://lavron.dev/
 * Description: Real Estate Property Manager
 * Version: 1.0.1
 * Author: Viktor Lavron
 * Author URI: https://lavron.dev/
 * Text Domain: property_manager
 * Domain Path: /i18n/languages/
 * Requires at least: 5.3
 * Requires PHP: 5.4
 *
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'PM_PLUGIN_FILE' ) ) {
	define( 'PM_PLUGIN_FILE', __FILE__ );
}


if ( ! class_exists( 'PropertyManager', false ) ) {
	include_once dirname( PM_PLUGIN_FILE ) . '/includes/property-manager.php';
}

function PM() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return PropertyManager::instance();
}

// class WP_Property_Object extends PM_Property {

// }

// class WP_Property_Offer extends PM_Property {

// }
$GLOBALS['property_manager'] = PM();
