/**
 * Flexible Remote Services - About Page JavaScript
 */
(function () {
    'use strict';

    /* ========== Scroll Progress Bar ========== */
    var progressBar = document.getElementById('scrollProgress');

    function updateScrollProgress() {
        if (!progressBar) return;
        var scrollTop = window.scrollY;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        progressBar.style.width = progress + '%';
    }

    window.addEventListener('scroll', updateScrollProgress);

    /* ========== Init on DOMContentLoaded ========== */
    document.addEventListener('DOMContentLoaded', function () {
        updateScrollProgress();
    });
})();
