<?php
/**
 * @package Worpress_wordle_hun
 * @version 1.0.0
 */
/*
Plugin Name: WordPress Szózat
Plugin URI: http://https://github.com/sgtGiggsy/szozatjatek
Description: WordPress Szózat játék plugin statisztikákkal
Author: Király Béla
Version: 1.0.0
Author URI: https://github.com/sgtGiggsy
*/

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'SZOZAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once SZOZAT_PLUGIN_DIR . 'class.szozat.php';
require_once SZOZAT_PLUGIN_DIR . 'class.szozat-widget.php';
Szozat::init();

// Hookok a plugin aktiváláshoz, deaktiváláshoz, eltávolításhoz
register_activation_hook(__FILE__, array( 'Szozat', 'plugin_activation'));
register_deactivation_hook(__FILE__, array( 'Szozat', 'plugin_deactivation'));
register_uninstall_hook(__FILE__, ['Szozat', 'plugin_uninstall']);

// Admin felület
add_action('admin_menu', ['Szozat', 'admin_menu']);
add_action('admin_notices', ['Szozat', 'admin_notices']);

// Frontend felület
add_filter('query_vars', ['Szozat', 'register_query_var']);
add_action('wp_footer', ['Szozat', 'enqueue_assets']);