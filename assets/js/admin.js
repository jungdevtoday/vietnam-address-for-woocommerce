jQuery(document).ready(function($) {
    'use strict';

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    // Test central data server connection
    $('#test-server').on('click', function(e) {
        e.preventDefault();

        const $button = $(this);
        const $status = $('#server-status');
        const url = $('#vn_address_wc_server_url').val();

        $button.prop('disabled', true).text(vnAddressAdmin.i18n.testing);
        $status.text('').removeClass('success error');

        $.ajax({
            url: vnAddressAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'vn_address_test_server',
                nonce: vnAddressAdmin.nonce,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').text('✓ ' + response.data.message + ' (' + response.data.count + ' provinces)');
                } else {
                    $status.addClass('error').text('✗ ' + response.data.message);
                }
            },
            error: function() {
                $status.addClass('error').text('✗ ' + vnAddressAdmin.i18n.connection_error);
            },
            complete: function() {
                $button.prop('disabled', false).text(vnAddressAdmin.i18n.test_server);
            }
        });
    });

    // Clear cached central-server responses
    $('#clear-server-cache').on('click', function(e) {
        e.preventDefault();

        const $button = $(this);
        const $status = $('#server-status');

        if (!confirm(vnAddressAdmin.i18n.clear_cache_confirm)) {
            return;
        }

        $button.prop('disabled', true).text(vnAddressAdmin.i18n.clearing);
        $status.text('').removeClass('success error');

        $.ajax({
            url: vnAddressAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'vn_address_clear_server_cache',
                nonce: vnAddressAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').text('✓ ' + response.data.message);
                } else {
                    $status.addClass('error').text('✗ ' + vnAddressAdmin.i18n.error_clearing_cache);
                }
            },
            error: function() {
                $status.addClass('error').text('✗ ' + vnAddressAdmin.i18n.error_clearing_cache);
            },
            complete: function() {
                $button.prop('disabled', false).text(vnAddressAdmin.i18n.clear_cache);
            }
        });
    });

    // Convert orders
    $('#convert-orders').on('click', function(e) {
        e.preventDefault();

        const $button = $(this);
        const $progress = $('#conversion-progress');
        const $results = $('#conversion-results');
        const $status = $('#converter-status');

        if (!confirm(vnAddressAdmin.i18n.convert_confirm)) {
            return;
        }

        $button.prop('disabled', true).text(vnAddressAdmin.i18n.converting);
        $progress.show();
        $results.html('');
        $status.html('');

        let currentProgress = 0;
        const progressInterval = setInterval(function() {
            if (currentProgress < 90) {
                currentProgress += 2;
                updateProgress(currentProgress);
            }
        }, 200);

        $.ajax({
            url: vnAddressAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'vn_address_convert_orders',
                nonce: vnAddressAdmin.nonce
            },
            success: function(response) {
                clearInterval(progressInterval);
                updateProgress(100);

                if (response.success) {
                    const data = response.data;
                    $status.html('<div class="notice notice-success"><p>' + escapeHtml(data.message) + '</p></div>');

                    let resultsHtml = '<h3>' + escapeHtml(vnAddressAdmin.i18n.conversion_results) + '</h3>';
                    resultsHtml += '<div class="result-item success">✓ ' + escapeHtml(vnAddressAdmin.i18n.converted) + ' ' + escapeHtml(data.converted) + '</div>';
                    if (data.ambiguous > 0) {
                        resultsHtml += '<div class="result-item warning">⚠ ' + escapeHtml(vnAddressAdmin.i18n.needs_review) + ' ' + escapeHtml(data.ambiguous) + '</div>';
                    }
                    if (data.failed > 0) {
                        resultsHtml += '<div class="result-item error">✗ ' + escapeHtml(vnAddressAdmin.i18n.failed) + ' ' + escapeHtml(data.failed) + '</div>';
                    }

                    if (data.errors && data.errors.length > 0) {
                        resultsHtml += '<h4>' + escapeHtml(vnAddressAdmin.i18n.errors) + '</h4>';
                        data.errors.forEach(function(error) {
                            resultsHtml += '<div class="result-item error">' + escapeHtml(error) + '</div>';
                        });
                    }

                    $results.html(resultsHtml);
                } else {
                    $status.html('<div class="notice notice-error"><p>' + escapeHtml(response.data.message) + '</p></div>');
                }
            },
            error: function() {
                clearInterval(progressInterval);
                $status.html('<div class="notice notice-error"><p>' + escapeHtml(vnAddressAdmin.i18n.conversion_failed) + '</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text(vnAddressAdmin.i18n.convert_all_orders);
                setTimeout(function() {
                    $progress.fadeOut();
                }, 2000);
            }
        });
    });

    function updateProgress(percent) {
        $('#conversion-progress .vn-progress-fill').css('width', percent + '%');
        $('#conversion-progress .vn-progress-text').text(percent + '%');
    }
});
