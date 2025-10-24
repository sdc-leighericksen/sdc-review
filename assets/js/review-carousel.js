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

    /**
     * Measures the rendered width of a single carousel item.
     *
     * @param {HTMLElement} list
     * @returns {number}
     */
    function getItemWidth(list) {
        var item = list ? list.querySelector('[data-carousel-item]') : null;

        if (!item) {
            return 0;
        }

        return item.getBoundingClientRect().width;
    }

    function getCurrentIndex(list, total) {
        var itemWidth = getItemWidth(list);

        if (!itemWidth) {
            return 0;
        }

        var rawIndex = list.scrollLeft / itemWidth;
        var index = Math.round(rawIndex);

        if (total > 0) {
            index = ((index % total) + total) % total;
        }

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
        var items = list.querySelectorAll('[data-carousel-item]:not([data-carousel-clone])');
        var total = items.length;

        if (!total) {
            return;
        }

        var targetIndex = Math.max(0, Math.min(index, total - 1));
        var itemWidth = getItemWidth(list);
        var target = targetIndex * itemWidth;

        animateScroll(list, target, duration);
    }

    /**
     * Clones carousel items to provide a seamless ticker loop.
     *
     * @param {HTMLElement} list
     * @param {Array<HTMLElement>} originals
     * @returns {Array<HTMLElement>}
     */
    function cloneItemsForLoop(list, originals) {
        var clones = [];

        if (!list || !originals.length) {
            return clones;
        }

        if (list.querySelector('[data-carousel-clone]')) {
            return clones;
        }

        originals.forEach(function (item) {
            var clone = item.cloneNode(true);
            clone.setAttribute('data-carousel-clone', 'true');
            list.appendChild(clone);
            clones.push(clone);
        });

        return clones;
    }

    /**
     * Creates a ticker-style autoplay controller.
     *
     * TODO: Gracefully bail out when there are fewer cards than visible slots.
     *
     * @param {HTMLElement} carousel
     * @param {HTMLElement} list
     * @param {number} totalOriginal
     * @param {number} durationPerCard
     * @returns {{pause: Function, resume: Function, reset: Function} | null}
     */
    function createTickerAutoplay(carousel, list, totalOriginal, durationPerCard) {
        if (!totalOriginal || totalOriginal <= 1) {
            return null;
        }

        var paused = false;
        var animationId = null;
        var lastTimestamp = null;
        var duration = durationPerCard > 0 ? durationPerCard : 5000;

        function cancelAnimation() {
            if (animationId) {
                window.cancelAnimationFrame(animationId);
                animationId = null;
            }
        }

        function getLoopWidth(itemWidth) {
            return itemWidth * totalOriginal;
        }

        function tick(timestamp) {
            if (paused || prefersReducedMotion.matches) {
                lastTimestamp = null;
                animationId = null;
                return;
            }

            var itemWidth = getItemWidth(list);

            if (!itemWidth) {
                lastTimestamp = timestamp;
                animationId = window.requestAnimationFrame(tick);
                return;
            }

            var loopWidth = getLoopWidth(itemWidth);

            if (!loopWidth) {
                lastTimestamp = timestamp;
                animationId = window.requestAnimationFrame(tick);
                return;
            }

            if (!lastTimestamp) {
                lastTimestamp = timestamp;
            }

            var delta = timestamp - lastTimestamp;
            var distance = (itemWidth / duration) * delta;
            var nextLeft = list.scrollLeft + distance;

            if (nextLeft >= loopWidth) {
                nextLeft = nextLeft % loopWidth;
            }

            list.scrollLeft = nextLeft;
            lastTimestamp = timestamp;
            animationId = window.requestAnimationFrame(tick);
        }

        function ensureWithinLoop() {
            var itemWidth = getItemWidth(list);
            var loopWidth = getLoopWidth(itemWidth);

            if (!loopWidth) {
                return;
            }

            if (list.scrollLeft >= loopWidth) {
                list.scrollLeft = list.scrollLeft % loopWidth;
            }
        }

        function schedule() {
            cancelAnimation();

            if (paused || prefersReducedMotion.matches) {
                return;
            }

            animationId = window.requestAnimationFrame(tick);
        }

        function pause() {
            paused = true;
            cancelAnimation();
        }

        function resume() {
            if (!paused) {
                return;
            }

            paused = false;
            lastTimestamp = null;
            schedule();
        }

        function reset() {
            ensureWithinLoop();

            if (!paused) {
                lastTimestamp = null;
                schedule();
            }
        }

        schedule();

        carousel.addEventListener('mouseenter', pause);
        carousel.addEventListener('mouseleave', function () {
            paused = false;
            lastTimestamp = null;
            schedule();
        });
        carousel.addEventListener('focusin', pause);
        carousel.addEventListener('focusout', function (event) {
            if (carousel.contains(event.relatedTarget)) {
                return;
            }

            paused = false;
            lastTimestamp = null;
            schedule();
        });
        carousel.addEventListener('pointerdown', pause);
        carousel.addEventListener('pointerup', function () {
            paused = false;
            lastTimestamp = null;
            schedule();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                cancelAnimation();
            } else if (!paused) {
                lastTimestamp = null;
                schedule();
            }
        });

        if (prefersReducedMotion.addEventListener) {
            prefersReducedMotion.addEventListener('change', function (event) {
                if (event.matches) {
                    cancelAnimation();
                } else if (!paused) {
                    lastTimestamp = null;
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
        var items = list ? list.querySelectorAll('[data-carousel-item]:not([data-carousel-clone])') : [];

        if (!list || !items.length) {
            return;
        }

        var liveRegion = carousel.querySelector('[data-carousel-live]');
        var dots = carousel.querySelectorAll('[data-carousel-dot]');
        var dotsArray = Array.prototype.slice.call(dots);
        var transitionDuration = parseInt(carousel.getAttribute('data-transition-duration'), 10) || 0;
        var autoplayDelayAttribute = carousel.getAttribute('data-autoplay-delay');
        var parsedAutoplayDelay = autoplayDelayAttribute !== null ? parseInt(autoplayDelayAttribute, 10) : null;
        var rafId;
        var originals = Array.prototype.slice.call(items);

        cloneItemsForLoop(list, originals);

        var autoplay = null;
        var tickerDuration = 5000;

        if (parsedAutoplayDelay === null) {
            autoplay = createTickerAutoplay(carousel, list, originals.length, tickerDuration);
        } else if (parsedAutoplayDelay > 0) {
            tickerDuration = parsedAutoplayDelay;
            autoplay = createTickerAutoplay(carousel, list, originals.length, tickerDuration);
        }

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
