<?php
/**
 * One-time script to seed SKU tracker with existing enhanced products
 * Run this ONCE to initialize the tracking system
 */

// Load WordPress
require_once(__DIR__ . '/../../../../wp/wp-load.php');
require_once(__DIR__ . '/SKUTracker.php');

use App\Importers\SKUTracker;

$tracker = new SKUTracker();

// Seed with the 3 AI-enhanced Pakoworld products
$enhanced_skus = [
    '123-000001',
    '123-000002',
    '123-000003'
];

foreach ($enhanced_skus as $sku) {
    $tracker->markEnhanced($sku, 'pakoworld', [
        'source' => 'manual_seed',
        'date' => current_time('mysql')
    ]);
}

echo "✅ SKU Tracker seeded with " . count($enhanced_skus) . " Pakoworld SKUs\n";
echo "\nTracked SKUs:\n";
foreach ($enhanced_skus as $sku) {
    echo "  - {$sku}\n";
}

$stats = $tracker->getStats();
echo "\nTotal tracked SKUs: " . $stats['total'] . "\n";
echo "By supplier:\n";
foreach ($stats['by_supplier'] as $supplier => $count) {
    echo "  {$supplier}: {$count}\n";
}
