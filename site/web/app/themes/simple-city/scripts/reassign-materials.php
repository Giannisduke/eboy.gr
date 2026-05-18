<?php
/**
 * Reassign pa_υλικό terms on existing products using the ProductSync material mapping.
 *
 * Usage:
 *   wp eval-file scripts/reassign-materials.php --url=sc-staging.eboy.gr           # dry run
 *   wp eval-file scripts/reassign-materials.php apply --url=sc-staging.eboy.gr     # apply changes
 */

use App\Importers\ProductSync;

$dry_run = ! in_array('apply', $args ?? [], true);

if ($dry_run) {
    WP_CLI::log("DRY RUN — no changes will be saved. Pass -- --apply to apply.");
} else {
    WP_CLI::log("APPLY MODE — changes will be saved.");
}

// ── Load all published products ──────────────────────────────────────────────

$product_ids = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$total    = count($product_ids);
$updated  = 0;
$skipped  = 0;
$unmapped = [];

WP_CLI::log("Found {$total} published products.");

$progress = WP_CLI\Utils\make_progress_bar("Processing", $total);

foreach ($product_ids as $product_id) {
    $progress->tick();

    // Current pa_υλικό terms on this product
    $current_terms = wp_get_post_terms($product_id, 'pa_υλικό', ['fields' => 'names']);
    if (is_wp_error($current_terms) || empty($current_terms)) {
        $skipped++;
        continue;
    }

    // Normalize each current term via ProductSync::detectMaterials()
    $new_terms = [];
    foreach ($current_terms as $term_name) {
        $upper_raw = mb_strtoupper(trim($term_name), 'UTF-8');
        if ($upper_raw === 'MULTICOLOR') {
            continue;
        }
        $matched = ProductSync::detectMaterials($term_name);
        if (!empty($matched)) {
            foreach ($matched as $t) {
                $new_terms[] = $t;
            }
        } else {
            // Unmapped: keep raw value, track for the report
            $new_terms[] = trim($term_name);
            $unmapped[$term_name] = ($unmapped[$term_name] ?? 0) + 1;
        }
    }

    $new_terms = array_values(array_unique($new_terms));

    if (empty($new_terms)) {
        $skipped++;
        continue;
    }

    // Skip if nothing changed
    sort($current_terms);
    sort($new_terms);
    if ($current_terms === $new_terms) {
        $skipped++;
        continue;
    }

    if (!$dry_run) {
        wp_set_object_terms($product_id, $new_terms, 'pa_υλικό');
    }

    $updated++;
}

$progress->finish();

WP_CLI::log("─────────────────────────────");
WP_CLI::log("Updated : {$updated}");
WP_CLI::log("Skipped : {$skipped} (no material or already correct)");

if (!empty($unmapped)) {
    WP_CLI::log("");
    WP_CLI::warning("Unmapped terms (kept as-is):");
    arsort($unmapped);
    foreach ($unmapped as $term => $count) {
        WP_CLI::log("  [{$count}] {$term}");
    }
}

if ($dry_run) {
    WP_CLI::log("");
    WP_CLI::log("Run with -- --apply to save changes.");
}
