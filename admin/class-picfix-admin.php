<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Picfix
 * @subpackage Picfix/admin
 */

if ( ! class_exists( 'Picfix_Admin' ) ) {

    class Picfix_Admin {

        private $plugin_name;
        private $version;

        public function __construct( $plugin_name, $version ) {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
        }

        public function enqueue_styles( $hook ) {
            if ( 'tools_page_picfix' !== $hook ) {
                return;
            }
            wp_enqueue_style( $this->plugin_name, PICFIX_PLUGIN_URL . 'admin/css/picfix-admin.css', array(), $this->version, 'all' );
        }

        public function enqueue_scripts( $hook ) {
            if ( 'tools_page_picfix' !== $hook ) {
                return;
            }
            wp_enqueue_script( $this->plugin_name, PICFIX_PLUGIN_URL . 'admin/js/picfix-admin.js', array( 'jquery' ), $this->version, true );
            wp_localize_script( $this->plugin_name, 'picfix_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'picfix_nonce' )
            ) );
        }

        public function add_plugin_admin_menu() {
            add_submenu_page(
                'tools.php',
                'PicFix Image Tools',
                'PicFix',
                'manage_options',
                $this->plugin_name,
                array( $this, 'display_plugin_setup_page' )
            );
        }

        public function display_plugin_setup_page() {
            include_once( 'partials/picfix-admin-display.php' );
        }

        // --- AJAX HANDLERS ---

        public function ajax_scan_unused_images() {
            check_ajax_referer( 'picfix_nonce', 'nonce' );
            global $wpdb;

            // Get all image attachments
            $attachments = get_posts( array(
                'post_type'      => 'attachment',
                'posts_per_page' => -1,
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'fields'         => 'ids' // More efficient
            ) );

            $unused_images = array();
            foreach ( $attachments as $attachment_id ) {
                // Efficiently check if the attachment is used in any post content
                $attachment_filename = basename( get_attached_file( $attachment_id ) );
                $search_string = '%' . $wpdb->esc_like( $attachment_filename ) . '%';
                
                $is_used = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment', 'revision') AND post_status = 'publish' AND post_content LIKE %s",
                    $search_string
                ) );
                
                // This is a basic check. A more thorough check would also scan post meta, theme options, etc.
                if ( $is_used == 0 ) {
                    $thumb_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
                    $unused_images[] = array(
                        'id' => $attachment_id,
                        'url' => wp_get_attachment_url( $attachment_id ),
                        'thumb' => $thumb_url[0],
                        'filename' => $attachment_filename
                    );
                }
            }

            wp_send_json_success( $unused_images );
        }

        public function ajax_scan_duplicate_images() {
            check_ajax_referer( 'picfix_nonce', 'nonce' );
            // Ensure WordPress functions for attachment handling are available
            if ( ! function_exists( 'attachment_url_to_postid' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/post.php' );
            }

            $upload_dir = wp_upload_dir();
            $path = $upload_dir['basedir'];
            $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) );
            $hashes = array();
            $duplicates = array();

            foreach ($files as $file) {
                // Use getPathname() to get the full path as a string
                $pathname = $file->getPathname();
                if ($file->isDir() || !@getimagesize($pathname)){ 
                    continue;
                }
                $hash = md5_file($pathname);
                $hashes[$hash][] = $pathname;
            }

            foreach ($hashes as $hash => $files) {
                if (count($files) > 1) {
                    $duplicate_group = [];
                    foreach($files as $file_path) {
                        $attachment_id = attachment_url_to_postid(str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path));
                        if($attachment_id) {
                            $thumb_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
                            $duplicate_group[] = [
                                'id' => $attachment_id,
                                'path' => $file_path,
                                'filename' => basename($file_path),
                                'thumb' => $thumb_url[0] ?? ''
                            ];
                        }
                    }
                    if (count($duplicate_group) > 1) {
                        $duplicates[] = $duplicate_group;
                    }
                }
            }

            wp_send_json_success( $duplicates );
        }

        public function ajax_scan_non_webp_images() {
            check_ajax_referer( 'picfix_nonce', 'nonce' );

            $attachments = get_posts( array(
                'post_type'      => 'attachment',
                'posts_per_page' => -1,
                'post_status'    => 'inherit',
                'post_mime_type' => array('image/jpeg', 'image/png'),
            ) );

            $non_webp_images = array();
            foreach ( $attachments as $attachment ) {
                $file_path = get_attached_file( $attachment->ID );
                if ( ! file_exists( $file_path ) ) continue;

                $webp_path = $file_path . '.webp';
                if ( ! file_exists( $webp_path ) ) {
                    $thumb_url = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' );
                    $non_webp_images[] = array(
                        'id' => $attachment->ID,
                        'path' => $file_path,
                        'filename' => basename($file_path),
                        'thumb' => $thumb_url[0]
                    );
                }
            }
            wp_send_json_success( $non_webp_images );
        }

        public function ajax_convert_to_webp() {
            check_ajax_referer( 'picfix_nonce', 'nonce' );

            if ( ! isset( $_POST['path'] ) || empty( $_POST['path'] ) ) {
                wp_send_json_error( 'Invalid file path.' );
            }
            if (!function_exists('imagewebp')) {
                wp_send_json_error('WebP conversion (GD extension) is not available on your server.');
            }

            $file_path = sanitize_text_field( $_POST['path'] );
            if ( ! file_exists( $file_path ) ) {
                wp_send_json_error( 'Original file not found.' );
            }

            $original_size = filesize($file_path);
            $info = getimagesize($file_path);
            if ($info === false) { wp_send_json_error( 'Could not read image info.' ); }

            $mime = $info['mime'];
            $image = null;

            switch ($mime) {
                case 'image/jpeg': $image = imagecreatefromjpeg($file_path); break;
                case 'image/png':
                    $image = imagecreatefrompng($file_path);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;
                default: wp_send_json_error( 'Unsupported image type.' );
            }
            if ($image === null) { wp_send_json_error( 'Failed to create image resource.' ); }

            $webp_path = $file_path . '.webp';
            $success = imagewebp($image, $webp_path, 80); // Quality 80
            imagedestroy($image);

            if ($success && file_exists($webp_path)) {
                $new_size = filesize($webp_path);
                $reduction = $original_size - $new_size;
                $percent_saved = $original_size > 0 ? round(($reduction / $original_size) * 100) : 0;
                
                wp_send_json_success( [
                    'message' => 'Image converted successfully.',
                    'original_size' => size_format($original_size, 2),
                    'new_size' => size_format($new_size, 2),
                    'percent_saved' => $percent_saved
                ] );
            } else {
                wp_send_json_error( 'Failed to save WebP image.' );
            }
        }
        
        public function ajax_delete_attachment() {
            check_ajax_referer( 'picfix_nonce', 'nonce' );

            if ( ! isset( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) ) {
                wp_send_json_error( 'Invalid attachment ID.' );
            }
            if (!current_user_can('delete_post', $_POST['id'])) {
                wp_send_json_error( 'Permission denied.' );
            }

            $attachment_id = intval( $_POST['id'] );
            $result = wp_delete_attachment( $attachment_id, true );

            if ( $result ) {
                wp_send_json_success( 'Attachment deleted.' );
            } else {
                wp_send_json_error( 'Failed to delete attachment.' );
            }
        }
    }
} // End if class_exists check
