<?php
/**
 * Thank You Page Shortcode Handler for LeadTrack Pro
 *
 * Registers the [leadtrack_thankyou] shortcode and renders the template.
 *
 * @package LeadTrack_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeadTrack_Thankyou_Page
 */
class LeadTrack_Thankyou_Page {

	/**
	 * Boot the class.
	 */
	public static function init() {
		add_shortcode( 'leadtrack_thankyou', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Render the [leadtrack_thankyou] shortcode.
	 *
	 * Loads the template file and returns its output as a string.
	 *
	 * @param array  $atts    Shortcode attributes (unused for now).
	 * @param string $content Enclosed content (unused).
	 * @return string HTML output.
	 */
	public static function render_shortcode( $atts = array(), $content = '' ) {
		$atts = shortcode_atts(
			array(
				'title'   => '',
				'message' => '',
			),
			$atts,
			'leadtrack_thankyou'
		);

		// Allow child themes or other plugins to override the template.
		$template = locate_template( 'leadtrack-pro/thankyou-page-template.php' );

		if ( ! $template ) {
			$template = LEADTRACK_PRO_DIR . 'templates/thankyou-page-template.php';
		}

		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Thank You page template not found.', 'leadtrack-pro' ) . '</p>';
		}

		ob_start();
		// Make $atts available to the template via $leadtrack_atts.
		$leadtrack_atts = $atts;
		include $template;
		return ob_get_clean();
	}

	/**
	 * Get the configured Thank You page URL.
	 *
	 * @return string Permalink or empty string.
	 */
	public static function get_url() {
		$options = get_option( LEADTRACK_PRO_OPTION_KEY, array() );
		$page_id = isset( $options['thankyou_page_id'] ) ? absint( $options['thankyou_page_id'] ) : 0;
		return $page_id ? (string) get_permalink( $page_id ) : '';
	}
}
