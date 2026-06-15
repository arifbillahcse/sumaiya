<?php
/**
 * Flexible Remote Services Theme Functions
 *
 * @package Flexible_Remote_Services
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FRS_VERSION', '1.0' );

// ─── Site owner config ────────────────────────────────────────────────────────
// Replace the placeholder values below with your real IDs.
define( 'FRS_GTM_ID',             'GTM-XXXXXXX' );   // e.g. GTM-AB12CD3
define( 'FRS_META_PIXEL_ID',      'XXXXXXXXXXXXXXXXXX' ); // e.g. 1234567890123456
define( 'FRS_RECAPTCHA_SITE_KEY', '' );  // reCAPTCHA v3 site key (leave blank to skip)
define( 'FRS_RECAPTCHA_SECRET',   '' );  // reCAPTCHA v3 secret key
define( 'FRS_RECAPTCHA_SCORE',    0.5 ); // minimum score threshold (0.0–1.0)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Theme Setup
 */
function frs_theme_setup() {
    // Translation support
    load_theme_textdomain( 'flexible-remote-services', get_template_directory() . '/languages' );

    // Theme support
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ) );

    // Register navigation menus
    register_nav_menus( array(
        'primary-menu'      => esc_html__( 'Primary Menu', 'flexible-remote-services' ),
        'footer-quick-links' => esc_html__( 'Footer Quick Links', 'flexible-remote-services' ),
        'footer-services'   => esc_html__( 'Footer Services', 'flexible-remote-services' ),
        'footer-legal'      => esc_html__( 'Footer Legal', 'flexible-remote-services' ),
    ) );
}
add_action( 'after_setup_theme', 'frs_theme_setup' );

/**
 * Enqueue Styles and Scripts
 */
