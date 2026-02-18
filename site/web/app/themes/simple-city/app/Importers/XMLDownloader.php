<?php
/**
 * XML Downloader
 * Downloads XML feeds from supplier URLs
 */

namespace App\Importers;

class XMLDownloader {
    private $urls_file;
    private $cache_dir;

    public function __construct() {
        // Use scripts/xml_files directory inside theme
        $xml_dir = get_template_directory() . '/scripts/xml_files/';

        $this->urls_file = $xml_dir . 'xml_urls.txt';
        $this->cache_dir = $xml_dir;  // Base directory, will use subdirectories for en/gr/enhanced

        // Ensure cache directory exists
        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
    }

    /**
     * Read URLs from file
     */
    public function getURLs() {
        if (!file_exists($this->urls_file)) {
            throw new \Exception("URLs file not found: {$this->urls_file}");
        }

        $content = file_get_contents($this->urls_file);
        $urls = array_filter(array_map('trim', explode("\n", $content)));

        return $urls;
    }

    /**
     * Download all XML feeds
     */
    public function downloadAll() {
        $urls = $this->getURLs();
        $results = [];

        foreach ($urls as $url) {
            try {
                $supplier = $this->identifySupplier($url);
                $result = $this->downloadFeed($url, $supplier);
                $results[$supplier] = $result;
            } catch (\Exception $e) {
                $results[$url] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Download single XML feed
     */
    public function downloadFeed($url, $supplier = null) {
        if (!$supplier) {
            $supplier = $this->identifySupplier($url);
        }

        // Store downloaded XMLs in gr/ subdirectory
        $filename = $this->cache_dir . 'gr/' . $supplier . '.xml';

        // Use WordPress HTTP API
        $response = wp_remote_get($url, [
            'timeout' => 120,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            throw new \Exception("Failed to download XML from {$url}: " . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            throw new \Exception("HTTP Error {$status_code} when downloading from {$url}");
        }

        $body = wp_remote_retrieve_body($response);

        if (empty($body)) {
            throw new \Exception("Empty response from {$url}");
        }

        // Validate XML
        libxml_use_internal_errors(true);
        $test = simplexml_load_string($body);
        if ($test === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception("Invalid XML from {$url}");
        }

        // Save to file
        $bytes_written = file_put_contents($filename, $body);

        if ($bytes_written === false) {
            throw new \Exception("Failed to save XML to {$filename}");
        }

        return [
            'success' => true,
            'supplier' => $supplier,
            'filename' => $filename,
            'size' => $bytes_written,
            'downloaded_at' => current_time('mysql')
        ];
    }

    /**
     * Identify supplier from URL
     */
    private function identifySupplier($url) {
        $url_lower = strtolower($url);

        if (strpos($url_lower, 'libertab2b') !== false) {
            return 'libertab2b';
        } elseif (strpos($url_lower, 'b2bmarkt') !== false) {
            return 'b2bmarkt';
        } elseif (strpos($url_lower, 'estiahomeart') !== false) {
            return 'estiahomeart';
        } elseif (strpos($url_lower, 'pakoworld') !== false) {
            return 'pakoworld';
        }

        // Default: use domain name
        $parsed = parse_url($url);
        return str_replace(['www.', '.com', '.gr'], '', $parsed['host']);
    }

    /**
     * Get local XML file path for supplier
     *
     * @param string $supplier Supplier name
     * @param bool $enhanced_only If true, ONLY return enhanced XML. If false, prefer enhanced with fallback to original.
     * @return string|false File path or false if not found
     */
    public function getLocalFile($supplier, $enhanced_only = false) {
        $enhanced_filename = $this->cache_dir . 'enhanced/' . $supplier . '-enhanced.xml';
        $original_filename = $this->cache_dir . 'gr/' . $supplier . '.xml';

        // If enhanced_only is true, ONLY return enhanced XML
        if ($enhanced_only) {
            if (!file_exists($enhanced_filename)) {
                error_log("XMLDownloader: Enhanced XML not found for {$supplier}");
                return false;
            }
            error_log("XMLDownloader: Using AI-enhanced XML (enhanced_only mode) for {$supplier}");
            return $enhanced_filename;
        }

        // Default behavior: Prefer enhanced XML, fallback to original
        if (file_exists($enhanced_filename)) {
            error_log("XMLDownloader: Using AI-enhanced XML for {$supplier}");
            return $enhanced_filename;
        }

        if (file_exists($original_filename)) {
            error_log("XMLDownloader: Using original XML for {$supplier} (enhanced not found)");
            return $original_filename;
        }

        error_log("XMLDownloader: No XML found for {$supplier}");
        return false;
    }

    /**
     * Check if local files exist
     */
    public function hasLocalFiles() {
        $suppliers = ['libertab2b', 'b2bmarkt', 'estiahomeart', 'pakoworld'];

        foreach ($suppliers as $supplier) {
            if ($this->getLocalFile($supplier)) {
                return true;
            }
        }

        return false;
    }
}
