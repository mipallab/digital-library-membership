// Public JS - Digital Library Membership Checkout, Auth forms and interactions
jQuery(document).ready(function($) {
    var selectedInterval = 'monthly';
    var paypalButtonsInstance = null;

    // 1. Open Checkout Modal and set selected plan
    $('.select-plan-btn').click(function(e) {
        e.preventDefault();
        selectedInterval = $(this).data('interval');
        
        // If WooCommerce integration is active, redirect to WC checkout
        if (dlmParams.useWooCommerce) {
            var $btn = $(this);
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Redirecting to checkout...');

            $.post(dlmParams.ajaxUrl, {
                action: 'dlm_wc_add_to_cart_redirect',
                nonce: dlmParams.nonce,
                interval: selectedInterval
            }, function(res) {
                if (res.success && res.data.redirect) {
                    window.location.href = res.data.redirect;
                } else {
                    alert(res.data.message || 'An error occurred during WooCommerce redirect.');
                    $btn.prop('disabled', false).text(originalText);
                }
            }).fail(function() {
                alert('Connection timeout. Try again.');
                $btn.prop('disabled', false).text(originalText);
            });
            return;
        }
        
        var planName = 'Monthly Plan';
        if (selectedInterval === 'yearly') {
            planName = 'Yearly Plan';
        } else if (selectedInterval === 'lifetime') {
            planName = 'Lifetime Access';
        }
        $('#selected-plan-name').text(planName);

        // Reset manual checkout drawer
        $('#dlm-manual-checkout-fields').hide();
        $('#manual_txn_reference').val('');

        $('#dlmCheckoutModal').fadeIn();
        renderPayPalButtons(selectedInterval);
    });

    // Close Modal
    $('.dlm-close-modal').click(function() {
        $('#dlmCheckoutModal').fadeOut();
    });

    $(window).click(function(e) {
        if ($(e.target).is('#dlmCheckoutModal')) {
            $('#dlmCheckoutModal').fadeOut();
        }
    });

    // 2. Handle Stripe Checkout Creation
    $('#dlm-stripe-btn').click(function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text('Loading stripe secure checkout...');

        $.post(dlmParams.ajaxUrl, {
            action: 'dlm_stripe_create_session',
            nonce: dlmParams.nonce,
            interval: selectedInterval
        }, function(res) {
            if (res.success && res.data.url) {
                window.location.href = res.data.url;
            } else {
                alert(res.data.message || 'An error occurred creating Stripe Checkout Session.');
                $btn.prop('disabled', false).html('<span class="stripe-icon"></span> Pay with Credit/Debit Card (Stripe)');
            }
        }).fail(function() {
            alert('Failed connection to Stripe Gateway. Try again.');
            $btn.prop('disabled', false).html('<span class="stripe-icon"></span> Pay with Credit/Debit Card (Stripe)');
        });
    });

    // 2b. Handle WooCommerce Checkout Redirect
    $('body').on('click', '#dlm-wc-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalText = $btn.text();
        var planInterval = $btn.data('interval') || selectedInterval;
        $btn.prop('disabled', true).text('Redirecting to WooCommerce checkout...');

        $.post(dlmParams.ajaxUrl, {
            action: 'dlm_wc_add_to_cart_redirect',
            nonce: dlmParams.nonce,
            interval: planInterval
        }, function(res) {
            if (res.success && res.data.redirect) {
                window.location.href = res.data.redirect;
            } else {
                alert(res.data.message || 'An error occurred during WooCommerce redirect.');
                $btn.prop('disabled', false).text(originalText);
            }
        }).fail(function() {
            alert('Connection timeout. Try again.');
            $btn.prop('disabled', false).text(originalText);
        });
    });

    // 3. Render PayPal Buttons Dynamically for the selected plan
    function renderPayPalButtons(interval) {
        $('#paypal-button-container').html('');

        if (typeof paypal === 'undefined') {
            $('#paypal-button-container').html('<p style="color:#d32f2f; font-size:12px;">PayPal JS SDK failed to load. Check PayPal Client ID.</p>');
            return;
        }

        // Setup paypal buttons
        paypal.Buttons({
            style: {
                shape: 'rect',
                color: 'gold',
                layout: 'vertical',
                label: interval === 'lifetime' ? 'checkout' : 'subscribe'
            },
            createSubscription: function(data, actions) {
                var actualPlanId = (interval === 'yearly') ? 
                    (dlmParams.paypalYearlyPlanId || '') : 
                    (dlmParams.paypalMonthlyPlanId || '');
                
                if (!actualPlanId) {
                    alert('PayPal Plan ID is not configured on the server settings page.');
                    return;
                }

                return actions.subscription.create({
                    plan_id: actualPlanId
                });
            },
            createOrder: function(data, actions) {
                // If it is lifetime (one-time purchase), we create a standard order instead of subscription
                if (interval === 'lifetime') {
                    var lifetimePrice = parseFloat(jQuery('#card-lifetime .amount').text()) || 199.99;
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: lifetimePrice.toFixed(2),
                                currency_code: 'USD'
                            },
                            description: 'Digital Library Lifetime Access Membership'
                        }]
                    });
                }
            },
            onApprove: function(data, actions) {
                $('#paypal-button-container').html('<p style="color:#2e7d32;">Verifying transaction status...</p>');
                
                var txnId = (interval === 'lifetime') ? data.orderID : data.subscriptionID;
                
                $.post(dlmParams.ajaxUrl, {
                    action: 'dlm_paypal_create_subscription',
                    nonce: dlmParams.nonce,
                    subscription_id: txnId,
                    interval: interval
                }, function(res) {
                    if (res.success && res.data.redirect) {
                        window.location.href = res.data.redirect;
                    } else {
                        alert(res.data.message || 'Failed to verify PayPal payment.');
                    }
                }).fail(function() {
                    alert('Connection timeout logging PayPal transaction.');
                });
            },
            onError: function(err) {
                console.error('PayPal Error: ', err);
                alert('An error occurred during PayPal checkout process.');
            }
        }).render('#paypal-button-container');
    }

    // 4. Handle Manual Bank Transfer Checkout
    $('#dlm-manual-toggle-btn').click(function(e) {
        e.preventDefault();
        $('#dlm-manual-checkout-fields').slideToggle();
    });

    $('#dlm-submit-manual-payment-btn').click(function(e) {
        e.preventDefault();
        var ref = $('#manual_txn_reference').val().trim();
        var $btn = $(this);

        if (!ref) {
            alert('Please supply the transaction reference code.');
            return;
        }

        $btn.prop('disabled', true).text('Submitting reference details...');

        $.post(dlmParams.ajaxUrl, {
            action: 'dlm_submit_manual_payment',
            nonce: dlmParams.nonce,
            interval: selectedInterval,
            reference: ref
        }, function(res) {
            if (res.success && res.data.redirect) {
                window.location.href = res.data.redirect;
            } else {
                alert(res.data.message || 'Failed to submit manual payment request.');
                $btn.prop('disabled', false).text('Submit Reference Code');
            }
        }).fail(function() {
            alert('Connection timeout submitting request. Try again.');
            $btn.prop('disabled', false).text('Submit Reference Code');
        });
    });

    // ------------------------------------------------------------------------
    // APPLE STYLE FRONTEND LOGIN & REGISTRATION TABS & AJAX
    // ------------------------------------------------------------------------

    // Tab switcher
    $('body').on('click', '.dlm-auth-tab-btn', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        $('.dlm-auth-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.dlm-auth-panel').hide();
        $('#panel-' + tab).show();
    });

    // Ajax Login Form
    $('body').on('submit', '#dlm-login-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $alert = $form.find('.dlm-auth-alert');
        var $btn = $form.find('button[type="submit"]');

        $alert.hide().removeClass('alert-danger alert-success');
        $btn.prop('disabled', true).text('Signing in...');

        $.post(dlmParams.ajaxUrl, {
            action: 'dlm_ajax_login',
            nonce: dlmParams.nonce,
            username: $('#dlm_username').val().trim(),
            password: $('#dlm_password').val()
        }, function(res) {
            if (res.success && res.data.redirect) {
                $alert.css('background', '#e1f5fe').css('color', '#0288d1').text('Success! Redirecting...').fadeIn();
                window.location.href = res.data.redirect;
            } else {
                $alert.css('background', '#ffe082').css('color', '#f57c00').html(res.data.message || 'Incorrect credentials.').fadeIn();
                $btn.prop('disabled', false).text('Sign In');
            }
        }).fail(function() {
            $alert.css('background', '#ffe082').css('color', '#f57c00').text('Server timeout. Try again.').fadeIn();
            $btn.prop('disabled', false).text('Sign In');
        });
    });

    // Ajax Register Form (with auto login approval)
    $('body').on('submit', '#dlm-register-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $alert = $form.find('.dlm-auth-alert');
        var $btn = $form.find('button[type="submit"]');

        $alert.hide().removeClass('alert-danger alert-success');
        
        var name = $('#dlm_reg_name').val().trim();
        var email = $('#dlm_reg_email').val().trim();
        var pass = $('#dlm_reg_password').val();

        if (pass.length < 6) {
            $alert.css('background', '#ffe082').css('color', '#f57c00').text('Password must be at least 6 characters.').fadeIn();
            return;
        }

        $btn.prop('disabled', true).text('Creating account...');

        $.post(dlmParams.ajaxUrl, {
            action: 'dlm_ajax_register',
            nonce: dlmParams.nonce,
            name: name,
            email: email,
            password: pass
        }, function(res) {
            if (res.success && res.data.redirect) {
                $alert.css('background', '#e1f5fe').css('color', '#0288d1').text('Account created! Redirecting to checkout...').fadeIn();
                window.location.href = res.data.redirect;
            } else {
                $alert.css('background', '#ffe082').css('color', '#f57c00').html(res.data.message || 'Failed to create account.').fadeIn();
                $btn.prop('disabled', false).text('Register & Auto-Login');
            }
        }).fail(function() {
            $alert.css('background', '#ffe082').css('color', '#f57c00').text('Server timeout. Try again.').fadeIn();
            $btn.prop('disabled', false).text('Register & Auto-Login');
        });
    });

    // ------------------------------------------------------------------------
    // DYNAMIC LIBRARY GRID (Real-time Filtering, Searching, Sorting & Load More)
    // ------------------------------------------------------------------------
    if (window.dlmLibraryData && window.dlmLibraryData.books) {
        var libState = {
            category: 'All',
            sort: 'recent',
            search: '',
            visibleCount: 12,
            pageSize: 6
        };

        var allBooks = window.dlmLibraryData.books;
        var isLoggedIn = window.dlmLibraryData.isLoggedIn;
        var isActive = window.dlmLibraryData.isActive;
        var pricingUrl = window.dlmLibraryData.pricingUrl;

        function getFilteredAndSortedBooks() {
            var filtered = allBooks.filter(function(book) {
                var matchCategory = (libState.category === 'All') || (book.category === libState.category);
                var q = libState.search.toLowerCase().trim();
                var matchSearch = !q || book.title.toLowerCase().indexOf(q) !== -1 || book.author.toLowerCase().indexOf(q) || book.category.toLowerCase().indexOf(q);
                return matchCategory && matchSearch;
            });

            if (libState.sort === 'recent') {
                filtered.sort(function(a, b) { return new Date(b.date) - new Date(a.date); });
            } else if (libState.sort === 'title-asc') {
                filtered.sort(function(a, b) { return a.title.localeCompare(b.title); });
            } else if (libState.sort === 'progress-desc') {
                filtered.sort(function(a, b) { return b.progress - a.progress; });
            } else if (libState.sort === 'category') {
                filtered.sort(function(a, b) { return a.category.localeCompare(b.category); });
            }

            return filtered;
        }

        function renderLibraryGrid() {
            var $grid = $('#dlm-library-grid, #books-grid');
            var $empty = $('#dlm-library-empty, #empty-state');
            var $loadMore = $('#dlm-load-more-btn, #load-more-btn');
            var $loadMoreText = $('#dlm-load-more-text, #load-more-text');
            var $stats = $('#dlm-result-stats, #result-stats');
            var $visibleNum = $('#dlm-visible-count, #visible-count-num');
            var $totalNum = $('#dlm-total-count, #total-count-num');

            if (!$grid.length) return;

            var filtered = getFilteredAndSortedBooks();
            var total = filtered.length;
            var displayed = filtered.slice(0, libState.visibleCount);

            $visibleNum.text(displayed.length);
            $totalNum.text(total);
            $stats.removeClass('hidden');

            if (total === 0) {
                $grid.empty();
                $empty.removeClass('hidden');
                $loadMore.addClass('hidden');
                return;
            }

            $empty.addClass('hidden');

            var html = displayed.map(function(book) {
                var coverMarkup = book.cover 
                    ? '<img class="w-full h-full object-cover" src="' + book.cover + '" alt="' + book.title + '" loading="lazy">' 
                    : '<div class="dlm-book-cover-placeholder"><span>' + book.title + '</span></div>';

                var actionButton = '';
                if (isActive) {
                    var btnText = book.progress > 0 ? 'Continue Reading' : 'Read Book';
                    actionButton = '<a href="' + book.read_url + '" class="px-6 py-2 bg-primary text-white font-bold rounded-full transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 shadow-md">' + btnText + '</a>';
                } else if (isLoggedIn) {
                    actionButton = '<a href="' + pricingUrl + '" class="px-6 py-2 bg-primary text-white font-bold rounded-full transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 shadow-md">Unlock Access</a>';
                } else {
                    actionButton = '<a href="' + pricingUrl + '" class="px-6 py-2 bg-primary text-white font-bold rounded-full transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 shadow-md">Sign Up to Read</a>';
                }

                var progressBarHtml = book.progress > 0 
                    ? '<div class="absolute bottom-0 left-0 w-full h-1.5 bg-black/20"><div class="h-full bg-surface-amber transition-all duration-300" style="width: ' + book.progress + '%;"></div></div>' 
                    : '';

                var progressTextHtml = book.progress > 0 
                    ? '<span class="text-label-micro text-secondary font-semibold">' + book.progress + '% Read</span>' 
                    : '';

                return '<div class="group cursor-pointer animate-fade-in dlm-book-card-item" data-book-id="' + book.id + '">' +
                    '<div class="relative aspect-[3/4] mb-4 rounded-xl overflow-hidden book-card-shadow transition-all duration-300 group-hover:-translate-y-2 group-hover:book-card-shadow-hover border border-outline-variant/30">' +
                        coverMarkup +
                        '<div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center backdrop-blur-[2px]">' +
                            actionButton +
                        '</div>' +
                        progressBarHtml +
                    '</div>' +
                    '<div class="space-y-1">' +
                        '<div class="flex items-center justify-between">' +
                            '<span class="text-label-micro text-primary font-bold uppercase tracking-wider">' + book.category + '</span>' +
                            progressTextHtml +
                        '</div>' +
                        '<h3 class="font-title-sm text-on-surface serif-title truncate m-0">' + book.title + '</h3>' +
                        '<p class="text-[13px] text-secondary m-0">' + book.author + '</p>' +
                    '</div>' +
                '</div>';
            }).join('');

            $grid.html(html);

            if (libState.visibleCount >= total) {
                $loadMore.addClass('hidden');
            } else {
                $loadMore.removeClass('hidden');
                var remaining = total - libState.visibleCount;
                $loadMoreText.text('Load More Manuscripts (' + remaining + ' remaining)');
            }
        }

        // Category Filter Buttons
        $('body').on('click', '.dlm-cat-btn, .filter-btn', function() {
            $('.dlm-cat-btn, .filter-btn').removeClass('bg-primary text-white shadow-sm active').addClass('bg-surface-container text-secondary');
            $(this).removeClass('bg-surface-container text-secondary').addClass('bg-primary text-white shadow-sm active');

            libState.category = $(this).data('category');
            libState.visibleCount = 12;
            renderLibraryGrid();
        });

        // Search Input Handler
        $('body').on('input', '#dlm-library-search, #search-input', function() {
            libState.search = $(this).val();
            libState.visibleCount = 12;
            renderLibraryGrid();
        });

        // Sort Dropdown Trigger
        $('body').on('click', '#dlm-sort-trigger, #sort-trigger', function(e) {
            e.stopPropagation();
            $('#dlm-sort-dropdown, #sort-dropdown').toggleClass('hidden');
        });

        $(document).click(function() {
            $('#dlm-sort-dropdown, #sort-dropdown').addClass('hidden');
        });

        // Sort Option Selection
        $('body').on('click', '.dlm-sort-opt, .sort-opt', function(e) {
            e.stopPropagation();
            libState.sort = $(this).data('sort');

            $('.dlm-sort-opt, .sort-opt').removeClass('font-bold text-on-surface').addClass('font-medium text-secondary').find('span').remove();
            $(this).removeClass('font-medium text-secondary').addClass('font-bold text-on-surface').append(' <span>✓</span>');

            var sortNames = {
                'recent': 'Recent',
                'title-asc': 'Title (A - Z)',
                'progress-desc': 'Progress (Highest)',
                'category': 'Category'
            };
            $('#dlm-sort-label, #current-sort-label').text(sortNames[libState.sort] || 'Recent');
            $('#dlm-sort-dropdown, #sort-dropdown').addClass('hidden');

            renderLibraryGrid();
        });

        // Load More Handler
        $('body').on('click', '#dlm-load-more-btn, #load-more-btn', function() {
            libState.visibleCount += libState.pageSize;
            renderLibraryGrid();
        });

        // Reset Filters Button
        $('body').on('click', '#dlm-reset-filters-btn, #reset-filters-btn', function() {
            libState.category = 'All';
            libState.sort = 'recent';
            libState.search = '';
            $('#dlm-library-search, #search-input').val('');

            $('.dlm-cat-btn, .filter-btn').removeClass('bg-primary text-white shadow-sm active').addClass('bg-surface-container text-secondary');
            $('.dlm-cat-btn[data-category="All"], .filter-btn[data-category="All"]').removeClass('bg-surface-container text-secondary').addClass('bg-primary text-white shadow-sm active');

            libState.visibleCount = 12;
            renderLibraryGrid();
        });

        // Card Click / Reader Modal Interactivity
        var selectedBookForModal = null;

        $('body').on('click', '.dlm-book-card-item', function(e) {
            if ($(e.target).closest('a').length) return; // Allow direct link clicks on overlay buttons

            var bookId = $(this).data('book-id');
            var book = allBooks.find(function(b) { return b.id == bookId; });
            if (!book) return;

            selectedBookForModal = book;

            $('#modal-cover').attr('src', book.cover || '').attr('alt', book.title);
            $('#modal-category').text(book.category);
            $('#modal-title').text(book.title);
            $('#modal-author').text(book.author);
            $('#modal-progress-text').text(book.progress + '%');
            $('#modal-progress-bar').css('width', book.progress + '%');

            if (isActive) {
                $('#modal-read-now-btn').text('Start Reading').off('click').on('click', function() {
                    window.location.href = book.read_url;
                });
            } else if (isLoggedIn) {
                $('#modal-read-now-btn').text('Unlock Access').off('click').on('click', function() {
                    window.location.href = pricingUrl;
                });
            } else {
                $('#modal-read-now-btn').text('Sign Up to Read').off('click').on('click', function() {
                    window.location.href = pricingUrl;
                });
            }

            $('#reader-modal').removeClass('hidden').addClass('flex');
        });

        // Close Modal
        $('body').on('click', '#close-modal-btn', function() {
            $('#reader-modal').addClass('hidden').removeClass('flex');
        });

        $('#reader-modal').on('click', function(e) {
            if (e.target === this) {
                $('#reader-modal').addClass('hidden').removeClass('flex');
            }
        });

        // Progress simulator button
        $('body').on('click', '#modal-mark-complete-btn', function() {
            if (selectedBookForModal) {
                selectedBookForModal.progress = Math.min(100, selectedBookForModal.progress + 15);
                $('#modal-progress-text').text(selectedBookForModal.progress + '%');
                $('#modal-progress-bar').css('width', selectedBookForModal.progress + '%');
                renderLibraryGrid();
                showToast('Updated progress to ' + selectedBookForModal.progress + '% for "' + selectedBookForModal.title + '"');
            }
        });

        function showToast(msg) {
            var $toast = $('#toast-notif');
            var $msg = $('#toast-message');
            if (!$toast.length) return;

            $msg.text(msg);
            $toast.removeClass('translate-y-20 opacity-0').addClass('translate-y-0 opacity-100');

            setTimeout(function() {
                $toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-20 opacity-0');
            }, 3000);
        }

        // Initial Grid Render
        renderLibraryGrid();
    }
});
