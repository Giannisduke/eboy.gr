<?php
/**
 * XML Product Importer Initialization
 */

// Autoload importer classes
spl_autoload_register(function ($class) {
    // Only autoload classes in App\Importers namespace
    if (strpos($class, 'App\\Importers\\') !== 0) {
        return;
    }

    // Remove namespace prefix
    $class = str_replace('App\\Importers\\', '', $class);

    // Convert namespace separators to directory separators
    $class = str_replace('\\', '/', $class);

    // Build file path
    $file = get_template_directory() . '/app/Importers/' . $class . '.php';

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

        // Store results
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
        // Schedule at 3 AM daily
        $timestamp = strtotime('tomorrow 3:00 AM');
        wp_schedule_event($timestamp, 'daily', 'xml_importer_daily_sync');
    }
}

// Add admin notices
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

// Add WP-CLI command if available
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('xml-import', function($args, $assoc_args) {
        $action = isset($args[0]) ? $args[0] : 'full';

        if ($action === 'test-title') {
            $title = isset($args[1]) ? $args[1] : '';
            if (empty($title)) {
                WP_CLI::error('Usage: wp xml-import test-title "Τίτλος Προϊόντος"');
                return;
            }

            $theme_root  = dirname(dirname(dirname(__FILE__)));
            $script_dir  = $theme_root . '/scripts/product-ai-processor';
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
        // 1. AI-processes the SKU from raw XML  2. Imports to WordPress
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

            $theme_root  = dirname(dirname(dirname(__FILE__)));
            $xml_dir     = $theme_root . '/scripts/xml_files/';
            $script_dir  = $theme_root . '/scripts/product-ai-processor';
            $venv_python = $script_dir . '/venv/bin/python3';
            $python_main = $script_dir . '/main.py';
            $input_xml   = $xml_dir . 'gr/' . $supplier . '.xml';
            $enhanced_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

            // Validate paths
            if (!file_exists($input_xml)) {
                WP_CLI::error("Raw XML not found: {$input_xml}");
                return;
            }
            if (!file_exists($venv_python)) {
                WP_CLI::error("Python venv not found: {$venv_python}");
                return;
            }

            // Step 1: AI processing
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

            // Step 2: Import from enhanced XML
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
        // Same as `sku` but processes a batch in one AI run. Never trashes missing products.
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

            // Resolve SKU list: @file.txt (one SKU per line) or comma-separated
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

            $theme_root   = dirname(dirname(dirname(__FILE__)));
            $xml_dir      = $theme_root . '/scripts/xml_files/';
            $script_dir   = $theme_root . '/scripts/product-ai-processor';
            $venv_python  = $script_dir . '/venv/bin/python3';
            $python_main  = $script_dir . '/main.py';
            $input_xml    = $xml_dir . 'gr/' . $supplier . '.xml';
            $enhanced_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

            if (!file_exists($input_xml)) {
                WP_CLI::error("Raw XML not found: {$input_xml}");
                return;
            }
            if (!file_exists($venv_python)) {
                WP_CLI::error("Python venv not found: {$venv_python}");
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

        // Display results
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
