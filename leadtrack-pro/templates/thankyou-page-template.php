<?php
/**
 * Thank You Page Template
 *
 * Loaded by the [leadtrack_thankyou] shortcode via LeadTrack_Thankyou_Page::render_shortcode().
 * Variables available:
 *   $leadtrack_atts (array) — shortcode attributes: 'title', 'message'.
 *
 * You can override this template by copying it to:
 *   your-theme/leadtrack-pro/thankyou-page-template.php
 *
 * @package LeadTrack_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Resolve display strings (shortcode attr → option → default).
$options = get_option( LEADTRACK_PRO_OPTION_KEY, array() );

$title = ! empty( $leadtrack_atts['title'] )
	? sanitize_text_field( $leadtrack_atts['title'] )
	: esc_html__( 'Thank You!', 'leadtrack-pro' );

$message = ! empty( $leadtrack_atts['message'] )
	? wp_kses_post( $leadtrack_atts['message'] )
	: esc_html__( "We've received your message and will be in touch shortly.", 'leadtrack-pro' );

// Home URL for the "Back to Home" link.
$home_url  = esc_url( home_url( '/' ) );
$home_text = esc_html__( 'Back to Home', 'leadtrack-pro' );
?>
<div class="lt-thankyou" role="main" aria-labelledby="lt-thankyou-title">

	<div class="lt-thankyou__card">

		<!-- Animated success checkmark -->
		<div class="lt-thankyou__icon" aria-hidden="true">
			<div class="lt-checkmark">
				<div class="lt-checkmark__circle"></div>
				<div class="lt-checkmark__stem"></div>
				<div class="lt-checkmark__kick"></div>
			</div>
		</div><!-- /.lt-thankyou__icon -->

		<h1 id="lt-thankyou-title" class="lt-thankyou__title">
			<?php echo esc_html( $title ); ?>
		</h1>

		<p class="lt-thankyou__message">
			<?php echo wp_kses_post( $message ); ?>
		</p>

		<div class="lt-thankyou__actions">
			<a href="<?php echo $home_url; ?>" class="lt-thankyou__cta">
				<?php echo $home_text; ?>
			</a>
		</div><!-- /.lt-thankyou__actions -->

	</div><!-- /.lt-thankyou__card -->

</div><!-- /.lt-thankyou -->
