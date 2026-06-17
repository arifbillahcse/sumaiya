<?php
/**
 * Spam Protection: Honeypot + reCAPTCHA v3 server-side validation.
 *
 * How it works:
 *  1. A hidden honeypot field is injected into every Elementor form.
 *     Bots fill it in; real users leave it empty.
 *  2. If reCAPTCHA v3 is enabled, a token is generated client-side and
 *     verified server-side against Google's API before Elementor processes
 *     the submission. Submissions scoring below the threshold are rejected.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class FRS_LT_Spam_Guard {

    public static function init() {
        add_action( 'wp_enqueue_scripts',      array( __CLASS__, 'enqueue_recaptcha_script' ) );
        add_filter( 'elementor_pro/forms/validation', array( __CLASS__, 'validate_submission' ), 10, 2 );
    }

    public static function enqueue_recaptcha_script() {
        if ( ! get_option( 'frs_lt_enable_recaptcha', 0 ) ) return;
        $site_key = get_option( 'frs_lt_recaptcha_site_key', '' );
        if ( ! $site_key ) return;

        wp_enqueue_script(
            'google-recaptcha-v3',
            'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $site_key ),
            array(),
            null,
            false
        );
        wp_enqueue_script(
            'frs-lt-recaptcha',
            FRS_LT_URL . 'assets/js/recaptcha.js',
            array( 'google-recaptcha-v3' ),
            FRS_LT_VERSION,
            true
        );
        wp_localize_script( 'frs-lt-recaptcha', 'frsLtRecaptcha', array(
            'siteKey' => $site_key,
        ) );
    }

    /**
     * Hooked into Elementor Pro form validation.
     * Runs honeypot check and optional reCAPTCHA v3 verification.
     */
    public static function validate_submission( $record, $ajax_handler ) {
        // ── Honeypot ──────────────────────────────────────────────────────────
        if ( get_option( 'frs_lt_enable_honeypot', 1 ) ) {
            $honeypot_value = isset( $_POST['frs_hp_field'] ) ? sanitize_text_field( wp_unslash( $_POST['frs_hp_field'] ) ) : '';
            if ( $honeypot_value !== '' ) {
                $ajax_handler->add_error_message( __( 'Spam detected. Please try again.', 'frs-lead-tracker' ) );
                $ajax_handler->set_success( false );
                return;
            }
        }

        // ── reCAPTCHA v3 ─────────────────────────────────────────────────────
        if ( get_option( 'frs_lt_enable_recaptcha', 0 ) ) {
            $token  = isset( $_POST['frs_recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['frs_recaptcha_token'] ) ) : '';
            $secret = get_option( 'frs_lt_recaptcha_secret', '' );

            if ( empty( $token ) || empty( $secret ) ) {
                $ajax_handler->add_error_message( __( 'Security check failed. Please reload and try again.', 'frs-lead-tracker' ) );
                $ajax_handler->set_success( false );
                return;
            }

            $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
                'body' => array(
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
                ),
                'timeout' => 10,
            ) );

            if ( is_wp_error( $response ) ) {
                // Allow on network error to avoid blocking real users.
                return;
            }

            $body  = json_decode( wp_remote_retrieve_body( $response ), true );
            $score = $body['score'] ?? 0;
            $min   = (float) get_option( 'frs_lt_recaptcha_score', 0.5 );

            if ( empty( $body['success'] ) || $score < $min ) {
                $ajax_handler->add_error_message( __( 'Security score too low. Please try again.', 'frs-lead-tracker' ) );
                $ajax_handler->set_success( false );
            }
        }
    }
}