function frs_enqueue_assets() {
    // Styles
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );

    wp_enqueue_style(
        'google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'frs-style',
        get_stylesheet_uri(),
        array( 'font-awesome', 'google-fonts' ),
        FRS_VERSION
    );

    // Scripts
    wp_enqueue_script(
        'frs-main',
        get_template_directory_uri() . '/js/main.js',
        array(),
        FRS_VERSION,
        true
    );

    // Conditionally load about.js on the about page
    if ( is_page( 'about' ) || is_page( 'about-us' ) ) {
        wp_enqueue_script(
            'frs-about',
            get_template_directory_uri() . '/js/about.js',
            array( 'frs-main' ),
            FRS_VERSION,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'frs_enqueue_assets' );

// ─── Google Tag Manager ───────────────────────────────────────────────────────

function frs_gtm_head() {
    if ( ! FRS_GTM_ID ) return;
    $id = esc_js( FRS_GTM_ID );
    echo "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$id}');</script>
<!-- End Google Tag Manager -->\n";
}
add_action( 'wp_head', 'frs_gtm_head', 1 );

function frs_gtm_body() {
    if ( ! FRS_GTM_ID ) return;
    $id = esc_attr( FRS_GTM_ID );
    echo "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$id}\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->\n";
}
add_action( 'wp_body_open', 'frs_gtm_body', 1 );

// ─── Meta Pixel (base code only — Lead event fires on Thank You Page) ─────────

function frs_meta_pixel_head() {
    if ( ! FRS_META_PIXEL_ID ) return;
    $id = esc_js( FRS_META_PIXEL_ID );
    echo "<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{$id}');
fbq('track', 'PageView');
</script>
<noscript><img height=\"1\" width=\"1\" style=\"display:none\"
src=\"https://www.facebook.com/tr?id={$id}&ev=PageView&noscript=1\"/></noscript>
<!-- End Meta Pixel Code -->\n";
}
add_action( 'wp_head', 'frs_meta_pixel_head', 2 );

// ─── Thank You Page: token guard + Lead event script ──────────────────────────

/**
 * Generate a one-time redirect token and store it in a transient.
 * Called by the Elementor form action hook right before redirect.
 */
function frs_generate_redirect_token() {
    $token = wp_generate_password( 32, false );
    set_transient( 'frs_redirect_' . $token, 1, 300 ); // valid for 5 minutes
    return $token;
}

/**
 * Validate the token present in the current request.
 * Consumes the transient so the token can only be used once.
 */
function frs_is_valid_form_redirect() {
    $token = isset( $_GET['ref'] ) ? sanitize_text_field( wp_unslash( $_GET['ref'] ) ) : '';
    if ( ! $token ) {
        return false;
    }
    $key = 'frs_redirect_' . $token;
    if ( get_transient( $key ) ) {
        delete_transient( $key ); // single-use
        return true;
    }
    return false;
}

/**
 * Elementor Pro form action: generate token, then let Elementor redirect.
 * Hook into elementor_pro/forms/new_record after successful validation.
 */
function frs_elementor_form_success( $record, $ajax_handler ) {
    $settings = $record->get( 'form_settings' );

    // Only act on forms that have our redirect action enabled.
    if ( empty( $settings['frs_thank_you_redirect'] ) || 'yes' !== $settings['frs_thank_you_redirect'] ) {
        return;
    }

    $token         = frs_generate_redirect_token();
    $thank_you_url = add_query_arg( 'ref', $token, home_url( '/thank-you/' ) );

    $ajax_handler->add_response_data( 'redirect_url', $thank_you_url );
}
add_action( 'elementor_pro/forms/new_record', 'frs_elementor_form_success', 10, 2 );

/**
 * Enqueue the Lead event script only on the Thank You Page.
 */
function frs_enqueue_thank_you_script() {
    if ( ! is_page( array( 'thank-you', 'thank-you-page' ) ) ) {
        return;
    }
    wp_enqueue_script(
        'frs-thank-you',
        get_template_directory_uri() . '/js/thank-you.js',
        array(),
        FRS_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'frs_enqueue_thank_you_script' );

// ─── Spam Protection: Honeypot + reCAPTCHA v3 ────────────────────────────────

/**
 * Inject honeypot field + reCAPTCHA v3 token into every Elementor form.
 * The honeypot is a visually hidden input; bots fill it, humans don't.
 */
function frs_inject_spam_fields() {
    // Honeypot (CSS hides it — see style.css)
    echo '<input type="text" name="frs_hp" class="frs-hp-field" autocomplete="off" tabindex="-1" aria-hidden="true">';

    // reCAPTCHA v3 hidden token field
    if ( FRS_RECAPTCHA_SITE_KEY ) {
        echo '<input type="hidden" name="frs_recaptcha_token" id="frs_recaptcha_token">';
    }
}
add_action( 'elementor_pro/forms/render_field_content', 'frs_inject_spam_fields' );

/**
 * Load reCAPTCHA v3 JS and auto-attach token generation to form submits.
 */
function frs_enqueue_recaptcha() {
    if ( ! FRS_RECAPTCHA_SITE_KEY ) return;
    $site_key = esc_attr( FRS_RECAPTCHA_SITE_KEY );
    wp_enqueue_script(
        'google-recaptcha',
        "https://www.google.com/recaptcha/api.js?render={$site_key}",
        array(),
        null,
        true
    );
    wp_add_inline_script( 'google-recaptcha', "
grecaptcha.ready(function(){
    document.querySelectorAll('.elementor-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            var tokenField = form.querySelector('#frs_recaptcha_token');
            if(!tokenField) return;
            e.preventDefault();
            grecaptcha.execute('" . esc_js( FRS_RECAPTCHA_SITE_KEY ) . "',{action:'lead_form'}).then(function(token){
                tokenField.value = token;
                form.submit();
            });
        }, true);
    });
});
" );
}
add_action( 'wp_enqueue_scripts', 'frs_enqueue_recaptcha' );

/**
 * Server-side spam validation before Elementor processes the form.
 */
