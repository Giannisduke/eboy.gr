#!/usr/bin/env php
<?php
/**
 * Realtime Import CLI Script
 * Watches for AI-enhanced products and imports them in real-time
 *
 * Usage: php realtime-import-cli.php <supplier>
 */

// Get supplier from command line
// When run via WP-CLI eval-file, arguments are in $args (not $argv)
if (!empty($args)) {
    $supplier = $args[0];
} elseif (!empty($argv[1])) {
    $supplier = $argv[1];
} else {
    echo "Usage: wp eval-file realtime-import-cli.php <supplier>\n";
    exit(1);
}

// Resolve theme root from this file's location:
// realtime-import-cli.php lives in scripts/ → parent is theme root
$theme_root = dirname(dirname(__FILE__));

// WordPress is bootstrapped by WP-CLI (eval-file context)
// Load importer classes
require_once $theme_root . '/app/Importers/Models/NormalizedProduct.php';
require_once $theme_root . '/app/Importers/XMLDownloader.php';
require_once $theme_root . '/app/Importers/ProductSync.php';
require_once $theme_root . '/app/Importers/SKUTracker.php';
require_once $theme_root . '/app/Importers/RealtimeImporter.php';
require_once $theme_root . '/app/Importers/Parsers/AbstractParser.php';
require_once $theme_root . '/app/Importers/Parsers/PakoworldParser.php';
require_once $theme_root . '/app/Importers/Parsers/B2BMarktParser.php';
require_once $theme_root . '/app/Importers/Parsers/LibertaParser.php';
require_once $theme_root . '/app/Importers/Parsers/EstiahParser.php';

// No PHP time limit or memory cap — parsing large XMLs (50MB+) needs headroom
set_time_limit(0);
ini_set('memory_limit', '1024M');

error_log("=== Realtime Import CLI Started ===");
error_log("Supplier: {$supplier}");
error_log("Theme root: {$theme_root}");

// Wait for Python AI processor to start a fresh run before we read anything.
// Python writes progress.json with status='processing' AFTER it has reset ready.json
// and enhanced.xml. Waiting here prevents PHP from consuming stale files left over
// from a previous run (which would cause duplicate product creates).
$progress_file = $theme_root . '/scripts/xml_files/' . $supplier . '-progress.json';
$wait_start    = time();
$wait_timeout  = 3600; // give Python up to 1 hour to start
error_log("RealtimeImporter CLI: Waiting for Python to initialise a fresh run (progress.json status != complete)...");
while (true) {
    if (time() - $wait_start > $wait_timeout) {
        error_log("RealtimeImporter CLI: Timed out waiting for Python to start. Aborting.");
        exit(1);
    }

    if (file_exists($progress_file)) {
        $progress = json_decode(file_get_contents($progress_file), true);
        // Python sets status='processing' only AFTER resetting ready.json — safe to proceed.
        if ($progress && isset($progress['status']) && $progress['status'] !== 'complete') {
            error_log("RealtimeImporter CLI: Python is active (status={$progress['status']}), starting import.");
            break;
        }
    }

    error_log("RealtimeImporter CLI: Python not ready yet, waiting 10 s...");
    sleep(10);
}

// Create realtime importer
$importer = new \App\Importers\RealtimeImporter();

// Poll every 5 seconds; max 24 h to handle large catalogs (AI ~3 h + image processing)
$stats = $importer->watchAndImport($supplier, 5, 86400);

error_log("=== Realtime Import CLI Completed ===");
error_log("Stats: " . json_encode($stats));

echo "Realtime import completed\n";
echo "Imported: {$stats['total_imported']} products\n";
echo "Created: {$stats['created']}, Updated: {$stats['updated']}, Errors: {$stats['errors']}\n";
if (isset($stats['trashed'])) {
    echo "Trashed: {$stats['trashed']} missing products\n";
}

exit(0);
