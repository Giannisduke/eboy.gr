<?php
/**
 * Admin Page for XML Product Importer
 */

namespace App\Importers\Admin;

use App\Importers\Importer;

class AdminPage {
    private $importer;

    public function __construct() {
        $this->importer = new Importer();
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_post_xml_import_run', [$this, 'handleImportAction']);
        add_action('admin_post_xml_import_download', [$this, 'handleDownloadAction']);
        add_action('admin_post_xml_import_enhance', [$this, 'handleEnhanceAction']);
        add_action('admin_post_xml_import_process', [$this, 'handleProcessAction']);
        add_action('admin_post_xml_import_save_settings', [$this, 'handleSaveSettings']);
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
     * Render admin page
     */
    public function renderPage() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'import';

        ?>
        <div class="wrap">
            <h1>XML Product Importer</h1>

            <?php
            // Display messages
            if (isset($_GET['message'])) {
                $message = $_GET['message'];
                $class = 'notice notice-success is-dismissible';
                $text = '';

                switch ($message) {
                    case 'enhance_started':
                        $text = 'AI Enhancement started in background. This may take a while depending on the number of products. Check back in 10-30 minutes.';
                        break;
                    case 'enhance_error':
                        $class = 'notice notice-error is-dismissible';
                        $text = 'AI Enhancement Error: ' . (isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error');
                        break;
                    case 'import_success':
                        $text = 'Products imported successfully!';
                        break;
                    case 'import_error':
                        $class = 'notice notice-error is-dismissible';
                        $text = 'Import Error: ' . (isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error');
                        break;
                    case 'download_success':
                        $text = 'XML files downloaded successfully!';
                        break;
                    case 'download_error':
                        $class = 'notice notice-error is-dismissible';
                        $text = 'Download Error: ' . (isset($_GET['error']) ? urldecode($_GET['error']) : 'Unknown error');
                        break;
                }

                if ($text) {
                    echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($text) . '</p></div>';
                }
            }
            ?>

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
        $next_scheduled = wp_next_scheduled('xml_importer_daily_sync');

        ?>
        <div class="card">
            <h2>Manual Import</h2>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('xml_import_run'); ?>
                <input type="hidden" name="action" value="xml_import_run">

                <p>
                    <button type="submit" class="button button-primary button-large">
                        Run Full Import (Download + Sync)
                    </button>
                </p>

                <p class="description">
                    This will download all XML feeds from suppliers and sync products to WooCommerce.
                </p>
            </form>

            <hr>

            <h3>Advanced Options</h3>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                <?php wp_nonce_field('xml_import_download'); ?>
                <input type="hidden" name="action" value="xml_import_download">
                <button type="submit" class="button">1. Download XMLs Only</button>
            </form>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block; margin-right: 10px;">
                <?php wp_nonce_field('xml_import_enhance'); ?>
                <input type="hidden" name="action" value="xml_import_enhance">
                <button type="submit" class="button button-secondary">2. Run AI Enhancement</button>
            </form>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                <?php wp_nonce_field('xml_import_process'); ?>
                <input type="hidden" name="action" value="xml_import_process">
                <button type="submit" class="button button-primary">3. Import Enhanced XMLs</button>
            </form>
        </div>

        <?php if (!empty($last_import)): ?>
        <div class="card">
            <h2>Last Import Results</h2>
            <p><strong>Date:</strong> <?php echo $last_import['date'] ?? 'N/A'; ?></p>

            <?php if (isset($last_import['results'])): ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Total</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Skipped</th>
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
                        <td><?php echo $stats['skipped'] ?? 0; ?></td>
                        <td><?php echo $stats['errors'] ?? 0; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($next_scheduled): ?>
        <div class="card">
            <h2>Scheduled Import</h2>
            <p>
                <strong>Next scheduled import:</strong>
                <?php echo date('Y-m-d H:i:s', $next_scheduled); ?>
            </p>
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

        ?>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('xml_import_save_settings'); ?>
            <input type="hidden" name="action" value="xml_import_save_settings">

            <div class="card">
                <h2>Price Markup Settings</h2>
                <p class="description">Set markup percentage for each supplier (leave 0 for no markup)</p>

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
                <h2>Automatic Sync</h2>
                <table class="form-table">
                    <tr>
                        <th>Enable Daily Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cron_enabled" value="1" <?php checked($cron_enabled, true); ?>>
                                Automatically sync products once daily
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <p>
                <button type="submit" class="button button-primary">Save Settings</button>
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

            <?php if (!empty($logs)): ?>
            <div style="background: #f0f0f0; padding: 15px; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                <?php foreach (array_reverse($logs) as $log): ?>
                    <div><?php echo esc_html($log); ?></div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p>No logs available.</p>
            <?php endif; ?>

            <p style="margin-top: 15px;">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('xml_import_clear_logs'); ?>
                    <input type="hidden" name="action" value="xml_import_clear_logs">
                    <button type="submit" class="button">Clear Logs</button>
                </form>
            </p>
        </div>
        <?php
    }

    /**
     * Handle full import action
     */
    public function handleImportAction() {
        check_admin_referer('xml_import_run');

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }

        try {
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            $results = $this->importer->runFullImport();

            update_option('xml_importer_last_run', [
                'date' => current_time('mysql'),
                'results' => $results
            ]);

            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'import_success'
            ], admin_url('admin.php')));
        } catch (\Exception $e) {
            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'import_error',
                'error' => urlencode($e->getMessage())
            ], admin_url('admin.php')));
        }

        exit;
    }

    /**
     * Handle download action
     */
    public function handleDownloadAction() {
        check_admin_referer('xml_import_download');

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }

        try {
            $results = $this->importer->downloadXMLs();

            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'download_success'
            ], admin_url('admin.php')));
        } catch (\Exception $e) {
            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'download_error',
                'error' => urlencode($e->getMessage())
            ], admin_url('admin.php')));
        }

        exit;
    }

    /**
     * Handle AI enhancement action
     */
    public function handleEnhanceAction() {
        check_admin_referer('xml_import_enhance');

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }

        try {
            // Get the path to the Python script
            $script_dir = WP_CONTENT_DIR . '/../../scripts/product-ai-processor';
            $python_script = $script_dir . '/main.py';
            $venv_python = $script_dir . '/venv/bin/python3';

            // Check if files exist
            if (!file_exists($python_script)) {
                throw new \Exception("AI Enhancement script not found at: {$python_script}");
            }

            if (!file_exists($venv_python)) {
                throw new \Exception("Python virtual environment not found. Please run setup first.");
            }

            // Get XML directory
            $xml_dir = get_template_directory() . '/xml_files/';

            // Run enhancement for each supplier
            $suppliers = ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
            $results = [];

            foreach ($suppliers as $supplier) {
                $input_xml = $xml_dir . $supplier . '.xml';
                $output_xml = $xml_dir . $supplier . '-enhanced.xml';

                // Skip if original XML doesn't exist
                if (!file_exists($input_xml)) {
                    $results[$supplier] = 'Skipped - XML not found';
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
                exec($command);
                $results[$supplier] = 'Started (background process)';
            }

            // Store results for display
            update_option('xml_importer_enhance_status', [
                'date' => current_time('mysql'),
                'results' => $results
            ]);

            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'enhance_started'
            ], admin_url('admin.php')));

        } catch (\Exception $e) {
            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'enhance_error',
                'error' => urlencode($e->getMessage())
            ], admin_url('admin.php')));
        }

        exit;
    }

    /**
     * Handle process action
     */
    public function handleProcessAction() {
        check_admin_referer('xml_import_process');

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }

        try {
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            $results = $this->importer->processLocalXMLs();

            update_option('xml_importer_last_run', [
                'date' => current_time('mysql'),
                'results' => $results
            ]);

            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'process_success'
            ], admin_url('admin.php')));
        } catch (\Exception $e) {
            wp_redirect(add_query_arg([
                'page' => 'xml-product-importer',
                'message' => 'process_error',
                'error' => urlencode($e->getMessage())
            ], admin_url('admin.php')));
        }

        exit;
    }

    /**
     * Handle save settings
     */
    public function handleSaveSettings() {
        check_admin_referer('xml_import_save_settings');

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }

        // Save markup settings
        if (isset($_POST['markup'])) {
            update_option('xml_importer_markup', $_POST['markup']);
        }

        // Save cron setting
        $cron_enabled = isset($_POST['cron_enabled']);
        update_option('xml_importer_cron_enabled', $cron_enabled);

        // Update cron schedule
        if ($cron_enabled) {
            if (!wp_next_scheduled('xml_importer_daily_sync')) {
                wp_schedule_event(time(), 'daily', 'xml_importer_daily_sync');
            }
        } else {
            wp_clear_scheduled_hook('xml_importer_daily_sync');
        }

        wp_redirect(add_query_arg([
            'page' => 'xml-product-importer',
            'tab' => 'settings',
            'message' => 'settings_saved'
        ], admin_url('admin.php')));

        exit;
    }
}
