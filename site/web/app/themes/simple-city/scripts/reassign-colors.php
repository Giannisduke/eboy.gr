<?php
/**
 * Reassign pa_color terms on existing products using the normalizeColors mapping.
 *
 * Usage:
 *   wp eval-file reassign-colors.php              # dry run (no changes)
 *   wp eval-file reassign-colors.php apply        # apply changes
 */

$dry_run = ! in_array('apply', $args ?? [], true);

if ($dry_run) {
    WP_CLI::log("DRY RUN — no changes will be saved. Pass apply to apply.");
} else {
    WP_CLI::log("APPLY MODE — changes will be saved.");
}

// ── Mapping (same as ProductSync::normalizeColors) ────────────────────────────

$map = [
    'Μαύρο'      => ['BLACK', 'ΒLACK', 'ΜΑΥΡΟ', ' BACK '],
    'Λευκό'      => ['WHITE', 'ΛΕΥΚΟ', 'IVORY', 'CREAM', 'NYMPHEAE ALBA'],
    'Γκρι'       => ['GREY', 'GRAY', 'ΓΚΡΙ', 'ELEPHANT', 'RUSTIC GREY', 'DARK GRET', 'TILE'],
    'Ανθρακί'    => ['ANTHRACITE', 'ΑΝΘΡΑΚΙ', 'CHARCOAL', 'ANTRACITE', 'ANTRHACITE', 'ATHRACITE'],
    'Μπεζ'       => ['BEIGE', 'ECRU', 'ECROU', 'CAMEL', 'KHAKI', ' TAN ', 'ΒΕΙΓΕ', 'MINK'],
    'Καφέ'       => ['BROWN', 'ΚΑΦΕ', 'TABAC', 'MOCHA', 'CAPPUCCINO', 'CAPPUCINO', 'CAPUCCINO', 'CAPUCINO'],
    'Χρυσό'      => ['GOLD', 'ΧΡΥΣΟ', 'COPPER', 'BRONZE', 'CHAMPAGNE', 'AMBER'],
    'Ασημί'      => ['SILVER', 'CHROME', 'ΑΣΗΜΙ', 'INOX', 'PIPE'],
    'Κόκκινο'    => ['RED', 'ROTTEN APPLE', 'CASTILLO TORO'],
    'Μπλε'       => ['BLUE', 'CIEL'],
    'Πράσινο'    => ['GREEN', 'MINT', 'ΜΙΝΤ', 'MENTA', 'OLIVE', 'PISTACHIO', 'GREN'],
    'Ροζ'        => ['PINK', 'DUSTY ROSE'],
    'Πορτοκαλί'  => ['ORANGE', 'TERRACOTTA', 'ΠΟΡΤΟΚΑΛΙ'],
    'Κίτρινο'    => ['YELLOW'],
    'Μωβ'        => ['PURPLE', 'VIOLET'],
    'Τυρκουάζ'   => ['WATER GREEN', 'TURQUOISE', 'TIRQOISE', 'PETROL', 'TURKEY'],
    'Πολύχρωμο'  => ['MULTICOLOR', 'MULTI', 'MNULTICOLOR', 'MULTIOCOLOR', 'COLORFUL', 'ΠΟΛΥΧΡΩΜΟ'],
    'Διάφανο'    => ['TRANSPARENT', 'CLEAR', 'CL.EAR'],
    'Σονόμα'     => ['SONOMA'],
    'Καρυδί'     => ['WALNUT', 'ΚΑΡΥΔΙ', 'LIGHT TEAK LOOK'],
    'Βέγκε'      => ['WENGE'],
    'Φυσικό'     => ['NATURAL', 'ΦΥΣΙΚΟ', 'OAK', 'NATURE', 'NATYRAL', 'ATLANTIC PINE', 'UNPAID WOOD', 'UNPAINTED BEACH WOOD', 'SOLID WOOD', 'INDIA'],
    'Σφενδάμι'   => ['MAPLE'],
    'Μαρμάρινο'  => ['MARBLE', 'TRAVERTEN', 'TRAVERTINE', 'ΤRAVERTINE'],
    'Τσιμέντο'   => ['CEMENT'],
];

function normalize_color(string $raw, array $map): array {
    $upper = mb_strtoupper(' ' . $raw . ' ', 'UTF-8');
    $found = [];
    foreach ($map as $canonical => $keywords) {
        foreach ($keywords as $kw) {
            if (mb_strpos($upper, $kw, 0, 'UTF-8') !== false) {
                $found[] = $canonical;
                break;
            }
        }
    }
    return $found ?: [trim($raw)];
}

// ── Load all published products ───────────────────────────────────────────────

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

    $current_terms = wp_get_post_terms($product_id, 'pa_color', ['fields' => 'names']);
    if (is_wp_error($current_terms) || empty($current_terms)) {
        $skipped++;
        continue;
    }

    $new_terms = [];
    foreach ($current_terms as $term_name) {
        $normalized = normalize_color($term_name, $map);
        foreach ($normalized as $t) {
            $new_terms[] = $t;
        }
        if (count($normalized) === 1 && $normalized[0] === trim($term_name)) {
            $canonical_names = array_keys($map);
            if (!in_array(trim($term_name), $canonical_names, true)) {
                $unmapped[$term_name] = ($unmapped[$term_name] ?? 0) + 1;
            }
        }
    }

    $new_terms = array_values(array_unique($new_terms));

    if (empty($new_terms)) {
        $skipped++;
        continue;
    }

    sort($current_terms);
    sort($new_terms);
    if ($current_terms === $new_terms) {
        $skipped++;
        continue;
    }

    if (!$dry_run) {
        wp_set_object_terms($product_id, $new_terms, 'pa_color');
    }

    $updated++;
}

$progress->finish();

WP_CLI::log("─────────────────────────────");
WP_CLI::log("Updated : {$updated}");
WP_CLI::log("Skipped : {$skipped} (no color or already correct)");

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
    WP_CLI::log("Run with apply argument to save changes.");
}
