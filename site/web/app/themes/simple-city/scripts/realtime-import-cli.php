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

// No PHP time limit — this process runs until import completes or max_wait expires
set_time_limit(0);

error_log("=== Realtime Import CLI Started ===");
error_log("Supplier: {$supplier}");
error_log("Theme root: {$theme_root}");

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
