/**
 * Digital Library Membership - Home Widgets & Addons Script
 * High-performance Swiper, GSAP, live filter search, and AJAX form handlers.
 *
 * @package    DLM
 * @subpackage DLM/public/js
 * @version    3.2.1
 */

(function () {
    'use strict';

    var ajaxUrl = (typeof dlm_home_widgets_ajax !== 'undefined' && dlm_home_widgets_ajax.ajax_url)
        ? dlm_home_widgets_ajax.ajax_url
        : ((typeof mipallab_ajax !== 'undefined' && mipallab_ajax.ajax_url) ? mipallab_ajax.ajax_url : '/wp-admin/admin-ajax.php');

    var ajaxNonce = (typeof dlm_home_widgets_ajax !== 'undefined' && dlm_home_widgets_ajax.nonce)
        ? dlm_home_widgets_ajax.nonce
        : ((typeof mipallab_ajax !== 'undefined' && mipallab_ajax.nonce) ? mipallab_ajax.nonce : '');

    /**
     * Check if currently in Elementor Live Editor or Preview Mode
     */
    function isEditorMode() {
        return Boolean(
            (typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode && elementorFrontend.isEditMode()) ||
            document.body.classList.contains('elementor-editor-active') ||
            document.body.classList.contains('elementor-editor-preview') ||
            window.location.search.indexOf('elementor-preview') !== -1
        );
    }

    /**
     * Ensure Swiper library is loaded before executing callback
     */
    function ensureSwiper(callback) {
        if (typeof Swiper !== 'undefined') {
            callback();
            return;
        }
        var retries = 0;
        var timer = setInterval(function () {
            retries++;
            if (typeof Swiper !== 'undefined') {
                clearInterval(timer);
                callback();
            } else if (retries > 60) {
                clearInterval(timer);
            }
        }, 100);
    }

    /**
     * Initialize all Swiper carousels inside container
     */
    function initDLMSwipers(container) {
        ensureSwiper(function () {
            var scope = container || document;
            var swipers = scope.querySelectorAll('.dlm-swiper-container, .mipallab-swiper-container');

            swipers.forEach(function (el) {
                if (el.swiper) {
                    try {
                        el.swiper.destroy(true, true);
                    } catch (e) {}
                }

                var speed = parseInt(el.getAttribute('data-speed'), 10) || 750;
                var slidesDesktop = parseInt(el.getAttribute('data-slides'), 10) || 3;
                var slidesTablet = parseInt(el.getAttribute('data-slides-tablet'), 10) || Math.min(2, slidesDesktop);
                var slidesMobile = parseInt(el.getAttribute('data-slides-mobile'), 10) || 1;
                var spaceDesktop = parseInt(el.getAttribute('data-space'), 10) || 24;
                var spaceMobile = parseInt(el.getAttribute('data-space-mobile'), 10) || 16;

                var isAutoplay = el.getAttribute('data-autoplay') === 'true' || el.getAttribute('data-autoplay') === 'yes';
                var autoplayDelay = parseInt(el.getAttribute('data-delay'), 10) || 4500;
                var isLoopAttr = el.getAttribute('data-loop');
                var slidesCount = el.querySelectorAll('.swiper-slide').length;

                var shouldLoop = (isLoopAttr === 'true' || isLoopAttr === 'yes') ? (slidesCount > slidesDesktop) : false;

                var parentSection = el.closest('.dlm-library-section, .dlm-hero-section, .mipallab-library-section, .mipallab-hero-section') || el.parentNode;
                var prevBtn = el.querySelector('.dlm-swiper-nav-prev, .mipallab-swiper-nav-prev') || (parentSection ? parentSection.querySelector('.dlm-swiper-nav-prev, .mipallab-swiper-nav-prev') : null);
                var nextBtn = el.querySelector('.dlm-swiper-nav-next, .mipallab-swiper-nav-next') || (parentSection ? parentSection.querySelector('.dlm-swiper-nav-next, .mipallab-swiper-nav-next') : null);
                var paginationEl = el.querySelector('.swiper-pagination') || (parentSection ? parentSection.querySelector('.swiper-pagination') : null);

                var swiperConfig = {
                    slidesPerView: slidesMobile,
                    spaceBetween: spaceMobile,
                    speed: speed,
                    loop: shouldLoop,
                    rewind: !shouldLoop,
                    grabCursor: true,
                    watchSlidesProgress: true,
                    watchOverflow: true,
                    observer: true,
                    observeParents: true,
                    observeSlideChildren: true,
                    resizeObserver: true,
                    updateOnWindowResize: true,
                    roundLengths: true,
                    touchRatio: 1.15,
                    touchAngle: 45,
                    shortSwipes: true,
                    longSwipes: true,
                    longSwipesRatio: 0.15,
                    threshold: 3,
                    preventClicks: true,
                    preventClicksPropagation: true,
                    keyboard: {
                        enabled: true,
                        onlyInViewport: true
                    },
                    breakpoints: {
                        320: {
                            slidesPerView: slidesMobile,
                            spaceBetween: spaceMobile
                        },
                        640: {
                            slidesPerView: slidesTablet,
                            spaceBetween: Math.max(16, spaceDesktop - 6)
                        },
                        1024: {
                            slidesPerView: slidesDesktop,
                            spaceBetween: spaceDesktop
                        }
                    }
                };

                if (paginationEl) {
                    swiperConfig.pagination = {
                        el: paginationEl,
                        clickable: true,
                        dynamicBullets: slidesCount > 6
                    };
                }

                if (prevBtn && nextBtn) {
                    swiperConfig.navigation = {
                        prevEl: prevBtn,
                        nextEl: nextBtn
                    };
                }

                if (isAutoplay && slidesCount > 1) {
                    swiperConfig.autoplay = {
                        delay: autoplayDelay,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true
                    };
                }

                try {
                    var inst = new Swiper(el, swiperConfig);
                    setTimeout(function () {
                        if (inst && !inst.destroyed) {
                            inst.update();
                        }
                    }, 150);
                } catch (e) {
                    // Fail silently or log in development
                }
            });
        });
    }

    /**
     * Initialize GSAP Motion & ScrollTrigger Animations
     */
    function initDLMGSAP(container) {
        var scope = container || document;
        var isEdit = isEditorMode();

        // If in Elementor Editor, immediately reveal all elements so canvas is never blank
        if (isEdit) {
            scope.querySelectorAll('.gsap-fade-up, .gsap-fade-left, .gsap-fade-right').forEach(function (el) {
                el.style.opacity = '1';
                el.style.transform = 'none';
                el.style.visibility = 'visible';
            });
            return;
        }

        if (typeof gsap === 'undefined') {
            scope.querySelectorAll('.gsap-fade-up, .gsap-fade-left, .gsap-fade-right').forEach(function (el) {
                el.style.opacity = '1';
                el.style.transform = 'none';
                el.style.visibility = 'visible';
            });
            return;
        }

        if (typeof ScrollTrigger !== 'undefined') {
            gsap.registerPlugin(ScrollTrigger);
        }

        scope.querySelectorAll('.gsap-float').forEach(function (el) {
            if (el.getAttribute('data-gsap-float-bound') === 'true') return;
            el.setAttribute('data-gsap-float-bound', 'true');
            gsap.to(el, {
                y: -10,
                rotation: 1.2,
                duration: 3.2,
                repeat: -1,
                yoyo: true,
                ease: 'sine.inOut'
            });
        });

        scope.querySelectorAll('.gsap-fade-up').forEach(function (el) {
            if (el.getAttribute('data-gsap-bound') === 'true') return;
            el.setAttribute('data-gsap-bound', 'true');
            gsap.fromTo(el,
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.75,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 92%',
                        toggleActions: 'play none none none'
                    }
                }
            );
        });

        scope.querySelectorAll('.gsap-fade-left').forEach(function (el) {
            if (el.getAttribute('data-gsap-bound') === 'true') return;
            el.setAttribute('data-gsap-bound', 'true');
            gsap.fromTo(el,
                { opacity: 0, x: -35 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 0.75,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 92%',
                        toggleActions: 'play none none none'
                    }
                }
            );
        });

        scope.querySelectorAll('.gsap-fade-right').forEach(function (el) {
            if (el.getAttribute('data-gsap-bound') === 'true') return;
            el.setAttribute('data-gsap-bound', 'true');
            gsap.fromTo(el,
                { opacity: 0, x: 35 },
                {
                    opacity: 1,
                    x: 0,
                    duration: 0.75,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: el,
                        start: 'top 92%',
                        toggleActions: 'play none none none'
                    }
                }
            );
        });
    }

    /**
     * Initialize Review Switcher Tabbed Panels
     */
    function initDLMReviewTabs(container) {
        var scope = container || document;
        var wrappers = scope.querySelectorAll('.dlm-review-switcher-wrapper, .mipallab-review-switcher-wrapper');

        wrappers.forEach(function (wrapper) {
            var buttons = wrapper.querySelectorAll('.dlm-review-tab-btn, .mipallab-review-tab-btn');
            var primaryColor = wrapper.getAttribute('data-primary-color') || '#855300';
            var textColor = wrapper.getAttribute('data-text-color') || '#1a1c1c';

            buttons.forEach(function (btn) {
                if (btn.getAttribute('data-bound') === 'true') return;
                btn.setAttribute('data-bound', 'true');

                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    var targetId = btn.getAttribute('data-target') || btn.getAttribute('data-pane');

                    wrapper.querySelectorAll('.dlm-review-tab-btn, .mipallab-review-tab-btn').forEach(function (b) {
                        b.classList.remove('active');
                        b.style.background = 'transparent';
                        b.style.color = textColor;
                    });

                    wrapper.querySelectorAll('.dlm-review-content-pane, .dlm-rev-pane, .mipallab-review-content-pane, .mipallab-rev-pane').forEach(function (p) {
                        p.style.display = 'none';
                        p.classList.remove('active');
                    });

                    btn.classList.add('active');
                    btn.style.background = primaryColor;
                    btn.style.color = '#ffffff';

                    if (targetId) {
                        var targetPane = wrapper.querySelector('#' + targetId) || document.getElementById(targetId);
                        if (targetPane) {
                            targetPane.style.display = 'block';
                            targetPane.classList.add('active');
                        }
                    }
                });
            });
        });
    }

    /**
     * Initialize Real-Time Client-Side Library Search & Filter
     */
    function initDLMLibrarySearch(container) {
        var scope = container || document;
        var searchInputs = scope.querySelectorAll('.dlm-library-search-input, .mipallab-library-search-input');

        searchInputs.forEach(function (input) {
            if (input.getAttribute('data-bound') === 'true') return;
            input.setAttribute('data-bound', 'true');

            input.addEventListener('input', function (e) {
                var query = (e.target.value || '').toLowerCase().trim();
                var section = input.closest('.dlm-library-grid-section, .dlm-library-section, .mipallab-library-grid-section, .mipallab-library-section');
                if (!section) return;

                var items = section.querySelectorAll('.dlm-book-grid-item, .mipallab-book-grid-item');
                var matchCount = 0;

                items.forEach(function (item) {
                    var title = (item.getAttribute('data-title') || '').toLowerCase();
                    var author = (item.getAttribute('data-author') || '').toLowerCase();
                    var cat = (item.getAttribute('data-category') || '').toLowerCase();

                    if (!query || title.indexOf(query) !== -1 || author.indexOf(query) !== -1 || cat.indexOf(query) !== -1) {
                        item.style.display = 'flex';
                        matchCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                var countEl = section.querySelector('.dlm-book-count-num, .mipallab-book-count-num');
                if (countEl) {
                    countEl.textContent = matchCount;
                }
            });
        });
    }

    /**
     * Initialize AJAX Contact Form Submission
     */
    function initDLMContactForms(container) {
        var scope = container || document;
        var forms = scope.querySelectorAll('.dlm-contact-form-el, .mipallab-contact-form-el');

        forms.forEach(function (form) {
            if (form.getAttribute('data-bound') === 'true') return;
            form.setAttribute('data-bound', 'true');

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var submitBtn = form.querySelector('button[type="submit"]');
                var msgBox = form.querySelector('.dlm-form-message, .mipallab-form-message');
                var origBtnText = submitBtn ? submitBtn.innerHTML : 'Send Message';

                if (submitBtn) {
                    submitBtn.classList.add('dlm-btn-loading');
                    submitBtn.innerHTML = 'Sending message...';
                    submitBtn.disabled = true;
                }

                if (msgBox) {
                    msgBox.className = 'dlm-form-message';
                    msgBox.style.display = 'none';
                    msgBox.innerHTML = '';
                }

                var formData = new FormData(form);
                if (!formData.get('action')) {
                    formData.append('action', 'dlm_contact_form_submit');
                }
                if (ajaxNonce && !formData.get('nonce')) {
                    formData.append('nonce', ajaxNonce);
                }

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    if (submitBtn) {
                        submitBtn.classList.remove('dlm-btn-loading');
                        submitBtn.innerHTML = origBtnText;
                        submitBtn.disabled = false;
                    }
                    if (msgBox) {
                        if (data && data.success) {
                            msgBox.className = 'dlm-form-message success';
                            msgBox.innerHTML = (data.data && data.data.message) ? data.data.message : 'Thank you! Your message has been sent successfully.';
                            form.reset();
                        } else {
                            msgBox.className = 'dlm-form-message error';
                            msgBox.innerHTML = (data && data.data && data.data.message) ? data.data.message : 'An error occurred. Please try again.';
                        }
                    }
                })
                .catch(function () {
                    if (submitBtn) {
                        submitBtn.classList.remove('dlm-btn-loading');
                        submitBtn.innerHTML = origBtnText;
                        submitBtn.disabled = false;
                    }
                    if (msgBox) {
                        msgBox.className = 'dlm-form-message error';
                        msgBox.innerHTML = 'Unable to send message at this time. Please try again later.';
                    }
                });
            });
        });
    }

    /**
     * Master Initializer
     */
    function initAll(container) {
        initDLMSwipers(container);
        initDLMGSAP(container);
        initDLMReviewTabs(container);
        initDLMLibrarySearch(container);
        initDLMContactForms(container);
    }

    // Document lifecycle hooks
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initAll();
        });
    } else {
        initAll();
    }

    window.addEventListener('load', function () {
        initAll();
    });

    window.addEventListener('resize', function () {
        document.querySelectorAll('.dlm-swiper-container, .mipallab-swiper-container').forEach(function (el) {
            if (el.swiper) {
                el.swiper.update();
            }
        });
    });

    /**
     * Register Elementor Live Editor Re-Initialization Hooks
     */
    function registerElementorHooks() {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            var widgets = [
                'dlm_hero_book_slider.default',
                'dlm_library_carousel.default',
                'dlm_membership_section.default',
                'dlm_review_switcher.default',
                'dlm_contact_section.default',
                'dlm_about_author.default',
                'mipallab_hero_book_slider.default',
                'mipallab_dlm_library_carousel.default',
                'mipallab_membership_section.default',
                'mipallab_review_switcher.default',
                'mipallab_contact_section.default',
                'mipallab_about_author.default',
                'global'
            ];

            widgets.forEach(function (widgetName) {
                elementorFrontend.hooks.addAction('frontend/element_ready/' + widgetName, function ($scope) {
                    var domEl = ($scope && $scope[0]) ? $scope[0] : document;
                    initAll(domEl);
                });
            });

            if (elementorFrontend.elements && elementorFrontend.elements.$window) {
                elementorFrontend.elements.$window.on('resize', function () {
                    document.querySelectorAll('.dlm-swiper-container, .mipallab-swiper-container').forEach(function (el) {
                        if (el.swiper) el.swiper.update();
                    });
                });
            }
        }
    }

    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
        registerElementorHooks();
    } else {
        window.addEventListener('elementor/frontend/init', registerElementorHooks);
    }

    /**
     * Global Tab Switcher helper for backward compatibility
     */
    window.switchDLMTab = function (btn, paneId) {
        var parent = btn.closest('.dlm-review-switcher-wrapper, .mipallab-review-switcher-wrapper');
        if (!parent) return;

        var primaryColor = parent.getAttribute('data-primary-color') || '#855300';
        var textColor = parent.getAttribute('data-text-color') || '#1a1c1c';

        parent.querySelectorAll('.dlm-review-tab-btn, .mipallab-review-tab-btn').forEach(function (b) {
            b.style.background = 'transparent';
            b.style.color = textColor;
            b.classList.remove('active');
        });

        parent.querySelectorAll('.dlm-rev-pane, .dlm-review-content-pane, .mipallab-rev-pane, .mipallab-review-content-pane').forEach(function (p) {
            p.style.display = 'none';
            p.classList.remove('active');
        });

        btn.style.background = primaryColor;
        btn.style.color = '#ffffff';
        btn.classList.add('active');

        var target = parent.querySelector('#' + paneId);
        if (target) {
            target.style.display = 'block';
            target.classList.add('active');
        }
    };

    window.switchMipallabTab = window.switchDLMTab;

})();
