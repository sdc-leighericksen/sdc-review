(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var numberFormatter = new Intl.NumberFormat(window.document.documentElement.lang || undefined);

    function getSlidesPerView(carousel) {
        var desktop = parseInt(carousel.getAttribute('data-slides-desktop'), 10) || 1;
        var tablet = parseInt(carousel.getAttribute('data-slides-tablet'), 10) || 1;
        var mobile = parseInt(carousel.getAttribute('data-slides-mobile'), 10) || 1;
        var width = window.innerWidth || document.documentElement.clientWidth;

        if (width >= 960) {
            return desktop;
        }

        if (width >= 600) {
            return tablet;
        }

        return mobile;
    }

    function formatStatus(start, end, total) {
        var template = (window.sdcGmbCarouselL10n && window.sdcGmbCarouselL10n.status) || 'Showing reviews %1$s–%2$s of %3$s';

        return template
            .replace('%1$s', numberFormatter.format(start))
            .replace('%2$s', numberFormatter.format(end))
            .replace('%3$s', numberFormatter.format(total));
    }

    function updateNavState(list, prevButton, nextButton) {
        var maxScroll = list.scrollWidth - list.clientWidth;

        if (prevButton) {
            prevButton.disabled = list.scrollLeft <= 1;
        }

        if (nextButton) {
            nextButton.disabled = list.scrollLeft + 1 >= maxScroll;
        }
    }

    function updateStatus(carousel, list, items, liveRegion) {
        if (!liveRegion) {
            return;
        }

        var slides = getSlidesPerView(carousel);
        var total = items.length;
        var itemWidth = total ? list.scrollWidth / total : 0;
        var index = itemWidth ? Math.round(list.scrollLeft / itemWidth) : 0;
        var start = Math.min(index + 1, total);
        var end = Math.min(index + slides, total);

        if (start < 1) {
            start = 1;
        }

        liveRegion.textContent = formatStatus(start, end, total);
    }

    function scrollBySlides(list, carousel, direction) {
        var items = list.querySelectorAll('[data-carousel-item]');
        var total = items.length;

        if (!total) {
            return;
        }

        var slides = getSlidesPerView(carousel);
        var itemWidth = list.scrollWidth / total;
        var scrollAmount = itemWidth * slides * direction;
        var behavior = prefersReducedMotion.matches ? 'auto' : 'smooth';

        list.scrollBy({ left: scrollAmount, behavior: behavior });
    }

    function initCarousel(carousel) {
        var list = carousel.querySelector('[data-carousel-list]');
        var items = list ? list.querySelectorAll('[data-carousel-item]') : [];

        if (!list || !items.length) {
            return;
        }

        var prevButton = carousel.querySelector('[data-carousel-prev]');
        var nextButton = carousel.querySelector('[data-carousel-next]');
        var liveRegion = carousel.querySelector('[data-carousel-live]');
        var rafId;

        function handleScroll() {
            if (rafId) {
                window.cancelAnimationFrame(rafId);
            }

            rafId = window.requestAnimationFrame(function () {
                updateStatus(carousel, list, items, liveRegion);
                updateNavState(list, prevButton, nextButton);
            });
        }

        if (prevButton) {
            prevButton.addEventListener('click', function () {
                scrollBySlides(list, carousel, -1);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function () {
                scrollBySlides(list, carousel, 1);
            });
        }

        list.addEventListener('scroll', handleScroll, { passive: true });

        window.addEventListener('resize', handleScroll);

        if (prefersReducedMotion.addEventListener) {
            prefersReducedMotion.addEventListener('change', handleScroll);
        }

        handleScroll();
    }

    function init() {
        var carousels = document.querySelectorAll('.sdc-gmb-carousel[data-carousel-total]');

        carousels.forEach(function (carousel) {
            initCarousel(carousel);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
