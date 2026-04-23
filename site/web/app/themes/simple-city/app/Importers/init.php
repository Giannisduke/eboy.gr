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
