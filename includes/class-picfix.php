<?php
/**
 * The core plugin class.
 *
 * @since      1.1.0
 * @package    Picfix
 * @subpackage Picfix/includes
 */

if ( ! class_exists( 'Picfix' ) ) {

    class Picfix {

        /**
         * The loader that's responsible for maintaining and registering all hooks that power
         * the plugin.
         * @var      Picfix_Loader    $loader
         */
        protected $loader;

        /**
         * The unique identifier of this plugin.
         * @var      string    $plugin_name
         */
        protected $plugin_name;

        /**
         * The current version of the plugin.
         * @var      string    $version
         */
        protected $version;

        /**
         * Define the core functionality of the plugin.
         */
        public function __construct() {
            $this->version = PICFIX_VERSION;
            $this->plugin_name = 'picfix';

            $this->load_dependencies();
            $this->define_admin_hooks();
        }

        /**
         * Load the required dependencies for this plugin.
         */
        private function load_dependencies() {
            require_once PICFIX_PLUGIN_DIR . 'includes/class-picfix-loader.php';
            require_once PICFIX_PLUGIN_DIR . 'admin/class-picfix-admin.php';
            $this->loader = new Picfix_Loader();
        }

        /**
         * Register all of the hooks related to the admin area functionality.
         */
        private function define_admin_hooks() {
            $plugin_admin = new Picfix_Admin( $this->get_plugin_name(), $this->get_version() );

            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
            $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );

            // Register AJAX handlers
            $this->loader->add_action( 'wp_ajax_picfix_scan_unused', $plugin_admin, 'ajax_scan_unused_images' );
            $this->loader->add_action( 'wp_ajax_picfix_scan_duplicates', $plugin_admin, 'ajax_scan_duplicate_images' );
            $this->loader->add_action( 'wp_ajax_picfix_scan_non_webp', $plugin_admin, 'ajax_scan_non_webp_images' );
            $this->loader->add_action( 'wp_ajax_picfix_convert_to_webp', $plugin_admin, 'ajax_convert_to_webp' );
            $this->loader->add_action( 'wp_ajax_picfix_delete_attachment', $plugin_admin, 'ajax_delete_attachment' );
        }

        /**
         * Run the loader to execute all of the hooks with WordPress.
         */
        public function run() {
            $this->loader->run();
        }

        /**
         * The name of the plugin.
         */
        public function get_plugin_name() {
            return $this->plugin_name;
        }

        /**
         * The reference to the class that orchestrates the hooks with the plugin.
         */
        public function get_loader() {
            return $this->loader;
        }

        /**
         * Retrieve the version number of the plugin.
         */
        public function get_version() {
            return $this->version;
        }
    }

} // End if class_exists check
