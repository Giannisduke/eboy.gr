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

        // Ensure required directories exist
        foreach (['', 'gr', 'enhanced', 'en'] as $subdir) {
            $dir = $this->cache_dir . ($subdir ? $subdir . '/' : '');
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    /**
     * Read URLs from file
     */
    public function getURLs() {
        if (!file_exists($this->urls_file)) {
            throw new \Exception(
                "Το αρχείο xml_urls.txt δεν βρέθηκε ({$this->urls_file}). " .
                "Δημιουργήστε το αρχείο με τα URLs των suppliers (ένα URL ανά γραμμή)."
            );
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
        $gr_dir   = $this->cache_dir . 'gr/';
        $filename = $gr_dir . $supplier . '.xml';

        // Ensure gr/ directory exists before writing
        if (!file_exists($gr_dir)) {
            if (!wp_mkdir_p($gr_dir)) {
                throw new \Exception(
                    "Cannot create directory {$gr_dir}. Check filesystem permissions for: {$this->cache_dir}"
                );
            }
        }

        if (!is_writable($gr_dir)) {
            throw new \Exception(
                "Directory {$gr_dir} is not writable. Run: chmod -R 775 {$this->cache_dir}"
            );
        }

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

        // Persist ETag / Last-Modified for future conditional GETs
        $this->saveMeta($supplier, $response);

        return [
            'success'       => true,
            'changed'       => true,
            'supplier'      => $supplier,
            'filename'      => $filename,
            'size'          => $bytes_written,
            'downloaded_at' => current_time('mysql'),
        ];
    }

    /**
     * Conditional GET — re-downloads only when the supplier XML has changed.
     *
     * Uses ETag / Last-Modified from the previous download (stored in a .meta file).
     * If the server responds 304, the existing local file is kept as-is.
     * Falls back to a full download when no metadata is stored yet.
     *
     * @param string $url      Supplier feed URL
     * @param string $supplier Supplier identifier
     * @param bool   $force    True → always re-download (fresh import)
     * @return array  ['success' => bool, 'changed' => bool, ...]
     */
    public function refreshIfChanged(string $url, string $supplier, bool $force = false): array {
        $gr_dir   = $this->cache_dir . 'gr/';
        $filename = $gr_dir . $supplier . '.xml';

        // Force re-download (user clicked "Fresh Import") or no local file yet
        if ($force || !file_exists($filename)) {
            return $this->downloadFeed($url, $supplier);
        }

        // Build conditional headers from stored metadata
        $meta    = $this->readMeta($supplier);
        $headers = [];
        if (!empty($meta['etag'])) {
            $headers['If-None-Match'] = $meta['etag'];
        }
        if (!empty($meta['last_modified'])) {
            $headers['If-Modified-Since'] = $meta['last_modified'];
        }

        // No stored metadata → full download to capture headers for next time
        if (empty($headers)) {
            error_log("XMLDownloader: No metadata for {$supplier} — downloading fresh to capture ETag");
            return $this->downloadFeed($url, $supplier);
        }

        $response = wp_remote_get($url, [
            'timeout'   => 120,
            'sslverify' => false,
            'headers'   => $headers,
        ]);

        if (is_wp_error($response)) {
            error_log("XMLDownloader: Conditional GET failed for {$supplier}: " . $response->get_error_message() . " — keeping existing file");
            return ['success' => true, 'supplier' => $supplier, 'changed' => false];
        }

        $status = wp_remote_retrieve_response_code($response);

        if ($status === 304) {
            error_log("XMLDownloader: {$supplier} XML unchanged (304 Not Modified)");
            return ['success' => true, 'supplier' => $supplier, 'changed' => false];
        }

        if ($status === 200) {
            $body = wp_remote_retrieve_body($response);

            if (empty($body)) {
                error_log("XMLDownloader: Empty body on refresh for {$supplier} — keeping existing file");
                return ['success' => true, 'supplier' => $supplier, 'changed' => false];
            }

            libxml_use_internal_errors(true);
            if (simplexml_load_string($body) === false) {
                libxml_clear_errors();
                error_log("XMLDownloader: Invalid XML on refresh for {$supplier} — keeping existing file");
                return ['success' => true, 'supplier' => $supplier, 'changed' => false];
            }

            file_put_contents($filename, $body);
            $this->saveMeta($supplier, $response);

            error_log("XMLDownloader: {$supplier} XML changed (200) — downloaded fresh copy");
            return ['success' => true, 'supplier' => $supplier, 'changed' => true, 'filename' => $filename];
        }

        error_log("XMLDownloader: Unexpected HTTP {$status} for {$supplier} on conditional GET — keeping existing file");
        return ['success' => true, 'supplier' => $supplier, 'changed' => false];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function readMeta(string $supplier): array {
        $file = $this->cache_dir . 'gr/' . $supplier . '.xml.meta';
        if (!file_exists($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true) ?? [];
    }

    private function saveMeta(string $supplier, $response): void {
        $etag          = wp_remote_retrieve_header($response, 'etag');
        $last_modified = wp_remote_retrieve_header($response, 'last-modified');
        if (!$etag && !$last_modified) {
            return; // Server doesn't send caching headers — nothing to store
        }
        $file = $this->cache_dir . 'gr/' . $supplier . '.xml.meta';
        file_put_contents($file, json_encode([
            'etag'          => $etag ?: null,
            'last_modified' => $last_modified ?: null,
            'saved_at'      => current_time('mysql'),
        ]));
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
