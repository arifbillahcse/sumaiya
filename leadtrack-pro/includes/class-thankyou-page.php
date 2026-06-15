<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LeadTrack_Thankyou_Page {

	public static function init() {
		$instance = new self();
		add_shortcode( 'leadtrack_thankyou', array( $instance, 'render_shortcode' ) );
	}

	public function render_shortcode( $atts ) {
		$options  = get_option( LEADTRACK_PRO_OPTION_KEY, array() );
		$headline = ! empty( $options['thankyou_headline'] ) ? $options['thankyou_headline'] : 'Thank You!';
		$message  = ! empty( $options['thankyou_message'] ) ? $options['thankyou_message'] : 'Your submission has been received. We will be in touch shortly.';
		$cta_text = ! empty( $options['thankyou_cta_text'] ) ? $options['thankyou_cta_text'] : 'Go Back Home';
		$cta_url  = ! empty( $options['thankyou_cta_url'] ) ? $options['thankyou_cta_url'] : home_url( '/' );

		ob_start();
		?>
		<div class="ltp-thankyou" id="ltp-thankyou" role="main">
			<div class="ltp-thankyou__inner">
				<div class="ltp-thankyou__icon" aria-hidden="true">
					<svg class="ltp-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
						<circle class="ltp-checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
						<path class="ltp-checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
					</svg>
				</div>

				<h1 class="ltp-thankyou__headline"><?php echo esc_html( $headline ); ?></h1>

				<p class="ltp-thankyou__message"><?php echo esc_html( $message ); ?></p>

				<div class="ltp-thankyou__steps">
					<div class="ltp-step">
						<span class="ltp-step__num">1</span>
						<span class="ltp-step__text"><?php esc_html_e( 'Form submitted successfully', 'leadtrack-pro' ); ?></span>
					</div>
					<div class="ltp-step ltp-step--active">
						<span class="ltp-step__num">2</span>
						<span class="ltp-step__text"><?php esc_html_e( 'We\'ve received your information', 'leadtrack-pro' ); ?></span>
					</div>
					<div class="ltp-step">
						<span class="ltp-step__num">3</span>
						<span class="ltp-step__text"><?php esc_html_e( 'Our team will contact you shortly', 'leadtrack-pro' ); ?></span>
					</div>
				</div>

				<a href="<?php echo esc_url( $cta_url ); ?>" class="ltp-thankyou__cta">
					<?php echo esc_html( $cta_text ); ?>
				</a>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
