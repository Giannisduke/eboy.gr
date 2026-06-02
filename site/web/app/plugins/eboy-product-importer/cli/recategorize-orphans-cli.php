<?php
/**
 * Re-categorize products in non-whitelisted ("orphan") product_cat terms via AI.
 *
 * For each product currently sitting in one of the supplied term_ids, the
 * Python CategoryMapper is asked to pick one of the 8 whitelisted WooCommerce
 * categories based on the orphan term's name and the product's title. The
 * product is then moved into the AI-chosen category. The orphan terms are
 * left behind once empty so the user can review and delete them afterwards.
 *
 * Run via WP-CLI:
 *   wp --url=sc-staging.eboy.gr eval-file recategorize-orphans-cli.php \
 *       1527 1535 1537 2299 2579 2586
 *
 * Optional flag: dry-run (don't write changes, just log what would happen):
 *   wp --url=sc-staging.eboy.gr eval-file recategorize-orphans-cli.php dry-run 1527 1535 ...
 */

$WHITELIST = [
    'Σαλόνι - Καθιστικό',
    'Υπνοδωμάτιο',
    'Γραφείο',
    'Οργάνωση',
    'Διακόσμηση',
    'Εξωτερικός Χώρος',
    'Μπάνιο',
    'Κουζίνα',
];

// Parse args (works for both `wp eval-file` $args and plain CLI $argv).
$cli_args = !empty($args) ? $args : array_slice($argv, 1);
$dry_run  = false;
$term_ids = [];
foreach ($cli_args as $arg) {
    if (strtolower(trim((string) $arg)) === 'dry-run') {
        $dry_run = true;
    } elseif (ctype_digit((string) $arg)) {
        $term_ids[] = (int) $arg;
    }
}

if (empty($term_ids)) {
    echo "Usage: wp --url=<site> eval-file recategorize-orphans-cli.php [dry-run] <term_id> [<term_id> ...]\n";
    exit(1);
}

$wrapper = dirname(__FILE__) . '/product-ai-processor/categorize_one.sh';
if (!file_exists($wrapper)) {
    echo "ERROR: AI wrapper not found at {$wrapper}\n";
    exit(1);
}

$total_moved   = 0;
$total_skipped = 0;
$total_failed  = 0;

foreach ($term_ids as $orphan_term_id) {
    $orphan_term = get_term($orphan_term_id, 'product_cat');
    if (!$orphan_term || is_wp_error($orphan_term)) {
        echo "Term {$orphan_term_id}: not found, skipping.\n";
        continue;
    }
    $orphan_name = $orphan_term->name;

    $product_ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'numberposts'    => -1,
        'fields'         => 'ids',
        'tax_query'      => [[
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $orphan_term_id,
        ]],
    ]);

    echo "\n=== Term {$orphan_term_id} '{$orphan_name}': " . count($product_ids) . " products ===\n";

    foreach ($product_ids as $pid) {
        $title = get_the_title($pid);

        // Send stderr (Python logs) to /tmp/recategorize-orphans.log so it doesn't
        // pollute stdout where the JSON payload lives. stdout-only is parsed below.
        $cmd = sprintf(
            'sh %s %s %s 2>>/tmp/recategorize-orphans.log',
            escapeshellarg($wrapper),
            escapeshellarg($orphan_name),
            escapeshellarg($title)
        );
        exec($cmd, $output_lines, $return_code);
        $raw = trim(implode("\n", $output_lines));
        $output_lines = [];

        if ($return_code !== 0) {
            echo "  [FAIL] ID {$pid} '{$title}' — AI exit {$return_code}: {$raw}\n";
            $total_failed++;
            continue;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['category'])) {
            echo "  [FAIL] ID {$pid} '{$title}' — bad AI output: {$raw}\n";
            $total_failed++;
            continue;
        }

        $new_cat_name = $decoded['category'];
        $confidence   = $decoded['confidence'] ?? 0;

        if (!in_array($new_cat_name, $WHITELIST, true)) {
            echo "  [FAIL] ID {$pid} '{$title}' — AI returned non-whitelisted '{$new_cat_name}'\n";
            $total_failed++;
            continue;
        }

        $new_term = get_term_by('name', $new_cat_name, 'product_cat');
        if (!$new_term) {
            echo "  [FAIL] ID {$pid} '{$title}' — whitelisted term '{$new_cat_name}' does not exist in WC\n";
            $total_failed++;
            continue;
        }

        // Preserve any other whitelisted categories the product already has;
        // just swap the orphan one for the AI-chosen one.
        $current_term_ids = wp_get_post_terms($pid, 'product_cat', ['fields' => 'ids']);
        if (is_wp_error($current_term_ids)) {
            $current_term_ids = [];
        }
        $new_term_ids = array_values(array_unique(array_merge(
            array_diff($current_term_ids, [$orphan_term_id]),
            [(int) $new_term->term_id]
        )));

        if ($dry_run) {
            echo "  [DRY] ID {$pid} '{$title}': {$orphan_name} → {$new_cat_name} (conf={$confidence})\n";
            $total_moved++;
            continue;
        }

        $set = wp_set_object_terms($pid, $new_term_ids, 'product_cat');
        if (is_wp_error($set)) {
            echo "  [FAIL] ID {$pid} — could not set terms: " . $set->get_error_message() . "\n";
            $total_failed++;
            continue;
        }

        echo "  [OK] ID {$pid} '{$title}': {$orphan_name} → {$new_cat_name} (conf={$confidence})\n";
        $total_moved++;
    }
}

$mode = $dry_run ? '[DRY-RUN] ' : '';
echo "\n{$mode}Done. Moved: {$total_moved}, Failed: {$total_failed}, Skipped: {$total_skipped}\n";

