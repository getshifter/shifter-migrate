<?php

/**
 * Plugin Name:       Very simple backup for WordPress
 * Plugin URI:
 * Description:       This plugin is for small sites that do not need fancy WP plugins for backup jobs. It zip all files from Your WP directory and add database dump into zip.
 * Version:           1.1.0
 * Author:            Aleksandar Predic
 * Author URI:        http://acapredic.com/
 * License:
 * License URI:
 *
 * Requires at least: 3.9
 * Tested up to: 4.5
 *
 * Text Domain:       predic-simple-backup
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks if any.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-predic-simple-backup.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_predic_simple_backup() {

    $plugin = new Predic_Simple_Backup();

    if ( version_compare( phpversion(), '5.3.0' ) >= 0 ) {

        $plugin->run();

    } else {

        $plugin->abort();
    }

}
run_predic_simple_backup();
?>
