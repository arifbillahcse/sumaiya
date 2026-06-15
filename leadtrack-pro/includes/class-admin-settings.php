<?php
/**
 * Admin Settings for LeadTrack Pro
 *
 * @package LeadTrack_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LeadTrack_Admin_Settings
 *
 * Registers the admin menu, settings sections, and fields using the
 * WordPress Settings API.
 */
class LeadTrack_Admin_Settings {

	/**
	 * Option group name (used in register_setting).
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'leadtrack_pro_option_group';

	/**
	 * Available tabs.
	 *
	 * @var array
	 */
	private static $tabs = array(
		'general'     => 'General',
		'pixel'       => 'Pixel Tracking',
		'antispam'    => 'Anti-Spam',
		'setup_guide' => 'Setup Guide',
	);

	/**
	 * Boot the class – attach WordPress hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	// -----------------------------------------------------------------------
	// Menu
	// -----------------------------------------------------------------------

	/**
	 * Register the top-level admin menu item.
	 */
	public static function register_menu() {
		add_options_page(
			esc_html__( 'LeadTrack Pro Settings', 'leadtrack-pro' ),
			esc_html__( 'LeadTrack Pro', 'leadtrack-pro' ),
			'manage_options',
			'leadtrack-pro',
			array( __CLASS__, 'render_page' )
		);
	}

	// -----------------------------------------------------------------------
	// Settings API registration
	// -----------------------------------------------------------------------

	/**
	 * Register setting, sections, and fields.
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			LEADTRACK_PRO_OPTIONS_KEY,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
			)
		);

		// --- General section ---
		add_settings_section(
			'leadtrack_general_section',
			esc_html__( 'General Settings', 'leadtrack-pro' ),
			array( __CLASS__, 'render_general_section_description' ),
			'leadtrack-pro-general'
		);

		add_settings_field(
			'enable_elementor',
			esc_html__( 'Enable Elementor Integration', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_enable_elementor' ),
			'leadtrack-pro-general',
			'leadtrack_general_section'
		);

		add_settings_field(
			'thankyou_page_id',
			esc_html__( 'Thank You Page', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_thankyou_page' ),
			'leadtrack-pro-general',
			'leadtrack_general_section'
		);

		// --- Pixel section ---
		add_settings_section(
			'leadtrack_pixel_section',
			esc_html__( 'Meta Pixel Settings', 'leadtrack-pro' ),
			array( __CLASS__, 'render_pixel_section_description' ),
			'leadtrack-pro-pixel'
		);

		add_settings_field(
			'enable_pixel',
			esc_html__( 'Enable Meta Pixel', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_enable_pixel' ),
			'leadtrack-pro-pixel',
			'leadtrack_pixel_section'
		);

		add_settings_field(
			'pixel_id',
			esc_html__( 'Pixel ID', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_pixel_id' ),
			'leadtrack-pro-pixel',
			'leadtrack_pixel_section'
		);

		// --- Anti-Spam section ---
		add_settings_section(
			'leadtrack_antispam_section',
			esc_html__( 'Anti-Spam Settings', 'leadtrack-pro' ),
			array( __CLASS__, 'render_antispam_section_description' ),
			'leadtrack-pro-antispam'
		);

		add_settings_field(
			'enable_antispam',
			esc_html__( 'Enable Anti-Spam', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_enable_antispam' ),
			'leadtrack-pro-antispam',
			'leadtrack_antispam_section'
		);

		add_settings_field(
			'honeypot_field',
			esc_html__( 'Honeypot Field Name', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_honeypot' ),
			'leadtrack-pro-antispam',
			'leadtrack_antispam_section'
		);

		add_settings_field(
			'recaptcha_site_key',
			esc_html__( 'reCAPTCHA Site Key', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_recaptcha_site_key' ),
			'leadtrack-pro-antispam',
			'leadtrack_antispam_section'
		);

		add_settings_field(
			'recaptcha_secret_key',
			esc_html__( 'reCAPTCHA Secret Key', 'leadtrack-pro' ),
			array( __CLASS__, 'render_field_recaptcha_secret_key' ),
			'leadtrack-pro-antispam',
			'leadtrack_antispam_section'
		);
	}

	// -----------------------------------------------------------------------
	// Sanitization
	// -----------------------------------------------------------------------

	/**
	 * Sanitize the options array before saving.
	 *
	 * @param array $raw Raw POST data.
	 * @return array Sanitized options.
	 */
	public static function sanitize_options( $raw ) {
		$clean = array();

		$clean['pixel_id']             = isset( $raw['pixel_id'] ) ? sanitize_text_field( $raw['pixel_id'] ) : '';
		$clean['enable_pixel']         = ! empty( $raw['enable_pixel'] ) ? '1' : '0';
		$clean['enable_elementor']     = ! empty( $raw['enable_elementor'] ) ? '1' : '0';
		$clean['enable_antispam']      = ! empty( $raw['enable_antispam'] ) ? '1' : '0';
		$clean['honeypot_field']       = isset( $raw['honeypot_field'] ) ? sanitize_key( $raw['honeypot_field'] ) : 'lt_hp_email';
		$clean['recaptcha_site_key']   = isset( $raw['recaptcha_site_key'] ) ? sanitize_text_field( $raw['recaptcha_site_key'] ) : '';
		$clean['recaptcha_secret_key'] = isset( $raw['recaptcha_secret_key'] ) ? sanitize_text_field( $raw['recaptcha_secret_key'] ) : '';
		$clean['thankyou_page_id']     = isset( $raw['thankyou_page_id'] ) ? absint( $raw['thankyou_page_id'] ) : 0;

		// Honeypot field must not be empty.
		if ( empty( $clean['honeypot_field'] ) ) {
			$clean['honeypot_field'] = 'lt_hp_email';
		}

		return $clean;
	}

