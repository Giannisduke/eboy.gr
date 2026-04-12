<?php
/**
 * Batch Importer
 * Processes imports in smaller batches to avoid timeouts
 */

namespace App\Importers;

use App\Importers\Parsers\PakoworldParser;
use App\Importers\Parsers\B2BMarktParser;
use App\Importers\Parsers\LibertaParser;
use App\Importers\Parsers\EstiahParser;

class BatchImporter {
    private $downloader;
    private $sync;
    private $batch_size = 25; // Process 25 products at a time (reduced for memory)

    public function __construct() {
        $this->downloader = new XMLDownloader();

        // Enable auto-tracking for enhanced XMLs
        $this->sync = new ProductSync(true);

        // Get batch size from settings if available
        $saved_batch_size = get_option('xml_importer_batch_size', 25);
        if ($saved_batch_size > 0) {
            $this->batch_size = (int)$saved_batch_size;
        }
    }

    /**
     * Download XMLs (step 1)
     */
    public function downloadXMLs() {
        set_time_limit(300); // 5 minutes for download

        $results = $this->downloader->downloadAll();

        // Store download results
        update_option('xml_importer_download_results', $results);

        return $results;
    }

    /**
     * Get suppliers list
     */
    public function getSuppliers() {
        return ['pakoworld', 'b2bmarkt', 'libertab2b', 'estiahomeart'];
    }

