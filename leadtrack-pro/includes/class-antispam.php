<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LeadTrack_Antispam {

	public static function init() {
		$options = get_option( LEADTRACK_PRO_OPTION_KEY, array() );
		if ( empty( $options['enable_antispam'] ) ) {
			return;
		}

		$instance = new self();

		// reCAPTCHA v3 verification hook (fires before Elementor processes form).
		if ( ! empty( $options['enable_recaptcha'] ) && ! empty( $options['recaptcha_secret_key'] ) ) {
			add_action( 'elementor_pro/forms/validation', array( $instance, 'verify_recaptcha' ), 10, 2 );
		}

		// Honeypot + time validation via Elementor form validation hook.
		add_action( 'elementor_pro/forms/validation', array( $instance, 'validate_honeypot_and_time' ), 5, 2 );
	}

	/**
	 * Server-side: reject if honeypot field (_ltp_hp) has a value.
	 */
	public function validate_honeypot_and_time( $record, $ajax_handler ) {
		$raw_fields = $record->get( 'fields' );

		// Honeypot check — JS injects a hidden field; bots fill it.
		if ( isset( $_POST['_ltp_hp'] ) && '' !== sanitize_text_field( wp_unslash( $_POST['_ltp_hp'] ) ) ) {
			$ajax_handler->add_error_message( esc_html__( 'Spam detected.', 'leadtrack-pro' ) );
			$ajax_handler->is_success = false;
			return;
		}

		// Time-based check.
		$options    = get_option( LEADTRACK_PRO_OPTION_KEY, array() );
		$time_limit = absint( $options['antispam_time_limit'] ?? 3 );

		if ( isset( $_POST['_ltp_ts'] ) ) {
			$submitted_at  = absint( wp_unslash( $_POST['_ltp_ts'] ) );
			$time_elapsed  = time() - $submitted_at;
			if ( $time_elapsed < $time_limit ) {
				$ajax_handler->add_error_message( esc_html__( 'Please take a moment before submitting.', 'leadtrack-pro' ) );
				$ajax_handler->is_success = false;
			}
		}
	}

	/**
	 * Server-side: verify reCAPTCHA v3 token.
	 */
	public function verify_recaptcha( $record, $ajax_handler ) {
		$options    = get_option( LEADTRACK_PRO_OPTION_KEY, array() );
		$secret_key = sanitize_text_field( $options['recaptcha_secret_key'] ?? '' );

		if ( empty( $secret_key ) || ! isset( $_POST['_ltp_recaptcha_token'] ) ) {
			return;
		}

		$token    = sanitize_text_field( wp_unslash( $_POST['_ltp_recaptcha_token'] ) );
		$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
			'body' => array(
				'secret'   => $secret_key,
				'response' => $token,
				'remoteip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return; // Fail open on network errors to avoid blocking real users.
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['success'] ) || ( isset( $body['score'] ) && $body['score'] < 0.5 ) ) {
			$ajax_handler->add_error_message( esc_html__( 'reCAPTCHA verification failed. Please try again.', 'leadtrack-pro' ) );
			$ajax_handler->is_success = false;
		}
	}
}
