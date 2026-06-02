jQuery(document).ready(function($) {
    var importing = false;
    var currentSupplierIndex = 0;
    var currentOffset = 0;
    var totalStats = { created: 0, updated: 0, trashed: 0, errors: 0 };
    var allResults = {};
    var suppliersToProcess = [];
    var supplierLimits = {}; // Store limits for each supplier
    var freshImport = false; // True only on first batch call per supplier (user-initiated)

    // Fast Stock & Price Sync button (Daily Stock Sync)
    $('#fast-stock-sync').on('click', function() {
        if (importing) return;

        if (!confirm('Run fast stock & price sync?\n\nThis will update ONLY stock quantities and prices for all products (5-10 minutes).\n\nNo AI processing, no images, no categories.')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('⏳ Syncing...');

        addLog('Starting fast stock & price sync...');
        $('#import-progress').show();

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            timeout: 600000, // 10 minutes
            data: {
                action: 'xml_import_fast_sync',
                nonce: xmlImporter.nonce
            },
            success: function(response) {
                if (response.success) {
                    addLog('✓ Fast stock sync completed!', 'success');

                    // Display results
                    if (response.data.results) {
                        $.each(response.data.results, function(supplier, stats) {
                            if (stats.error) {
                                addLog('✗ ' + supplier + ': ' + stats.error, 'error');
                            } else {
                                addLog('✓ ' + supplier + ': Updated=' + stats.updated + ', Not found=' + stats.not_found + ', Errors=' + stats.errors);
                            }
                        });
                    }

                    alert('Stock & price sync completed!\n\nCheck the log for details.');
                } else {
                    addLog('✗ Error: ' + response.data.message, 'error');
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr) {
                addLog('✗ AJAX error: ' + xhr.statusText, 'error');
                alert('Error during sync. Check console for details.');
            },
            complete: function() {
                $button.prop('disabled', false).text('⚡ Fast Stock & Price Sync');
            }
        });
    });

    // Detect New Products button
    $('#detect-new-products').on('click', function() {
        if (importing) return;

        if (!confirm('Detect new products?\n\nThis will check for products in XML that have not been AI-enhanced yet.')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('⏳ Detecting...');

        addLog('Detecting new products...');
        $('#import-progress').show();

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'xml_import_detect_new',
                nonce: xmlImporter.nonce
            },
            success: function(response) {
                if (response.success) {
                    addLog('✓ Detection completed!', 'success');

                    // Display results
                    if (response.data.results) {
                        var totalNew = 0;
                        var totalDeleted = 0;

                        $.each(response.data.results, function(supplier, stats) {
                            if (stats.error) {
                                addLog('✗ ' + supplier + ': ' + stats.error, 'error');
                            } else {
                                totalNew += stats.new_count;
                                totalDeleted += stats.deleted_count;
                                addLog('✓ ' + supplier + ': New=' + stats.new_count + ', Deleted=' + stats.deleted_count);
                            }
                        });

                        var message = 'Detection completed!\n\n';
                        message += 'Total new products: ' + totalNew + '\n';
                        message += 'Total deleted products: ' + totalDeleted + '\n\n';

                        if (totalNew > 0) {
                            message += 'Run AI Enhancement to process new products.';
                        } else {
                            message += 'No new products found.';
                        }

                        alert(message);
                    } else if (response.data.supplier) {
                        alert('Found ' + response.data.new_count + ' new products for ' + response.data.supplier);
                    }
                } else {
                    addLog('✗ Error: ' + response.data.message, 'error');
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr) {
                addLog('✗ AJAX error: ' + xhr.statusText, 'error');
                alert('Error during detection. Check console for details.');
            },
            complete: function() {
                $button.prop('disabled', false).text('🔍 Detect New Products');
            }
        });
    });

    // Download XMLs button
    $('#download-xmls').on('click', function() {
        if (importing) return;

        if (!confirm('Download XML files from suppliers?')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('⏳ Downloading...');

        addLog('Downloading XML feeds...');
        $('#import-progress').show();
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
                    addLog('✓ XML feeds downloaded successfully!', 'success');
                    alert('XMLs downloaded successfully! You can now run AI Enhancement.');
                } else {
                    addLog('✗ Error downloading XMLs: ' + response.data.message, 'error');
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr) {
                addLog('✗ AJAX error: ' + xhr.statusText, 'error');
                alert('Error downloading XMLs. Check console for details.');
            },
            complete: function() {
                $button.prop('disabled', false).text('📥 Download XMLs Only');
            }
        });
    });

    // AI Enhancement button
    $('#run-enhancement').on('click', function() {
        if (importing) return;

        if (!confirm('Run AI Enhancement? This will process XMLs with AI to optimize titles, descriptions, tags, and categories. This may take 10-30 minutes.')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('⏳ Processing...');

        addLog('Starting AI Enhancement...');
        $('#import-progress').show();

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'xml_import_enhance',
                nonce: xmlImporter.nonce
            },
            success: function(response) {
                if (response.success) {
                    addLog('✓ AI Enhancement started in background!', 'success');
                    addLog('⏱️ This process may take 10-30 minutes. Check back later.', 'info');
                    alert('AI Enhancement started!\n\nThis process runs in the background and may take 10-30 minutes.\n\nYou can close this page and come back later to import the enhanced XMLs.');
                } else {
                    addLog('✗ Error: ' + response.data.message, 'error');
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr) {
                addLog('✗ AJAX error: ' + xhr.statusText, 'error');
                alert('Error starting AI Enhancement. Check console for details.');
            },
            complete: function() {
                $button.prop('disabled', false).text('🤖 Run AI Enhancement');
            }
        });
    });

    // Start full import button - Import All Suppliers
    $('#start-import').on('click', function() {
        if (importing) return;

        // Read all limits
        var limits = {};
        $('.supplier-limit').each(function() {
            var supplier = $(this).attr('id').replace('limit-', '');
            limits[supplier] = parseInt($(this).val()) || 0;
        });

        var confirmMsg = 'Import ALL suppliers?\n\n';
        var hasLimits = false;
        $.each(limits, function(supplier, limit) {
            if (limit > 0) {
                confirmMsg += supplier + ': ' + limit + ' products\n';
                hasLimits = true;
            }
        });
        if (!hasLimits) {
            confirmMsg += 'All products will be imported for all suppliers.';
        }
        confirmMsg += '\n\nThis will download XMLs and import products to WooCommerce.';

        if (!confirm(confirmMsg)) {
            return;
        }

        // Store limits for all suppliers
        supplierLimits = limits;

        // Start import for all suppliers
        suppliersToProcess = ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
        addLog('Starting import for all suppliers...');
        $('#import-progress').show();
        startImport();
    });

    // Single supplier import buttons
    $('.import-single-supplier').on('click', function() {
        if (importing) return;

        var supplier = $(this).data('supplier');
        var limit = parseInt($('#limit-' + supplier).val()) || 0;

        var confirmMsg = 'Import ' + supplier + '?';
        if (limit > 0) {
            confirmMsg += '\n\nLimit: ' + limit + ' products';
        } else {
            confirmMsg += '\n\nAll products will be imported';
        }
        confirmMsg += '\n\nThis will download XML and import products to WooCommerce.';

        if (!confirm(confirmMsg)) {
            return;
        }

        // Store limit for this supplier
        supplierLimits = {};
        supplierLimits[supplier] = limit;

        // Start import for single supplier
        suppliersToProcess = [supplier];
        addLog('Starting import for ' + supplier + (limit > 0 ? ' (limit: ' + limit + ' products)' : '') + '...');
        $('#import-progress').show();
        startImport();
    });

    // OLD SMART IMPORT CODE - DISABLED
    $('.import-single-supplier-DISABLED').on('click', function() {
        if (importing) return;

        var supplier = $(this).data('supplier');

        if (!confirm('Smart import ' + supplier + '?\n\nThis will:\n1. Download latest XML\n2. Detect new products\n3. AI enhance only NEW products\n4. Fast sync stock/price for existing products')) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true);

        addLog('Starting smart import for ' + supplier + '...');
        $('#import-progress').show();

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            timeout: 120000, // 2 minutes
            data: {
                action: 'xml_import_smart',
                nonce: xmlImporter.nonce,
                supplier: supplier
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;

                    addLog('✓ Smart import initiated for ' + supplier, 'success');
                    addLog('  New products: ' + data.new_count);
                    addLog('  Existing products: ' + data.existing_count);

                    if (data.deleted_count > 0) {
                        addLog('  Deleted products: ' + data.deleted_count);
                    }

                    if (data.ai_enhancement === 'started') {
                        addLog('  AI enhancement running in background for ' + data.new_count + ' new products...');
                        addLog('  You can now start the batch import for enhanced products.');
                    } else if (data.sync_stats) {
                        addLog('  Stock/price updated for ' + data.sync_stats.updated + ' products');
                    }

                    alert(data.message);

                    // If new products found, start batch import after AI finishes
                    if (data.ai_enhancement === 'started') {
                        setTimeout(function() {
                            if (confirm('AI enhancement may take time. Start batch import now?\n\nNote: Import will only succeed after AI enhancement completes.')) {
                                suppliersToProcess = [supplier];
                                startImport();
                            }
                        }, 2000);
                    }

                } else {
                    addLog('✗ Error: ' + response.data.message, 'error');
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr) {
                addLog('✗ AJAX error: ' + xhr.statusText, 'error');
                alert('Error during smart import. Check console.');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
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

    function smartImportAllSuppliers() {
        importing = true;
        currentSupplierIndex = 0;
        var suppliers = xmlImporter.suppliers;

        $('#start-import').hide();
        $('.import-single-supplier').prop('disabled', true);
        $('#cancel-import').show();
        $('#import-progress').show();
        $('#import-log').html('');

        addLog('Starting smart import for all suppliers...');

        function processNextSupplier() {
            if (currentSupplierIndex >= suppliers.length) {
                addLog('✓ Smart import completed for all suppliers!', 'success');
                finishImport(true);
                return;
            }

            var supplier = suppliers[currentSupplierIndex];
            addLog('Processing ' + supplier + '...');

            $.ajax({
                url: xmlImporter.ajaxUrl,
                type: 'POST',
                timeout: 120000,
                data: {
                    action: 'xml_import_smart',
                    nonce: xmlImporter.nonce,
                    supplier: supplier
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        addLog('✓ ' + supplier + ': New=' + data.new_count + ', Existing=' + data.existing_count);

                        if (data.ai_enhancement === 'started') {
                            addLog('  AI enhancement started for ' + data.new_count + ' new products');
                        } else if (data.sync_stats) {
                            addLog('  Updated stock/price for ' + data.sync_stats.updated + ' products');
                        }

                        // Move to next supplier
                        currentSupplierIndex++;
                        setTimeout(processNextSupplier, 1000);
                    } else {
                        addLog('✗ ' + supplier + ': ' + response.data.message, 'error');
                        currentSupplierIndex++;
                        setTimeout(processNextSupplier, 1000);
                    }
                },
                error: function(xhr) {
                    addLog('✗ ' + supplier + ': AJAX error', 'error');
                    currentSupplierIndex++;
                    setTimeout(processNextSupplier, 1000);
                }
            });
        }

        processNextSupplier();
    }

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

        addLog('Starting import of AI-enhanced XMLs...');

        // Start processing suppliers directly (no download step)
        processNextSupplier();
    }

    function processNextSupplier() {
        if (!importing || currentSupplierIndex >= suppliersToProcess.length) {
            finishImport(true);
            return;
        }

        var supplier = suppliersToProcess[currentSupplierIndex];
        currentOffset = 0;
        freshImport = true;

        addLog('Processing ' + supplier + '...');
        processBatch(supplier);
    }

    function processBatch(supplier) {
        if (!importing) {
            return;
        }

        var limit = supplierLimits[supplier] || 0;
        var isFresh = freshImport ? 1 : 0;
        freshImport = false; // Only first call is fresh
        console.log('Processing batch:', supplier, 'offset:', currentOffset, 'limit:', limit, 'fresh:', isFresh);

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            timeout: 600000, // 10 minutes timeout
            data: {
                action: 'xml_import_batch',
                nonce: xmlImporter.nonce,
                supplier: supplier,
                offset: currentOffset,
                limit: limit,
                fresh_import: isFresh
            },
            success: function(response) {
                console.log('Batch response:', response);

                // Check if AI processing is in progress (priority check)
                if (response.data && response.data.ai_processing) {
                    var current = response.data.offset || response.data.processed || 0;
                    var total = response.data.total || 0;
                    var percent = total > 0 ? Math.round((current / total) * 100) : 0;

                    if (response.data.ai_started) {
                        addLog(supplier + ': AI enhancement started. This will take several minutes...', 'info');
                    } else {
                        addLog(supplier + ': AI processing ' + current + '/' + total + ' (' + percent + '%)', 'info');
                    }

                    // Update progress display
                    updateProgress(supplier + ' (AI)', current, total, percent);

                    // Start polling for AI progress
                    pollAIProgress(supplier);
                } else if (response.success) {
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
                    // Error response
                    if (response.data && response.data.ai_processing) {
                        var current = response.data.offset || response.data.processed || 0;
                        var total = response.data.total || 0;
                        var percent = total > 0 ? Math.round((current / total) * 100) : 0;

                        if (response.data.ai_started) {
                            addLog(supplier + ': AI enhancement started. This will take several minutes...', 'info');
                        } else {
                            addLog(supplier + ': AI processing ' + current + '/' + total + ' (' + percent + '%)', 'info');
                        }

                        // Update progress display
                        updateProgress(supplier + ' (AI)', current, total, percent);

                        // Start polling for AI progress
                        pollAIProgress(supplier);
                    } else {
                        addLog('Error processing ' + supplier + ': ' + response.data.message, 'error');
                        // Continue with next supplier anyway
                        currentSupplierIndex++;
                        processNextSupplier();
                    }
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

    function pollAIProgress(supplier) {
        if (!importing) {
            return;
        }

        $.ajax({
            url: xmlImporter.ajaxUrl,
            type: 'POST',
            data: {
                action: 'xml_import_check_ai_progress',
                nonce: xmlImporter.nonce,
                supplier: supplier
            },
            success: function(response) {
                if (response.success) {
                    var progress = response.data;
                    console.log('AI Progress:', progress);

                    if (progress.status === 'complete') {
                        addLog(supplier + ': AI enhancement completed!', 'success');
                        // Reset offset and retry batch processing now that AI is complete
                        currentOffset = 0;
                        processBatch(supplier);
                    } else if (progress.status === 'processing') {
                        // Update progress display
                        var current = progress.current || 0;
                        var total = progress.total || 0;
                        var aiPercent = Math.round(progress.percent || 0);

                        updateProgress(supplier + ' (AI)', current, total, aiPercent);

                        // Only log every 10% or so to avoid spam
                        if (aiPercent % 10 === 0 || current === total) {
                            addLog(supplier + ': AI processing ' + current + '/' + total + ' (' + aiPercent + '%)', 'info');
                        }

                        // Poll again in 5 seconds
                        setTimeout(function() {
                            pollAIProgress(supplier);
                        }, 5000);
                    } else if (progress.status === 'starting') {
                        // AI is starting up, wait a bit longer before polling
                        addLog(supplier + ': AI enhancement starting...', 'info');
                        setTimeout(function() {
                            pollAIProgress(supplier);
                        }, 10000); // Wait 10 seconds for Python to start
                    } else {
                        // Unknown status, check if enhanced XML exists
                        addLog(supplier + ': Checking for enhanced XML...', 'info');
                        setTimeout(function() {
                            currentOffset = 0;
                            processBatch(supplier);
                        }, 5000);
                    }
                } else {
                    addLog(supplier + ': Error checking AI progress: ' + response.data.message, 'error');
                    // Retry after delay
                    setTimeout(function() {
                        pollAIProgress(supplier);
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                console.error('AI progress check error:', status, error);
                // Retry after delay
                setTimeout(function() {
                    pollAIProgress(supplier);
                }, 5000);
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

    // Cleanup function to stop AI processing
    function cleanupAIProcessing() {
        if (importing) {
            console.log('Page unload detected - cleaning up AI processing...');

            // Send synchronous request to stop AI processing
            $.ajax({
                url: xmlImporter.ajaxUrl,
                type: 'POST',
                async: false, // Synchronous to ensure it completes before page unload
                data: {
                    action: 'xml_import_stop_ai',
                    nonce: xmlImporter.nonce
                }
            });
        }
    }

    // Handle page unload (close tab, refresh, navigate away)
    $(window).on('beforeunload', function(e) {
        if (importing) {
            cleanupAIProcessing();
            return 'AI processing is running. Are you sure you want to leave?';
        }
    });

    // Handle page visibility change (tab switch)
    $(document).on('visibilitychange', function() {
        if (document.hidden && importing) {
            console.log('Tab hidden - AI will continue in background');
            // Don't stop - allow background processing
        }
    });

});