    /**
     * Get total products count for a supplier
     */
    public function getSupplierProductCount($supplier) {
        // IMPORTANT: Only read from AI-enhanced XML files
        $xml_file = $this->downloader->getLocalFile($supplier, true);

        if (!$xml_file) {
            return 0;
        }

        try {
            $parser = $this->getParser($supplier, $xml_file);
            return $parser->getProductCount();
        } catch (\Exception $e) {
            error_log("Error counting products for {$supplier}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Process a batch of products for a supplier
     *
     * @param string $supplier Supplier name
     * @param int $offset Starting offset for this batch
     * @param int $total_limit Total product limit (0 = all products)
     * @param int|null $batch_size Batch size (null = use default)
     */
    public function processBatch($supplier, $offset = 0, $total_limit = 0, $batch_size = null, $fresh_import = 0) {
        if ($batch_size === null) {
            $batch_size = $this->batch_size;
        }

        set_time_limit(120); // 2 minutes per batch
        ini_set('memory_limit', '1024M'); // Increase memory for this request

        // Use __FILE__ for reliable path resolution
        $theme_root = dirname(dirname(dirname(__FILE__)));
        $xml_dir = $theme_root . '/scripts/xml_files/';
        $progress_file = $xml_dir . $supplier . '-progress.json';
        $enhanced_xml_path = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';

        // On fresh user-initiated import, always clear previous run so AI processes fresh
        if ($fresh_import) {
            if (file_exists($progress_file)) {
                unlink($progress_file);
                error_log("BatchImporter: Cleared previous progress file for {$supplier} (fresh import)");
            }
            if (file_exists($enhanced_xml_path)) {
                unlink($enhanced_xml_path);
                error_log("BatchImporter: Cleared previous enhanced XML for {$supplier} (fresh import)");
            }
        }
        $xml_file = false;

        if (!$xml_file) {
            // STEP 2: Check if original XML exists, if not download it
            $original_xml = $this->downloader->getLocalFile($supplier, false);

            if (!$original_xml) {
                error_log("BatchImporter: Original XML not found for {$supplier}, attempting download...");

                $supplier_url = $this->getSupplierURL($supplier);
                if (!$supplier_url) {
                    throw new \Exception("No URL configured for supplier: {$supplier}");
                }

                try {
                    $download_result = $this->downloader->downloadFeed($supplier_url, $supplier);
                    if (!$download_result['success']) {
                        throw new \Exception("Failed to download XML: " . ($download_result['error'] ?? 'Unknown error'));
                    }

                    $original_xml = $this->downloader->getLocalFile($supplier, false);

                    if (!$original_xml) {
                        throw new \Exception("XML file not found after download");
                    }

                    error_log("BatchImporter: Successfully downloaded original XML for {$supplier}");
                } catch (\Exception $e) {
                    throw new \Exception("Download failed: " . $e->getMessage());
                }
            }

            // STEP 3: Check if AI enhancement is already running
            if (file_exists($progress_file)) {
                $progress_data = json_decode(file_get_contents($progress_file), true);

                if ($progress_data && isset($progress_data['status'])) {
                    if ($progress_data['status'] === 'complete') {
                        // AI enhancement completed, check if XML exists
                        $xml_file = $this->downloader->getLocalFile($supplier, true);
                        if ($xml_file) {
                            error_log("BatchImporter: AI enhancement already completed for {$supplier}");
                        }
                    } else {
                        // AI enhancement still running
                        error_log("BatchImporter: AI enhancement in progress for {$supplier} - " .
                                  $progress_data['current'] . "/" . $progress_data['total']);
                        return [
                            'success' => false,
                            'ai_processing' => true,
                            'progress' => $progress_data,
                            'total' => $progress_data['total'] ?? 0,
                            'offset' => $progress_data['current'] ?? 0,
                            'processed' => $progress_data['current'] ?? 0,
                            'complete' => false,
                            'message' => 'AI enhancement in progress. Please wait...'
                        ];
                    }
                }
            }

            if (!$xml_file) {
                // STEP 4: AI enhancement or fallback to original XML

                // Skip AI if OLLAMA_HOST is not configured (e.g. staging without Tailscale)
                $ollama_available = !empty(getenv('OLLAMA_HOST'));

                if (!$ollama_available) {
                    error_log("BatchImporter: OLLAMA_HOST not set — skipping AI enhancement, using original XML for {$supplier}");
                    $xml_file = $original_xml;
                } elseif (!file_exists($progress_file)) {
                    // OLLAMA available: start AI enhancement in background
                    error_log("BatchImporter: Starting AI enhancement for {$supplier}...");

                    // Delete any existing enhanced XML so AI always processes fresh
                    if (file_exists($enhanced_xml_path)) {
                        unlink($enhanced_xml_path);
                        error_log("BatchImporter: Deleted old enhanced XML for {$supplier} before AI processing");
                    }

                    // Create initial progress file immediately
                    $initial_progress = [
                        'status' => 'starting',
                        'current' => 0,
                        'total' => $total_limit > 0 ? $total_limit : 0,
                        'percent' => 0
                    ];
                    file_put_contents($progress_file, json_encode($initial_progress));

                    try {
                        $this->runAIEnhancement($supplier, $original_xml, $total_limit);

                        // Start realtime import in parallel with AI enhancement
                        $this->startRealtimeImport($supplier);

                        // Return status that AI enhancement has started
                        return [
                            'success' => false,
                            'ai_processing' => true,
                            'ai_started' => true,
                            'realtime_import' => true,
                            'total' => $total_limit,
                            'offset' => 0,
                            'message' => 'AI enhancement started with real-time import. Products will be imported as they are processed...'
                        ];
                    } catch (\Exception $e) {
                        // Remove progress file on error
                        if (file_exists($progress_file)) {
                            unlink($progress_file);
                        }
                        throw new \Exception("Failed to start AI enhancement: " . $e->getMessage());
                    }
                } else {
                    // Progress file exists but no enhanced XML yet - still processing
                    error_log("BatchImporter: AI enhancement already in progress for {$supplier} (no duplicate start)");
                    return [
                        'success' => false,
                        'ai_processing' => true,
                        'total' => $total_limit,
                        'offset' => 0,
                        'message' => 'AI enhancement already in progress...'
                    ];
                }
            }
        }

        error_log("BatchImporter: Using enhanced XML file: {$xml_file}");

        // Use cached products (stored in transient) if available
        // Disable caching for low-memory environments (development)
        $use_cache = wp_get_environment_type() !== 'development';
        $cache_key = 'xml_import_parsed_' . $supplier;
        $all_products = $use_cache ? get_transient($cache_key) : false;

        if ($all_products === false) {
            if ($offset === 0) {
                error_log("BatchImporter: Parsing {$supplier} XML (cache=" . ($use_cache ? 'enabled' : 'disabled') . ")");
            }

            try {
                $parser = $this->getParser($supplier, $xml_file);
                $all_products = $parser->parseProducts();

                // Only cache in production to save memory
                if ($use_cache) {
                    set_transient($cache_key, $all_products, HOUR_IN_SECONDS);
                }

                if ($offset === 0) {
                    error_log("BatchImporter: Parsed " . count($all_products) . " products from {$supplier}");
                }

                // In development, free memory aggressively after parsing
                if (!$use_cache) {
                    unset($parser);
                    gc_collect_cycles();
                }
            } catch (\Exception $e) {
                error_log("BatchImporter: Error parsing {$supplier}: " . $e->getMessage());
                throw $e;
            }
        } else {
            if ($offset === 0) {
                error_log("BatchImporter: Using cached products for {$supplier}");
            }
        }

        // Apply total limit if specified
        if ($total_limit > 0 && count($all_products) > $total_limit) {
            $all_products = array_slice($all_products, 0, $total_limit);
            error_log("BatchImporter: Limited to first {$total_limit} products");
        }

        $total = count($all_products);

        // Get the batch
        $batch_products = array_slice($all_products, $offset, $batch_size);
        $batch_count = count($batch_products);

        // In low-memory mode, free the full product array ASAP
        if (!$use_cache) {
            unset($all_products);
            gc_collect_cycles();
        }

        error_log("BatchImporter: Processing batch for {$supplier} - offset={$offset}, batch_size={$batch_count}, total={$total}");

        if ($batch_count === 0) {
            // Clear cache when done
            if ($use_cache) {
                delete_transient($cache_key);
            }

            return [
                'success' => true,
                'processed' => 0,
                'total' => $total,
                'offset' => $offset,
                'complete' => true,
                'stats' => [
                    'created' => 0,
                    'updated' => 0,
                    'trashed' => 0,
                    'errors' => 0
                ]
            ];
        }

        try {
            // Track active SKUs across batches
            $active_skus_key = 'xml_import_active_skus_' . $supplier;

            // Initialize active SKUs list on first batch
            if ($offset === 0) {
                delete_transient($active_skus_key);
                error_log("BatchImporter: Starting new import session for {$supplier}");
            }

            // Get existing active SKUs
            $active_skus = get_transient($active_skus_key);
            if ($active_skus === false) {
                $active_skus = [];
            }

            // Sync batch
            $stats = $this->sync->syncProducts($batch_products, $supplier);

            // Free batch products from memory immediately
            unset($batch_products);

            // Add synced SKUs to active list
            $batch_skus = $this->sync->getSyncedSkus();
            $active_skus = array_merge($active_skus, $batch_skus);
            $active_skus = array_unique($active_skus); // Remove duplicates

            // Save updated active SKUs list
            set_transient($active_skus_key, $active_skus, HOUR_IN_SECONDS);

            error_log("BatchImporter: Batch synced - created={$stats['created']}, updated={$stats['updated']}, errors={$stats['errors']}");
            error_log("BatchImporter: Total active SKUs so far: " . count($active_skus));

            $new_offset = $offset + $batch_count;
            $is_complete = $new_offset >= $total;

            // When complete, trash missing products and clean up files
            if ($is_complete) {
                error_log("BatchImporter: Import complete for {$supplier}, checking for missing products");

                // Trash products not in XML
                $trashed = $this->sync->trashMissingProducts($supplier, $active_skus);
                $stats['trashed'] = $trashed;

                // Clear caches (only if using cache)
                if ($use_cache) {
                    delete_transient($cache_key);
                }
                delete_transient($active_skus_key);

                error_log("BatchImporter: Completed {$supplier}, cleared cache, trashed {$trashed} products");
            }

            // Force garbage collection after each batch
            if (function_exists('gc_mem_caches')) {
                gc_mem_caches(); // PHP 7.0+
            }
            gc_collect_cycles();

            return [
                'success' => true,
                'processed' => $batch_count,
                'total' => $total,
                'offset' => $new_offset,
                'complete' => $is_complete,
                'stats' => $stats
            ];
        } catch (\Exception $e) {
            error_log("BatchImporter: Error syncing batch for {$supplier}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get parser for supplier
     */
    private function getParser($supplier, $xml_file) {
        switch (strtolower($supplier)) {
            case 'pakoworld':
                return new PakoworldParser($xml_file);

            case 'b2bmarkt':
                return new B2BMarktParser($xml_file);

            case 'libertab2b':
                return new LibertaParser($xml_file);

            case 'estiahomeart':
                return new EstiahParser($xml_file);

            default:
                throw new \Exception("Unknown supplier: {$supplier}");
        }
    }

    /**
     * Get batch size
     */
    public function getBatchSize() {
        return $this->batch_size;
    }

    /**
     * Run AI enhancement on XML file (asynchronously in background)
     */
    private function runAIEnhancement($supplier, $input_xml, $limit = 0) {
        // Use __FILE__ to get the actual filesystem path, immune to WordPress context switching
        // __FILE__ = /path/to/theme/app/Importers/BatchImporter.php
        // Theme root = dirname(dirname(dirname(__FILE__)))
        $theme_root = dirname(dirname(dirname(__FILE__)));
        $script_dir = $theme_root . '/scripts/product-ai-processor';
        $xml_dir = $theme_root . '/scripts/xml_files/';

        // Write debug info directly to log file BEFORE running command
        $debug_log = $xml_dir . 'debug-paths.log';
        $debug_info = "=== PATH DEBUG [" . date('Y-m-d H:i:s') . "] ===\n";
        $debug_info .= "__FILE__ = " . __FILE__ . "\n";
        $debug_info .= "dirname(__FILE__) = " . dirname(__FILE__) . "\n";
        $debug_info .= "dirname(dirname(__FILE__)) = " . dirname(dirname(__FILE__)) . "\n";
        $debug_info .= "dirname(dirname(dirname(__FILE__))) = " . dirname(dirname(dirname(__FILE__))) . "\n";
        $debug_info .= "ABSPATH = " . ABSPATH . "\n";
        $debug_info .= "\$_SERVER['DOCUMENT_ROOT'] = " . $_SERVER['DOCUMENT_ROOT'] . "\n";
        $debug_info .= "get_template_directory() = " . get_template_directory() . "\n";
        $debug_info .= "\$theme_root (calculated) = " . $theme_root . "\n";
        $debug_info .= "\$script_dir (calculated) = " . $script_dir . "\n";
        $debug_info .= "=== END DEBUG ===\n\n";
        file_put_contents($debug_log, $debug_info, FILE_APPEND);

        $output_file = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml';
        $venv_python = $script_dir . '/venv/bin/python3';
        $python_bin = file_exists($venv_python) ? $venv_python : '/usr/bin/python3';
        $log_file = $xml_dir . 'ai-enhancement.log';
        $progress_file = $xml_dir . $supplier . '-progress.json';

        error_log("BatchImporter: Using script directory: {$script_dir}");

        // Note: Skip file_exists() checks for symlinks in mounted filesystems
        // Let the shell command fail naturally if files don't exist

        // Build command - use absolute paths for everything
        $limit_arg = $limit > 0 ? '--limit ' . intval($limit) : '';
        $main_script = $script_dir . '/main.py';

        // Calculate uploads directory (theme_root -> themes -> app -> uploads)
        // $theme_root = /path/to/web/app/themes/simple-city
        // We need: /path/to/web/app/uploads
        $app_dir = dirname(dirname($theme_root)); // simple-city → themes → app
        $uploads_dir = $app_dir . '/uploads/ai-processed-images';

        // Build command parts with proper escaping
        $cd_cmd = 'cd ' . escapeshellarg($script_dir);
        $python_cmd = escapeshellarg($python_bin) . ' ' . escapeshellarg($main_script);
        $input_arg = '--input ' . escapeshellarg($input_xml);
        $output_arg = '--output ' . escapeshellarg($output_file);
        $skip_arg = '--skip-images';
        $redirect = '> ' . escapeshellarg($log_file) . ' 2>&1';

        // Set environment variables for Python script
        // IMAGE_OUTPUT_DIR: Absolute path to uploads directory (always needed)
        $env_vars = 'IMAGE_OUTPUT_DIR=' . escapeshellarg($uploads_dir);

        // OLLAMA_HOST: read from environment variable (set in .env)
        $ollama_host = getenv('OLLAMA_HOST');
        if (!$ollama_host) {
            error_log("BatchImporter: WARNING - OLLAMA_HOST not set in environment");
        } else {
            $env_vars .= ' OLLAMA_HOST=' . escapeshellarg($ollama_host);
            error_log("BatchImporter: Using Ollama host: " . $ollama_host);
        }

        // Check if backup file exists (means we're extending existing enhanced XML)
        $backup_file = $xml_dir . 'enhanced/' . $supplier . '-enhanced.xml.backup';
        $extend_from_backup = file_exists($backup_file) ? '--extend-from-backup' : '';

        // Full command with background execution
        $full_command = "{$cd_cmd} && {$env_vars} {$python_cmd} {$input_arg} {$output_arg} {$skip_arg} {$limit_arg} {$extend_from_backup} {$redirect} & echo $!";

        error_log("BatchImporter: Starting AI enhancement in background");
        error_log("BatchImporter: Command: {$full_command}");

        // Execute in background and get process ID
        $pid = shell_exec($full_command);

        if ($pid) {
            error_log("BatchImporter: AI enhancement process started with PID: " . trim($pid));
        } else {
            error_log("BatchImporter: AI enhancement process started (no PID returned)");
        }

        // Note: Initial progress file is created by the caller (processBatch) before calling this method
        // The Python script will update it as it processes products

        error_log("BatchImporter: AI enhancement process started in background");
        return $output_file;
    }

    /**
     * Get supplier URL from xml_urls.txt
     */
    private function getSupplierURL($supplier) {
        // Use __FILE__ for reliable path resolution
        $theme_root = dirname(dirname(dirname(__FILE__)));
        $xml_dir = $theme_root . '/scripts/xml_files/';
        $urls_file = $xml_dir . 'xml_urls.txt';

        if (!file_exists($urls_file)) {
            error_log("BatchImporter: URLs file not found at {$urls_file}");
            return null;
        }

        $urls = file($urls_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($urls as $url) {
            if (stripos($url, $supplier) !== false) {
                error_log("BatchImporter: Found URL for {$supplier}: {$url}");
                return trim($url);
            }
        }

        error_log("BatchImporter: No URL found for {$supplier} in xml_urls.txt");
        return null;
    }

    /**
     * Set batch size
     */
    public function setBatchSize($size) {
        $this->batch_size = (int)$size;
    }

    /**
     * Start realtime import in background (monitors AI progress and imports products as they're ready)
     */
    private function startRealtimeImport($supplier) {
        $theme_root = dirname(dirname(dirname(__FILE__)));
        $script_path = $theme_root . '/scripts/realtime-import-cli.php';
        $log_file = $theme_root . '/scripts/xml_files/realtime-import.log';

        // Build command to run realtime import in background via WP-CLI
        // WP-CLI properly bootstraps WordPress in CLI context (Bedrock/Acorn compatible)
        $web_root   = dirname(dirname(dirname(dirname(dirname($theme_root))))); // …/web
        $project_root = dirname($web_root); // Bedrock project root (where .env lives)
        $wp_cli_bin = trim(shell_exec('which wp') ?: '') ?: '/usr/local/bin/wp';
        $command = 'cd ' . escapeshellarg($project_root) .
                   ' && ' . escapeshellarg($wp_cli_bin) .
                   ' eval-file ' . escapeshellarg($script_path) .
                   ' ' . escapeshellarg($supplier) .
                   ' --path=' . escapeshellarg($web_root . '/wp') .
                   ' > ' . escapeshellarg($log_file) . ' 2>&1 & echo $!';

        error_log("BatchImporter: Starting realtime import in background");
        error_log("BatchImporter: Command: {$command}");

        // Execute in background
        $pid = shell_exec($command);

        if ($pid) {
            error_log("BatchImporter: Realtime import process started with PID: " . trim($pid));
        } else {
            error_log("BatchImporter: Realtime import process started (no PID returned)");
        }

        return true;
    }
}
