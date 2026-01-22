jQuery(document).ready(function($) {
    var importing = false;
    var currentSupplierIndex = 0;
    var currentOffset = 0;
    var totalStats = { created: 0, updated: 0, trashed: 0, errors: 0 };
    var allResults = {};
    var singleSupplierMode = false;
    var suppliersToProcess = [];

    // Start full import button
    $('#start-import').on('click', function() {
        if (importing) return;

        if (!confirm('Start full import? This will download XMLs and sync all products.')) {
            return;
        }

        singleSupplierMode = false;
        suppliersToProcess = xmlImporter.suppliers;
        startImport();
    });

    // Single supplier import buttons
    $('.import-single-supplier').on('click', function() {
        if (importing) return;

        var supplier = $(this).data('supplier');

        if (!confirm('Import ' + supplier + ' products only?')) {
            return;
        }

        singleSupplierMode = true;
        suppliersToProcess = [supplier];
        startImport();
    });

    // Cancel button
    $('#cancel-import').on('click', function() {
        if (confirm('Cancel import?')) {
            importing = false;
            $('#cancel-import').hide();
            $('#start-import').show();
            addLog('Import cancelled by user');
        }
    });

    // Clear logs button
    $('#clear-logs-btn').on('click', function() {
        if (!confirm('Clear all logs?')) {
            return;
        }

        var $button = $(this);
        var $spinner = $button.siblings('.spinner');
        var $message = $('#logs-message');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $message.html('');

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'xml_import_clear_logs',
                nonce: xmlImporter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#logs-content').remove();
                    $('#no-logs-message').remove();
                    $('#logs-container').html('<p id="no-logs-message">No logs available.</p>');
                    $message.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                } else {
                    $message.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function() {
                $message.html('<span style="color: red;">✗ Error clearing logs</span>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');

                setTimeout(function() {
                    $message.fadeOut(function() {
                        $(this).html('').show();
                    });
                }, 3000);
            }
        });
    });

    // Settings form
    $('#settings-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $spinner = $form.find('.spinner');
        var $message = $('#settings-message');

        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $message.html('');

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'xml_import_save_settings',
                nonce: xmlImporter.nonce,
                markup: $form.find('input[name^="markup"]').serializeArray().reduce(function(obj, item) {
                    var key = item.name.match(/\[(.*?)\]/)[1];
                    obj[key] = item.value;
                    return obj;
                }, {}),
                batch_size: $form.find('input[name="batch_size"]').val(),
                cron_enabled: $form.find('input[name="cron_enabled"]').is(':checked') ? '1' : ''
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                } else {
                    $message.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function() {
                $message.html('<span style="color: red;">✗ Error saving settings</span>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');

                setTimeout(function() {
                    $message.fadeOut(function() {
                        $(this).html('').show();
                    });
                }, 3000);
            }
        });
    });

    function startImport() {
        importing = true;
        currentSupplierIndex = 0;
        currentOffset = 0;
        totalStats = { created: 0, updated: 0, trashed: 0, errors: 0 };
        allResults = {};

        $('#start-import').hide();
        $('.import-single-supplier').prop('disabled', true);
        $('#cancel-import').show();
        $('#import-progress').show();
        $('#import-log').html('');

        addLog('Starting import...');

        // Step 1: Download XMLs (only if full import)
        if (singleSupplierMode) {
            addLog('Single supplier mode - using existing XML files');
            processNextSupplier();
        } else {
            downloadXMLs();
        }
    }

    function downloadXMLs() {
        addLog('Downloading XML feeds...');
        updateProgress('Downloading XMLs', 0, 100);

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'xml_import_download',
                nonce: xmlImporter.nonce
            },
            success: function(response) {
                if (response.success) {
                    addLog('XML feeds downloaded successfully');
                    // Start processing suppliers
                    processNextSupplier();
                } else {
                    addLog('Error downloading XMLs: ' + response.data.message, 'error');
                    finishImport(false);
                }
            },
            error: function(xhr) {
                addLog('AJAX error downloading XMLs: ' + xhr.statusText, 'error');
                finishImport(false);
            }
        });
    }

    function processNextSupplier() {
        if (!importing || currentSupplierIndex >= suppliersToProcess.length) {
            finishImport(true);
            return;
        }

        var supplier = suppliersToProcess[currentSupplierIndex];
        currentOffset = 0;

        addLog('Processing ' + supplier + '...');
        processBatch(supplier);
    }

    function processBatch(supplier) {
        if (!importing) {
            return;
        }

        console.log('Processing batch:', supplier, 'offset:', currentOffset);

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            timeout: 180000, // 3 minutes timeout
            data: {
                action: 'xml_import_batch',
                nonce: xmlImporter.nonce,
                supplier: supplier,
                offset: currentOffset
            },
            success: function(response) {
                console.log('Batch response:', response);
                if (response.success) {
                    var data = response.data;

                    // Update progress
                    var percent = data.total > 0 ? Math.round((data.offset / data.total) * 100) : 100;
                    updateProgress(supplier, data.offset, data.total, percent);

                    // Update stats
                    if (data.stats) {
                        totalStats.created += data.stats.created || 0;
                        totalStats.updated += data.stats.updated || 0;
                        totalStats.trashed += data.stats.trashed || 0;
                        totalStats.errors += data.stats.errors || 0;
                        updateStats();
                    }

                    addLog(supplier + ': Processed ' + data.offset + '/' + data.total + ' products');

                    if (data.complete) {
                        // Log trashed count if any
                        if (data.stats.trashed > 0) {
                            addLog(supplier + ': Trashed ' + data.stats.trashed + ' missing products');
                        }

                        // Store results for this supplier
                        allResults[supplier] = {
                            total_products: data.total,
                            created: data.stats.created || 0,
                            updated: data.stats.updated || 0,
                            trashed: data.stats.trashed || 0,
                            errors: data.stats.errors || 0
                        };

                        addLog(supplier + ' completed: ' + data.total + ' products processed');

                        // Move to next supplier
                        currentSupplierIndex++;
                        processNextSupplier();
                    } else {
                        // Continue with next batch
                        currentOffset = data.offset;
                        processBatch(supplier);
                    }
                } else {
                    addLog('Error processing ' + supplier + ': ' + response.data.message, 'error');
                    // Continue with next supplier anyway
                    currentSupplierIndex++;
                    processNextSupplier();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr, status, error);

                var errorMsg = 'AJAX error processing ' + supplier + ': ';
                if (status === 'timeout') {
                    errorMsg += 'Request timeout (3 minutes). Continuing...';
                    addLog(errorMsg, 'error');
                    // Try to continue anyway
                    currentSupplierIndex++;
                    processNextSupplier();
                } else {
                    errorMsg += xhr.statusText + ' - ' + error;
                    if (xhr.responseText) {
                        console.error('Response text:', xhr.responseText);
                        errorMsg += ' (check console for details)';
                    }
                    addLog(errorMsg, 'error');
                    // Continue with next supplier
                    currentSupplierIndex++;
                    processNextSupplier();
                }
            }
        });
    }

    function updateProgress(supplier, current, total, percent) {
        $('#current-supplier').text(supplier);
        $('#progress-current').text(current);
        $('#progress-total').text(total);

        if (percent !== undefined) {
            $('#progress-percent').text(percent);
            $('#progress-bar').css('width', percent + '%');
        }
    }

    function updateStats() {
        $('#stat-created').text(totalStats.created);
        $('#stat-updated').text(totalStats.updated);
        $('#stat-trashed').text(totalStats.trashed);
        $('#stat-errors').text(totalStats.errors);
    }

    function addLog(message, type) {
        var timestamp = new Date().toLocaleTimeString();
        var color = type === 'error' ? 'red' : 'inherit';
        var $log = $('#import-log');

        $log.append('<div style="color: ' + color + ';">[' + timestamp + '] ' + message + '</div>');
        $log.scrollTop($log[0].scrollHeight);
    }

    function finishImport(success) {
        importing = false;

        $('#cancel-import').hide();
        $('#start-import').show();
        $('.import-single-supplier').prop('disabled', false);

        if (success) {
            addLog('Import completed successfully!');

            // Save results
            $.post(xmlImporter.ajaxUrl, {
                action: 'xml_import_save_results',
                nonce: xmlImporter.nonce,
                results: allResults
            });

            // Reload page after 2 seconds to show results
            setTimeout(function() {
                location.reload();
            }, 2000);
        } else {
            addLog('Import failed or was cancelled', 'error');
        }
    }
});
