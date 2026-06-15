<?php
/**
 * Admin Settings Class
 *
 * @package Lead_Tracker_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LTP_Admin_Settings {

    private static $instance = null;
    private $option_group    = 'ltp_settings_group';
    private $option_name     = 'ltp_settings';
    private $page_slug       = 'lead-tracker-pro';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu_page() {
        add_options_page(
            __( 'Lead Tracker Pro Settings', 'lead-tracker-pro' ),
            __( 'Lead Tracker Pro', 'lead-tracker-pro' ),
            'manage_options',
            $this->page_slug,
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            [ $this, 'sanitize_settings' ]
        );

        // ── General Settings ──────────────────────────────────────────────
        add_settings_section(
            'ltp_general',
            __( 'General Settings', 'lead-tracker-pro' ),
            [ $this, 'section_general_cb' ],
            $this->page_slug
        );

        add_settings_field( 'enable_redirect', __( 'Enable Thank You Page Redirect', 'lead-tracker-pro' ), [ $this, 'field_enable_redirect' ], $this->page_slug, 'ltp_general' );
        add_settings_field( 'thankyou_url', __( 'Custom Thank You Page URL', 'lead-tracker-pro' ), [ $this, 'field_thankyou_url' ], $this->page_slug, 'ltp_general' );
        add_settings_field( 'thankyou_title', __( 'Thank You Page Title', 'lead-tracker-pro' ), [ $this, 'field_thankyou_title' ], $this->page_slug, 'ltp_general' );

        // ── Meta Pixel Settings ───────────────────────────────────────────
        add_settings_section(
            'ltp_pixel',
            __( 'Meta Pixel Settings', 'lead-tracker-pro' ),
            [ $this, 'section_pixel_cb' ],
            $this->page_slug
        );

        add_settings_field( 'enable_pixel', __( 'Enable Meta Pixel Tracking', 'lead-tracker-pro' ), [ $this, 'field_enable_pixel' ], $this->page_slug, 'ltp_pixel' );
        add_settings_field( 'pixel_id', __( 'Meta Pixel ID', 'lead-tracker-pro' ), [ $this, 'field_pixel_id' ], $this->page_slug, 'ltp_pixel' );
        add_settings_field( 'pixel_fire_on', __( 'Fire Lead Event On', 'lead-tracker-pro' ), [ $this, 'field_pixel_fire_on' ], $this->page_slug, 'ltp_pixel' );

        // ── Anti-Spam Settings ────────────────────────────────────────────
        add_settings_section(
            'ltp_antispam',
            __( 'Anti-Spam Settings', 'lead-tracker-pro' ),
            [ $this, 'section_antispam_cb' ],
            $this->page_slug
        );

        add_settings_field( 'enable_honeypot', __( 'Enable Honeypot Protection', 'lead-tracker-pro' ), [ $this, 'field_enable_honeypot' ], $this->page_slug, 'ltp_antispam' );
        add_settings_field( 'enable_time_check', __( 'Enable Time-based Validation', 'lead-tracker-pro' ), [ $this, 'field_enable_time_check' ], $this->page_slug, 'ltp_antispam' );
        add_settings_field( 'min_submit_time', __( 'Minimum Submit Time', 'lead-tracker-pro' ), [ $this, 'field_min_submit_time' ], $this->page_slug, 'ltp_antispam' );
        add_settings_field( 'enable_recaptcha', __( 'Enable reCAPTCHA v3', 'lead-tracker-pro' ), [ $this, 'field_enable_recaptcha' ], $this->page_slug, 'ltp_antispam' );
        add_settings_field( 'recaptcha_site_key', __( 'reCAPTCHA v3 Site Key', 'lead-tracker-pro' ), [ $this, 'field_recaptcha_site_key' ], $this->page_slug, 'ltp_antispam' );
        add_settings_field( 'recaptcha_secret', __( 'reCAPTCHA v3 Secret Key', 'lead-tracker-pro' ), [ $this, 'field_recaptcha_secret' ], $this->page_slug, 'ltp_antispam' );

        // ── Elementor Integration ─────────────────────────────────────────
        add_settings_section(
            'ltp_elementor',
            __( 'Elementor Integration', 'lead-tracker-pro' ),
            [ $this, 'section_elementor_cb' ],
            $this->page_slug
        );

        add_settings_field( 'enable_elementor', __( 'Auto-redirect After Form Submit', 'lead-tracker-pro' ), [ $this, 'field_enable_elementor' ], $this->page_slug, 'ltp_elementor' );
        add_settings_field( 'redirect_delay', __( 'Redirect Delay', 'lead-tracker-pro' ), [ $this, 'field_redirect_delay' ], $this->page_slug, 'ltp_elementor' );
        add_settings_field( 'tracked_form_ids', __( 'Form IDs to Track', 'lead-tracker-pro' ), [ $this, 'field_tracked_form_ids' ], $this->page_slug, 'ltp_elementor' );
    }

    public function sanitize_settings( $input ) {
        $sanitized = [];

        $sanitized['enable_redirect']   = ! empty( $input['enable_redirect'] ) ? 1 : 0;
        $sanitized['thankyou_url']      = isset( $input['thankyou_url'] ) ? esc_url_raw( $input['thankyou_url'] ) : '';
        $sanitized['thankyou_title']    = isset( $input['thankyou_title'] ) ? sanitize_text_field( $input['thankyou_title'] ) : __( 'Thank You!', 'lead-tracker-pro' );
        $sanitized['enable_pixel']      = ! empty( $input['enable_pixel'] ) ? 1 : 0;
        $sanitized['pixel_id']          = isset( $input['pixel_id'] ) ? sanitize_text_field( $input['pixel_id'] ) : '';
        $sanitized['pixel_fire_on']     = in_array( $input['pixel_fire_on'] ?? 'thankyou', [ 'thankyou', 'submit' ], true ) ? $input['pixel_fire_on'] : 'thankyou';
        $sanitized['enable_honeypot']   = ! empty( $input['enable_honeypot'] ) ? 1 : 0;
        $sanitized['enable_time_check'] = ! empty( $input['enable_time_check'] ) ? 1 : 0;
        $sanitized['min_submit_time']   = isset( $input['min_submit_time'] ) ? absint( $input['min_submit_time'] ) : 3;
        $sanitized['enable_recaptcha']  = ! empty( $input['enable_recaptcha'] ) ? 1 : 0;
        $sanitized['recaptcha_site_key'] = isset( $input['recaptcha_site_key'] ) ? sanitize_text_field( $input['recaptcha_site_key'] ) : '';
        $sanitized['recaptcha_secret']  = isset( $input['recaptcha_secret'] ) ? sanitize_text_field( $input['recaptcha_secret'] ) : '';
        $sanitized['enable_elementor']  = ! empty( $input['enable_elementor'] ) ? 1 : 0;
        $sanitized['redirect_delay']    = isset( $input['redirect_delay'] ) ? absint( $input['redirect_delay'] ) : 0;
        $sanitized['tracked_form_ids']  = isset( $input['tracked_form_ids'] ) ? sanitize_textarea_field( $input['tracked_form_ids'] ) : '';

        add_settings_error( $this->option_name, 'settings_updated', __( 'Settings saved successfully.', 'lead-tracker-pro' ), 'updated' );

        return $sanitized;
    }

    // ── Section callbacks ─────────────────────────────────────────────────

    public function section_general_cb() {
        echo '<p class="ltp-section-desc">' . esc_html__( 'Configure general plugin behavior and Thank You page settings.', 'lead-tracker-pro' ) . '</p>';
    }

    public function section_pixel_cb() {
        echo '<p class="ltp-section-desc">' . esc_html__( 'Configure Meta (Facebook) Pixel tracking for lead events.', 'lead-tracker-pro' ) . '</p>';
    }

    public function section_antispam_cb() {
        echo '<p class="ltp-section-desc">' . esc_html__( 'Configure anti-spam measures to filter out bot submissions.', 'lead-tracker-pro' ) . '</p>';
    }

    public function section_elementor_cb() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            echo '<div class="ltp-notice ltp-notice-warning"><p>';
            echo esc_html__( 'Elementor is not currently active. These settings will take effect once Elementor Pro is installed and activated.', 'lead-tracker-pro' );
            echo '</p></div>';
        } else {
            echo '<p class="ltp-section-desc">' . esc_html__( 'Configure how Lead Tracker Pro integrates with Elementor forms.', 'lead-tracker-pro' ) . '</p>';
        }
    }

    // ── Field callbacks ───────────────────────────────────────────────────

    private function get_option( $key, $default = '' ) {
        $options = get_option( $this->option_name, [] );
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }

    public function field_enable_redirect() {
        $value = $this->get_option( 'enable_redirect', 1 );
        echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name ) . '[enable_redirect]" value="1" ' . checked( 1, $value, false ) . '> ';
        echo esc_html__( 'Redirect visitors to Thank You page after form submission', 'lead-tracker-pro' ) . '</label>';
    }

    public function field_thankyou_url() {
        $value = $this->get_option( 'thankyou_url', '' );
        $auto_url = '';
        $page_id  = get_option( 'ltp_thankyou_page_id' );
        if ( $page_id ) {
            $auto_url = get_permalink( $page_id );
        }
        echo '<input type="url" name="' . esc_attr( $this->option_name ) . '[thankyou_url]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr__( 'Leave empty to use auto-created page', 'lead-tracker-pro' ) . '">';
        if ( $auto_url ) {
            echo '<p class="description">' . sprintf(
                /* translators: %s: auto-created page URL */
                esc_html__( 'Auto-created page: %s', 'lead-tracker-pro' ),
                '<a href="' . esc_url( $auto_url ) . '" target="_blank">' . esc_html( $auto_url ) . '</a>'
            ) . '</p>';
        }
    }

    public function field_thankyou_title() {
        $value = $this->get_option( 'thankyou_title', __( 'Thank You!', 'lead-tracker-pro' ) );
        echo '<input type="text" name="' . esc_attr( $this->option_name ) . '[thankyou_title]" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__( 'Title displayed on the Thank You page.', 'lead-tracker-pro' ) . '</p>';
    }

    public function field_enable_pixel() {
        $value = $this->get_option( 'enable_pixel', 0 );
        echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name ) . '[enable_pixel]" value="1" ' . checked( 1, $value, false ) . '> ';
        echo esc_html__( 'Enable Meta Pixel and fire Lead event', 'lead-tracker-pro' ) . '</label>';
    }

    public function field_pixel_id() {
        $value = $this->get_option( 'pixel_id', '' );
        echo '<input type="text" name="' . esc_attr( $this->option_name ) . '[pixel_id]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="123456789012345">';
        echo '<p class="description">' . esc_html__( 'Your Meta Pixel ID (numeric).', 'lead-tracker-pro' ) . '</p>';
    }

    public function field_pixel_fire_on() {
        $value = $this->get_option( 'pixel_fire_on', 'thankyou' );
        $options = [
            'thankyou' => __( 'Thank You Page load', 'lead-tracker-pro' ),
            'submit'   => __( 'Form Submit', 'lead-tracker-pro' ),
        ];
        foreach ( $options as $key => $label ) {
            echo '<label style="display:block;margin-bottom:5px;"><input type="radio" name="' . esc_attr( $this->option_name ) . '[pixel_fire_on]" value="' . esc_attr( $key ) . '" ' . checked( $value, $key, false ) . '> ';
            echo esc_html( $label ) . '</label>';
        }
    }

    public function field_enable_honeypot() {
        $value = $this->get_option( 'enable_honeypot', 1 );
        echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name ) . '[enable_honeypot]" value="1" ' . checked( 1, $value, false ) . '> ';
        echo esc_html__( 'Add hidden honeypot field to detect bots', 'lead-tracker-pro' ) . '</label>';
    }

    public function field_enable_time_check() {
        $value = $this->get_option( 'enable_time_check', 1 );
        echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name ) . '[enable_time_check]" value="1" ' . checked( 1, $value, false ) . '> ';
        echo esc_html__( 'Reject submissions that are too fast (likely bots)', 'lead-tracker-pro' ) . '</label>';
    }

    public function field_min_submit_time() {
        $value = $this->get_option( 'min_submit_time', 3 );
        echo '<input type="number" name="' . esc_attr( $this->option_name ) . '[min_submit_time]" value="' . esc_attr( $value ) . '" min="1" max="60" style="width:80px;"> ';
        echo '<span class="description">' . esc_html__( 'seconds', 'lead-tracker-pro' ) . '</span>';
        echo '<p class="description">' . esc_html__( 'Minimum time (in seconds) a user must spend on the form before submitting.', 'lead-tracker-pro' ) . '</p>';
    }

    public function field_enable_recaptcha() {
        $value = $this->get_option( 'enable_recaptcha', 0 );
        echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name ) . '[enable_recaptcha]" value="1" ' . checked( 1, $value, false ) . '> ';
        echo esc_html__( 'Enable Google reCAPTCHA v3 verification', 'lead-tracker-pro' ) . '</label>';
    }

    public function field_recaptcha_site_key() {
        $value = $this->get_option( 'recaptcha_site_key', '' );
        echo '<input type="text" name="' . esc_attr( $this->option_name ) . '[recaptcha_site_key]" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__( 'Your reCAPTCHA v3 site key (public).', 'lead-tracker-pro' ) . '</p>';
    }

    public function field_recaptcha_secret() {
        $value = $this->get_option( 'recaptcha_secret', '' );
        echo '<input type="password" name="' . esc_attr( $this->option_name ) . '[recaptcha_secret]" value="' . esc_attr( $value ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__( 'Your reCAPTCHA v3 secret key (private, never share).', 'lead-tracker-pro' ) . '</p>';
    }

    public function field_enable_elementor() {
        $value = $this->get_option( 'enable_elementor', 1 );
        echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name ) . '[enable_elementor]" value="1" ' . checked( 1, $value, false ) . '> ';
        echo esc_html__( 'Automatically redirect after Elementor form submit', 'lead-tracker-pro' ) . '</label>';
    }

    public function field_redirect_delay() {
        $value = $this->get_option( 'redirect_delay', 0 );
        echo '<input type="number" name="' . esc_attr( $this->option_name ) . '[redirect_delay]" value="' . esc_attr( $value ) . '" min="0" max="10000" step="100" style="width:100px;"> ';
        echo '<span class="description">' . esc_html__( 'ms', 'lead-tracker-pro' ) . '</span>';
        echo '<p class="description">' . esc_html__( 'Delay in milliseconds before redirecting (0 = immediate).', 'lead-tracker-pro' ) . '</p>';
    }

    public function field_tracked_form_ids() {
        $value = $this->get_option( 'tracked_form_ids', '' );
        echo '<textarea name="' . esc_attr( $this->option_name ) . '[tracked_form_ids]" rows="4" class="large-text" placeholder="' . esc_attr__( 'Leave empty to track all forms', 'lead-tracker-pro' ) . '">' . esc_textarea( $value ) . '</textarea>';
        echo '<p class="description">' . esc_html__( 'Enter one Elementor form ID per line. Leave blank to track all Elementor forms.', 'lead-tracker-pro' ) . '</p>';
    }

    // ── Render page ───────────────────────────────────────────────────────

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options = get_option( $this->option_name, [] );
        ?>
        <div class="wrap ltp-settings-wrap">
            <h1 class="ltp-page-title">
                <span class="ltp-logo">&#128202;</span>
                <?php echo esc_html( get_admin_page_title() ); ?>
            </h1>

            <?php settings_errors( $this->option_name ); ?>

            <div class="ltp-settings-layout">
                <div class="ltp-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( $this->option_group );
                        ?>

                        <div class="ltp-settings-card">
                            <?php do_settings_sections( $this->page_slug ); ?>
                        </div>

                        <?php submit_button( __( 'Save Settings', 'lead-tracker-pro' ), 'primary ltp-save-btn', 'submit', true ); ?>
                    </form>
                </div>

                <div class="ltp-settings-sidebar">
                    <div class="ltp-sidebar-card">
                        <h3><?php esc_html_e( 'Plugin Status', 'lead-tracker-pro' ); ?></h3>
                        <ul class="ltp-status-list">
                            <li>
                                <span class="ltp-status-label"><?php esc_html_e( 'Redirect:', 'lead-tracker-pro' ); ?></span>
                                <?php if ( ! empty( $options['enable_redirect'] ) ) : ?>
                                    <span class="ltp-badge ltp-badge-active"><?php esc_html_e( 'Active', 'lead-tracker-pro' ); ?></span>
                                <?php else : ?>
                                    <span class="ltp-badge ltp-badge-inactive"><?php esc_html_e( 'Inactive', 'lead-tracker-pro' ); ?></span>
                                <?php endif; ?>
                            </li>
                            <li>
                                <span class="ltp-status-label"><?php esc_html_e( 'Meta Pixel:', 'lead-tracker-pro' ); ?></span>
                                <?php if ( ! empty( $options['enable_pixel'] ) && ! empty( $options['pixel_id'] ) ) : ?>
                                    <span class="ltp-badge ltp-badge-active"><?php esc_html_e( 'Active', 'lead-tracker-pro' ); ?></span>
                                <?php else : ?>
                                    <span class="ltp-badge ltp-badge-inactive"><?php esc_html_e( 'Inactive', 'lead-tracker-pro' ); ?></span>
                                <?php endif; ?>
                            </li>
                            <li>
                                <span class="ltp-status-label"><?php esc_html_e( 'Honeypot:', 'lead-tracker-pro' ); ?></span>
                                <?php if ( ! empty( $options['enable_honeypot'] ) ) : ?>
                                    <span class="ltp-badge ltp-badge-active"><?php esc_html_e( 'Active', 'lead-tracker-pro' ); ?></span>
                                <?php else : ?>
                                    <span class="ltp-badge ltp-badge-inactive"><?php esc_html_e( 'Inactive', 'lead-tracker-pro' ); ?></span>
                                <?php endif; ?>
                            </li>
                            <li>
                                <span class="ltp-status-label"><?php esc_html_e( 'reCAPTCHA:', 'lead-tracker-pro' ); ?></span>
                                <?php if ( ! empty( $options['enable_recaptcha'] ) && ! empty( $options['recaptcha_site_key'] ) ) : ?>
                                    <span class="ltp-badge ltp-badge-active"><?php esc_html_e( 'Active', 'lead-tracker-pro' ); ?></span>
                                <?php else : ?>
                                    <span class="ltp-badge ltp-badge-inactive"><?php esc_html_e( 'Inactive', 'lead-tracker-pro' ); ?></span>
                                <?php endif; ?>
                            </li>
                            <li>
                                <span class="ltp-status-label"><?php esc_html_e( 'Elementor:', 'lead-tracker-pro' ); ?></span>
                                <?php if ( did_action( 'elementor/loaded' ) ) : ?>
                                    <span class="ltp-badge ltp-badge-active"><?php esc_html_e( 'Loaded', 'lead-tracker-pro' ); ?></span>
                                <?php else : ?>
                                    <span class="ltp-badge ltp-badge-warning"><?php esc_html_e( 'Not loaded', 'lead-tracker-pro' ); ?></span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>

                    <div class="ltp-sidebar-card">
                        <h3><?php esc_html_e( 'Thank You Page', 'lead-tracker-pro' ); ?></h3>
                        <?php
                        $page_id = get_option( 'ltp_thankyou_page_id' );
                        if ( $page_id ) {
                            $page = get_post( $page_id );
                            if ( $page && 'publish' === $page->post_status ) {
                                echo '<p>';
                                echo '<a href="' . esc_url( get_permalink( $page_id ) ) . '" target="_blank" class="button button-secondary">';
                                echo esc_html__( 'View Thank You Page', 'lead-tracker-pro' );
                                echo '</a>';
                                echo '</p>';
                            } else {
                                echo '<p class="description">' . esc_html__( 'Auto-created page not found or unpublished.', 'lead-tracker-pro' ) . '</p>';
                            }
                        } else {
                            echo '<p class="description">' . esc_html__( 'No auto-created page found.', 'lead-tracker-pro' ) . '</p>';
                        }
                        ?>
                    </div>

                    <div class="ltp-sidebar-card">
                        <h3><?php esc_html_e( 'Quick Info', 'lead-tracker-pro' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Version:', 'lead-tracker-pro' ); ?> <strong><?php echo esc_html( LTP_VERSION ); ?></strong></p>
                        <p class="description"><?php esc_html_e( 'Shortcode:', 'lead-tracker-pro' ); ?> <code>[lead_tracker_thankyou]</code></p>
                        <?php
                        global $wpdb;
                        $table = $wpdb->prefix . 'ltp_submissions';
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
                        echo '<p class="description">' . esc_html__( 'Total Submissions:', 'lead-tracker-pro' ) . ' <strong>' . esc_html( number_format_i18n( (int) $count ) ) . '</strong></p>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
