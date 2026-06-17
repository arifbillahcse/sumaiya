<?php
/**
 * Plugin Name:       Lead Tracking Pro
 * Plugin URI:        https://example.com/lead-tracking-pro
 * Description:       Complete lead tracking with Meta Pixel, Google reCAPTCHA v3, honeypot spam protection, GTM dataLayer integration, and Elementor form redirect. Fires the Meta Pixel Lead event only on a dedicated Thank You Page.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Lead Tracking Pro
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lead-tracking-pro
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------
define( 'LTP_VERSION',     '1.0.0' );
define( 'LTP_PLUGIN_FILE', __FILE__ );
define( 'LTP_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'LTP_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'LTP_OPTION_KEY',  'lead_tracking_pro_options' );
define( 'LTP_LOG_KEY',     'lead_tracking_pro_last_submission' );

// ---------------------------------------------------------------------------
// Default options helper
// ---------------------------------------------------------------------------
function ltp_defaults(): array {
    return [
        'pixel_id'          => '',
        'recaptcha_site'    => '',
        'recaptcha_secret'  => '',
        'thank_you_url'     => '',
        'recaptcha_score'   => '0.5',
    ];
}

function ltp_get_options(): array {
    $saved = get_option( LTP_OPTION_KEY, [] );
    return wp_parse_args( $saved, ltp_defaults() );
}

// ---------------------------------------------------------------------------
// Activation — create Thank You page
// ---------------------------------------------------------------------------
register_activation_hook( LTP_PLUGIN_FILE, 'ltp_activate' );
function ltp_activate(): void {
    $opts = ltp_get_options();

    // Only create the page if we don't already have a URL stored
    if ( empty( $opts['thank_you_url'] ) ) {
        $existing = get_page_by_path( 'thank-you-lead' );
        if ( $existing ) {
            $page_id = $existing->ID;
        } else {
            $page_id = wp_insert_post( [
                'post_title'   => 'Thank You',
                'post_name'    => 'thank-you-lead',
                'post_content' => '<!-- wp:paragraph --><p>Thank you for reaching out! We\'ll be in touch shortly.</p><!-- /wp:paragraph -->',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id() ?: 1,
            ] );
        }

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            $opts['thank_you_url'] = get_permalink( $page_id );
            update_option( LTP_OPTION_KEY, $opts );
            update_option( 'ltp_thank_you_page_id', $page_id );
        }
    }

    // Flush rewrite rules so the new page is reachable immediately
    flush_rewrite_rules();
}

// ---------------------------------------------------------------------------
// Deactivation
// ---------------------------------------------------------------------------
register_deactivation_hook( LTP_PLUGIN_FILE, 'ltp_deactivate' );
function ltp_deactivate(): void {
    flush_rewrite_rules();
}

// ---------------------------------------------------------------------------
// Helper: is current page the Thank You page?
// ---------------------------------------------------------------------------
function ltp_is_thank_you_page(): bool {
    $opts        = ltp_get_options();
    $thank_url   = trailingslashit( $opts['thank_you_url'] );
    $current_url = trailingslashit( home_url( add_query_arg( [], $GLOBALS['wp']->request ?? '' ) ) );

    // Compare by stored page ID when available (most reliable)
    $page_id = (int) get_option( 'ltp_thank_you_page_id', 0 );
    if ( $page_id && is_page( $page_id ) ) {
        return true;
    }

    // Fallback: URL comparison
    if ( ! empty( $thank_url ) && $thank_url === $current_url ) {
        return true;
    }

    return false;
}

// ---------------------------------------------------------------------------
// Enqueue front-end scripts
// ---------------------------------------------------------------------------
add_action( 'wp_enqueue_scripts', 'ltp_enqueue_frontend' );
function ltp_enqueue_frontend(): void {
    $opts = ltp_get_options();

    // --- reCAPTCHA v3 on all pages that have Elementor forms ---
    if ( ! empty( $opts['recaptcha_site'] ) ) {
        wp_enqueue_script(
            'google-recaptcha-v3',
            'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $opts['recaptcha_site'] ),
            [],
            null,
            true
        );

        // Inline script: attach reCAPTCHA token to Elementor form before submit
        $inline = sprintf(
            '(function(){
                var siteKey = %s;
                document.addEventListener("DOMContentLoaded", function(){
                    document.querySelectorAll(".elementor-form").forEach(function(form){
                        form.addEventListener("submit", function(e){
                            var existing = form.querySelector("input[name=ltp_recaptcha_token]");
                            if(existing){ return; }
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            var honeypot = form.querySelector("input[name=ltp_honeypot]");
                            if(honeypot && honeypot.value !== ""){
                                console.warn("LTP: honeypot triggered, blocking submit.");
                                return;
                            }
                            grecaptcha.ready(function(){
                                grecaptcha.execute(siteKey, {action: "elementor_form"}).then(function(token){
                                    var inp = document.createElement("input");
                                    inp.type = "hidden";
                                    inp.name = "ltp_recaptcha_token";
                                    inp.value = token;
                                    form.appendChild(inp);
                                    // Re-trigger native submit so Elementor AJAX picks it up
                                    var submitBtn = form.querySelector("[type=submit]");
                                    if(submitBtn){ submitBtn.click(); }
                                    else { form.submit(); }
                                });
                            });
                        }, true);
                    });
                });
            }());',
            wp_json_encode( $opts['recaptcha_site'] )
        );
        wp_add_inline_script( 'google-recaptcha-v3', $inline );
    }

    // --- Thank You Page scripts ---
    if ( ltp_is_thank_you_page() ) {
        if ( ! empty( $opts['pixel_id'] ) ) {
            wp_enqueue_script(
                'ltp-thank-you',
                LTP_PLUGIN_URL . 'assets/thank-you.js',
                [],
                LTP_VERSION,
                true
            );

            wp_localize_script( 'ltp-thank-you', 'ltpData', [
                'pixelId' => $opts['pixel_id'],
            ] );

            // Meta Pixel base code injected via wp_head for earliest possible load
            add_action( 'wp_head', 'ltp_inject_pixel_base_code', 1 );
        }
    }
}

// ---------------------------------------------------------------------------
// Meta Pixel base code (Thank You page only)
// ---------------------------------------------------------------------------
function ltp_inject_pixel_base_code(): void {
    $opts = ltp_get_options();
    if ( empty( $opts['pixel_id'] ) ) {
        return;
    }
    $pixel_id = esc_js( $opts['pixel_id'] );
    ?>
<!-- Meta Pixel Code — Lead Tracking Pro -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo $pixel_id; ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo esc_attr( $opts['pixel_id'] ); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
    <?php
}

// ---------------------------------------------------------------------------
// Honeypot injection into Elementor forms
// ---------------------------------------------------------------------------
add_action( 'elementor_pro/forms/render/item', 'ltp_inject_honeypot_field', 10, 3 );
function ltp_inject_honeypot_field( $item, $item_index, $form ): void {
    // We inject only once (on the first field rendered)
    if ( 0 !== $item_index ) {
        return;
    }
    echo '<div style="position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;width:0;overflow:hidden;" aria-hidden="true" tabindex="-1">'
        . '<label for="ltp_honeypot">Leave this field empty</label>'
        . '<input type="text" id="ltp_honeypot" name="ltp_honeypot" value="" autocomplete="off" tabindex="-1">'
        . '</div>';
}

// Alternate hook — some Elementor versions use this
add_action( 'elementor_pro/forms/render_field/text', 'ltp_maybe_inject_honeypot_once', 1, 3 );
function ltp_maybe_inject_honeypot_once( $item, $item_index, $form ): void {
    // Guard with a static so we only echo once per page
    static $injected = false;
    if ( $injected ) {
        return;
    }
    $injected = true;
    echo '<div style="position:absolute;left:-9999px;top:-9999px;opacity:0;height:0;width:0;overflow:hidden;" aria-hidden="true" tabindex="-1">'
        . '<label for="ltp_honeypot_b">Leave this field empty</label>'
        . '<input type="text" id="ltp_honeypot_b" name="ltp_honeypot" value="" autocomplete="off" tabindex="-1">'
        . '</div>';
}

// ---------------------------------------------------------------------------
// Elementor form submission hook — validate reCAPTCHA + honeypot, then redirect
// ---------------------------------------------------------------------------
add_action( 'elementor_pro/forms/process', 'ltp_process_elementor_form', 10, 2 );
function ltp_process_elementor_form( $record, $ajax_handler ): void {
    $opts = ltp_get_options();
    $log  = [
        'time'            => current_time( 'mysql' ),
        'recaptcha_score' => null,
        'recaptcha_pass'  => null,
        'honeypot_pass'   => null,
        'redirected'      => false,
        'error'           => '',
    ];

    // 1. Honeypot check
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $honeypot = isset( $_POST['ltp_honeypot'] ) ? sanitize_text_field( wp_unslash( $_POST['ltp_honeypot'] ) ) : '';
    if ( '' !== $honeypot ) {
        $log['honeypot_pass'] = false;
        $log['error']         = 'Honeypot triggered.';
        update_option( LTP_LOG_KEY, $log );
        $ajax_handler->add_error_message( __( 'Spam detected. Please try again.', 'lead-tracking-pro' ) );
        $ajax_handler->is_success = false;
        return;
    }
    $log['honeypot_pass'] = true;

    // 2. reCAPTCHA v3 server-side validation
    if ( ! empty( $opts['recaptcha_secret'] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $token = isset( $_POST['ltp_recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['ltp_recaptcha_token'] ) ) : '';

        if ( empty( $token ) ) {
            $log['error'] = 'Missing reCAPTCHA token.';
            update_option( LTP_LOG_KEY, $log );
            $ajax_handler->add_error_message( __( 'Security verification failed. Please reload and try again.', 'lead-tracking-pro' ) );
            $ajax_handler->is_success = false;
            return;
        }

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body'    => [
                'secret'   => $opts['recaptcha_secret'],
                'response' => $token,
                'remoteip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            $log['error'] = 'reCAPTCHA API error: ' . $response->get_error_message();
            update_option( LTP_LOG_KEY, $log );
            // Fail open — do not block if API is unreachable
        } else {
            $body  = json_decode( wp_remote_retrieve_body( $response ), true );
            $score = isset( $body['score'] ) ? (float) $body['score'] : 0.0;
            $threshold = (float) ( $opts['recaptcha_score'] ?? 0.5 );

            $log['recaptcha_score'] = $score;

            if ( empty( $body['success'] ) || $score < $threshold ) {
                $log['recaptcha_pass'] = false;
                $log['error']          = sprintf( 'reCAPTCHA failed. Score: %.2f, Threshold: %.2f', $score, $threshold );
                update_option( LTP_LOG_KEY, $log );
                $ajax_handler->add_error_message( __( 'Security score too low. Please try again.', 'lead-tracking-pro' ) );
                $ajax_handler->is_success = false;
                return;
            }
            $log['recaptcha_pass'] = true;
        }
    }

    // 3. All checks passed — store log and set redirect
    $log['redirected'] = true;
    update_option( LTP_LOG_KEY, $log );

    if ( ! empty( $opts['thank_you_url'] ) ) {
        $ajax_handler->add_response_data( 'redirect_url', esc_url( $opts['thank_you_url'] ) );
    }
}

// Hook after successful Elementor form send to force redirect
add_action( 'elementor_pro/forms/new_record', 'ltp_elementor_form_redirect', 10, 2 );
function ltp_elementor_form_redirect( $record, $ajax_handler ): void {
    $opts = ltp_get_options();
    if ( empty( $opts['thank_you_url'] ) ) {
        return;
    }
    $ajax_handler->add_response_data( 'redirect_url', esc_url( $opts['thank_you_url'] ) );
}

// ---------------------------------------------------------------------------
// Admin menu
// ---------------------------------------------------------------------------
add_action( 'admin_menu', 'ltp_admin_menu' );
function ltp_admin_menu(): void {
    add_options_page(
        __( 'Lead Tracking Pro', 'lead-tracking-pro' ),
        __( 'Lead Tracking Pro', 'lead-tracking-pro' ),
        'manage_options',
        'lead-tracking-pro',
        'ltp_settings_page'
    );

    add_management_page(
        __( 'LTP Diagnostics', 'lead-tracking-pro' ),
        __( 'LTP Diagnostics', 'lead-tracking-pro' ),
        'manage_options',
        'ltp-diagnostics',
        'ltp_diagnostics_page'
    );
}

// ---------------------------------------------------------------------------
// Admin styles
// ---------------------------------------------------------------------------
add_action( 'admin_enqueue_scripts', 'ltp_admin_enqueue' );
function ltp_admin_enqueue( string $hook ): void {
    $allowed_hooks = [ 'settings_page_lead-tracking-pro', 'tools_page_ltp-diagnostics' ];
    if ( ! in_array( $hook, $allowed_hooks, true ) ) {
        return;
    }
    wp_enqueue_style(
        'ltp-admin-style',
        LTP_PLUGIN_URL . 'assets/admin-style.css',
        [],
        LTP_VERSION
    );
}

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------
function ltp_settings_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'lead-tracking-pro' ) );
    }

    // Handle form save
    if ( isset( $_POST['ltp_save_settings'] ) ) {
        check_admin_referer( 'ltp_save_settings_action', 'ltp_nonce' );

        $new_opts = [
            'pixel_id'         => sanitize_text_field( wp_unslash( $_POST['pixel_id'] ?? '' ) ),
            'recaptcha_site'   => sanitize_text_field( wp_unslash( $_POST['recaptcha_site'] ?? '' ) ),
            'recaptcha_secret' => sanitize_text_field( wp_unslash( $_POST['recaptcha_secret'] ?? '' ) ),
            'thank_you_url'    => esc_url_raw( wp_unslash( $_POST['thank_you_url'] ?? '' ) ),
            'recaptcha_score'  => ltp_sanitize_score( wp_unslash( $_POST['recaptcha_score'] ?? '0.5' ) ),
        ];

        update_option( LTP_OPTION_KEY, $new_opts );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'lead-tracking-pro' ) . '</p></div>';
    }

    $opts = ltp_get_options();
    ?>
    <div class="wrap ltp-wrap">
        <h1 class="ltp-page-title">
            <span class="ltp-logo">&#128202;</span>
            <?php esc_html_e( 'Lead Tracking Pro — Settings', 'lead-tracking-pro' ); ?>
        </h1>

        <form method="post" action="" class="ltp-settings-form">
            <?php wp_nonce_field( 'ltp_save_settings_action', 'ltp_nonce' ); ?>

            <div class="ltp-card">
                <h2 class="ltp-card-title"><?php esc_html_e( 'Meta Pixel', 'lead-tracking-pro' ); ?></h2>
                <table class="form-table ltp-table">
                    <tr>
                        <th scope="row">
                            <label for="pixel_id"><?php esc_html_e( 'Meta Pixel ID', 'lead-tracking-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="pixel_id" name="pixel_id"
                                   value="<?php echo esc_attr( $opts['pixel_id'] ); ?>"
                                   class="regular-text" placeholder="e.g. 1234567890123456">
                            <p class="description"><?php esc_html_e( 'Your Meta (Facebook) Pixel ID. The Lead event fires only on the Thank You page.', 'lead-tracking-pro' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="ltp-card">
                <h2 class="ltp-card-title"><?php esc_html_e( 'Google reCAPTCHA v3', 'lead-tracking-pro' ); ?></h2>
                <table class="form-table ltp-table">
                    <tr>
                        <th scope="row">
                            <label for="recaptcha_site"><?php esc_html_e( 'Site Key', 'lead-tracking-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="recaptcha_site" name="recaptcha_site"
                                   value="<?php echo esc_attr( $opts['recaptcha_site'] ); ?>"
                                   class="regular-text" placeholder="6Le...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recaptcha_secret"><?php esc_html_e( 'Secret Key', 'lead-tracking-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="password" id="recaptcha_secret" name="recaptcha_secret"
                                   value="<?php echo esc_attr( $opts['recaptcha_secret'] ); ?>"
                                   class="regular-text" placeholder="6Le...">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recaptcha_score"><?php esc_html_e( 'Minimum Score Threshold', 'lead-tracking-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="recaptcha_score" name="recaptcha_score"
                                   value="<?php echo esc_attr( $opts['recaptcha_score'] ); ?>"
                                   class="small-text" min="0" max="1" step="0.05">
                            <p class="description"><?php esc_html_e( 'Score from 0.0 (likely bot) to 1.0 (likely human). Default: 0.5', 'lead-tracking-pro' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="ltp-card">
                <h2 class="ltp-card-title"><?php esc_html_e( 'Thank You Page', 'lead-tracking-pro' ); ?></h2>
                <table class="form-table ltp-table">
                    <tr>
                        <th scope="row">
                            <label for="thank_you_url"><?php esc_html_e( 'Thank You Page URL', 'lead-tracking-pro' ); ?></label>
                        </th>
                        <td>
                            <input type="url" id="thank_you_url" name="thank_you_url"
                                   value="<?php echo esc_attr( $opts['thank_you_url'] ); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php esc_html_e( 'Auto-filled on plugin activation. Change if you use a custom page.', 'lead-tracking-pro' ); ?>
                                <?php if ( ! empty( $opts['thank_you_url'] ) ) : ?>
                                    &mdash; <a href="<?php echo esc_url( $opts['thank_you_url'] ); ?>" target="_blank"><?php esc_html_e( 'Preview page', 'lead-tracking-pro' ); ?></a>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <input type="submit" name="ltp_save_settings" class="button button-primary button-hero"
                       value="<?php esc_attr_e( 'Save Settings', 'lead-tracking-pro' ); ?>">
            </p>
        </form>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Score sanitizer helper
// ---------------------------------------------------------------------------
function ltp_sanitize_score( string $value ): string {
    $score = (float) $value;
    $score = max( 0.0, min( 1.0, $score ) );
    return number_format( $score, 2, '.', '' );
}

// ---------------------------------------------------------------------------
// Diagnostics / testing page
// ---------------------------------------------------------------------------
function ltp_diagnostics_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'lead-tracking-pro' ) );
    }

    $opts = ltp_get_options();
    $log  = get_option( LTP_LOG_KEY, [] );

    // Status helpers
    $pixel_ok      = ! empty( $opts['pixel_id'] );
    $recap_site_ok = ! empty( $opts['recaptcha_site'] );
    $recap_sec_ok  = ! empty( $opts['recaptcha_secret'] );
    $ty_url_ok     = ! empty( $opts['thank_you_url'] );

    // Live reCAPTCHA ping (only if both keys present and button clicked)
    $recap_live_result = null;
    if ( isset( $_POST['ltp_test_recaptcha'] ) ) {
        check_admin_referer( 'ltp_diag_action', 'ltp_diag_nonce' );
        if ( $recap_sec_ok ) {
            $test_response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
                'timeout' => 10,
                'body'    => [
                    'secret'   => $opts['recaptcha_secret'],
                    'response' => 'diag-test-token',
                ],
            ] );
            if ( is_wp_error( $test_response ) ) {
                $recap_live_result = 'API error: ' . $test_response->get_error_message();
            } else {
                $body = json_decode( wp_remote_retrieve_body( $test_response ), true );
                // A "success:false" with "invalid-input-response" means the API is reachable
                if ( isset( $body['error-codes'] ) && in_array( 'invalid-input-response', $body['error-codes'], true ) ) {
                    $recap_live_result = 'API reachable — secret key accepted (test token intentionally invalid).';
                } else {
                    $recap_live_result = 'API response: ' . wp_json_encode( $body );
                }
            }
        } else {
            $recap_live_result = 'Secret key not configured.';
        }
    }

    // Clear log
    if ( isset( $_POST['ltp_clear_log'] ) ) {
        check_admin_referer( 'ltp_diag_action', 'ltp_diag_nonce' );
        delete_option( LTP_LOG_KEY );
        $log = [];
    }
    ?>
    <div class="wrap ltp-wrap">
        <h1 class="ltp-page-title">
            <span class="ltp-logo">&#128202;</span>
            <?php esc_html_e( 'Lead Tracking Pro — Diagnostics', 'lead-tracking-pro' ); ?>
        </h1>

        <!-- Status Grid -->
        <div class="ltp-card">
            <h2 class="ltp-card-title"><?php esc_html_e( 'Configuration Status', 'lead-tracking-pro' ); ?></h2>
            <div class="ltp-status-grid">
                <?php
                ltp_status_row( __( 'Meta Pixel ID', 'lead-tracking-pro' ), $pixel_ok,
                    $pixel_ok ? sprintf( __( 'Configured: %s', 'lead-tracking-pro' ), esc_html( $opts['pixel_id'] ) ) : __( 'Not configured', 'lead-tracking-pro' ) );
                ltp_status_row( __( 'reCAPTCHA Site Key', 'lead-tracking-pro' ), $recap_site_ok,
                    $recap_site_ok ? __( 'Configured', 'lead-tracking-pro' ) : __( 'Not configured', 'lead-tracking-pro' ) );
                ltp_status_row( __( 'reCAPTCHA Secret Key', 'lead-tracking-pro' ), $recap_sec_ok,
                    $recap_sec_ok ? __( 'Configured', 'lead-tracking-pro' ) : __( 'Not configured', 'lead-tracking-pro' ) );
                ltp_status_row( __( 'Thank You Page URL', 'lead-tracking-pro' ), $ty_url_ok,
                    $ty_url_ok ? esc_html( $opts['thank_you_url'] ) : __( 'Not configured', 'lead-tracking-pro' ) );
                ltp_status_row( __( 'reCAPTCHA Score Threshold', 'lead-tracking-pro' ), true,
                    esc_html( $opts['recaptcha_score'] ) );
                ltp_status_row( __( 'Elementor Pro', 'lead-tracking-pro' ), defined( 'ELEMENTOR_PRO_VERSION' ),
                    defined( 'ELEMENTOR_PRO_VERSION' ) ? sprintf( __( 'Active (v%s)', 'lead-tracking-pro' ), ELEMENTOR_PRO_VERSION ) : __( 'Not detected — hooks will not fire without Elementor Pro', 'lead-tracking-pro' ) );
                ?>
            </div>
        </div>

        <!-- reCAPTCHA Live Test -->
        <div class="ltp-card">
            <h2 class="ltp-card-title"><?php esc_html_e( 'reCAPTCHA API Connectivity Test', 'lead-tracking-pro' ); ?></h2>
            <?php if ( $recap_live_result !== null ) : ?>
                <div class="ltp-diag-result"><?php echo esc_html( $recap_live_result ); ?></div>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field( 'ltp_diag_action', 'ltp_diag_nonce' ); ?>
                <input type="submit" name="ltp_test_recaptcha" class="button button-secondary"
                       value="<?php esc_attr_e( 'Test reCAPTCHA API', 'lead-tracking-pro' ); ?>">
            </form>
        </div>

        <!-- Pixel Status -->
        <div class="ltp-card">
            <h2 class="ltp-card-title"><?php esc_html_e( 'Meta Pixel Status', 'lead-tracking-pro' ); ?></h2>
            <?php if ( $pixel_ok ) : ?>
                <p class="ltp-ok"><?php printf( esc_html__( 'Pixel ID %s is configured. The Lead event will fire on: %s', 'lead-tracking-pro' ), '<strong>' . esc_html( $opts['pixel_id'] ) . '</strong>', '<a href="' . esc_url( $opts['thank_you_url'] ) . '" target="_blank">' . esc_html( $opts['thank_you_url'] ) . '</a>' ); ?></p>
                <p class="description"><?php esc_html_e( 'sessionStorage key "meta_lead_fired" prevents duplicate events within the same browser session.', 'lead-tracking-pro' ); ?></p>
            <?php else : ?>
                <p class="ltp-warn"><?php esc_html_e( 'Meta Pixel ID not configured. Go to Settings to add it.', 'lead-tracking-pro' ); ?></p>
            <?php endif; ?>
        </div>

        <!-- Last Submission Log -->
        <div class="ltp-card">
            <h2 class="ltp-card-title"><?php esc_html_e( 'Last Form Submission Log', 'lead-tracking-pro' ); ?></h2>
            <?php if ( ! empty( $log ) ) : ?>
                <table class="widefat striped ltp-log-table">
                    <tbody>
                        <tr><th><?php esc_html_e( 'Time', 'lead-tracking-pro' ); ?></th>
                            <td><?php echo esc_html( $log['time'] ?? '—' ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Honeypot Passed', 'lead-tracking-pro' ); ?></th>
                            <td><?php echo ltp_bool_badge( $log['honeypot_pass'] ?? null ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'reCAPTCHA Passed', 'lead-tracking-pro' ); ?></th>
                            <td><?php echo ltp_bool_badge( $log['recaptcha_pass'] ?? null ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'reCAPTCHA Score', 'lead-tracking-pro' ); ?></th>
                            <td><?php echo isset( $log['recaptcha_score'] ) ? esc_html( number_format( (float) $log['recaptcha_score'], 4 ) ) : '—'; ?></td></tr>
                        <tr><th><?php esc_html_e( 'Redirected to Thank You', 'lead-tracking-pro' ); ?></th>
                            <td><?php echo ltp_bool_badge( $log['redirected'] ?? null ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Error / Note', 'lead-tracking-pro' ); ?></th>
                            <td><?php echo esc_html( $log['error'] ?: '—' ); ?></td></tr>
                    </tbody>
                </table>
                <br>
                <form method="post">
                    <?php wp_nonce_field( 'ltp_diag_action', 'ltp_diag_nonce' ); ?>
                    <input type="submit" name="ltp_clear_log" class="button button-secondary"
                           value="<?php esc_attr_e( 'Clear Log', 'lead-tracking-pro' ); ?>">
                </form>
            <?php else : ?>
                <p class="description"><?php esc_html_e( 'No form submission recorded yet. Submit an Elementor form to see data here.', 'lead-tracking-pro' ); ?></p>
            <?php endif; ?>
        </div>

        <!-- GTM Info -->
        <div class="ltp-card">
            <h2 class="ltp-card-title"><?php esc_html_e( 'GTM dataLayer Setup Guide', 'lead-tracking-pro' ); ?></h2>
            <p><?php esc_html_e( 'On the Thank You page, the following dataLayer event is pushed:', 'lead-tracking-pro' ); ?></p>
            <pre class="ltp-code">dataLayer.push({
    event: 'meta_lead_conversion',
    pixel_id: '<?php echo esc_html( $opts['pixel_id'] ?: 'YOUR_PIXEL_ID' ); ?>'
});</pre>
            <p><?php esc_html_e( 'In Google Tag Manager, create a Custom Event trigger with Event Name: meta_lead_conversion, then attach your Meta Pixel tag to it.', 'lead-tracking-pro' ); ?></p>
        </div>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Diagnostics page helpers
// ---------------------------------------------------------------------------
function ltp_status_row( string $label, bool $ok, string $detail ): void {
    $badge = $ok
        ? '<span class="ltp-badge ltp-badge-ok">&#10003; OK</span>'
        : '<span class="ltp-badge ltp-badge-warn">&#9888; Warning</span>';
    printf(
        '<div class="ltp-status-row">%s <strong>%s</strong><span class="ltp-status-detail">%s</span></div>',
        $badge,
        esc_html( $label ),
        esc_html( $detail )
    );
}

function ltp_bool_badge( $value ): string {
    if ( null === $value ) {
        return '<span class="ltp-badge ltp-badge-neutral">—</span>';
    }
    return $value
        ? '<span class="ltp-badge ltp-badge-ok">&#10003; Yes</span>'
        : '<span class="ltp-badge ltp-badge-fail">&#10007; No</span>';
}

// ---------------------------------------------------------------------------
// Settings link on Plugins page
// ---------------------------------------------------------------------------
add_filter( 'plugin_action_links_' . plugin_basename( LTP_PLUGIN_FILE ), 'ltp_plugin_action_links' );
function ltp_plugin_action_links( array $links ): array {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=lead-tracking-pro' ) ) . '">'
        . esc_html__( 'Settings', 'lead-tracking-pro' ) . '</a>';
    $diag_link = '<a href="' . esc_url( admin_url( 'tools.php?page=ltp-diagnostics' ) ) . '">'
        . esc_html__( 'Diagnostics', 'lead-tracking-pro' ) . '</a>';
    array_unshift( $links, $settings_link, $diag_link );
    return $links;
}

// ---------------------------------------------------------------------------
// Load text domain
// ---------------------------------------------------------------------------
add_action( 'plugins_loaded', 'ltp_load_textdomain' );
function ltp_load_textdomain(): void {
    load_plugin_textdomain( 'lead-tracking-pro', false, dirname( plugin_basename( LTP_PLUGIN_FILE ) ) . '/languages' );
}
