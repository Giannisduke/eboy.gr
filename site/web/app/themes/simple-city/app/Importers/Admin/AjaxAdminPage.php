<?php
/**
 * AJAX Admin Page for XML Product Importer
 * With batch processing and progress bar
 */

namespace App\Importers\Admin;

use App\Importers\Importer;
use App\Importers\BatchImporter;

class AjaxAdminPage {
    private $batch_importer;

    public function __construct() {
        $this->batch_importer = new BatchImporter();

        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);

        // AJAX handlers
        add_action('wp_ajax_xml_import_download', [$this, 'ajaxDownload']);
        add_action('wp_ajax_xml_import_enhance', [$this, 'ajaxEnhance']);
        add_action('wp_ajax_xml_import_check_ai_progress', [$this, 'ajaxCheckAIProgress']);
        add_action('wp_ajax_xml_import_batch', [$this, 'ajaxBatch']);
        add_action('wp_ajax_xml_import_smart', [$this, 'ajaxSmartImport']);
        add_action('wp_ajax_xml_import_fast_sync', [$this, 'ajaxFastSync']);
        add_action('wp_ajax_xml_import_detect_new', [$this, 'ajaxDetectNew']);
        add_action('wp_ajax_xml_import_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_xml_import_save_results', [$this, 'ajaxSaveResults']);
        add_action('wp_ajax_xml_import_clear_logs', [$this, 'ajaxClearLogs']);
        add_action('wp_ajax_xml_import_stop_ai', [$this, 'ajaxStopAI']);
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu() {
        add_menu_page(
            'XML Product Importer',
            'XML Importer',
            'manage_woocommerce',
            'xml-product-importer',
            [$this, 'renderPage'],
            'dashicons-download',
            56
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueueScripts($hook) {
        if ($hook !== 'toplevel_page_xml-product-importer') {
            return;
        }

        $js_path = get_template_directory() . '/app/Importers/Admin/assets/admin.js';
        wp_enqueue_script(
            'xml-importer-admin',
            get_template_directory_uri() . '/app/Importers/Admin/assets/admin.js',
            ['jquery'],
            file_exists($js_path) ? filemtime($js_path) : '1.0.0',
            true
        );

        wp_localize_script('xml-importer-admin', 'xmlImporter', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xml_importer_ajax'),
            'suppliers' => $this->batch_importer->getSuppliers()
        ]);

        wp_enqueue_style(
            'xml-importer-admin',
            get_template_directory_uri() . '/app/Importers/Admin/assets/admin.css',
            [],
            '1.0.0'
        );
    }

    /**
     * Render admin page
     */
    public function renderPage() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'import';
        ?>
        <div class="wrap">
            <h1>XML Product Importer</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=xml-product-importer&tab=import" class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">
                    Import
                </a>
                <a href="?page=xml-product-importer&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    Settings
                </a>
                <a href="?page=xml-product-importer&tab=logs" class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    Logs
                </a>
            </h2>

            <?php
            switch ($active_tab) {
                case 'settings':
                    $this->renderSettingsTab();
                    break;
                case 'logs':
                    $this->renderLogsTab();
                    break;
                default:
                    $this->renderImportTab();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render Import tab
     */
    private function renderImportTab() {
        $last_import = get_option('xml_importer_last_run', []);
        ?>
        <div class="card">
            <h2>Batch Import</h2>
            <p class="description">Import will be processed in batches to avoid timeouts.</p>

            <div id="import-controls">
                <div class="card" style="background: #f0f7ff; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0;">⚡ Daily Stock Sync (Most Common)</h3>

                    <p>
                        <button type="button" id="fast-stock-sync" class="button button-primary button-large">
                            ⚡ Fast Stock & Price Sync
                        </button>
                    </p>
                    <p class="description">
                        <strong>Daily sync:</strong> Updates ONLY stock quantities and prices (5-10 minutes). No AI processing.
                    </p>
                </div>

                <hr style="margin: 20px 0;">

                <h3>Import Products by Supplier</h3>
                <p class="description" style="margin-bottom: 15px;">
                    Downloads XML from supplier and imports products to WooCommerce.
                </p>

                <p>
                    <button type="button" id="start-import" class="button button-primary button-large">
                        🚀 Full Import (All Suppliers)
                    </button>
                    <button type="button" id="cancel-import" class="button" style="display:none;">
                        Cancel
                    </button>
                </p>

                <hr style="margin: 20px 0;">

                <h4>Import Single Supplier</h4>
                <p class="description" style="margin-bottom: 15px;">
                    <strong>Product Limit:</strong> Set the maximum number of products to import (0 = all products).
                    Useful for testing or gradual imports.
                </p>

                <table class="form-table" style="margin-top: 0;">
                    <tr>
                        <td style="padding: 10px 0; width: 150px;">
                            <strong>Pakoworld</strong>
                        </td>
                        <td style="padding: 10px 0;">
                            <input type="number" id="limit-pakoworld" class="supplier-limit" min="0" max="10000" value="0" style="width: 100px; margin-right: 10px;" placeholder="0 = all">
                            <span style="color: #666; font-size: 12px;">products</span>
                        </td>
                        <td style="padding: 10px 0;">
                            <button type="button" class="button import-single-supplier" data-supplier="pakoworld">
                                Import Pakoworld
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <strong>B2BMarkt</strong>
                        </td>
                        <td style="padding: 10px 0;">
                            <input type="number" id="limit-b2bmarkt" class="supplier-limit" min="0" max="10000" value="0" style="width: 100px; margin-right: 10px;" placeholder="0 = all">
                            <span style="color: #666; font-size: 12px;">products</span>
                        </td>
                        <td style="padding: 10px 0;">
                            <button type="button" class="button import-single-supplier" data-supplier="b2bmarkt">
                                Import B2BMarkt
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <strong>Libertab2b</strong>
                        </td>
                        <td style="padding: 10px 0;">
                            <input type="number" id="limit-libertab2b" class="supplier-limit" min="0" max="10000" value="0" style="width: 100px; margin-right: 10px;" placeholder="0 = all">
                            <span style="color: #666; font-size: 12px;">products</span>
                        </td>
                        <td style="padding: 10px 0;">
                            <button type="button" class="button import-single-supplier" data-supplier="libertab2b">
                                Import Libertab2b
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0;">
                            <strong>Estiah</strong>
                        </td>
                        <td style="padding: 10px 0;">
                            <input type="number" id="limit-estiahomeart" class="supplier-limit" min="0" max="10000" value="0" style="width: 100px; margin-right: 10px;" placeholder="0 = all">
                            <span style="color: #666; font-size: 12px;">products</span>
                        </td>
                        <td style="padding: 10px 0;">
                            <button type="button" class="button import-single-supplier" data-supplier="estiahomeart">
                                Import Estiah
                            </button>
                        </td>
                    </tr>
                </table>
            </div>

            <div id="import-progress" style="display:none; margin-top: 20px;">
                <h3>Import Progress</h3>

                <div class="progress-section">
                    <strong>Current: <span id="current-supplier">-</span></strong>
                    <div class="progress-bar-wrapper">
                        <div id="progress-bar" class="progress-bar"></div>
                    </div>
                    <div class="progress-text">
                        <span id="progress-current">0</span> / <span id="progress-total">0</span> products
                        (<span id="progress-percent">0</span>%)
                    </div>
                </div>

                <div id="import-stats" style="margin-top: 15px;">
                    <strong>Stats:</strong>
                    <span class="stat">Created: <span id="stat-created">0</span></span>
                    <span class="stat">Updated: <span id="stat-updated">0</span></span>
                    <span class="stat">Trashed: <span id="stat-trashed">0</span></span>
                    <span class="stat">Errors: <span id="stat-errors">0</span></span>
                </div>

                <div id="import-log" style="margin-top: 15px; background: #f5f5f5; padding: 10px; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                </div>
            </div>
        </div>

        <?php if (!empty($last_import)): ?>
        <div class="card">
            <h2>Last Import Results</h2>
            <p><strong>Date:</strong> <?php echo $last_import['date'] ?? 'N/A'; ?></p>
            <p><strong>Type:</strong> <?php echo ($last_import['type'] ?? 'manual') === 'auto' ? 'Automatic' : 'Manual'; ?></p>

            <?php if (isset($last_import['results'])): ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Total</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Trashed</th>
                        <th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($last_import['results'] as $supplier => $stats): ?>
                    <tr>
                        <td><?php echo esc_html(ucfirst($supplier)); ?></td>
                        <td><?php echo $stats['total_products'] ?? 0; ?></td>
                        <td><?php echo $stats['created'] ?? 0; ?></td>
                        <td><?php echo $stats['updated'] ?? 0; ?></td>
                        <td><?php echo $stats['trashed'] ?? 0; ?></td>
                        <td><?php echo $stats['errors'] ?? 0; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render Settings tab
     */
    private function renderSettingsTab() {
        $markup_settings = get_option('xml_importer_markup', [
            'default' => 0,
            'pakoworld' => 0,
            'b2bmarkt' => 0,
            'libertab2b' => 0,
            'estiahomeart' => 0
        ]);

        $cron_enabled = get_option('xml_importer_cron_enabled', true);

        // Default batch size: smaller for development (low RAM)
        $default_batch_size = (wp_get_environment_type() === 'development') ? 10 : 25;
        $batch_size = get_option('xml_importer_batch_size', $default_batch_size);
        ?>
        <form id="settings-form">
            <div class="card">
                <h2>Price Markup Settings</h2>
                <p class="description">Set markup percentage for each supplier</p>

                <table class="form-table">
                    <tr>
                        <th>Default Markup %</th>
                        <td>
                            <input type="number" name="markup[default]" value="<?php echo esc_attr($markup_settings['default']); ?>" step="0.01" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th>Pakoworld Markup %</th>
                        <td>
                            <input type="number" name="markup[pakoworld]" value="<?php echo esc_attr($markup_settings['pakoworld']); ?>" step="0.01" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th>B2BMarkt Markup %</th>
                        <td>
                            <input type="number" name="markup[b2bmarkt]" value="<?php echo esc_attr($markup_settings['b2bmarkt']); ?>" step="0.01" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th>Libertab2b Markup %</th>
                        <td>
                            <input type="number" name="markup[libertab2b]" value="<?php echo esc_attr($markup_settings['libertab2b']); ?>" step="0.01" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th>Estiah Markup %</th>
                        <td>
                            <input type="number" name="markup[estiahomeart]" value="<?php echo esc_attr($markup_settings['estiahomeart']); ?>" step="0.01" min="0">
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>Import Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Batch Size</th>
                        <td>
                            <input type="number" name="batch_size" value="<?php echo esc_attr($batch_size); ?>" min="5" max="100">
                            <p class="description">
                                Products per batch. Lower = slower but safer for low-RAM environments.<br>
                                <strong>Local dev:</strong> 5-10 | <strong>Production:</strong> 20-30
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>Enable Daily Auto-Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cron_enabled" value="1" <?php checked($cron_enabled, true); ?>>
                                Automatically sync products once daily at 3 AM
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <p>
                <button type="submit" class="button button-primary">Save Settings</button>
                <span class="spinner" style="float: none;"></span>
                <span id="settings-message"></span>
            </p>
        </form>
        <?php
    }

    /**
     * Render Logs tab
     */
    private function renderLogsTab() {
        $logs = Importer::getLogs(100);
        ?>
        <div class="card">
            <h2>Import Logs</h2>

            <div id="logs-container">
                <?php if (!empty($logs)): ?>
                <div id="logs-content" style="background: #f0f0f0; padding: 15px; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                    <?php foreach (array_reverse($logs) as $log): ?>
                        <div><?php echo esc_html($log); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p id="no-logs-message">No logs available.</p>
                <?php endif; ?>
            </div>

            <p style="margin-top: 15px;">
                <button type="button" id="clear-logs-btn" class="button">
                    Clear Logs
                </button>
                <span class="spinner" style="float: none;"></span>
                <span id="logs-message"></span>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX: Download XMLs
     */
    public function ajaxDownload() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        try {
            $results = $this->batch_importer->downloadXMLs();
            wp_send_json_success(['results' => $results]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Run AI Enhancement
     */
    public function ajaxEnhance() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        try {
            // Use __DIR__ to get actual filesystem path
            // __DIR__ = /path/to/theme/app/Importers/Admin
            // Theme root = __DIR__ . '/../../..'
            $theme_root = dirname(dirname(dirname(__DIR__)));
            $script_dir = $theme_root . '/scripts/product-ai-processor';
            $xml_dir = $theme_root . '/scripts/xml_files/';

            if (!file_exists($script_dir)) {
                wp_send_json_error(['message' => "AI Enhancement script directory not found at: {$script_dir}. Theme root: {$theme_root}"]);
            }

            $python_script = $script_dir . '/main.py';
            $venv_python = $script_dir . '/venv/bin/python3';

            // Check if files exist
            if (!file_exists($python_script)) {
                wp_send_json_error(['message' => "AI Enhancement script not found at: {$python_script}"]);
            }

            if (!file_exists($venv_python)) {
                wp_send_json_error(['message' => 'Python virtual environment not found. Please run setup first.']);
            }

            // Get requested supplier or process all
            $supplier = $this->validateSupplier($_POST['supplier'] ?? null);
            if (isset($_POST['supplier']) && $supplier === null) {
                wp_send_json_error(['message' => 'Invalid supplier']);
            }
            $suppliers = $supplier ? [$supplier] : $this->allowedSuppliers();

            $results = [];

            foreach ($suppliers as $sup) {
                $input_xml = $xml_dir . 'gr/' . $sup . '.xml';
                $output_xml = $xml_dir . 'enhanced/' . $sup . '-enhanced.xml';

                // Skip if original XML doesn't exist
                if (!file_exists($input_xml)) {
                    $results[$sup] = [
                        'success' => false,
                        'message' => 'Original XML not found. Please download first.'
                    ];
                    continue;
                }

                // Build the command
                $command = sprintf(
                    'cd %s && %s %s --mode process --input %s --output %s --skip-images > /dev/null 2>&1 &',
                    escapeshellarg($script_dir),
                    escapeshellarg($venv_python),
                    escapeshellarg($python_script),
                    escapeshellarg($input_xml),
                    escapeshellarg($output_xml)
                );

                // Execute in background
                exec($command, $output, $return_code);

                $results[$sup] = [
                    'success' => true,
                    'message' => 'AI Enhancement started in background'
                ];
            }

            wp_send_json_success([
                'results' => $results,
                'message' => 'AI Enhancement started. This may take 10-30 minutes depending on the number of products.'
            ]);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Check AI Enhancement Progress
     */
    public function ajaxCheckAIProgress() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $supplier = $this->validateSupplier($_POST['supplier'] ?? null);

        if ($supplier === null) {
            wp_send_json_error(['message' => 'Invalid or missing supplier']);
        }

        $xml_dir = get_template_directory() . '/scripts/xml_files/';
        $progress_file = $xml_dir . $supplier . '-progress.json';
        $enhanced_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

        // Check if progress file exists
        if (!file_exists($progress_file)) {
            // No progress file - check if enhanced XML exists
            if (file_exists($enhanced_xml) && filesize($enhanced_xml) > 1000) {
                wp_send_json_success([
                    'status' => 'complete',
                    'current' => 100,
                    'total' => 100,
                    'percent' => 100
                ]);
            } else {
                wp_send_json_success([
                    'status' => 'not_started',
                    'current' => 0,
                    'total' => 0,
                    'percent' => 0
                ]);
            }
            return;
        }

        // Read progress file
        $progress_data = json_decode(file_get_contents($progress_file), true);

        if (!$progress_data) {
            wp_send_json_error(['message' => 'Failed to read progress file']);
        }

        wp_send_json_success($progress_data);
    }

    /**
     * AJAX: Fast Stock & Price Sync
     */
    public function ajaxFastSync() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        try {
            require_once(__DIR__ . '/../FastStockSync.php');
            $fast_sync = new \App\Importers\FastStockSync();

            $supplier = $this->validateSupplier($_POST['supplier'] ?? null);

            if ($supplier) {
                $results = $fast_sync->syncSupplier($supplier);
                wp_send_json_success([
                    'supplier' => $supplier,
                    'stats' => $results,
                    'message' => "Fast sync completed for {$supplier}"
                ]);
            } else {
                $results = $fast_sync->syncAll();
                wp_send_json_success([
                    'results' => $results,
                    'message' => 'Fast stock sync completed for all suppliers'
                ]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Detect New Products
     */
    public function ajaxDetectNew() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        try {
            require_once(__DIR__ . '/../SKUTracker.php');
            require_once(__DIR__ . '/../IncrementalImporter.php');

            $incremental = new \App\Importers\IncrementalImporter();

            $supplier = $this->validateSupplier($_POST['supplier'] ?? null);

            if ($supplier) {
                $diff = $incremental->detectNewProducts($supplier);
                wp_send_json_success([
                    'supplier' => $supplier,
                    'new_count' => count($diff['new']),
                    'deleted_count' => count($diff['deleted']),
                    'existing_count' => count($diff['existing']),
                    'new_skus' => array_slice($diff['new'], 0, 10), // First 10 for preview
                    'message' => "Found " . count($diff['new']) . " new products"
                ]);
            } else {
                // Check all suppliers
                $suppliers = ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
                $all_results = [];

                foreach ($suppliers as $sup) {
                    try {
                        $diff = $incremental->detectNewProducts($sup);
                        $all_results[$sup] = [
                            'new_count' => count($diff['new']),
                            'deleted_count' => count($diff['deleted'])
                        ];
                    } catch (\Exception $e) {
                        $all_results[$sup] = ['error' => $e->getMessage()];
                    }
                }

                wp_send_json_success([
                    'results' => $all_results,
                    'message' => 'Detection completed for all suppliers'
                ]);
            }

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Smart Incremental Import
     * Download → Detect New → AI Enhancement (new only) → Import
     */
    public function ajaxSmartImport() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        try {
            require_once(__DIR__ . '/../SKUTracker.php');
            require_once(__DIR__ . '/../IncrementalImporter.php');
            require_once(__DIR__ . '/../FastStockSync.php');

            $supplier = $this->validateSupplier($_POST['supplier'] ?? null);

            if (!$supplier) {
                wp_send_json_error(['message' => 'Invalid or missing supplier']);
            }

            // Step 1: Download XML
            $downloader = new \App\Importers\XMLDownloader();
            $download_result = $downloader->downloadFeed(
                $this->getSupplierURL($supplier),
                $supplier
            );

            if (!$download_result['success']) {
                wp_send_json_error(['message' => 'Failed to download XML']);
            }

            // Step 2: Detect new products
            $incremental = new \App\Importers\IncrementalImporter();
            $diff = $incremental->detectNewProducts($supplier);

            $response = [
                'supplier' => $supplier,
                'new_count' => count($diff['new']),
                'deleted_count' => count($diff['deleted']),
                'existing_count' => count($diff['existing'])
            ];

            // Step 3: AI Enhancement for new products (if any)
            if (!empty($diff['new'])) {
                // Python AI processor script (in project root /scripts/)
            $script_dir = get_template_directory() . '/../../../../../scripts/product-ai-processor';
                $python_script = $script_dir . '/main.py';
                $venv_python = $script_dir . '/venv/bin/python3';
                $xml_dir = get_template_directory() . '/scripts/xml_files/';

                $input_xml = $xml_dir . 'gr/' . $supplier . '.xml';
                $output_xml = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

                // Create SKU list (comma-separated)
                $sku_list = implode(',', $diff['new']);

                // Build command with SKU filter
                $command = sprintf(
                    'cd %s && %s %s --mode process --input %s --output %s --skus %s --skip-images > /dev/null 2>&1 &',
                    escapeshellarg($script_dir),
                    escapeshellarg($venv_python),
                    escapeshellarg($python_script),
                    escapeshellarg($input_xml),
                    escapeshellarg($output_xml),
                    escapeshellarg($sku_list)
                );

                // Execute in background
                exec($command);

                $response['ai_enhancement'] = 'started';
                $response['message'] = "Smart import started: " . count($diff['new']) . " new products will be AI-enhanced in background. Existing products will sync stock/price only.";
            } else {
                // No new products - just fast sync
                $fast_sync = new \App\Importers\FastStockSync();
                $sync_stats = $fast_sync->syncSupplier($supplier);

                $response['ai_enhancement'] = 'skipped';
                $response['sync_stats'] = $sync_stats;
                $response['message'] = "No new products found. Updated stock/price for {$sync_stats['updated']} existing products.";
            }

            // Trash deleted products
            if (!empty($diff['deleted'])) {
                $trashed = $incremental->trashDeletedProducts($supplier);
                $response['trashed'] = $trashed;
            }

            wp_send_json_success($response);

        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Whitelist of allowed supplier slugs.
     */
    private function allowedSuppliers(): array {
        return ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
    }

    /**
     * Validate a supplier value against the whitelist.
     * Returns the sanitized slug or null if invalid.
     */
    private function validateSupplier(?string $value): ?string {
        if (empty($value)) {
            return null;
        }
        $slug = sanitize_key($value);
        return in_array($slug, $this->allowedSuppliers(), true) ? $slug : null;
    }

    /**
     * Get supplier URL from xml_urls.txt
     */
    private function getSupplierURL($supplier) {
        $xml_dir = get_template_directory() . '/scripts/xml_files/';
        $urls_file = $xml_dir . 'xml_urls.txt';

        if (!file_exists($urls_file)) {
            return null;
        }

        $urls = file($urls_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($urls as $url) {
            if (stripos($url, $supplier) !== false) {
                return trim($url);
            }
        }

        return null;
    }

    /**
     * AJAX: Process batch
     */
    public function ajaxBatch() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $supplier = $this->validateSupplier($_POST['supplier'] ?? null);
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 0;
        $fresh_import = isset($_POST['fresh_import']) ? intval($_POST['fresh_import']) : 0;

        if (empty($supplier)) {
            wp_send_json_error(['message' => 'Supplier is required']);
        }

        try {
            $limit_msg = $limit > 0 ? " limit={$limit}" : "";
            error_log("AJAX Batch: Processing {$supplier} offset={$offset}{$limit_msg} fresh={$fresh_import}");

            // processBatch($supplier, $offset, $total_limit, $batch_size, $fresh_import)
            $result = $this->batch_importer->processBatch($supplier, $offset, $limit, null, $fresh_import);

            error_log("AJAX Batch: Success for {$supplier} offset={$offset}");

            wp_send_json_success($result);
        } catch (\Exception $e) {
            error_log("AJAX Batch Error: " . $e->getMessage());
            error_log("AJAX Batch Error Stack: " . $e->getTraceAsString());

            wp_send_json_error([
                'message' => $e->getMessage(),
                'trace' => WP_DEBUG ? $e->getTraceAsString() : null
            ]);
        }
    }

    /**
     * AJAX: Save settings
     */
    public function ajaxSaveSettings() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        // Save markup settings
        if (isset($_POST['markup']) && is_array($_POST['markup'])) {
            $clean_markup = [];
            foreach ($_POST['markup'] as $key => $value) {
                $clean_key = sanitize_key($key);
                if ($clean_key !== '') {
                    $clean_markup[$clean_key] = (float) $value;
                }
            }
            update_option('xml_importer_markup', $clean_markup);
        }

        // Save batch size
        if (isset($_POST['batch_size'])) {
            update_option('xml_importer_batch_size', intval($_POST['batch_size']));
        }

        // Save cron setting
        $cron_enabled = isset($_POST['cron_enabled']);
        update_option('xml_importer_cron_enabled', $cron_enabled);

        // Update cron schedule
        if ($cron_enabled) {
            if (!wp_next_scheduled('xml_importer_daily_sync')) {
                $timestamp = strtotime('tomorrow 3:00 AM');
                wp_schedule_event($timestamp, 'daily', 'xml_importer_daily_sync');
            }
        } else {
            wp_clear_scheduled_hook('xml_importer_daily_sync');
        }

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }

    /**
     * AJAX: Save import results
     */
    public function ajaxSaveResults() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (isset($_POST['results']) && is_array($_POST['results'])) {
            $int_fields = ['total_products', 'created', 'updated', 'trashed', 'errors'];
            $clean_results = [];
            foreach ($_POST['results'] as $supplier => $stats) {
                if (!is_array($stats)) {
                    continue;
                }
                $clean_supplier = sanitize_key($supplier);
                if ($clean_supplier === '') {
                    continue;
                }
                $clean_stats = [];
                foreach ($int_fields as $field) {
                    $clean_stats[$field] = isset($stats[$field]) ? absint($stats[$field]) : 0;
                }
                $clean_results[$clean_supplier] = $clean_stats;
            }
            update_option('xml_importer_last_run', [
                'date'    => current_time('mysql'),
                'results' => $clean_results,
                'type'    => 'manual',
            ]);

            wp_send_json_success(['message' => 'Results saved']);
        } else {
            wp_send_json_error(['message' => 'No results provided']);
        }
    }

    /**
     * Get parser class for supplier
     */
    private function getParserClass($supplier) {
        $parser_map = [
            'pakoworld' => '\\App\\Importers\\Parsers\\PakoworldParser',
            'b2bmarkt' => '\\App\\Importers\\Parsers\\B2BMarktParser',
            'libertab2b' => '\\App\\Importers\\Parsers\\LibertaParser',
            'estiahomeart' => '\\App\\Importers\\Parsers\\EstiahParser'
        ];

        return $parser_map[$supplier] ?? '\\App\\Importers\\Parsers\\PakoworldParser';
    }

    /**
     * AJAX: Clear logs
     */
    public function ajaxClearLogs() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        Importer::clearLogs();

        wp_send_json_success(['message' => 'Logs cleared successfully']);
    }

    /**
     * AJAX: Stop AI processing
     * Called when user closes/refreshes page during AI enhancement
     */
    public function ajaxStopAI() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        error_log("AjaxAdminPage: Stopping AI processing (user closed page)");

        // Use __FILE__ for reliable path resolution
        $theme_root = dirname(dirname(dirname(dirname(__FILE__))));
        $xml_dir = $theme_root . '/scripts/xml_files/';

        // Kill Python AI processes
        $kill_command = "pkill -9 -f 'product-ai-processor'";
        shell_exec($kill_command);
        error_log("AjaxAdminPage: Killed AI processes");

        // Clean up progress files for all suppliers
        $suppliers = ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
        foreach ($suppliers as $supplier) {
            $progress_file = $xml_dir . $supplier . '-progress.json';
            $ready_file = $xml_dir . $supplier . '-ready.json';

            if (file_exists($progress_file)) {
                unlink($progress_file);
            }
            if (file_exists($ready_file)) {
                unlink($ready_file);
            }
        }

        error_log("AjaxAdminPage: Cleaned up progress files");

        wp_send_json_success(['message' => 'AI processing stopped']);
    }
}
