<?php
/**
 * Plugin Name: bzzix_restrictive_categories
 * Version: 1.0.0
 * Plugin URI: http://bzzix.com/
 * Description: Control and restrict the categories that users can publish posts in. With bzzix_restrictive_categories, you can assign specific categories to user roles and make category selection mandatory before publishing. Perfect for multi-author websites that need precise content organization.
 * Author: Mahmoud Hassan
 * Author URI: https://wa.me/00201062332549
 * Requires at least: 4.0
 * Tested up to: 6.0
 * Requires PHP: 8.0
 *
 * Text Domain: bzzix-restrictive-categories
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Mahmoud Hassan
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('admin_init', function() {
    $roles = wp_roles()->roles;
    $options = array();

    foreach ($roles as $role_key => $role_data) {
        $options[$role_key] = $role_data['name'];
    }

    update_option('bzzix_restrictive_categories_roles_options', $options);
});

// Load plugin class files.
require_once 'includes/class-bzzix-restrictive-categories.php';
require_once 'includes/class-bzzix-restrictive-categories-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-bzzix-restrictive-categories-admin-api.php';
// Load plugin core.
require_once 'includes/core/restrictive-categories-hooks.php';

/**
 * Returns the main instance of bzzix_restrictive_categories to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object bzzix_restrictive_categories
 */
function bzzix_restrictive_categories() {
	$instance = bzzix_restrictive_categories::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = bzzix_restrictive_categories_Settings::instance( $instance );
	}

	return $instance;
}

bzzix_restrictive_categories();
