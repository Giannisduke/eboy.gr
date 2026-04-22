<?php
/**
 * Reassign pa_υλικό terms on existing products using the normalizeMaterials mapping.
 *
 * Usage:
 *   wp eval-file reassign-materials.php              # dry run (no changes)
 *   wp eval-file reassign-materials.php -- --apply   # apply changes
 */

$dry_run = ! in_array('apply', $args ?? [], true);

if ($dry_run) {
    WP_CLI::log("DRY RUN — no changes will be saved. Pass -- --apply to apply.");
} else {
    WP_CLI::log("APPLY MODE — changes will be saved.");
}

// ── Mapping (same as ProductSync::normalizeMaterials) ────────────────────────

$map = [
    'Βελούδο'           => ['VELVET', 'VELOUR', 'TEDDY', 'SUEDE'],
    'MDF'               => ['MDF', 'CLIPBOARD', 'CLIPBORD', 'CHIPBOARD', 'MELAMINE', 'MELAMINR', 'MELANM', 'ΜΕΛΑΜΙΝ', 'ΜΟΡΙΟΣΑΝΙΔ', 'PAPER WOOD', '3D PAPER', 'PAPER MELAMINE', 'LPL', 'PARTICLE BOARD', 'PARTICLEBOARD', 'E1 PARTICLE', 'FIBERBOARD', 'FIBREBOARD', 'MFC', ' PB '],
    'Κόντρα πλακέ'      => ['PLYWOOD', 'CONTRA PLAQUE', ' PL '],
    'HPL'               => ['HPL', 'WERZALIT', 'COMPACT LAMINATE'],
    'Ξύλο Teak'         => ['TEAK'],
    'Ξύλο'              => [' WOOD', 'PINE WOOD', 'RUBBERWOOD', 'BEECHWOOD', 'BEECH WOOD', 'HARDWOOD', 'MANGO WOOD', 'FINGER JOINTED', 'ΞΥΛΟ', 'ΑΚΑΚΙΑ', 'ΠΑΥΛΩΝΙΑ', 'ACACIA', 'MAHOGANY', 'MINDI', 'SUAR', ' PINE ', 'MERANTI', 'PAULOWNIA', 'MANGO'],
    'Μέταλλο'           => ['METAL', 'ΜΕΤΑΛΛΟ', 'STEEL', 'IRON'],
    'Inox'              => ['INOX', 'STAINLESS'],
    'Αλουμίνιο'         => ['ALUMIN', 'ALUM', 'ALU '],
    'Χαρτί'             => [' PAPER '],
    'Μπαμπού'           => ['BAMBOO', 'BAMBOU', 'ΜΠΑΜΠΟΥ'],
    'Ύφασμα'            => ['FABRIC', 'CANVAS', 'ΥΦΑΣΜΑ', 'TEXTILENE', 'TEXTILE', 'ROPE', 'MESH', 'OXFORD', 'LINEN', 'WOOL'],
    'Δερματίνη'         => ['PU LEATHER', ' PU ', ' PU-', '-PU ', '.PU', 'PU.', 'LEATHERETTE', 'FAUX LEATHER'],
    'Γυαλί'             => ['GLASS', 'ΓΥΑΛ', 'TEMPERED'],
    'Ρατάν'             => ['RATTAN', 'WICKER', 'RATAN', ' CANE'],
    'Φυσικές Ίνες'      => ['JUTE', 'SEAGRASS', 'SISAL', 'SICAL', 'ABACA', 'HEMP', 'COTTON', 'HYACINTH', 'HYACHINT', 'MENDONG', 'PANDANUS', 'STRAW', 'PALM LEAF', 'BANANA ROOT', 'BANANA MIX', 'ALANG', 'RAYUNG', 'RAFFIA', 'GRASS'],
    'Κεραμικό'          => ['CERAMIC', 'TERRACOTTA', 'STONEWARE', 'DOLOMITE', 'BONE CHINA', 'PORCELAIN', 'SINTERED', 'EARTHENWARE'],
    'Πολυπροπυλένιο'    => ['HDPE', ' PP ', ' PP-', '-PP ', 'POLYPROPYLENE', 'POLYETHYLENE'],
    'PVC'               => ['PVC'],
    'Πολυεστέρας'       => ['POLYESTER', '420D', '600D', '100D', 'SILICON COATED FIBER', 'MICROFIBER', 'MICRO FIBER'],
    'Πλαστικό'          => [' ABS ', 'PLASTIC', 'POLYRESIN', ' PC ', ' PS ', 'ACRYLIC', 'POLYCARBONATE'],
    'Σφουγγάρι'         => ['FOAM', 'EPS BEADS', ' EPS ', 'SPRING MATTRESS', 'POCKET SPRING', 'MEMORY FOAM', 'LATEX'],
];

$ignore = ['MULTICOLOR'];

function normalize_material(string $raw, array $map, array $ignore): array {
    $upper_raw = mb_strtoupper(trim($raw), 'UTF-8');
    if (in_array($upper_raw, $ignore, true)) {
        return [];
    }
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

    // Normalize each current term
    $new_terms = [];
    foreach ($current_terms as $term_name) {
        $normalized = normalize_material($term_name, $map, $ignore);
        foreach ($normalized as $t) {
            $new_terms[] = $t;
        }
        // Track unmapped fallbacks
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
