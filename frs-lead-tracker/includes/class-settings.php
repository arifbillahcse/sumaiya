<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FRS_LT_Settings {

    public static function init() {
        add_action( 'admin_menu',    array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init',    array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
    }

    public static function add_menu() {
        add_options_page(
            __( 'FRS Lead Tracker', 'frs-lead-tracker' ),
            __( 'FRS Lead Tracker', 'frs-lead-tracker' ),
            'manage_options',
            'frs-lead-tracker',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    public static function register_settings() {
        register_setting( 'frs_lt_group', 'frs_lt_meta_pixel_id',      array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'frs_lt_group', 'frs_lt_recaptcha_site_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'frs_lt_group', 'frs_lt_recaptcha_secret',   array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'frs_lt_group', 'frs_lt_recaptcha_score',    array( 'sanitize_callback' => 'floatval' ) );
        register_setting( 'frs_lt_group', 'frs_lt_enable_gtm',         array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'frs_lt_group', 'frs_lt_thankyou_page_id',   array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'frs_lt_group', 'frs_lt_enable_honeypot',    array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'frs_lt_group', 'frs_lt_enable_recaptcha',   array( 'sanitize_callback' => 'absint' ) );
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_frs-lead-tracker' !== $hook ) return;
        wp_enqueue_style(  'frs-lt-admin', FRS_LT_URL . 'assets/css/admin.css', array(), FRS_LT_VERSION );
        wp_enqueue_script( 'frs-lt-admin', FRS_LT_URL . 'assets/js/admin.js',  array( 'jquery' ), FRS_LT_VERSION, true );
    }

    public static function render_settings_page() {
        $pixel_id      = get_option( 'frs_lt_meta_pixel_id', '' );
        $site_key      = get_option( 'frs_lt_recaptcha_site_key', '' );
        $secret        = get_option( 'frs_lt_recaptcha_secret', '' );
        $score         = get_option( 'frs_lt_recaptcha_score', 0.5 );
        $gtm           = get_option( 'frs_lt_enable_gtm', 0 );
        $page_id       = get_option( 'frs_lt_thankyou_page_id', 0 );
        $honeypot      = get_option( 'frs_lt_enable_honeypot', 1 );
        $use_recaptcha = get_option( 'frs_lt_enable_recaptcha', 0 );

        $pages = get_pages( array( 'sort_column' => 'post_title' ) );
        ?>
        <div class="frs-lt-wrap wrap">
            <div class="frs-lt-header">
                <div class="frs-lt-logo">
                    <span class="frs-lt-icon">&#128202;</span>
                    <h1><?php esc_html_e( 'FRS Lead Tracker', 'frs-lead-tracker' ); ?></h1>
                </div>
                <p class="frs-lt-tagline"><?php esc_html_e( 'Reliable Meta Pixel Lead tracking via Thank You Page — built for Elementor + GTM.', 'frs-lead-tracker' ); ?></p>
            </div>

            <?php settings_errors( 'frs_lt_group' ); ?>

            <form method="post" action="options.php" id="frs-lt-form">
                <?php settings_fields( 'frs_lt_group' ); ?>

                <div class="frs-lt-grid">

                    <!-- ── Meta Pixel ── -->
                    <div class="frs-lt-card">
                        <div class="frs-lt-card-header">
                            <span class="frs-lt-card-icon">&#128247;</span>
                            <h2><?php esc_html_e( 'Meta Pixel', 'frs-lead-tracker' ); ?></h2>
                        </div>
                        <div class="frs-lt-card-body">
                            <label for="frs_lt_meta_pixel_id">
                                <?php esc_html_e( 'Pixel ID', 'frs-lead-tracker' ); ?>
                                <span class="frs-lt-required">*</span>
                            </label>
                            <input type="text" id="frs_lt_meta_pixel_id" name="frs_lt_meta_pixel_id"
                                   value="<?php echo esc_attr( $pixel_id ); ?>"
                                   placeholder="e.g. 123456789012345" class="regular-text" required>
                            <p class="description"><?php esc_html_e( 'Your 15–16 digit Meta Pixel ID from Events Manager.', 'frs-lead-tracker' ); ?></p>
                        </div>
                    </div>

                    <!-- ── Thank You Page ── -->
                    <div class="frs-lt-card">
                        <div class="frs-lt-card-header">
                            <span class="frs-lt-card-icon">&#10003;</span>
                            <h2><?php esc_html_e( 'Thank You Page', 'frs-lead-tracker' ); ?></h2>
                        </div>
                        <div class="frs-lt-card-body">
                            <label for="frs_lt_thankyou_page_id"><?php esc_html_e( 'Select Page', 'frs-lead-tracker' ); ?></label>
                            <select id="frs_lt_thankyou_page_id" name="frs_lt_thankyou_page_id" class="regular-text">
                                <option value="0"><?php esc_html_e( '— Select a page —', 'frs-lead-tracker' ); ?></option>
                                <?php foreach ( $pages as $p ) : ?>
                                    <option value="<?php echo esc_attr( $p->ID ); ?>"
                                        <?php selected( $page_id, $p->ID ); ?>>
                                        <?php echo esc_html( $p->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ( $page_id ) : ?>
                                <p class="description">
                                    <a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank">
                                        <?php esc_html_e( 'Preview Thank You Page ↗', 'frs-lead-tracker' ); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <p class="description"><?php esc_html_e( 'A "Thank You" page was auto-created on activation. Set Elementor Form redirect to its URL.', 'frs-lead-tracker' ); ?></p>
                        </div>
                    </div>

                    <!-- ── Spam Protection ── -->
                    <div class="frs-lt-card">
                        <div class="frs-lt-card-header">
                            <span class="frs-lt-card-icon">&#128274;</span>
                            <h2><?php esc_html_e( 'Spam Protection', 'frs-lead-tracker' ); ?></h2>
                        </div>
                        <div class="frs-lt-card-body">
                            <label class="frs-lt-toggle">
                                <input type="checkbox" name="frs_lt_enable_honeypot" value="1" <?php checked( $honeypot, 1 ); ?>>
                                <span class="frs-lt-toggle-label"><?php esc_html_e( 'Honeypot Protection (always-on, invisible)', 'frs-lead-tracker' ); ?></span>
                            </label>
                            <hr>
                            <label class="frs-lt-toggle">
                                <input type="checkbox" name="frs_lt_enable_recaptcha" id="frs_lt_enable_recaptcha" value="1" <?php checked( $use_recaptcha, 1 ); ?>>
                                <span class="frs-lt-toggle-label"><?php esc_html_e( 'Google reCAPTCHA v3 (invisible, score-based)', 'frs-lead-tracker' ); ?></span>
                            </label>
                            <div id="frs-lt-recaptcha-fields" class="<?php echo $use_recaptcha ? '' : 'frs-lt-hidden'; ?>">
                                <label for="frs_lt_recaptcha_site_key"><?php esc_html_e( 'Site Key', 'frs-lead-tracker' ); ?></label>
                                <input type="text" id="frs_lt_recaptcha_site_key" name="frs_lt_recaptcha_site_key"
                                       value="<?php echo esc_attr( $site_key ); ?>" class="regular-text" placeholder="6Lc...">

                                <label for="frs_lt_recaptcha_secret"><?php esc_html_e( 'Secret Key', 'frs-lead-tracker' ); ?></label>
                                <input type="password" id="frs_lt_recaptcha_secret" name="frs_lt_recaptcha_secret"
                                       value="<?php echo esc_attr( $secret ); ?>" class="regular-text">

                                <label for="frs_lt_recaptcha_score">
                                    <?php esc_html_e( 'Minimum Score (0.0 – 1.0)', 'frs-lead-tracker' ); ?>
                                </label>
                                <input type="number" id="frs_lt_recaptcha_score" name="frs_lt_recaptcha_score"
                                       value="<?php echo esc_attr( $score ); ?>"
                                       min="0" max="1" step="0.1" class="small-text">
                                <p class="description"><?php esc_html_e( '0.5 is recommended. Higher = stricter (more humans may be blocked).', 'frs-lead-tracker' ); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- ── GTM ── -->
                    <div class="frs-lt-card">
                        <div class="frs-lt-card-header">
                            <span class="frs-lt-card-icon">&#127991;</span>
                            <h2><?php esc_html_e( 'Google Tag Manager', 'frs-lead-tracker' ); ?></h2>
                        </div>
                        <div class="frs-lt-card-body">
                            <label class="frs-lt-toggle">
                                <input type="checkbox" name="frs_lt_enable_gtm" value="1" <?php checked( $gtm, 1 ); ?>>
                                <span class="frs-lt-toggle-label"><?php esc_html_e( 'Push lead_conversion event to GTM dataLayer', 'frs-lead-tracker' ); ?></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'When enabled, a dataLayer.push({event:"lead_conversion"}) fires on the Thank You Page so GTM can fire its own tags.', 'frs-lead-tracker' ); ?></p>
                        </div>
                    </div>

                </div><!-- /.frs-lt-grid -->

                <!-- ── GTM Setup Guide ── -->
                <div class="frs-lt-card frs-lt-full-width">
                    <div class="frs-lt-card-header">
                        <span class="frs-lt-card-icon">&#128221;</span>
                        <h2><?php esc_html_e( 'GTM Setup Guide', 'frs-lead-tracker' ); ?></h2>
                    </div>
                    <div class="frs-lt-card-body">
                        <div class="frs-lt-steps">
                            <div class="frs-lt-step">
                                <span class="frs-lt-step-num">1</span>
                                <div>
                                    <strong><?php esc_html_e( 'Create a Custom Event Trigger', 'frs-lead-tracker' ); ?></strong>
                                    <p><?php esc_html_e( 'In GTM → Triggers → New → Custom Event. Set Event Name to: lead_conversion', 'frs-lead-tracker' ); ?></p>
                                    <code>Event Name: lead_conversion</code>
                                </div>
                            </div>
                            <div class="frs-lt-step">
                                <span class="frs-lt-step-num">2</span>
                                <div>
                                    <strong><?php esc_html_e( 'Create a Custom HTML Tag', 'frs-lead-tracker' ); ?></strong>
                                    <p><?php esc_html_e( 'In GTM → Tags → New → Custom HTML. Paste the code below. Set trigger to the one above.', 'frs-lead-tracker' ); ?></p>
                                    <pre class="frs-lt-code">&lt;script&gt;
fbq('track', 'Lead');
&lt;/script&gt;</pre>
                                </div>
                            </div>
                            <div class="frs-lt-step">
                                <span class="frs-lt-step-num">3</span>
                                <div>
                                    <strong><?php esc_html_e( 'Preview & Publish', 'frs-lead-tracker' ); ?></strong>
                                    <p><?php esc_html_e( 'Use GTM Preview mode, submit your form, land on Thank You Page, and confirm "lead_conversion" event fires once in the GTM debugger.', 'frs-lead-tracker' ); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Testing Checklist ── -->
                <div class="frs-lt-card frs-lt-full-width">
                    <div class="frs-lt-card-header">
                        <span class="frs-lt-card-icon">&#9989;</span>
                        <h2><?php esc_html_e( 'Testing Checklist', 'frs-lead-tracker' ); ?></h2>
                    </div>
                    <div class="frs-lt-card-body">
                        <ul class="frs-lt-checklist">
                            <li><?php esc_html_e( 'Submit your Elementor form with real data — confirm redirect lands on the Thank You Page.', 'frs-lead-tracker' ); ?></li>
                            <li><?php esc_html_e( 'Open browser DevTools → Network → filter "facebook" — verify fbq Lead event fires exactly once.', 'frs-lead-tracker' ); ?></li>
                            <li><?php esc_html_e( 'Refresh the Thank You Page — Meta Pixel Lead event must NOT fire again (duplicate guard).', 'frs-lead-tracker' ); ?></li>
                            <li><?php esc_html_e( 'Use Meta Pixel Helper browser extension to confirm Lead event on Thank You Page.', 'frs-lead-tracker' ); ?></li>
                            <li><?php esc_html_e( 'Check Meta Events Manager → Test Events tool — verify Lead event appears under your Pixel.', 'frs-lead-tracker' ); ?></li>
                            <li><?php esc_html_e( 'Navigate directly to /thank-you/ (without ?lead=1) — confirm Meta Lead event does NOT fire.', 'frs-lead-tracker' ); ?></li>
                            <li><?php esc_html_e( 'Use GTM Preview mode — confirm "lead_conversion" dataLayer event fires once per real submission.', 'frs-lead-tracker' ); ?></li>
                            <li><?php esc_html_e( 'Submit a bot/spam entry — confirm it is blocked before redirect.', 'frs-lead-tracker' ); ?></li>
                        </ul>
                    </div>
                </div>

                <?php submit_button( __( 'Save Settings', 'frs-lead-tracker' ), 'primary frs-lt-save-btn' ); ?>
            </form>
        </div>
        <?php
    }
}