function frs_validate_spam( $record, $ajax_handler ) {
    $fields = $record->get( 'fields' );

    // 1. Honeypot check
    if ( ! empty( $_POST['frs_hp'] ) ) {
        $ajax_handler->add_error_message( esc_html__( 'Spam detected.', 'flexible-remote-services' ) );
        $ajax_handler->set_success( false );
        return;
    }

    // 2. reCAPTCHA v3 check
    if ( FRS_RECAPTCHA_SECRET ) {
        $token = isset( $_POST['frs_recaptcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['frs_recaptcha_token'] ) ) : '';
        if ( $token ) {
            $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
                'body' => array(
                    'secret'   => FRS_RECAPTCHA_SECRET,
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ),
                'timeout' => 10,
            ) );
            if ( ! is_wp_error( $response ) ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( empty( $data['success'] ) || ( $data['score'] ?? 0 ) < FRS_RECAPTCHA_SCORE ) {
                    $ajax_handler->add_error_message( esc_html__( 'Could not verify you are human. Please try again.', 'flexible-remote-services' ) );
                    $ajax_handler->set_success( false );
                    return;
                }
            }
        }
    }
}
add_action( 'elementor_pro/forms/validation', 'frs_validate_spam', 10, 2 );

/**
 * Register Widget Areas
 */
function frs_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Footer Column 1', 'flexible-remote-services' ),
        'id'            => 'footer-1',
        'description'   => esc_html__( 'Footer widget area column 1.', 'flexible-remote-services' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Column 2', 'flexible-remote-services' ),
        'id'            => 'footer-2',
        'description'   => esc_html__( 'Footer widget area column 2.', 'flexible-remote-services' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Column 3', 'flexible-remote-services' ),
        'id'            => 'footer-3',
        'description'   => esc_html__( 'Footer widget area column 3.', 'flexible-remote-services' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer Column 4', 'flexible-remote-services' ),
        'id'            => 'footer-4',
        'description'   => esc_html__( 'Footer widget area column 4.', 'flexible-remote-services' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'frs_widgets_init' );

/**
 * Fallback menus
 */
function frs_primary_menu_fallback() {
    echo '<ul class="menu">';
    echo '<li class="' . ( is_front_page() ? 'current-menu-item' : '' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/services/' ) ) . '">' . esc_html__( 'Services', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/industries/' ) ) . '">' . esc_html__( 'Industries', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/portfolio/' ) ) . '">' . esc_html__( 'Portfolio', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'About', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/blog/' ) ) . '">' . esc_html__( 'Blog', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact', 'flexible-remote-services' ) . '</a></li>';
    echo '</ul>';
}

function frs_footer_quick_links_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/services/' ) ) . '">' . esc_html__( 'Services', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/industries/' ) ) . '">' . esc_html__( 'Industries', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/portfolio/' ) ) . '">' . esc_html__( 'Portfolio', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'About Us', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact', 'flexible-remote-services' ) . '</a></li>';
    echo '</ul>';
}

function frs_footer_services_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/services/wordpress-development/' ) ) . '">' . esc_html__( 'WordPress Development', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/services/seo-optimization/' ) ) . '">' . esc_html__( 'SEO Optimization', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/services/lead-generation/' ) ) . '">' . esc_html__( 'Lead Generation', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/services/web-design/' ) ) . '">' . esc_html__( 'Web Design', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/services/maintenance/' ) ) . '">' . esc_html__( 'Maintenance', 'flexible-remote-services' ) . '</a></li>';
    echo '</ul>';
}

function frs_footer_legal_fallback() {
    echo '<ul class="menu">';
    echo '<li><a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">' . esc_html__( 'Privacy Policy', 'flexible-remote-services' ) . '</a></li>';
    echo '<li><a href="' . esc_url( home_url( '/terms-and-conditions/' ) ) . '">' . esc_html__( 'Terms & Conditions', 'flexible-remote-services' ) . '</a></li>';
    echo '</ul>';
}
