/**
 * Flexible Remote Services - Main JavaScript
 */
(function () {
    'use strict';

    /* ========== Sticky Header ========== */
    const siteHeader = document.getElementById('siteHeader');

    function handleStickyHeader() {
        if (window.scrollY > 50) {
            siteHeader.classList.add('scrolled');
        } else {
            siteHeader.classList.remove('scrolled');
        }
    }

    /* ========== Mobile Menu Toggle ========== */
    const hamburger = document.getElementById('hamburger');
    const mobileNav = document.getElementById('mobileNav');

    function toggleMobileMenu() {
        const isActive = hamburger.classList.toggle('active');
        mobileNav.classList.toggle('active');
        hamburger.setAttribute('aria-expanded', isActive);
        document.body.style.overflow = isActive ? 'hidden' : '';
    }

    if (hamburger) {
        hamburger.addEventListener('click', toggleMobileMenu);
    }

    // Close mobile menu on link click
    if (mobileNav) {
        mobileNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileNav.classList.contains('active')) {
                    toggleMobileMenu();
                }
            });
        });
    }

    /* ========== Scroll To Top ========== */
    const scrollToTopBtn = document.getElementById('scrollToTop');

    function handleScrollToTop() {
        if (window.scrollY > 500) {
            scrollToTopBtn.classList.add('visible');
        } else {
            scrollToTopBtn.classList.remove('visible');
        }
    }

    if (scrollToTopBtn) {
        scrollToTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* ========== Fade-In Animations (Intersection Observer) ========== */
    function initFadeInAnimations() {
        var elements = document.querySelectorAll('.fade-in');
        if (!elements.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        elements.forEach(function (el) {
            observer.observe(el);
        });
    }

    /* ========== Counter Animation ========== */
    function animateCounters() {
        var counters = document.querySelectorAll('[data-count]');
        if (!counters.length) return;

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var target = parseInt(entry.target.getAttribute('data-count'), 10);
                    var duration = 2000;
                    var start = 0;
                    var startTime = null;

                    function step(timestamp) {
                        if (!startTime) startTime = timestamp;
                        var progress = Math.min((timestamp - startTime) / duration, 1);
                        entry.target.textContent = Math.floor(progress * target);
                        if (progress < 1) {
                            requestAnimationFrame(step);
                        } else {
                            entry.target.textContent = target;
                        }
                    }

                    requestAnimationFrame(step);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(function (c) {
            observer.observe(c);
        });
    }

    /* ========== Smooth Anchor Scrolling ========== */
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var targetId = this.getAttribute('href');
            if (targetId === '#') return;
            var targetEl = document.querySelector(targetId);
            if (targetEl) {
                e.preventDefault();
                var headerHeight = siteHeader ? siteHeader.offsetHeight : 0;
                var targetPos = targetEl.getBoundingClientRect().top + window.scrollY - headerHeight;
                window.scrollTo({ top: targetPos, behavior: 'smooth' });
            }
        });
    });

    /* ========== Form Validation Helper ========== */
    window.handleNewsletter = function (event) {
        event.preventDefault();
        var form = event.target;
        var emailInput = form.querySelector('input[type="email"]');
        if (emailInput && emailInput.value) {
            alert('Thank you for subscribing!');
            form.reset();
        }
    };

    /* ========== Scroll Event Listener ========== */
    window.addEventListener('scroll', function () {
        handleStickyHeader();
        handleScrollToTop();
    });

    /* ========== Init ========== */
    document.addEventListener('DOMContentLoaded', function () {
        initFadeInAnimations();
        animateCounters();
    });
})();