	// -----------------------------------------------------------------------
	// Page renderer
	// -----------------------------------------------------------------------

	/**
	 * Output the settings page HTML.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'leadtrack-pro' ) );
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! array_key_exists( $current_tab, self::$tabs ) ) {
			$current_tab = 'general';
		}
		?>
		<div class="wrap leadtrack-wrap">
			<h1 class="leadtrack-page-title">
				<span class="leadtrack-logo">&#128202;</span>
				<?php esc_html_e( 'LeadTrack Pro', 'leadtrack-pro' ); ?>
			</h1>

			<!-- Tab Navigation -->
			<nav class="leadtrack-tabs nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Settings tabs', 'leadtrack-pro' ); ?>">
				<?php foreach ( self::$tabs as $tab_key => $tab_label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=leadtrack-pro&tab=' . $tab_key ) ); ?>"
					   class="nav-tab<?php echo ( $current_tab === $tab_key ) ? ' nav-tab-active' : ''; ?>"
					   aria-current="<?php echo ( $current_tab === $tab_key ) ? 'page' : 'false'; ?>">
						<?php echo esc_html__( $tab_label, 'leadtrack-pro' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="leadtrack-tab-content">
			<?php if ( 'setup_guide' === $current_tab ) : ?>
				<?php self::render_setup_guide(); ?>
			<?php else : ?>
				<form method="post" action="options.php">
					<?php
					settings_fields( self::OPTION_GROUP );

					switch ( $current_tab ) {
						case 'pixel':
							do_settings_sections( 'leadtrack-pro-pixel' );
							break;
						case 'antispam':
							do_settings_sections( 'leadtrack-pro-antispam' );
							break;
						default:
							do_settings_sections( 'leadtrack-pro-general' );
							break;
					}

					submit_button( esc_html__( 'Save Settings', 'leadtrack-pro' ) );
					?>
				</form>
			<?php endif; ?>
			</div><!-- .leadtrack-tab-content -->
		</div><!-- .leadtrack-wrap -->
		<?php
	}

	// -----------------------------------------------------------------------
	// Section descriptions
	// -----------------------------------------------------------------------

	/** Render General section description. */
	public static function render_general_section_description() {
		echo '<p>' . esc_html__( 'Configure the core LeadTrack Pro features and the Thank You page.', 'leadtrack-pro' ) . '</p>';
	}

