<?php
/**
 * Template Name: FRS Thank You Page
 * Template Post Type: page
 *
 * Standalone Thank You Page template.
 * Meta Pixel Lead event is fired by assets/js/pixel.js (enqueued only on this page).
 * GTM dataLayer push is also handled there if enabled.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<!DOCTYPE html><!-- inner content only; get_header() already opens <html><head><body> -->
<main class="frs-ty-main" role="main">
    <div class="frs-ty-container">

        <!-- Success icon -->
        <div class="frs-ty-icon" aria-hidden="true">
            <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" width="80" height="80">
                <circle cx="40" cy="40" r="40" fill="#00c851" fill-opacity=".12"/>
                <circle cx="40" cy="40" r="30" fill="#00c851" fill-opacity=".2"/>
                <path d="M26 40l10 10 18-20" stroke="#00c851" stroke-width="4"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1 class="frs-ty-title">
            <?php echo esc_html( get_the_title() ?: __( 'Thank You!', 'frs-lead-tracker' ) ); ?>
        </h1>

        <p class="frs-ty-message">
            <?php
            $content = get_the_content();
            echo $content
                ? wp_kses_post( $content )
                : esc_html__( "We've received your message and will be in touch shortly.", 'frs-lead-tracker' );
            ?>
        </p>

        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="frs-ty-btn">
            <?php esc_html_e( '&larr; Back to Home', 'frs-lead-tracker' ); ?>
        </a>

    </div>
</main>

<style>
.frs-ty-main {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: #f8fafc;
}
.frs-ty-container {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,.08);
    padding: 60px 48px;
    max-width: 520px;
    width: 100%;
    text-align: center;
    animation: frs-ty-fade-in .5s ease both;
}
@keyframes frs-ty-fade-in {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.frs-ty-icon { margin-bottom: 24px; }
.frs-ty-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 16px;
    line-height: 1.2;
}
.frs-ty-message {
    font-size: 16px;
    color: #6b7280;
    line-height: 1.7;
    margin: 0 0 36px;
}
.frs-ty-btn {
    display: inline-block;
    background: #1877f2;
    color: #fff;
    padding: 14px 32px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s, transform .15s;
}
.frs-ty-btn:hover {
    background: #1260c4;
    transform: translateY(-1px);
    color: #fff;
    text-decoration: none;
}
@media (max-width: 540px) {
    .frs-ty-container { padding: 40px 24px; }
    .frs-ty-title { font-size: 24px; }
}
</style>

<?php get_footer(); ?>
