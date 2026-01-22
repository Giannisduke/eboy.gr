<?php
/**
 * AJAX Admin Page for XML Product Importer
 * With batch processing and progress bar
 */

namespace App\Importers\Admin;

use App\Importers\Importer;
use App\Importers\BatchImporter;

class AjaxAdminPage {
    private $importer;
    private $batch_importer;

    public function __construct() {
        $this->importer = new Importer();
        $this->batch_importer = new BatchImporter();

        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);

        // AJAX handlers
        add_action('wp_ajax_xml_import_download', [$this, 'ajaxDownload']);
        add_action('wp_ajax_xml_import_batch', [$this, 'ajaxBatch']);
        add_action('wp_ajax_xml_import_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_xml_import_save_results', [$this, 'ajaxSaveResults']);
        add_action('wp_ajax_xml_import_clear_logs', [$this, 'ajaxClearLogs']);
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

        wp_enqueue_script(
            'xml-importer-admin',
            get_template_directory_uri() . '/app/Importers/Admin/assets/admin.js',
            ['jquery'],
            '1.0.0',
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
                <p>
                    <button type="button" id="start-import" class="button button-primary button-large">
                        🚀 Start Full Import (All Suppliers)
                    </button>
                    <button type="button" id="cancel-import" class="button" style="display:none;">
                        Cancel
                    </button>
                </p>

                <hr style="margin: 20px 0;">

                <p><strong>Import Single Supplier:</strong></p>
                <p>
                    <button type="button" class="button import-single-supplier" data-supplier="pakoworld">
                        Import Pakoworld
                    </button>
                    <button type="button" class="button import-single-supplier" data-supplier="b2bmarkt">
                        Import B2BMarkt
                    </button>
                    <button type="button" class="button import-single-supplier" data-supplier="libertab2b">
                        Import Libertab2b
                    </button>
                    <button type="button" class="button import-single-supplier" data-supplier="estiahomeart">
                        Import Estiah
                    </button>
                </p>
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
     * AJAX: Process batch
     */
    public function ajaxBatch() {
        check_ajax_referer('xml_importer_ajax', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $supplier = isset($_POST['supplier']) ? sanitize_text_field($_POST['supplier']) : '';
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        if (empty($supplier)) {
            wp_send_json_error(['message' => 'Supplier is required']);
        }

        try {
            error_log("AJAX Batch: Processing {$supplier} offset={$offset}");

            $result = $this->batch_importer->processBatch($supplier, $offset);

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
        if (isset($_POST['markup'])) {
            update_option('xml_importer_markup', $_POST['markup']);
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

        if (isset($_POST['results'])) {
            update_option('xml_importer_last_run', [
                'date' => current_time('mysql'),
                'results' => $_POST['results'],
                'type' => 'manual'
            ]);

            wp_send_json_success(['message' => 'Results saved']);
        } else {
            wp_send_json_error(['message' => 'No results provided']);
        }
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
}
