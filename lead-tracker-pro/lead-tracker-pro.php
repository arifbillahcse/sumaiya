<?php
/**
 * Plugin Name: Lead Tracker Pro
 * Plugin URI: https://example.com/lead-tracker-pro
 * Description: Automated lead generation tracking with Elementor form integration, Meta Pixel firing, Thank You Page automation, and anti-spam protection.
 * Version: 1.0.0
 * Author: Lead Tracker Pro
 * License: GPL v2 or later
 * Text Domain: lead-tracker-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'LTP_VERSION', '1.0.0' );
define( 'LTP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LTP_PLUGIN_FILE', __FILE__ );

// Autoload includes
$includes = [
    'includes/class-antispam.php',
    'includes/class-thankyou-page.php',
    'includes/class-admin-settings.php',
    'includes/class-elementor-integration.php',
];

foreach ( $includes as $file ) {
    $path = LTP_PLUGIN_DIR . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

/**
 * Main plugin class
 */
class Lead_Tracker_Pro {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'init' ] );
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_shortcode( 'lead_tracker_thankyou', [ $this, 'thankyou_shortcode' ] );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
    }

    public function init() {
        load_plugin_textdomain( 'lead-tracker-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        // Initialize integrations
        if ( class_exists( 'LTP_Thank_You_Page' ) ) {
            LTP_Thank_You_Page::instance();
        }

        if ( class_exists( 'LTP_Elementor_Integration' ) ) {
            LTP_Elementor_Integration::instance();
        }

        if ( class_exists( 'LTP_Anti_Spam' ) ) {
            LTP_Anti_Spam::instance();
        }
    }

    public function admin_menu() {
        if ( class_exists( 'LTP_Admin_Settings' ) ) {
            LTP_Admin_Settings::instance();
        }
    }

    public function enqueue_frontend_scripts() {
        $options = get_option( 'ltp_settings', [] );

        wp_enqueue_script(
            'ltp-form-handler',
            LTP_PLUGIN_URL . 'assets/js/form-handler.js',
            [ 'jquery' ],
            LTP_VERSION,
            true
        );

        $thankyou_url = '';
        if ( class_exists( 'LTP_Thank_You_Page' ) ) {
            $thankyou_url = LTP_Thank_You_Page::instance()->get_thankyou_url();
        }

        wp_localize_script( 'ltp-form-handler', 'ltpData', [
            'redirectUrl'   => esc_url( $thankyou_url ),
            'redirectDelay' => isset( $options['redirect_delay'] ) ? absint( $options['redirect_delay'] ) : 0,
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'ltp_nonce' ),
        ] );

        $pixel_enabled = ! empty( $options['enable_pixel'] );
        $pixel_id      = isset( $options['pixel_id'] ) ? sanitize_text_field( $options['pixel_id'] ) : '';

        $is_thankyou = false;
        if ( class_exists( 'LTP_Thank_You_Page' ) ) {
            $is_thankyou = LTP_Thank_You_Page::instance()->is_thankyou_page();
        }

        wp_enqueue_script(
            'ltp-tracking',
            LTP_PLUGIN_URL . 'assets/js/tracking.js',
            [],
            LTP_VERSION,
            true
        );

        wp_localize_script( 'ltp-tracking', 'ltpTracking', [
            'isThankYouPage' => $is_thankyou ? '1' : '0',
            'pixelId'        => esc_js( $pixel_id ),
            'enabled'        => $pixel_enabled ? '1' : '0',
        ] );

        if ( $is_thankyou ) {
            wp_enqueue_style(
                'ltp-thankyou-page',
                LTP_PLUGIN_URL . 'assets/css/thankyou-page.css',
                [],
                LTP_VERSION
            );
        }
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'lead-tracker-pro' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ltp-admin',
            LTP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            LTP_VERSION
        );
    }

    public function register_rest_routes() {
        register_rest_route( 'lead-tracker-pro/v1', '/log-submission', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rest_log_submission' ],
            'permission_callback' => [ $this, 'rest_permission_check' ],
        ] );

        register_rest_route( 'lead-tracker-pro/v1', '/submissions', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_submissions' ],
            'permission_callback' => [ $this, 'rest_admin_permission_check' ],
        ] );
    }

    public function rest_permission_check( $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce ) {
            $nonce = $request->get_param( 'nonce' );
        }
        return wp_verify_nonce( $nonce, 'wp_rest' );
    }

    public function rest_admin_permission_check() {
        return current_user_can( 'manage_options' );
    }

    public function rest_log_submission( $request ) {
        global $wpdb;

        $params = $request->get_json_params();

        $form_id   = isset( $params['form_id'] ) ? sanitize_text_field( $params['form_id'] ) : '';
        $form_name = isset( $params['form_name'] ) ? sanitize_text_field( $params['form_name'] ) : '';
        $data      = isset( $params['data'] ) ? $params['data'] : [];

        $antispam = class_exists( 'LTP_Anti_Spam' ) ? LTP_Anti_Spam::instance() : null;

        $is_spam = 0;
        if ( $antispam ) {
            if ( ! $antispam->validate_honeypot( $data ) ) {
                $is_spam = 1;
            }
            if ( ! $antispam->check_rate_limit( $antispam->get_client_ip() ) ) {
                $is_spam = 1;
            }
        }

        $table = $wpdb->prefix . 'ltp_submissions';
        $wpdb->insert(
            $table,
            [
                'form_id'         => $form_id,
                'form_name'       => $form_name,
                'submission_data' => wp_json_encode( $data ),
                'ip_address'      => class_exists( 'LTP_Anti_Spam' ) ? LTP_Anti_Spam::instance()->get_client_ip() : '',
                'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'is_spam'         => $is_spam,
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%d' ]
        );

        return rest_ensure_response( [
            'success' => true,
            'is_spam' => (bool) $is_spam,
            'id'      => $wpdb->insert_id,
        ] );
    }

    public function rest_get_submissions( $request ) {
        global $wpdb;

        $table  = $wpdb->prefix . 'ltp_submissions';
        $limit  = absint( $request->get_param( 'per_page' ) ?: 20 );
        $offset = absint( $request->get_param( 'offset' ) ?: 0 );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            )
        );

        return rest_ensure_response( $results );
    }

    public function thankyou_shortcode( $atts ) {
        if ( class_exists( 'LTP_Thank_You_Page' ) ) {
            return LTP_Thank_You_Page::instance()->render_shortcode( $atts );
        }
        return '';
    }

    public function admin_notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options = get_option( 'ltp_settings', [] );

        if ( ! empty( $options['enable_elementor'] ) && ! did_action( 'elementor/loaded' ) ) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo '<strong>' . esc_html__( 'Lead Tracker Pro:', 'lead-tracker-pro' ) . '</strong> ';
            echo esc_html__( 'Elementor is not installed or activated. Elementor form integration will not work until Elementor Pro is installed.', 'lead-tracker-pro' );
            echo '</p></div>';
        }
    }
}

