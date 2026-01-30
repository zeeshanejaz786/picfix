<?php
/**
 * Plugin Name:       PicFix
 * Description:       Find unused images, detect duplicates, and convert images to the modern WebP format.
 * Version:           1.1.0
 * Author:            Zeeshan Ejaz
 * Author URI:        
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       picfix
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'PICFIX_VERSION', '1.1.0' );
define( 'PICFIX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PICFIX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class.
 */
require PICFIX_PLUGIN_DIR . 'includes/class-picfix.php';

/**
 * Begins execution of the plugin.
 */
function run_picfix() {
    $plugin = new Picfix();
    $plugin->run();
}
run_picfix();
