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
});