/**
 * Activation hook
 */
function ltp_activate() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'ltp_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        form_id varchar(100) DEFAULT NULL,
        form_name varchar(255) DEFAULT NULL,
        submission_data longtext DEFAULT NULL,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        is_spam tinyint(1) DEFAULT 0,
        PRIMARY KEY (id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Set default options
    $defaults = [
        'enable_redirect'    => 1,
        'thankyou_url'       => '',
        'thankyou_title'     => __( 'Thank You!', 'lead-tracker-pro' ),
        'enable_pixel'       => 0,
        'pixel_id'           => '',
        'pixel_fire_on'      => 'thankyou',
        'enable_honeypot'    => 1,
        'enable_time_check'  => 1,
        'min_submit_time'    => 3,
        'enable_recaptcha'   => 0,
        'recaptcha_site_key' => '',
        'recaptcha_secret'   => '',
        'enable_elementor'   => 1,
        'redirect_delay'     => 0,
        'tracked_form_ids'   => '',
    ];

    if ( ! get_option( 'ltp_settings' ) ) {
        add_option( 'ltp_settings', $defaults );
    }

    // Create default Thank You page
    if ( class_exists( 'LTP_Thank_You_Page' ) ) {
        $page_id = LTP_Thank_You_Page::instance()->create_default_page();
        if ( $page_id ) {
            update_option( 'ltp_thankyou_page_id', $page_id );
        }
    } else {
        // Create the page directly if class not loaded yet
        $existing = get_page_by_path( 'thank-you' );
        if ( ! $existing ) {
            $page_id = wp_insert_post( [
                'post_title'   => __( 'Thank You!', 'lead-tracker-pro' ),
                'post_content' => '[lead_tracker_thankyou]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => 'thank-you',
            ] );
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'ltp_thankyou_page_id', $page_id );
            }
        } else {
            update_option( 'ltp_thankyou_page_id', $existing->ID );
        }
    }

    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
function ltp_deactivate() {
    flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'ltp_activate' );
register_deactivation_hook( __FILE__, 'ltp_deactivate' );

// Bootstrap the plugin
Lead_Tracker_Pro::instance();
