<?php
/**
 * Anti-Spam for LeadTrack Pro
 *
 * Provides server-side validation for the honeypot field and timing check,
 * plus optional Google reCAPTCHA v2 verification.
 *
 * @package LeadTrack_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeadTrack_Antispam
 */
class LeadTrack_Antispam {

	/**
	 * Minimum number of seconds a real user would take to fill out a form.
	 *
	 * @var int
	 */
	const MIN_SUBMISSION_TIME = 3;

	/**
	 * Boot the class.
	 */
	public static function init() {
		$options = get_option( LEADTRACK_PRO_OPTION_KEY, array() );

		if ( empty( $options['enable_antispam'] ) ) {
			return;
		}

		// Validate on standard form POSTs (CF7, WPForms, Gravity Forms, etc.).
		add_action( 'init', array( __CLASS__, 'validate_submission' ), 1 );

		// Hook into Elementor Pro form validation.
		add_action( 'elementor/loaded', function () {
			add_action( 'elementor_pro/forms/validation', array( 'LeadTrack_Antispam', 'validate_elementor_form' ), 10, 2 );
		} );
	}

	/**
	 * Validate an incoming POST submission.
	 *
	 * Checks honeypot and timing fields injected by antispam.js.
	 * Silently terminates the request if spam is detected.
	 */
	public static function validate_submission() {
		// Only run on POST requests that contain our timestamp field.
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['_lt_form_time'] ) ) {
			return;
		}

		// 1. Honeypot check.
		$options        = get_option( LEADTRACK_PRO_OPTION_KEY, array() );
		$honeypot_field = isset( $options['honeypot_field'] ) ? sanitize_key( $options['honeypot_field'] ) : 'lt_hp_email';

		if ( ! empty( $_POST[ $honeypot_field ] ) ) {
			// Bot filled in the honeypot — silently die.
			wp_die( '', '', array( 'response' => 200 ) );
		}

		// 2. Timing check.
		$form_time     = isset( $_POST['_lt_form_time'] ) ? absint( $_POST['_lt_form_time'] ) : 0;
		$elapsed       = time() - $form_time;

		if ( $form_time > 0 && $elapsed < self::MIN_SUBMISSION_TIME ) {
			wp_die( '', '', array( 'response' => 200 ) );
		}

		// 3. Optional reCAPTCHA verification.
		if ( ! empty( $options['recaptcha_secret_key'] ) && isset( $_POST['g-recaptcha-response'] ) ) {
			$valid = self::verify_recaptcha(
				sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ),
				$options['recaptcha_secret_key']
			);

			if ( ! $valid ) {
				wp_die(
					esc_html__( 'reCAPTCHA verification failed. Please try again.', 'leadtrack-pro' ),
					esc_html__( 'Spam Detected', 'leadtrack-pro' ),
					array( 'response' => 403, 'back_link' => true )
				);
			}
		}
	}

	/**
	 * Validate an Elementor Pro form submission.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record  Form record.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler AJAX handler.
	 */
	public static function validate_elementor_form( $record, $handler ) {
		$options        = get_option( LEADTRACK_PRO_OPTION_KEY, array() );
		$honeypot_field = isset( $options['honeypot_field'] ) ? sanitize_key( $options['honeypot_field'] ) : 'lt_hp_email';

		// Honeypot.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST[ $honeypot_field ] ) ) {
			$handler->add_error( $honeypot_field, esc_html__( 'Spam detected.', 'leadtrack-pro' ) );
			return;
		}

		// Timing.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$form_time = isset( $_POST['_lt_form_time'] ) ? absint( $_POST['_lt_form_time'] ) : 0;
		if ( $form_time > 0 && ( time() - $form_time ) < self::MIN_SUBMISSION_TIME ) {
			$handler->add_error( '_lt_form_time', esc_html__( 'Submission too fast. Please try again.', 'leadtrack-pro' ) );
		}

		// reCAPTCHA.
		if ( ! empty( $options['recaptcha_secret_key'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '';
			if ( ! self::verify_recaptcha( $token, $options['recaptcha_secret_key'] ) ) {
				$handler->add_error( 'g-recaptcha-response', esc_html__( 'reCAPTCHA verification failed.', 'leadtrack-pro' ) );
			}
		}
	}

	/**
	 * Verify a Google reCAPTCHA v2 token with the remote API.
	 *
	 * @param string $token      The g-recaptcha-response token from the client.
	 * @param string $secret_key The reCAPTCHA secret key.
	 * @return bool True if the token is valid, false otherwise.
	 */
	public static function verify_recaptcha( $token, $secret_key ) {
		if ( empty( $token ) || empty( $secret_key ) ) {
			return false;
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => sanitize_text_field( $secret_key ),
					'response' => sanitize_text_field( $token ),
					'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		return ! empty( $result['success'] );
	}
}
