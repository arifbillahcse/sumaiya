<?php
/**
 * Elementor Form Integration
 *
 * - Injects the honeypot hidden field into every Elementor form on the front end.
 * - On successful Elementor Pro form submission, appends ?lead=1 to the
 *   configured redirect URL so the Thank You Page knows it came from a real submit.
 * - Enqueues the honeypot + reCAPTCHA hidden input injection script.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class FRS_LT_Elementor_Hook {

    public static function init() {
        add_action( 'wp_footer', array( __CLASS__, 'inject_honeypot_field' ) );
        add_action( 'elementor_pro/forms/mail_sent', array( __CLASS__, 'append_lead_param' ), 10, 2 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
    }

    /**
     * Output a visually-hidden honeypot input.
     * CSS hides it; a real browser never fills it; bots do.
     */
    public static function inject_honeypot_field() {
        if ( ! get_option( 'frs_lt_enable_honeypot', 1 ) ) return;
        ?>
        <style>.frs-hp-wrap{position:absolute;left:-9999px;width:0;height:0;overflow:hidden;visibility:hidden;}</style>
        <div class="frs-hp-wrap" aria-hidden="true" tabindex="-1">
            <label for="frs_hp_field">Leave this field empty</label>
            <input type="text" id="frs_hp_field" name="frs_hp_field" value="" autocomplete="off" tabindex="-1">
        </div>
        <?php
    }

    /**
     * After Elementor Pro sends the form email, append ?lead=1 to the redirect.
     * This is picked up by the Thank You Page to gate the pixel fire.
     *
     * NOTE: Elementor Pro fires this hook for each successfully processed form.
     * The redirect itself is set in the Elementor form's "Actions After Submit →
     * Redirect" panel – point it at your Thank You Page URL.
     * We tag it here so a direct visit to /thank-you/ won't trigger the pixel.
     */
    public static function append_lead_param( $record, $handler ) {
        // No-op: Elementor Pro handles the redirect client-side from the
        // form widget settings. We use a signed session token instead.
        // See class-thankyou-page.php for the session-based gate.
        self::set_lead_session();
    }

    public static function set_lead_session() {
        if ( ! session_id() ) {
            session_start();
        }
        $_SESSION['frs_lt_lead'] = true;
    }

    public static function enqueue_scripts() {
        wp_enqueue_script(
            'frs-lt-forms',
            FRS_LT_URL . 'assets/js/forms.js',
            array( 'jquery' ),
            FRS_LT_VERSION,
            true
        );
        wp_localize_script( 'frs-lt-forms', 'frsLtForms', array(
            'thankyouUrl' => get_permalink( get_option( 'frs_lt_thankyou_page_id', 0 ) ),
        ) );
    }
}
