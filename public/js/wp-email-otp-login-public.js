/* global jQuery, wp_email_otp */
jQuery(document).ready(function ($) {
    var modal = $('#otp-modal');
    var emailForm = $('#otp-email-form');
    var otpForm = $('#otp-verification-form');
    var resendInfo = $('.resend-info');
    var resendAttempts = $('#resend-attempts');
    var resendButton = $('#resend-otp');
    var countdownTimer = null;
    var currentEmail = '';
    var formStartTime = Date.now();
    var minFormTime = 1500;

    function showNotification(message, type) {
        $('.otp-modal-content .notice').remove();
        var notification = $('<div class="notice notice-' + type + '"><p></p></div>');
        notification.find('p').text(message);
        modal.find('.otp-modal-content').prepend(notification);
        setTimeout(function () {
            notification.fadeOut(function () { $(this).remove(); });
        }, 5000);
    }

    function formatTime(seconds) {
        var minutes = Math.floor(seconds / 60);
        var remainingSeconds = seconds % 60;
        return minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
    }

    function startCountdown(duration) {
        if (countdownTimer) { clearInterval(countdownTimer); }
        var timer = duration;
        resendButton.prop('disabled', true);
        resendInfo.show();
        countdownTimer = setInterval(function () {
            timer--;
            if (timer <= 0) {
                clearInterval(countdownTimer);
                resendButton.prop('disabled', false);
                resendInfo.hide();
            } else {
                resendInfo.text('Please wait ' + formatTime(timer) + ' before requesting a new code');
            }
        }, 1000);
    }

    function refreshNonce() {
        return $.ajax({
            url: wp_email_otp.ajax_url,
            type: 'POST',
            data: { action: wp_email_otp.refresh_nonce_action }
        });
    }

    function sendOTP(email, isResend) {
        if (!wp_email_otp || !wp_email_otp.nonce) {
            showNotification('Security token is missing. Please refresh the page.', 'error');
            return;
        }

        var $button = isResend ? resendButton : $('#generate-otp');
        $button.prop('disabled', true).text('Sending...');

        $.ajax({
            url: wp_email_otp.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_otp',
                email: email,
                nonce: wp_email_otp.nonce,
                website: $('#website').val()
            }
        }).done(function (response) {
            if (response.success) {
                if (!isResend) {
                    currentEmail = email;
                    emailForm.hide();
                    otpForm.show();
                }
                showNotification(response.data.message, 'success');
                if (response.data.attempts_remaining < 3) {
                    resendInfo.show();
                    resendAttempts.text(response.data.attempts_remaining);
                }
                if (response.data.attempts_remaining <= 0) {
                    startCountdown(response.data.wait_time || 1800);
                }
            } else {
                showNotification(response.data, 'error');
            }
        }).fail(function (xhr) {
            handleAjaxFailure(xhr, function () { sendOTP(email, isResend); });
        }).always(function () {
            $button.prop('disabled', false).text(isResend ? 'Resend Code' : 'Send me a Code');
        });
    }

    function verifyOTP() {
        var otp = $('#otp_code').val();
        if (!otp) { showNotification('Please enter the code', 'error'); return; }
        if (!currentEmail) { showNotification('Session expired. Please try again.', 'error'); return; }

        var $button = $('#verify-otp');
        $button.prop('disabled', true).text('Verifying...');

        $.ajax({
            url: wp_email_otp.ajax_url,
            type: 'POST',
            data: {
                action: 'verify_otp',
                email: currentEmail,
                otp: otp,
                nonce: wp_email_otp.nonce,
                website_verify: $('#website_verify').val()
            }
        }).done(function (response) {
            if (response.success) {
                showNotification('Login successful. Redirecting...', 'success');
                setTimeout(function () {
                    window.location.href = response.data.redirect || window.location.href;
                }, 800);
            } else {
                showNotification(response.data, 'error');
                $button.prop('disabled', false).text('Submit');
            }
        }).fail(function (xhr) {
            handleAjaxFailure(xhr, verifyOTP);
            $button.prop('disabled', false).text('Submit');
        });
    }

    function handleAjaxFailure(xhr, retry) {
        if (xhr.responseText && (xhr.responseText.indexOf('Security check failed') !== -1 || xhr.responseText.indexOf('nonce') !== -1)) {
            refreshNonce().done(function (response) {
                if (response && response.success) {
                    wp_email_otp.nonce = response.data.nonce;
                    retry();
                } else {
                    showNotification('Security token expired. Please reload the page.', 'error');
                }
            }).fail(function () {
                showNotification('Security token expired. Please reload the page.', 'error');
            });
            return;
        }
        showNotification('An error occurred. Please try again.', 'error');
    }

    $(document).on('click', '#trigger-otp-modal', function (e) {
        e.preventDefault();
        modal.fadeIn();
        emailForm.show();
        otpForm.hide();
        $('#user_email').val('');
        $('#otp_code').val('');
        resendInfo.hide();
        resendButton.prop('disabled', false);
        currentEmail = '';
        formStartTime = Date.now();
        refreshNonce().done(function (response) {
            if (response && response.success) { wp_email_otp.nonce = response.data.nonce; }
        });
        if (countdownTimer) { clearInterval(countdownTimer); }
    });

    $(document).on('click keydown', '.otp-modal-close', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') { return; }
        modal.fadeOut();
        if (countdownTimer) { clearInterval(countdownTimer); }
    });

    $(window).on('click', function (e) {
        if ($(e.target).is(modal)) {
            modal.fadeOut();
            if (countdownTimer) { clearInterval(countdownTimer); }
        }
    });

    $(document).on('click', '#generate-otp', function () {
        var email = $('#user_email').val();
        if (!email) { showNotification('Please enter your email address', 'error'); return; }
        if (Date.now() - formStartTime < minFormTime) {
            showNotification('Please wait a moment before submitting', 'error');
            return;
        }
        sendOTP(email, false);
    });

    $(document).on('click', '#resend-otp', function () {
        if (!currentEmail) { showNotification('Please enter your email address', 'error'); return; }
        sendOTP(currentEmail, true);
    });

    $(document).on('click', '#verify-otp', verifyOTP);

    $(document).on('input', '#otp_code', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
