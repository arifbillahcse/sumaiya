<?php
/**
 * Template Name: Thank You Page
 *
 * Dedicated landing page after a successful Elementor form submission.
 * The Meta Pixel Lead event fires here — not on the form page.
 *
 * @package Flexible_Remote_Services
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Block direct navigation: only allow arrival via a validated form token.
// Elementor sets a query-string token we verify server-side; see functions.php.
if ( ! frs_is_valid_form_redirect() ) {
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

get_header();
?>

<section class="thankyou-section">
    <div class="thankyou-container">
        <div class="thankyou-icon" aria-hidden="true">
            <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
                <circle cx="40" cy="40" r="40" fill="#00c8c8" fill-opacity="0.12"/>
                <circle cx="40" cy="40" r="30" fill="#00c8c8" fill-opacity="0.2"/>
                <path d="M26 40.5l9.5 9.5 18.5-19" stroke="#00c8c8" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1 class="thankyou-heading">
            <?php esc_html_e( "You're all set!", 'flexible-remote-services' ); ?>
        </h1>

        <p class="thankyou-subheading">
            <?php esc_html_e( 'Thank you for reaching out. We'll review your request and get back to you within 1 business day.', 'flexible-remote-services' ); ?>
        </p>

        <div class="thankyou-actions">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">
                <?php esc_html_e( 'Back to Home', 'flexible-remote-services' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="btn btn-outline">
                <?php esc_html_e( 'Explore Services', 'flexible-remote-services' ); ?>
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