	/** Render Pixel section description. */
	public static function render_pixel_section_description() {
		echo '<p>' . esc_html__( 'Enter your Meta (Facebook) Pixel ID to enable automatic Lead event tracking.', 'leadtrack-pro' ) . '</p>';
	}

	/** Render Anti-Spam section description. */
	public static function render_antispam_section_description() {
		echo '<p>' . esc_html__( 'Protect your forms against bots using a honeypot field and optional Google reCAPTCHA v2.', 'leadtrack-pro' ) . '</p>';
	}

	// -----------------------------------------------------------------------
	// Field renderers
	// -----------------------------------------------------------------------

	/** Render enable_elementor field. */
	public static function render_field_enable_elementor() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$checked = ! empty( $options['enable_elementor'] );
		printf(
			'<label><input type="checkbox" name="%1$s[enable_elementor]" value="1" %2$s> %3$s</label><p class="description">%4$s</p>',
			esc_attr( LEADTRACK_PRO_OPTIONS_KEY ),
			checked( $checked, true, false ),
			esc_html__( 'Enable Elementor form redirect to Thank You page', 'leadtrack-pro' ),
			esc_html__( 'Requires Elementor (free or Pro) to be active.', 'leadtrack-pro' )
		);
	}

	/** Render thankyou_page_id field. */
	public static function render_field_thankyou_page() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$page_id = isset( $options['thankyou_page_id'] ) ? absint( $options['thankyou_page_id'] ) : 0;

		wp_dropdown_pages( array(
			'name'              => LEADTRACK_PRO_OPTIONS_KEY . '[thankyou_page_id]',
			'id'                => 'leadtrack_thankyou_page_id',
			'selected'          => $page_id,
			'show_option_none'  => esc_html__( '— Select a page —', 'leadtrack-pro' ),
			'option_none_value' => '0',
		) );

		if ( $page_id ) {
			printf(
				' <a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url( get_permalink( $page_id ) ),
				esc_html__( 'View page', 'leadtrack-pro' )
			);
		}

		echo '<p class="description">' . esc_html__( 'Visitors will be redirected here after successful form submission.', 'leadtrack-pro' ) . '</p>';
	}

	/** Render enable_pixel field. */
	public static function render_field_enable_pixel() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$checked = ! empty( $options['enable_pixel'] );
		printf(
			'<label><input type="checkbox" name="%1$s[enable_pixel]" value="1" %2$s> %3$s</label>',
			esc_attr( LEADTRACK_PRO_OPTIONS_KEY ),
			checked( $checked, true, false ),
			esc_html__( 'Output the Meta Pixel base code in wp_head', 'leadtrack-pro' )
		);
	}

	/** Render pixel_id field. */
	public static function render_field_pixel_id() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		printf(
			'<input type="text" id="leadtrack_pixel_id" name="%1$s[pixel_id]" value="%2$s" class="regular-text" placeholder="123456789012345">
			<p class="description">%3$s</p>',
			esc_attr( LEADTRACK_PRO_OPTIONS_KEY ),
			esc_attr( isset( $options['pixel_id'] ) ? $options['pixel_id'] : '' ),
			esc_html__( 'Enter your numeric Meta Pixel ID. Find it in Meta Business Manager > Events Manager.', 'leadtrack-pro' )
		);
	}

	/** Render enable_antispam field. */
	public static function render_field_enable_antispam() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$checked = ! empty( $options['enable_antispam'] );
		printf(
			'<label><input type="checkbox" name="%1$s[enable_antispam]" value="1" %2$s> %3$s</label>',
			esc_attr( LEADTRACK_PRO_OPTIONS_KEY ),
			checked( $checked, true, false ),
			esc_html__( 'Enable honeypot and timing-based spam protection', 'leadtrack-pro' )
		);
	}

	/** Render honeypot_field field. */
	public static function render_field_honeypot() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		printf(
			'<input type="text" id="leadtrack_honeypot_field" name="%1$s[honeypot_field]" value="%2$s" class="regular-text">
			<p class="description">%3$s</p>',
			esc_attr( LEADTRACK_PRO_OPTIONS_KEY ),
			esc_attr( isset( $options['honeypot_field'] ) ? $options['honeypot_field'] : 'lt_hp_email' ),
			esc_html__( 'The hidden input name that bots will try to fill in. Use only lowercase letters, numbers, and underscores.', 'leadtrack-pro' )
		);
	}

	/** Render recaptcha_site_key field. */
	public static function render_field_recaptcha_site_key() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		printf(
			'<input type="text" id="leadtrack_recaptcha_site_key" name="%1$s[recaptcha_site_key]" value="%2$s" class="regular-text">
			<p class="description">%3$s</p>',
			esc_attr( LEADTRACK_PRO_OPTIONS_KEY ),
			esc_attr( isset( $options['recaptcha_site_key'] ) ? $options['recaptcha_site_key'] : '' ),
			esc_html__( 'Google reCAPTCHA v2 site key. Leave blank to disable reCAPTCHA.', 'leadtrack-pro' )
		);
	}

	/** Render recaptcha_secret_key field. */
	public static function render_field_recaptcha_secret_key() {
		$options = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		printf(
			'<input type="password" id="leadtrack_recaptcha_secret_key" name="%1$s[recaptcha_secret_key]" value="%2$s" class="regular-text">
			<p class="description">%3$s</p>',
			esc_attr( LEADTRACK_PRO_OPTIONS_KEY ),
			esc_attr( isset( $options['recaptcha_secret_key'] ) ? $options['recaptcha_secret_key'] : '' ),
			esc_html__( 'Google reCAPTCHA v2 secret key. Stored securely in the database.', 'leadtrack-pro' )
		);
	}

	// -----------------------------------------------------------------------
	// Setup Guide
	// -----------------------------------------------------------------------

	/**
	 * Render the Setup Guide tab content.
	 */
	public static function render_setup_guide() {
		$options       = get_option( LEADTRACK_PRO_OPTIONS_KEY, array() );
		$has_pixel     = ! empty( $options['pixel_id'] );
		$has_thankyou  = ! empty( $options['thankyou_page_id'] );
		$has_elementor = defined( 'ELEMENTOR_VERSION' );
		?>
		<div class="leadtrack-setup-guide">
			<h2><?php esc_html_e( 'Quick Start Guide', 'leadtrack-pro' ); ?></h2>
			<p><?php esc_html_e( 'Follow these steps to have LeadTrack Pro fully operational in under five minutes.', 'leadtrack-pro' ); ?></p>

			<ol class="leadtrack-steps">

				<li class="leadtrack-step <?php echo $has_thankyou ? 'leadtrack-step--done' : ''; ?>">
					<span class="leadtrack-step__icon" aria-hidden="true"><?php echo $has_thankyou ? '&#10003;' : '1'; ?></span>
					<div class="leadtrack-step__body">
						<h3><?php esc_html_e( 'Thank You Page', 'leadtrack-pro' ); ?></h3>
						<p><?php esc_html_e( 'A Thank You page was automatically created when you activated the plugin. You can find it in Pages > Thank You. Customise the content as you like — just keep the [leadtrack_thankyou] shortcode in the body.', 'leadtrack-pro' ); ?></p>
						<?php if ( $has_thankyou ) : ?>
							<a href="<?php echo esc_url( get_permalink( absint( $options['thankyou_page_id'] ) ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
								<?php esc_html_e( 'View Thank You Page', 'leadtrack-pro' ); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'options-general.php?page=leadtrack-pro&tab=general' ) ); ?>" class="button button-primary">
								<?php esc_html_e( 'Set Thank You Page', 'leadtrack-pro' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</li>

				<li class="leadtrack-step <?php echo $has_pixel ? 'leadtrack-step--done' : ''; ?>">
					<span class="leadtrack-step__icon" aria-hidden="true"><?php echo $has_pixel ? '&#10003;' : '2'; ?></span>
					<div class="leadtrack-step__body">
						<h3><?php esc_html_e( 'Add Your Meta Pixel ID', 'leadtrack-pro' ); ?></h3>
						<p>
							<?php esc_html_e( 'Navigate to the Pixel Tracking tab and paste in your Meta Pixel ID. You can find it in', 'leadtrack-pro' ); ?>
							<a href="https://business.facebook.com/events_manager" target="_blank" rel="noopener noreferrer">Meta Business Manager &rarr; Events Manager</a>.
						</p>
						<?php if ( ! $has_pixel ) : ?>
							<a href="<?php echo esc_url( admin_url( 'options-general.php?page=leadtrack-pro&tab=pixel' ) ); ?>" class="button button-primary">
								<?php esc_html_e( 'Enter Pixel ID', 'leadtrack-pro' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</li>

				<li class="leadtrack-step <?php echo $has_elementor ? 'leadtrack-step--done' : ''; ?>">
					<span class="leadtrack-step__icon" aria-hidden="true"><?php echo $has_elementor ? '&#10003;' : '3'; ?></span>
					<div class="leadtrack-step__body">
						<h3><?php esc_html_e( 'Connect Your Elementor Form', 'leadtrack-pro' ); ?></h3>
						<p><?php esc_html_e( 'Make sure Elementor is installed and the Elementor Integration toggle is enabled in the General tab. LeadTrack Pro will automatically redirect form submissions to the Thank You page.', 'leadtrack-pro' ); ?></p>
						<?php if ( ! $has_elementor ) : ?>
							<p class="leadtrack-notice leadtrack-notice--warning">
								<?php esc_html_e( 'Elementor does not appear to be active.', 'leadtrack-pro' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</li>

				<li class="leadtrack-step">
					<span class="leadtrack-step__icon" aria-hidden="true">4</span>
					<div class="leadtrack-step__body">
						<h3><?php esc_html_e( 'Enable Anti-Spam (Recommended)', 'leadtrack-pro' ); ?></h3>
						<p><?php esc_html_e( 'Go to the Anti-Spam tab to enable the hidden honeypot field. Optionally add Google reCAPTCHA v2 keys for an extra layer of protection.', 'leadtrack-pro' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'options-general.php?page=leadtrack-pro&tab=antispam' ) ); ?>" class="button button-secondary">
							<?php esc_html_e( 'Configure Anti-Spam', 'leadtrack-pro' ); ?>
						</a>
					</div>
				</li>

				<li class="leadtrack-step">
					<span class="leadtrack-step__icon" aria-hidden="true">5</span>
					<div class="leadtrack-step__body">
						<h3><?php esc_html_e( 'Test Your Setup', 'leadtrack-pro' ); ?></h3>
						<p><?php esc_html_e( 'Submit a test form entry. You should be redirected to your Thank You page. Open Meta Pixel Helper (Chrome extension) and confirm a "Lead" event fires on the Thank You page.', 'leadtrack-pro' ); ?></p>
						<a href="https://developers.facebook.com/docs/meta-pixel/support/pixel-helper/" target="_blank" rel="noopener noreferrer" class="button button-secondary">
							<?php esc_html_e( 'Get Meta Pixel Helper', 'leadtrack-pro' ); ?>
						</a>
					</div>
				</li>

			</ol><!-- .leadtrack-steps -->
		</div><!-- .leadtrack-setup-guide -->
		<?php
	}
}
