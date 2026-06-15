<?php
/**
 * Plugin Name:       LeadTrack Pro
 * Plugin URI:        https://example.com/leadtrack-pro
 * Description:       Tracks leads via Meta Pixel, integrates with Elementor forms, includes anti-spam protection, and provides a customisable Thank You page.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            LeadTrack Pro
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       leadtrack-pro
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------
define( 'LEADTRACK_PRO_VERSION', '1.0.0' );
define( 'LEADTRACK_PRO_PLUGIN_FILE', __FILE__ );
define( 'LEADTRACK_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEADTRACK_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LEADTRACK_PRO_OPTIONS_KEY', 'leadtrack_pro_options' );

// ---------------------------------------------------------------------------
// Includes
// ---------------------------------------------------------------------------
require_once LEADTRACK_PRO_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once LEADTRACK_PRO_PLUGIN_DIR . 'includes/class-elementor-integration.php';
require_once LEADTRACK_PRO_PLUGIN_DIR . 'includes/class-antispam.php';
require_once LEADTRACK_PRO_PLUGIN_DIR . 'includes/class-thankyou-page.php';

// ---------------------------------------------------------------------------
// Activation hook
// ---------------------------------------------------------------------------
register_activation_hook( __FILE__, 'leadtrack_pro_activate' );
function leadtrack_pro_activate() {
	if ( false === get_option( LEADTRACK_PRO_OPTIONS_KEY ) ) {
		$defaults = array(
			'pixel_id'             => '',
			'enable_pixel'         => '1',
			'enable_elementor'     => '1',
			'enable_antispam'      => '1',
			'honeypot_field'       => 'lt_hp_email',
			'recaptcha_site_key'   => '',
			'recaptcha_secret_key' => '',
			'thankyou_page_id'     => 0,
		);
		update_option( LEADTRACK_PRO_OPTIONS_KEY, $defaults );
	}

	$options = get_option( LEADTRACK_PRO_OPTIONS_KEY );

	if ( empty( $options['thankyou_page_id'] ) || ! get_post( $options['thankyou_page_id'] ) ) {
		$page_id = wp_insert_post( array(
			'post_title'   => esc_html__( 'Thank You', 'leadtrack-pro' ),
			'post_name'    => 'thank-you',
			'post_content' => '[leadtrack_thankyou]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		) );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			$options['thankyou_page_id'] = $page_id;
			update_option( LEADTRACK_PRO_OPTIONS_KEY, $options );
		}
	}

	flush_rewrite_rules();
}

// ---------------------------------------------------------------------------
// Deactivation hook
// ---------------------------------------------------------------------------
register_deactivation_hook( __FILE__, 'leadtrack_pro_deactivate' );
function leadtrack_pro_deactivate() {
	flush_rewrite_rules();
}

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------
add_action( 'plugins_loaded', 'leadtrack_pro_init' );
function leadtrack_pro_init() {
	load_plugin_textdomain( 'leadtrack-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	LeadTrack_Admin_Settings::init();
	LeadTrack_Elementor_Integration::init();
	LeadTrack_Antispam::init();
	LeadTrack_Thankyou_Page::init();
}

// ---------------------------------------------------------------------------
// Front-end scripts & styles
// ---------------------------------------------------------------------------
add_action( 'wp_enqueue_scripts', 'leadtrack_pro_enqueue_scripts' );
function leadtrack_pro_enqueue_scripts() {
	$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );

	wp_enqueue_style(
		'leadtrack-thankyou-page',
		LEADTRACK_PRO_PLUGIN_URL . 'assets/css/thankyou-page.css',
		array(),
		LEADTRACK_PRO_VERSION
	);

	wp_enqueue_script(
		'leadtrack-tracking',
		LEADTRACK_PRO_PLUGIN_URL . 'assets/js/tracking.js',
		array(),
		LEADTRACK_PRO_VERSION,
		true
	);

	$thankyou_page_id = isset( $options['thankyou_page_id'] ) ? absint( $options['thankyou_page_id'] ) : 0;
	$is_thankyou_page = ( $thankyou_page_id && is_page( $thankyou_page_id ) ) ? true : false;

	wp_localize_script(
		'leadtrack-tracking',
		'LeadTrackVars',
		array(
			'pixelId'        => isset( $options['pixel_id'] ) ? esc_js( $options['pixel_id'] ) : '',
			'enablePixel'    => ! empty( $options['enable_pixel'] ),
			'isThankyouPage' => $is_thankyou_page,
			'thankyouPageUrl'=> $thankyou_page_id ? get_permalink( $thankyou_page_id ) : '',
		)
	);

	if ( ! empty( $options['enable_elementor'] ) && did_action( 'elementor/loaded' ) ) {
		wp_enqueue_script(
			'leadtrack-elementor-redirect',
			LEADTRACK_PRO_PLUGIN_URL . 'assets/js/elementor-redirect.js',
			array( 'jquery' ),
			LEADTRACK_PRO_VERSION,
			true
		);

		wp_localize_script(
			'leadtrack-elementor-redirect',
			'LeadTrackElementor',
			array(
				'thankyouUrl' => $thankyou_page_id ? get_permalink( $thankyou_page_id ) : '',
			)
		);
	}

	if ( ! empty( $options['enable_antispam'] ) ) {
		wp_enqueue_script(
			'leadtrack-antispam',
			LEADTRACK_PRO_PLUGIN_URL . 'assets/js/antispam.js',
			array( 'jquery' ),
			LEADTRACK_PRO_VERSION,
			true
		);

		wp_localize_script(
			'leadtrack-antispam',
			'LeadTrackAntispam',
			array(
				'honeypotField'   => isset( $options['honeypot_field'] ) ? esc_js( $options['honeypot_field'] ) : 'lt_hp_email',
				'recaptchaSiteKey'=> isset( $options['recaptcha_site_key'] ) ? esc_js( $options['recaptcha_site_key'] ) : '',
			)
		);
	}
}

// ---------------------------------------------------------------------------
// Admin scripts & styles
// ---------------------------------------------------------------------------
add_action( 'admin_enqueue_scripts', 'leadtrack_pro_admin_enqueue_scripts' );
function leadtrack_pro_admin_enqueue_scripts( $hook ) {
	if ( false === strpos( $hook, 'leadtrack' ) ) {
		return;
	}

	wp_enqueue_style(
		'leadtrack-admin-settings',
		LEADTRACK_PRO_PLUGIN_URL . 'assets/css/admin-settings.css',
		array(),
		LEADTRACK_PRO_VERSION
	);
}

// ---------------------------------------------------------------------------
// wp_head — Meta Pixel base code
// ---------------------------------------------------------------------------
add_action( 'wp_head', 'leadtrack_pro_output_pixel' );
function leadtrack_pro_output_pixel() {
	$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );

	if ( empty( $options['enable_pixel'] ) || empty( $options['pixel_id'] ) ) {
		return;
	}

	$pixel_id = esc_js( $options['pixel_id'] );
	?>
<!-- LeadTrack Pro – Meta Pixel Base Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo $pixel_id; ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo rawurlencode( $options['pixel_id'] ); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End LeadTrack Pro – Meta Pixel Base Code -->
	<?php
}
