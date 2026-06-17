<?php
/**
 * Plugin Name: FRS Lead Tracker
 * Plugin URI:  https://flexibleremoteservices.com
 * Description: Reliable Meta Pixel Lead event tracking via Thank You Page redirect. Includes honeypot + reCAPTCHA v3 spam protection, GTM data layer push, and duplicate-event prevention.
 * Version:     1.0.0
 * Author:      Flexible Remote Services
 * License:     GPL-2.0+
 * Text Domain: frs-lead-tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'FRS_LT_VERSION', '1.0.0' );
define( 'FRS_LT_DIR',     plugin_dir_path( __FILE__ ) );
define( 'FRS_LT_URL',     plugin_dir_url( __FILE__ ) );

require_once FRS_LT_DIR . 'includes/class-settings.php';
require_once FRS_LT_DIR . 'includes/class-spam-guard.php';
require_once FRS_LT_DIR . 'includes/class-thankyou-page.php';
require_once FRS_LT_DIR . 'includes/class-pixel-tracker.php';
require_once FRS_LT_DIR . 'includes/class-elementor-hook.php';

FRS_LT_Settings::init();
FRS_LT_Spam_Guard::init();
FRS_LT_Thankyou_Page::init();
FRS_LT_Pixel_Tracker::init();
FRS_LT_Elementor_Hook::init();

register_activation_hook( __FILE__,   'frs_lt_activate' );
register_deactivation_hook( __FILE__, 'frs_lt_deactivate' );

function frs_lt_activate() {
    // Create the Thank You page if it does not exist yet
    $existing = get_page_by_path( 'thank-you' );
    if ( ! $existing ) {
        $page_id = wp_insert_post( array(
            'post_title'   => 'Thank You',
            'post_name'    => 'thank-you',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
            'page_template'=> 'frs-thankyou.php',
        ) );
        update_option( 'frs_lt_thankyou_page_id', $page_id );
    } else {
        update_option( 'frs_lt_thankyou_page_id', $existing->ID );
    }
    flush_rewrite_rules();
}

function frs_lt_deactivate() {
    flush_rewrite_rules();
}
