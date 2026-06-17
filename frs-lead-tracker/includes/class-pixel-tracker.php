<?php
/**
 * Meta Pixel base loader.
 * Outputs the standard Meta Pixel base code (PageView only) in <head> on all pages.
 * The Lead event is fired separately by assets/js/pixel.js on the Thank You Page only.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class FRS_LT_Pixel_Tracker {

    public static function init() {
        add_action( 'wp_head', array( __CLASS__, 'output_base_pixel' ), 1 );
    }

    public static function output_base_pixel() {
        $pixel_id = get_option( 'frs_lt_meta_pixel_id', '' );
        if ( ! $pixel_id ) return;
        $pid = esc_js( $pixel_id );
        ?>
<!-- FRS Lead Tracker – Meta Pixel Base Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo $pid; ?>');
fbq('track', 'PageView');
</script>
<noscript>
<img height="1" width="1" style="display:none"
     src="https://www.facebook.com/tr?id=<?php echo esc_attr( $pixel_id ); ?>&ev=PageView&noscript=1" alt="">
</noscript>
<!-- End Meta Pixel Base Code -->
        <?php
    }
}
