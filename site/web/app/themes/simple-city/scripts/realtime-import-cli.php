#!/usr/bin/env php
<?php
/**
 * Realtime Import CLI Script
 * Watches for AI-enhanced products and imports them in real-time
 *
 * Usage: php realtime-import-cli.php <supplier>
 */

// Get supplier from command line
if ($argc < 2) {
    echo "Usage: php realtime-import-cli.php <supplier>\n";
    exit(1);
}

$supplier = $argv[1];

// Bootstrap WordPress
define('WP_USE_THEMES', false);

// Find WordPress root (go up from theme/scripts to web/wp)
$theme_root = dirname(__DIR__);
$app_dir = dirname($theme_root); // app directory
$web_root = dirname($app_dir); // web directory
$wp_load = $web_root . '/wp/wp-load.php';

if (!file_exists($wp_load)) {
    error_log("RealtimeImport CLI: WordPress not found at {$wp_load}");
    exit(1);
}

require_once $wp_load;

// Load importer classes
require_once $theme_root . '/app/Importers/XMLDownloader.php';
require_once $theme_root . '/app/Importers/ProductSync.php';
require_once $theme_root . '/app/Importers/SKUTracker.php';
require_once $theme_root . '/app/Importers/RealtimeImporter.php';
require_once $theme_root . '/app/Importers/Parsers/PakoworldParser.php';
require_once $theme_root . '/app/Importers/Parsers/B2BMarktParser.php';
require_once $theme_root . '/app/Importers/Parsers/LibertaParser.php';
require_once $theme_root . '/app/Importers/Parsers/EstiahParser.php';

error_log("=== Realtime Import CLI Started ===");
error_log("Supplier: {$supplier}");
error_log("Theme root: {$theme_root}");

// Create realtime importer
$importer = new \App\Importers\RealtimeImporter();

// Start watching and importing (poll every 5 seconds, max wait 1 hour)
$stats = $importer->watchAndImport($supplier, 5, 3600);

error_log("=== Realtime Import CLI Completed ===");
error_log("Stats: " . json_encode($stats));

echo "Realtime import completed\n";
echo "Imported: {$stats['total_imported']} products\n";
echo "Created: {$stats['created']}, Updated: {$stats['updated']}, Errors: {$stats['errors']}\n";
if (isset($stats['trashed'])) {
    echo "Trashed: {$stats['trashed']} missing products\n";
}

exit(0);
