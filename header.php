<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="scroll-progress" id="scrollProgress"></div>

<header class="site-header" id="siteHeader" role="banner">
    <div class="header-inner">
        <!-- Logo -->
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" aria-label="<?php esc_attr_e( 'Home', 'flexible-remote-services' ); ?>">
            Media<span class="accent">Forge</span> Summit
        </a>

        <!-- Desktop Navigation -->
        <nav class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'flexible-remote-services' ); ?>">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary-menu',
                'container'      => false,
                'menu_class'     => 'menu',
                'fallback_cb'    => 'frs_primary_menu_fallback',
                'depth'          => 2,
            ) );
            ?>
        </nav>

        <!-- Header CTA Buttons -->
        <div class="header-cta">
            <a href="<?php echo esc_url( home_url( '/schedule/' ) ); ?>" class="btn btn-outline">
                <?php esc_html_e( 'View Schedule', 'flexible-remote-services' ); ?>
            </a>
            <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>" class="btn btn-primary">
                <?php esc_html_e( 'Register Now', 'flexible-remote-services' ); ?>
            </a>
        </div>

        <!-- Hamburger Menu -->
        <button class="hamburger" id="hamburger" aria-label="<?php esc_attr_e( 'Toggle Navigation Menu', 'flexible-remote-services' ); ?>" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<!-- Mobile Navigation -->
<nav class="mobile-nav" id="mobileNav" role="navigation" aria-label="<?php esc_attr_e( 'Mobile Navigation', 'flexible-remote-services' ); ?>">
    <?php
    wp_nav_menu( array(
        'theme_location' => 'primary-menu',
        'container'      => false,
        'menu_class'     => 'menu',
        'fallback_cb'    => 'frs_primary_menu_fallback',
        'depth'          => 1,
    ) );
    ?>
    <div class="mobile-cta">
        <a href="<?php echo esc_url( home_url( '/schedule/' ) ); ?>" class="btn btn-outline">
            <?php esc_html_e( 'View Schedule', 'flexible-remote-services' ); ?>
        </a>
        <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>" class="btn btn-primary">
            <?php esc_html_e( 'Register Now', 'flexible-remote-services' ); ?>
        </a>
    </div>
</nav>

<main id="main-content" role="main">
