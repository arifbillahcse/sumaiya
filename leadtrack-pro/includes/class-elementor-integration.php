<?php
/**
 * Elementor Integration for LeadTrack Pro
 *
 * Hooks into Elementor Pro form submission AJAX to inject the redirect URL
 * into the AJAX response data, and provides a fallback via a WordPress action.
 *
 * @package LeadTrack_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeadTrack_Elementor_Integration
 */
class LeadTrack_Elementor_Integration {

	/**
	 * Boot the class.
	 */
	public static function init() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );

		if ( empty( $options['enable_elementor'] ) ) {
			return;
		}

		// Hook in after Elementor has loaded.
		add_action( 'elementor/loaded', array( __CLASS__, 'bind_hooks' ) );
	}

	/**
	 * Bind Elementor-specific hooks.
	 * Called only after 'elementor/loaded' fires.
	 */
	public static function bind_hooks() {
		// Elementor Pro: fires on every form submission (AJAX and non-AJAX).
		add_action( 'elementor_pro/forms/new_record', array( __CLASS__, 'handle_form_submission' ), 10, 2 );

		// Also modify the AJAX response data so our JS can read the redirect URL.
		add_filter( 'elementor_pro/forms/wp_send_json_success', array( __CLASS__, 'inject_redirect_into_response' ), 10, 3 );
	}

	/**
	 * Handle a new Elementor Pro form record.
	 *
	 * Adds a redirect URL to the AJAX response so the front-end JS can
	 * perform the redirect without a full page reload.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record  Elementor form record.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler Elementor AJAX handler.
	 */
	public static function handle_form_submission( $record, $handler ) {
		$options      = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$page_id      = isset( $options['thankyou_page_id'] ) ? absint( $options['thankyou_page_id'] ) : 0;
		$redirect_url = $page_id ? get_permalink( $page_id ) : '';

		if ( empty( $redirect_url ) ) {
			return;
		}

		// Add redirect URL to the handler response so JS picks it up.
		if ( method_exists( $handler, 'add_response_data' ) ) {
			$handler->add_response_data( 'redirect_url', esc_url_raw( $redirect_url ) );
		}
	}

	/**
	 * Filter the wp_send_json_success response data array.
	 *
	 * Ensures the redirect URL survives into the JSON payload even for
	 * older versions of Elementor Pro.
	 *
	 * @param array                                                       $response Response data.
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record|null        $record   Form record.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler|null       $handler  AJAX handler.
	 * @return array Modified response data.
	 */
	public static function inject_redirect_into_response( $response, $record, $handler ) {
		$options      = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$page_id      = isset( $options['thankyou_page_id'] ) ? absint( $options['thankyou_page_id'] ) : 0;
		$redirect_url = $page_id ? get_permalink( $page_id ) : '';

		if ( $redirect_url && is_array( $response ) ) {
			$response['redirect_url'] = esc_url_raw( $redirect_url );
		}

		return $response;
	}

	/**
	 * Get the Thank You page permalink.
	 *
	 * Utility method used by other classes.
	 *
	 * @return string URL or empty string.
	 */
	public static function get_redirect_url() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$page_id = isset( $options['thankyou_page_id'] ) ? absint( $options['thankyou_page_id'] ) : 0;
		return $page_id ? (string) get_permalink( $page_id ) : '';
	}
}
