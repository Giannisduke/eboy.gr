#!/usr/bin/env php
<?php
/**
 * Realtime Import CLI Script
 * Watches for AI-enhanced products and imports them in real-time
 *
 * Usage: php realtime-import-cli.php <supplier>
 */

// Get supplier (+ optional flags) from command line. When run via WP-CLI
// eval-file, arguments are in $args (not $argv).
$resume       = false;
$resume_hours = 24;   // default: skip SKUs synced in the last 24h
$max_images   = null; // null = no limit; integer = max images per product
$cli_args     = !empty($args) ? $args : array_slice($argv, 1);
if (empty($cli_args)) {
    echo "Usage: wp eval-file realtime-import-cli.php <supplier> [resume] [<hours>h] [images=N]\n";
    echo "       resume    = skip Python-AI wait and import from existing enhanced.xml\n";
    echo "       <hours>h  = window for already-synced SKUs to skip (default 24h, e.g. 6h, 12h)\n";
    echo "       images=N  = process at most N images per product (e.g. images=1 → only featured)\n";
    exit(1);
}
$supplier = $cli_args[0];
foreach (array_slice($cli_args, 1) as $arg) {
    $a = strtolower(trim((string) $arg));
    if ($a === '') continue;
    if ($a === 'resume') {
        $resume = true;
    } elseif (preg_match('/^(\d+)\s*h$/', $a, $m)) {
        $resume_hours = max(1, (int) $m[1]);
    } elseif (preg_match('/^(?:images?|imgs|max-images)\s*=\s*(\d+)$/', $a, $m)) {
        $max_images = max(0, (int) $m[1]);
    } else {
        echo "Unknown argument: '{$arg}'.\n";
        echo "Usage: wp eval-file realtime-import-cli.php <supplier> [resume] [<hours>h] [images=N]\n";
        exit(1);
    }
}

// Plugin root from this file's location: cli/ → eboy-product-importer/
$plugin_root = dirname(__DIR__);

// WordPress is bootstrapped by WP-CLI (eval-file context).
// The plugin's autoloader handles App\Importers\* — these requires are
// defensive (in case the plugin isn't active or autoloader isn't reached).
require_once $plugin_root . '/includes/Models/NormalizedProduct.php';
require_once $plugin_root . '/includes/XMLDownloader.php';
require_once $plugin_root . '/includes/ProductSync.php';
require_once $plugin_root . '/includes/SKUTracker.php';
require_once $plugin_root . '/includes/RealtimeImporter.php';
require_once $plugin_root . '/includes/Parsers/AbstractParser.php';
require_once $plugin_root . '/includes/Parsers/PakoworldParser.php';
require_once $plugin_root . '/includes/Parsers/B2BMarktParser.php';
require_once $plugin_root . '/includes/Parsers/LibertaParser.php';
require_once $plugin_root . '/includes/Parsers/EstiahParser.php';

// No PHP time limit or memory cap — parsing large XMLs (50MB+) needs headroom
set_time_limit(0);
ini_set('memory_limit', '1024M');

error_log("=== Realtime Import CLI Started ===");
error_log("Supplier: {$supplier}");
error_log("Plugin root: {$plugin_root}");

// Wait for Python AI processor to start a fresh run before we read anything.
// Python writes progress.json with status='processing' AFTER it has reset ready.json
// and enhanced.xml. Waiting here prevents PHP from consuming stale files left over
// from a previous run (which would cause duplicate product creates).
//
// In `resume` mode the AI run already finished (status='complete') and we want to
// re-process the existing enhanced.xml — the wait would time out, so skip it.
$progress_file = $plugin_root . '/data/xml_files/' . $supplier . '-progress.json';
if ($resume) {
    error_log("RealtimeImporter CLI: Resume mode — skipping Python wait, using existing enhanced.xml.");
} else {
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
}

// Create realtime importer
$importer = new \App\Importers\RealtimeImporter();

// Apply image limit if requested. Skips downloading/rembg/sideloading images
// beyond the Nth, drastically cutting per-product time when N is small.
if ($max_images !== null) {
    $importer->setMaxImages($max_images);
    error_log("RealtimeImporter CLI: Per-product image cap set to {$max_images}.");
}

// In resume mode, skip SKUs already synced within the last `$resume_hours` so
// we don't re-download images and re-run rembg for items the previous run
// finished. The supplier slug used in WP meta is title-cased (e.g. "B2BMarkt").
if ($resume) {
    $supplier_slug = match (strtolower($supplier)) {
        'b2bmarkt'     => 'B2BMarkt',
        'pakoworld'    => 'Pakoworld',
        'libertab2b'   => 'LibertaB2B',
        'estiahomeart' => 'EstiaHomeArt',
        default        => $supplier,
    };
    $cutoff = time() - ($resume_hours * 3600);
    $already_done = $importer->findRecentlySyncedSkus($supplier_slug, $cutoff);
    if (!empty($already_done)) {
        $importer->seedProcessedSkus($already_done);
        error_log("RealtimeImporter CLI: Resume — pre-skipping " . count($already_done) . " SKUs synced in the last {$resume_hours}h.");
    } else {
        error_log("RealtimeImporter CLI: Resume — no SKUs synced in the last {$resume_hours}h; will re-process all from ready.json.");
    }
}

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
