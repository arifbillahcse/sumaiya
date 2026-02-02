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
