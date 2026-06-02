<?php
/**
 * Plugin Name: eboy Product Importer
 * Description: Imports WooCommerce products from supplier XML feeds (Pakoworld, B2BMarkt, LibertaB2B, EstiaHomeArt). Includes a Python-based AI enhancement pipeline (titles, categories, image background removal) and an admin UI for batch / realtime imports.
 * Version: 1.0.0
 * Author: eboy
 * Requires PHP: 8.2
 * Network: false
 * Text Domain: eboy-product-importer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Autoload importer classes (App\Importers\* namespace from includes/)
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\Importers\\') !== 0) {
        return;
    }

    $class = str_replace('App\\Importers\\', '', $class);
    $class = str_replace('\\', '/', $class);

    $file = plugin_dir_path(__FILE__) . 'includes/' . $class . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize admin page with AJAX support
if (is_admin()) {
    $admin_page = new \App\Importers\Admin\AjaxAdminPage();
}

// Register cron hook
add_action('xml_importer_daily_sync', function() {
    try {
        $importer = new \App\Importers\Importer();
        $results = $importer->runFullImport();

        update_option('xml_importer_last_run', [
            'date' => current_time('mysql'),
            'results' => $results,
            'type' => 'auto'
        ]);
    } catch (\Exception $e) {
        error_log('XML Importer Cron Error: ' . $e->getMessage());
    }
});

// Schedule cron if enabled and not already scheduled
if (get_option('xml_importer_cron_enabled', true)) {
    if (!wp_next_scheduled('xml_importer_daily_sync')) {
        $timestamp = strtotime('tomorrow 3:00 AM');
        wp_schedule_event($timestamp, 'daily', 'xml_importer_daily_sync');
    }
}

// Unschedule cron on plugin deactivation
register_deactivation_hook(__FILE__, function() {
    $next = wp_next_scheduled('xml_importer_daily_sync');
    if ($next) {
        wp_unschedule_event($next, 'xml_importer_daily_sync');
    }
});

// Admin notices
add_action('admin_notices', function() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'xml-product-importer') {
        return;
    }

    if (isset($_GET['message'])) {
        $message = $_GET['message'];
        $type = 'success';
        $text = '';

        switch ($message) {
            case 'import_success':
                $text = 'Import completed successfully!';
                break;
            case 'import_error':
                $text = 'Import failed: ' . (isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error');
                $type = 'error';
                break;
            case 'download_success':
                $text = 'XML feeds downloaded successfully!';
                break;
            case 'download_error':
                $text = 'Download failed: ' . (isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error');
                $type = 'error';
                break;
            case 'process_success':
                $text = 'Local XMLs processed successfully!';
                break;
            case 'process_error':
                $text = 'Processing failed: ' . (isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error');
                $type = 'error';
                break;
            case 'settings_saved':
                $text = 'Settings saved successfully!';
                break;
        }

        if ($text) {
            echo '<div class="notice notice-' . $type . ' is-dismissible"><p>' . esc_html($text) . '</p></div>';
        }
    }
});

// WP-CLI commands
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('xml-import', function($args, $assoc_args) {
        $plugin_root = plugin_dir_path(__FILE__);

        $action = isset($args[0]) ? $args[0] : 'full';

        if ($action === 'test-title') {
            $title = isset($args[1]) ? $args[1] : '';
            if (empty($title)) {
                WP_CLI::error('Usage: wp xml-import test-title "Τίτλος Προϊόντος"');
                return;
            }

            $script_dir  = $plugin_root . 'product-ai-processor';
            $venv_python = $script_dir . '/venv/bin/python3';
            $python_bin  = file_exists($venv_python) ? $venv_python : '/usr/bin/python3';
            $main_script = $script_dir . '/main.py';

            $ollama_host = getenv('OLLAMA_HOST') ?: '';
            $env_prefix  = $ollama_host ? 'OLLAMA_HOST=' . escapeshellarg($ollama_host) . ' ' : '';

            $command = sprintf(
                'cd %s && %s%s %s --title %s 2>/dev/null',
                escapeshellarg($script_dir),
                $env_prefix,
                escapeshellarg($python_bin),
                escapeshellarg($main_script),
                escapeshellarg($title)
            );

            $result = trim(shell_exec($command) ?? '');

            WP_CLI::log('Πριν : ' . $title);
            WP_CLI::log('Μετά : ' . ($result ?: '(κανένα αποτέλεσμα)'));
            return;
        }

        // Single-SKU full pipeline: wp xml-import sku <SKU> <supplier>
        if ($action === 'sku') {
            $sku      = isset($args[1]) ? trim($args[1]) : '';
            $supplier = isset($args[2]) ? trim($args[2]) : '';

            $parser_map = [
                'pakoworld'    => '\\App\\Importers\\Parsers\\PakoworldParser',
                'b2bmarkt'     => '\\App\\Importers\\Parsers\\B2BMarktParser',
                'libertab2b'   => '\\App\\Importers\\Parsers\\LibertaParser',
                'estiahomeart' => '\\App\\Importers\\Parsers\\EstiahParser',
            ];

            if (empty($sku) || empty($supplier)) {
                WP_CLI::error('Usage: wp xml-import sku <SKU> <supplier>');
                WP_CLI::error('  Suppliers: ' . implode(', ', array_keys($parser_map)));
                return;
            }

            if (!isset($parser_map[$supplier])) {
                WP_CLI::error("Unknown supplier: {$supplier}. Use: " . implode(', ', array_keys($parser_map)));
                return;
            }

            $xml_dir      = $plugin_root . 'data/xml_files/';
            $script_dir   = $plugin_root . 'product-ai-processor';
            $python_main  = $script_dir . '/main.py';
            $input_xml    = $xml_dir . 'gr/' . $supplier . '.xml';
            $enhanced_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

            $venv_candidates = [
                $script_dir . '/venv/bin/python3',
                $script_dir . '/.venv/bin/python3',
            ];
            $venv_python = null;
            foreach ($venv_candidates as $candidate) {
                if (file_exists($candidate)) {
                    $venv_python = $candidate;
                    break;
                }
            }
            if (!$venv_python) {
                WP_CLI::error('Python venv not found. Tried: ' . implode(', ', $venv_candidates));
                return;
            }

            if (!file_exists($input_xml)) {
                WP_CLI::error("Raw XML not found: {$input_xml}");
                return;
            }

            WP_CLI::log("Step 1/2 — AI processing SKU {$sku} from {$supplier}...");

            $command = sprintf(
                'cd %s && %s %s --mode process --input %s --output %s --skus %s --extend-from-backup --skip-images 2>&1',
                escapeshellarg($script_dir),
                escapeshellarg($venv_python),
                escapeshellarg($python_main),
                escapeshellarg($input_xml),
                escapeshellarg($enhanced_xml),
                escapeshellarg($sku)
            );

            $output      = [];
            $return_code = 0;
            exec($command, $output, $return_code);

            if ($return_code !== 0) {
                WP_CLI::warning('AI processor exited with code ' . $return_code);
                foreach ($output as $line) {
                    WP_CLI::log('  ' . $line);
                }
                WP_CLI::error('AI processing failed. Import aborted.');
                return;
            }

            WP_CLI::log('  AI processing complete.');

            WP_CLI::log("Step 2/2 — Importing {$sku} into WordPress...");

            if (!file_exists($enhanced_xml)) {
                WP_CLI::error("Enhanced XML not found after AI processing: {$enhanced_xml}");
                return;
            }

            $parser_class = $parser_map[$supplier];
            $parser       = new $parser_class($enhanced_xml);
            $products     = $parser->parseProducts();

            $target = null;
            foreach ($products as $p) {
                if ($p->sku === $sku) {
                    $target = $p;
                    break;
                }
            }

            if (!$target) {
                WP_CLI::error("SKU {$sku} not found in enhanced XML after AI processing.");
                return;
            }

            WP_CLI::log("  Product: {$target->name}");

            $sync = new \App\Importers\ProductSync();
            $sync->syncProduct($target);

            $post_id = wc_get_product_id_by_sku($sku);
            WP_CLI::success("Done. SKU={$sku}, post_id=" . ($post_id ?: '?'));
            return;
        }

        // Multi-SKU full pipeline: wp xml-import skus <SKU1,SKU2,...|@file.txt> <supplier>
        if ($action === 'skus') {
            $skus_arg = isset($args[1]) ? trim($args[1]) : '';
            $supplier = isset($args[2]) ? trim($args[2]) : '';

            $parser_map = [
                'pakoworld'    => '\\App\\Importers\\Parsers\\PakoworldParser',
                'b2bmarkt'     => '\\App\\Importers\\Parsers\\B2BMarktParser',
                'libertab2b'   => '\\App\\Importers\\Parsers\\LibertaParser',
                'estiahomeart' => '\\App\\Importers\\Parsers\\EstiahParser',
            ];

            if (empty($skus_arg) || empty($supplier)) {
                WP_CLI::error('Usage: wp xml-import skus <SKU1,SKU2,...|@/path/to/skus.txt> <supplier>');
                WP_CLI::error('  Suppliers: ' . implode(', ', array_keys($parser_map)));
                return;
            }

            if (!isset($parser_map[$supplier])) {
                WP_CLI::error("Unknown supplier: {$supplier}. Use: " . implode(', ', array_keys($parser_map)));
                return;
            }

            if (strpos($skus_arg, '@') === 0) {
                $skus_file = substr($skus_arg, 1);
                if (!is_readable($skus_file)) {
                    WP_CLI::error("Cannot read SKU file: {$skus_file}");
                    return;
                }
                $raw_skus = file($skus_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            } else {
                $raw_skus = explode(',', $skus_arg);
            }

            $skus = array_values(array_unique(array_filter(array_map('trim', $raw_skus))));
            if (empty($skus)) {
                WP_CLI::error('No SKUs provided.');
                return;
            }

            $xml_dir      = $plugin_root . 'data/xml_files/';
            $script_dir   = $plugin_root . 'product-ai-processor';
            $python_main  = $script_dir . '/main.py';
            $input_xml    = $xml_dir . 'gr/' . $supplier . '.xml';
            $enhanced_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

            $venv_candidates = [
                $script_dir . '/venv/bin/python3',
                $script_dir . '/.venv/bin/python3',
            ];
            $venv_python = null;
            foreach ($venv_candidates as $candidate) {
                if (file_exists($candidate)) {
                    $venv_python = $candidate;
                    break;
                }
            }
            if (!$venv_python) {
                WP_CLI::error('Python venv not found. Tried: ' . implode(', ', $venv_candidates));
                return;
            }

            if (!file_exists($input_xml)) {
                WP_CLI::error("Raw XML not found: {$input_xml}");
                return;
            }

            $skus_csv = implode(',', $skus);
            WP_CLI::log(sprintf('Step 1/2 — AI processing %d SKUs from %s...', count($skus), $supplier));

            $command = sprintf(
                'cd %s && %s %s --mode process --input %s --output %s --skus %s --extend-from-backup --skip-images 2>&1',
                escapeshellarg($script_dir),
                escapeshellarg($venv_python),
                escapeshellarg($python_main),
                escapeshellarg($input_xml),
                escapeshellarg($enhanced_xml),
                escapeshellarg($skus_csv)
            );

            $output      = [];
            $return_code = 0;
            exec($command, $output, $return_code);

            if ($return_code !== 0) {
                WP_CLI::warning('AI processor exited with code ' . $return_code);
                foreach ($output as $line) {
                    WP_CLI::log('  ' . $line);
                }
                WP_CLI::error('AI processing failed. Import aborted.');
                return;
            }

            WP_CLI::log('  AI processing complete.');

            if (!file_exists($enhanced_xml)) {
                WP_CLI::error("Enhanced XML not found after AI processing: {$enhanced_xml}");
                return;
            }

            WP_CLI::log(sprintf('Step 2/2 — Importing %d SKUs into WordPress...', count($skus)));

            $parser_class = $parser_map[$supplier];
            $parser       = new $parser_class($enhanced_xml);
            $products     = $parser->parseProducts();

            $by_sku = [];
            foreach ($products as $p) {
                if (!empty($p->sku)) {
                    $by_sku[(string) $p->sku] = $p;
                }
            }

            $sync       = new \App\Importers\ProductSync();
            $ok         = 0;
            $missing    = [];
            $failed     = [];

            foreach ($skus as $sku) {
                if (!isset($by_sku[$sku])) {
                    $missing[] = $sku;
                    WP_CLI::warning("  SKU {$sku} not in enhanced XML — skipped");
                    continue;
                }
                try {
                    $sync->syncProduct($by_sku[$sku]);
                    $post_id = wc_get_product_id_by_sku($sku);
                    WP_CLI::log("  ✓ {$sku} → post_id=" . ($post_id ?: '?'));
                    $ok++;
                } catch (\Throwable $e) {
                    $failed[] = $sku;
                    WP_CLI::warning("  ✗ {$sku} failed: " . $e->getMessage());
                }
            }

            WP_CLI::success(sprintf(
                'Done. Imported: %d, missing: %d, failed: %d (of %d)',
                $ok, count($missing), count($failed), count($skus)
            ));
            return;
        }

        // Delete every product + variation + image for a supplier
        if ($action === 'delete-supplier') {
            global $wpdb;

            $supplier = isset($args[1]) ? trim($args[1]) : '';
            $dry_run  = isset($assoc_args['dry-run']);
            $yes      = isset($assoc_args['yes']);

            if (empty($supplier)) {
                WP_CLI::error('Usage: wp xml-import delete-supplier <supplier> [--dry-run] [--yes]');
                return;
            }

            $product_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT pm.post_id
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE pm.meta_key = '_supplier'
                   AND LOWER(pm.meta_value) = LOWER(%s)
                   AND p.post_type IN ('product', 'product_variation')",
                $supplier
            ));
            $product_ids = array_map('intval', $product_ids);

            if (!empty($product_ids)) {
                $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
                $child_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                     WHERE post_type = 'product_variation'
                       AND post_parent IN ({$placeholders})",
                    ...$product_ids
                ));
                $product_ids = array_values(array_unique(array_merge($product_ids, array_map('intval', $child_ids))));
            }

            if (empty($product_ids)) {
                WP_CLI::warning("No products found for supplier '{$supplier}'.");
                return;
            }

            $attachment_ids = [];
            foreach ($product_ids as $pid) {
                $thumb = get_post_thumbnail_id($pid);
                if ($thumb) {
                    $attachment_ids[(int) $thumb] = true;
                }
                $gallery = get_post_meta($pid, '_product_image_gallery', true);
                if (!empty($gallery)) {
                    foreach (array_filter(array_map('absint', explode(',', $gallery))) as $gid) {
                        $attachment_ids[$gid] = true;
                    }
                }
                $children = get_children([
                    'post_parent' => $pid,
                    'post_type'   => 'attachment',
                    'numberposts' => -1,
                    'fields'      => 'ids',
                ]);
                foreach ($children as $cid) {
                    $attachment_ids[(int) $cid] = true;
                }
            }
            $attachment_ids = array_keys($attachment_ids);

            $product_count    = count($product_ids);
            $attachment_count = count($attachment_ids);

            WP_CLI::log(sprintf(
                "Supplier '%s': %d products/variations + %d attachments (images)",
                $supplier, $product_count, $attachment_count
            ));

            if ($dry_run) {
                WP_CLI::success('Dry run — nothing deleted.');
                return;
            }

            if (!$yes) {
                WP_CLI::confirm(sprintf(
                    'PERMANENTLY delete %d products and %d attachments for supplier "%s"? This cannot be undone.',
                    $product_count, $attachment_count, $supplier
                ));
            }

            $att_deleted = 0;
            $att_failed  = 0;
            foreach ($attachment_ids as $aid) {
                $r = wp_delete_attachment($aid, true);
                if ($r) {
                    $att_deleted++;
                } else {
                    $att_failed++;
                }
                if ($att_deleted % 100 === 0 && $att_deleted > 0) {
                    WP_CLI::log("  attachments deleted: {$att_deleted}/{$attachment_count}");
                }
            }

            $prod_deleted = 0;
            $prod_failed  = 0;
            foreach ($product_ids as $pid) {
                $r = wp_delete_post($pid, true);
                if ($r) {
                    $prod_deleted++;
                } else {
                    $prod_failed++;
                }
                if ($prod_deleted % 100 === 0 && $prod_deleted > 0) {
                    WP_CLI::log("  products deleted: {$prod_deleted}/{$product_count}");
                }
            }

            WP_CLI::success(sprintf(
                'Deleted %d/%d products and %d/%d attachments for %s (failures: %d products, %d attachments)',
                $prod_deleted, $product_count,
                $att_deleted, $attachment_count,
                $supplier, $prod_failed, $att_failed
            ));
            return;
        }

        // Single-supplier full import: wp xml-import supplier <supplier> [--ai]
        if ($action === 'supplier') {
            $supplier = isset($args[1]) ? trim($args[1]) : '';
            $use_ai   = isset($assoc_args['ai']);

            $parser_map = [
                'pakoworld'    => '\\App\\Importers\\Parsers\\PakoworldParser',
                'b2bmarkt'     => '\\App\\Importers\\Parsers\\B2BMarktParser',
                'libertab2b'   => '\\App\\Importers\\Parsers\\LibertaParser',
                'estiahomeart' => '\\App\\Importers\\Parsers\\EstiahParser',
            ];

            if (empty($supplier)) {
                WP_CLI::error('Usage: wp xml-import supplier <supplier> [--ai]');
                WP_CLI::error('  Suppliers: ' . implode(', ', array_keys($parser_map)));
                return;
            }

            if (!isset($parser_map[$supplier])) {
                WP_CLI::error("Unknown supplier: {$supplier}. Use: " . implode(', ', array_keys($parser_map)));
                return;
            }

            $xml_dir      = $plugin_root . 'data/xml_files/';
            $script_dir   = $plugin_root . 'product-ai-processor';
            $python_main  = $script_dir . '/main.py';
            $input_xml    = $xml_dir . 'gr/' . $supplier . '.xml';
            $enhanced_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

            if ($use_ai) {
                $venv_candidates = [
                    $script_dir . '/venv/bin/python3',
                    $script_dir . '/.venv/bin/python3',
                ];
                $venv_python = null;
                foreach ($venv_candidates as $candidate) {
                    if (file_exists($candidate)) {
                        $venv_python = $candidate;
                        break;
                    }
                }
                if (!$venv_python) {
                    WP_CLI::error('Python venv not found. Tried: ' . implode(', ', $venv_candidates));
                    return;
                }

                if (!file_exists($input_xml)) {
                    WP_CLI::error("Raw XML not found: {$input_xml}");
                    return;
                }

                WP_CLI::log("Step 1/2 — AI processing {$supplier} (extend-from-backup, skip-images)...");

                $command = sprintf(
                    'cd %s && %s %s --mode process --input %s --output %s --extend-from-backup --skip-images 2>&1',
                    escapeshellarg($script_dir),
                    escapeshellarg($venv_python),
                    escapeshellarg($python_main),
                    escapeshellarg($input_xml),
                    escapeshellarg($enhanced_xml)
                );

                $output      = [];
                $return_code = 0;
                exec($command, $output, $return_code);

                if ($return_code !== 0) {
                    WP_CLI::warning('AI processor exited with code ' . $return_code);
                    foreach ($output as $line) {
                        WP_CLI::log('  ' . $line);
                    }
                    WP_CLI::error('AI processing failed. Import aborted.');
                    return;
                }

                WP_CLI::log('  AI processing complete.');
                $step_label = 'Step 2/2';
            } else {
                $step_label = 'Step 1/1';
            }

            if (!file_exists($enhanced_xml)) {
                WP_CLI::error("Enhanced XML not found: {$enhanced_xml}");
                WP_CLI::error('Hint: run with --ai to generate it, or run the AI processor manually.');
                return;
            }

            WP_CLI::log("{$step_label} — Importing {$supplier} from enhanced XML...");

            try {
                $importer = new \App\Importers\Importer();
                $stats    = $importer->processSupplier($supplier);
            } catch (\Throwable $e) {
                WP_CLI::error("Import failed: " . $e->getMessage());
                return;
            }

            if (!empty($stats) && !empty($stats['success'])) {
                WP_CLI::success(sprintf(
                    '%s done. Total=%d, Created=%d, Updated=%d, Skipped=%d, Errors=%d',
                    $supplier,
                    $stats['total_products'] ?? 0,
                    $stats['created']        ?? 0,
                    $stats['updated']        ?? 0,
                    $stats['skipped']        ?? 0,
                    $stats['errors']         ?? 0
                ));
            } else {
                WP_CLI::error("Import of {$supplier} did not complete successfully.");
            }
            return;
        }

        $importer = new \App\Importers\Importer();

        switch ($action) {
            case 'download':
                WP_CLI::log('Downloading XML feeds...');
                $results = $importer->downloadXMLs();
                WP_CLI::success('XML feeds downloaded successfully!');
                break;

            case 'process':
                WP_CLI::log('Processing local XML files...');
                $results = $importer->processLocalXMLs();
                WP_CLI::success('Products processed successfully!');
                break;

            case 'full':
            default:
                WP_CLI::log('Running full import...');
                $results = $importer->runFullImport();
                WP_CLI::success('Import completed successfully!');
                break;
        }

        if (!empty($results)) {
            WP_CLI::log('');
            WP_CLI::log('Results:');
            foreach ($results as $supplier => $stats) {
                if (isset($stats['success']) && $stats['success']) {
                    WP_CLI::log(sprintf(
                        '%s: Created=%d, Updated=%d, Errors=%d',
                        ucfirst($supplier),
                        $stats['created'] ?? 0,
                        $stats['updated'] ?? 0,
                        $stats['errors'] ?? 0
                    ));
                }
            }
        }
    });
}
