</main><!-- #main-content -->

<footer class="site-footer" role="contentinfo">
    <div class="footer-grid">

        <!-- Column 1: Brand & Social -->
        <div class="footer-brand">
            <div class="footer-logo">
                flexible<span class="accent">remote</span>services
            </div>
            <p class="footer-description">
                <?php esc_html_e( 'We build stunning, SEO-optimized websites that help businesses grow. Specialized in serving event planners, medical professionals, electricians, plumbers, and jewelry businesses.', 'flexible-remote-services' ); ?>
            </p>
            <div class="footer-social">
                <a href="#" aria-label="<?php esc_attr_e( 'LinkedIn', 'flexible-remote-services' ); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="#" aria-label="<?php esc_attr_e( 'Facebook', 'flexible-remote-services' ); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" aria-label="<?php esc_attr_e( 'Twitter', 'flexible-remote-services' ); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" aria-label="<?php esc_attr_e( 'Instagram', 'flexible-remote-services' ); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-instagram"></i>
                </a>
            </div>
        </div>

        <!-- Column 2: Quick Links -->
        <div class="footer-column">
            <h4><?php esc_html_e( 'Quick Links', 'flexible-remote-services' ); ?></h4>
            <?php
            wp_nav_menu( array(
                'theme_location' => 'footer-quick-links',
                'container'      => false,
                'menu_class'     => 'menu',
                'fallback_cb'    => 'frs_footer_quick_links_fallback',
                'depth'          => 1,
            ) );
            ?>
        </div>

        <!-- Column 3: Services -->
        <div class="footer-column">
            <h4><?php esc_html_e( 'Services', 'flexible-remote-services' ); ?></h4>
            <?php
            wp_nav_menu( array(
                'theme_location' => 'footer-services',
                'container'      => false,
                'menu_class'     => 'menu',
                'fallback_cb'    => 'frs_footer_services_fallback',
                'depth'          => 1,
            ) );
            ?>
        </div>

        <!-- Column 4: Contact & Newsletter -->
        <div class="footer-column">
            <h4><?php esc_html_e( 'Get In Touch', 'flexible-remote-services' ); ?></h4>
            <div class="footer-contact-item">
                <i class="fas fa-envelope"></i>
                <a href="mailto:info@flexibleremoteservices.com">info@flexibleremoteservices.com</a>
            </div>
            <form class="newsletter-form" onsubmit="handleNewsletter(event)">
                <label for="newsletter-email"><?php esc_html_e( 'Subscribe to our newsletter', 'flexible-remote-services' ); ?></label>
                <div class="newsletter-input-group">
                    <input
                        type="email"
                        id="newsletter-email"
                        name="email"
                        placeholder="<?php esc_attr_e( 'Enter your email', 'flexible-remote-services' ); ?>"
                        required
                        aria-label="<?php esc_attr_e( 'Email address', 'flexible-remote-services' ); ?>"
                    >
                    <button type="submit"><?php esc_html_e( 'Subscribe', 'flexible-remote-services' ); ?></button>
                </div>
            </form>
        </div>

    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="footer-bottom-inner">
            <p>&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php esc_html_e( 'Flexible Remote Services. All rights reserved.', 'flexible-remote-services' ); ?></p>
            <nav class="footer-legal-nav" aria-label="<?php esc_attr_e( 'Legal Navigation', 'flexible-remote-services' ); ?>">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'footer-legal',
                    'container'      => false,
                    'menu_class'     => 'menu',
                    'fallback_cb'    => 'frs_footer_legal_fallback',
                    'depth'          => 1,
                ) );
                ?>
            </nav>
        </div>
    </div>
</footer>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" aria-label="<?php esc_attr_e( 'Scroll to top', 'flexible-remote-services' ); ?>">
    <i class="fas fa-arrow-up"></i>
</button>

<?php wp_footer(); ?>
</body>
</html>
