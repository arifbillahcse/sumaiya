<?php
/**
 * Thank You Page Controller
 *
 * Detects when a visitor lands on the configured Thank You Page and decides
 * whether to fire the Meta Pixel Lead event.
 *
 * Gate logic (two-layer):
 *   Layer 1 – PHP session token set by FRS_LT_Elementor_Hook::set_lead_session()
 *             immediately after Elementor processes a valid submission.
 *   Layer 2 – Client-side sessionStorage guard prevents duplicate fires on
 *             page refresh (assets/js/pixel.js).
 *
 * A direct visit to /thank-you/ without a prior submission never sets the
 * session token, so the pixel never fires.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class FRS_LT_Thankyou_Page {

    public static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'maybe_gate_page' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_pixel' ) );
        // Register the page template so Elementor can use it
        add_filter( 'theme_page_templates', array( __CLASS__, 'register_template' ) );
        add_filter( 'template_include',     array( __CLASS__, 'load_template' ) );
    }

    public static function maybe_gate_page() {
        $page_id = (int) get_option( 'frs_lt_thankyou_page_id', 0 );
        if ( ! $page_id || ! is_page( $page_id ) ) return;

        if ( ! session_id() ) {
            session_start();
        }

        // No valid session token → redirect to home to prevent direct access
        if ( empty( $_SESSION['frs_lt_lead'] ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        // Consume the token so a refresh triggers the redirect above
        unset( $_SESSION['frs_lt_lead'] );
    }

    public static function maybe_enqueue_pixel() {
        $page_id = (int) get_option( 'frs_lt_thankyou_page_id', 0 );
        if ( ! $page_id || ! is_page( $page_id ) ) return;

        $pixel_id = get_option( 'frs_lt_meta_pixel_id', '' );
        if ( ! $pixel_id ) return;

        wp_enqueue_script(
            'frs-lt-pixel',
            FRS_LT_URL . 'assets/js/pixel.js',
            array(),
            FRS_LT_VERSION,
            false // load in <head> so pixel fires as early as possible
        );
        wp_localize_script( 'frs-lt-pixel', 'frsLtPixel', array(
            'pixelId'  => esc_js( $pixel_id ),
            'enableGtm'=> (bool) get_option( 'frs_lt_enable_gtm', 0 ),
        ) );
    }

    public static function register_template( $templates ) {
        $templates['frs-thankyou.php'] = __( 'FRS Thank You Page', 'frs-lead-tracker' );
        return $templates;
    }

    public static function load_template( $template ) {
        if ( is_page() ) {
            $page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
            if ( 'frs-thankyou.php' === $page_template ) {
                $plugin_template = FRS_LT_DIR . 'templates/frs-thankyou.php';
                if ( file_exists( $plugin_template ) ) {
                    return $plugin_template;
                }
            }
        }
        return $template;
    }
}
