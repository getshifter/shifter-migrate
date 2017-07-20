<?php

/**
 * Plugin Name:       Shifter Migrate
 * Plugin URI:				https://getshifter.io
 * Description:       Simple archive and migrate tool for Shifter
 * Version:           1.0.0
 * Author:            Shifter
 * Author URI:        https://getshifter.io
 * License:						MIT
 * License URI:
 *
 * Requires at least: 3.9
 * Tested up to: 4.8
 *
 * Text Domain:       shifter-migrate
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
require plugin_dir_path( __FILE__ ) . 'includes/class-shifter-migrate.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_shifter_migrate() {

    $plugin = new Shifter_Migrate();

    if ( version_compare( phpversion(), '5.3.0' ) >= 0 ) {

        $plugin->run();

    } else {

        $plugin->abort();
    }

}
run_shifter_migrate();
?>
