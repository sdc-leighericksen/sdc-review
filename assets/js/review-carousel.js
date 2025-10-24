(function () {
    'use strict';

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var numberFormatter = new Intl.NumberFormat(document.documentElement.lang || undefined);

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
        var template = (window.sdcReviewCarouselL10n && window.sdcReviewCarouselL10n.status) || 'Showing reviews %1$s–%2$s of %3$s';

        return template
            .replace('%1$s', numberFormatter.format(start))
            .replace('%2$s', numberFormatter.format(end))
            .replace('%3$s', numberFormatter.format(total));
    }

    function stopAnimation(list) {
        if (list._sdcAnimation && list._sdcAnimation.frame) {
            window.cancelAnimationFrame(list._sdcAnimation.frame);
        }

        list._sdcAnimation = null;
    }

    function animateScroll(list, target, duration) {
        if (prefersReducedMotion.matches || duration <= 0) {
            stopAnimation(list);
            list.scrollLeft = target;
            return;
        }

        var start = list.scrollLeft;
        var distance = target - start;

        if (Math.abs(distance) < 1) {
            list.scrollLeft = target;
            return;
        }

        stopAnimation(list);

        var startTime = null;

        function step(timestamp) {
            if (!startTime) {
                startTime = timestamp;
            }

            var elapsed = timestamp - startTime;
            var progress = Math.min(elapsed / duration, 1);
            var eased = progress < 0.5 ? 2 * progress * progress : -1 + (4 - 2 * progress) * progress;

            list.scrollLeft = start + distance * eased;

            if (progress < 1) {
                list._sdcAnimation = { frame: window.requestAnimationFrame(step) };
            } else {
                stopAnimation(list);
            }
        }

        list._sdcAnimation = { frame: window.requestAnimationFrame(step) };
    }

    function getItemWidth(list, total) {
        if (!total) {
            return 0;
        }

        return list.scrollWidth / total;
    }

    function getCurrentIndex(list, total) {
        var itemWidth = getItemWidth(list, total);

        if (!itemWidth) {
            return 0;
        }

        var rawIndex = list.scrollLeft / itemWidth;
        var index = Math.round(rawIndex);

        if (index < 0) {
            index = 0;
        }

        if (index >= total) {
            index = total - 1;
        }

        return index;
    }

    function updateDots(dots, activeIndex) {
        if (!dots || !dots.length) {
            return;
        }

        dots.forEach(function (dot, index) {
            if (index === activeIndex) {
                dot.classList.add('is-active');
                dot.setAttribute('aria-current', 'true');
            } else {
                dot.classList.remove('is-active');
                dot.removeAttribute('aria-current');
            }
        });
    }

    function updateStatus(carousel, list, items, liveRegion, dots) {
        var total = items.length;

        if (!total) {
            return;
        }

        var slides = getSlidesPerView(carousel);
        var index = getCurrentIndex(list, total);
        var start = Math.min(index + 1, total);
        var end = Math.min(index + slides, total);

        if (start < 1) {
            start = 1;
        }

        if (liveRegion) {
            liveRegion.textContent = formatStatus(start, end, total);
        }

        carousel.setAttribute('data-active-index', index);
        updateDots(dots, index);
    }

    function scrollToIndex(list, carousel, index, duration) {
        var items = list.querySelectorAll('[data-carousel-item]');
        var total = items.length;

        if (!total) {
            return;
        }

        var targetIndex = Math.max(0, Math.min(index, total - 1));
        var itemWidth = getItemWidth(list, total);
        var target = targetIndex * itemWidth;

        animateScroll(list, target, duration);
    }

    function createAutoplay(carousel, list, items, delay, duration) {
        if (!delay || delay < 0 || items.length <= 1) {
            return null;
        }

        var timer = null;
        var paused = false;

        function stop() {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        }

        function schedule() {
            stop();

            if (paused || prefersReducedMotion.matches) {
                return;
            }

            timer = window.setTimeout(function advance() {
                var total = items.length;

                if (!total) {
                    return;
                }

                var currentIndex = getCurrentIndex(list, total);
                var nextIndex = currentIndex + getSlidesPerView(carousel);

                if (nextIndex >= total) {
                    nextIndex = 0;
                }

                scrollToIndex(list, carousel, nextIndex, duration);
                schedule();
            }, delay);
        }

        function pause() {
            paused = true;
            stop();
        }

        function resume() {
            paused = false;
            schedule();
        }

        function reset() {
            if (!paused) {
                schedule();
            }
        }

        schedule();

        carousel.addEventListener('mouseenter', pause);
        carousel.addEventListener('mouseleave', function () {
            paused = false;
            schedule();
        });
        carousel.addEventListener('focusin', pause);
        carousel.addEventListener('focusout', function (event) {
            if (carousel.contains(event.relatedTarget)) {
                return;
            }

            paused = false;
            schedule();
        });
        carousel.addEventListener('pointerdown', pause);
        carousel.addEventListener('pointerup', function () {
            paused = false;
            schedule();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stop();
            } else if (!paused) {
                schedule();
            }
        });

        if (prefersReducedMotion.addEventListener) {
            prefersReducedMotion.addEventListener('change', function (event) {
                if (event.matches) {
                    stop();
                } else if (!paused) {
                    schedule();
                }
            });
        }

        return {
            pause: pause,
            resume: resume,
            reset: reset,
        };
    }

    function initCarousel(carousel) {
        var list = carousel.querySelector('[data-carousel-list]');
        var items = list ? list.querySelectorAll('[data-carousel-item]') : [];

        if (!list || !items.length) {
            return;
        }

        var liveRegion = carousel.querySelector('[data-carousel-live]');
        var dots = carousel.querySelectorAll('[data-carousel-dot]');
        var dotsArray = Array.prototype.slice.call(dots);
        var transitionDuration = parseInt(carousel.getAttribute('data-transition-duration'), 10) || 0;
        var autoplayDelay = parseInt(carousel.getAttribute('data-autoplay-delay'), 10) || 0;
        var rafId;
        var autoplay = createAutoplay(carousel, list, items, autoplayDelay, transitionDuration);

        function handleScroll() {
            if (rafId) {
                window.cancelAnimationFrame(rafId);
            }

            rafId = window.requestAnimationFrame(function () {
                updateStatus(carousel, list, items, liveRegion, dotsArray);

                if (autoplay) {
                    autoplay.reset();
                }
            });
        }

        dotsArray.forEach(function (dot) {
            dot.addEventListener('click', function (event) {
                event.preventDefault();

                var targetIndex = parseInt(dot.getAttribute('data-index'), 10);

                if (isNaN(targetIndex)) {
                    return;
                }

                scrollToIndex(list, carousel, targetIndex, transitionDuration);

                if (autoplay) {
                    autoplay.reset();
                }
            });
        });

        list.addEventListener('scroll', handleScroll, { passive: true });
        window.addEventListener('resize', handleScroll);

        if (prefersReducedMotion.addEventListener) {
            prefersReducedMotion.addEventListener('change', handleScroll);
        }

        handleScroll();
    }

    function init() {
        var carousels = document.querySelectorAll('.sdc-review-carousel[data-carousel-total]');

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
