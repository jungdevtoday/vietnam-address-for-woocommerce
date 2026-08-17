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

    // Convert orders - runs in bounded batches so a store with a very large
    // number of old-structure orders never has a single request handle more
    // than one batch's worth (see VN_Address_Admin::CONVERT_BATCH_SIZE).
    function ajaxPost(action, extraData) {
        return $.ajax({
            url: vnAddressAdmin.ajax_url,
            type: 'POST',
            data: Object.assign({ action: action, nonce: vnAddressAdmin.nonce }, extraData || {})
        });
    }

    function renderConversionResults($results, totals) {
        let resultsHtml = '<h3>' + escapeHtml(vnAddressAdmin.i18n.conversion_results) + '</h3>';
        resultsHtml += '<div class="result-item success">✓ ' + escapeHtml(vnAddressAdmin.i18n.converted) + ' ' + escapeHtml(totals.converted) + '</div>';
        if (totals.ambiguous > 0) {
            resultsHtml += '<div class="result-item warning">⚠ ' + escapeHtml(vnAddressAdmin.i18n.needs_review) + ' ' + escapeHtml(totals.ambiguous) + '</div>';
        }
        if (totals.failed > 0) {
            resultsHtml += '<div class="result-item error">✗ ' + escapeHtml(vnAddressAdmin.i18n.failed) + ' ' + escapeHtml(totals.failed) + '</div>';
        }

        if (totals.errors.length > 0) {
            resultsHtml += '<h4>' + escapeHtml(vnAddressAdmin.i18n.errors) + '</h4>';
            totals.errors.forEach(function(error) {
                resultsHtml += '<div class="result-item error">' + escapeHtml(error) + '</div>';
            });
        }

        $results.html(resultsHtml);
    }

    $('#convert-orders').on('click', async function(e) {
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
        updateProgress(0);

        try {
            const countResponse = await ajaxPost('vn_address_convert_count');
            if (!countResponse.success) {
                throw new Error(countResponse.data && countResponse.data.message);
            }

            const total = countResponse.data.total;

            if (total === 0) {
                $status.html('<div class="notice notice-success"><p>' + escapeHtml(vnAddressAdmin.i18n.no_orders_to_convert) + '</p></div>');
                return;
            }

            const totals = { converted: 0, ambiguous: 0, failed: 0, errors: [] };
            let processed = 0;
            let hasMore = true;
            let stalled = false;

            // Safety cap: the loop should end once the server reports a
            // partial (non-full) batch, but this bounds iteration count
            // regardless, so an unexpected server-side state can never spin
            // the browser forever.
            const maxIterations = total + 100;

            for (let i = 0; i < maxIterations && hasMore; i++) {
                const batchResponse = await ajaxPost('vn_address_convert_batch');
                if (!batchResponse.success) {
                    throw new Error(batchResponse.data && batchResponse.data.message);
                }

                const data = batchResponse.data;
                totals.converted += data.converted;
                totals.ambiguous += data.ambiguous;
                totals.failed += data.failed;
                totals.errors = totals.errors.concat(data.errors);
                processed += data.processed;
                hasMore = data.has_more;

                updateProgress(Math.min(100, Math.round((processed / total) * 100)));
                $button.text(vnAddressAdmin.i18n.converting + ' (' + processed + '/' + total + ')');

                if (data.processed === 0) {
                    // A full-sized-but-empty batch shouldn't happen, but stop
                    // rather than loop endlessly if it ever does.
                    stalled = true;
                    break;
                }
            }

            updateProgress(100);

            if (stalled || hasMore) {
                $status.html('<div class="notice notice-error"><p>' + escapeHtml(vnAddressAdmin.i18n.conversion_failed) + '</p></div>');
            } else {
                $status.html('<div class="notice notice-success"><p>' + escapeHtml(vnAddressAdmin.i18n.conversion_results) + '</p></div>');
            }
            renderConversionResults($results, totals);
        } catch (err) {
            $status.html('<div class="notice notice-error"><p>' + escapeHtml(vnAddressAdmin.i18n.conversion_failed) + '</p></div>');
        } finally {
            $button.prop('disabled', false).text(vnAddressAdmin.i18n.convert_now);
            setTimeout(function() {
                $progress.fadeOut();
            }, 2000);
        }
    });

    function updateProgress(percent) {
        $('#conversion-progress .vn-progress-fill').css('width', percent + '%');
        $('#conversion-progress .vn-progress-text').text(percent + '%');
    }
});
