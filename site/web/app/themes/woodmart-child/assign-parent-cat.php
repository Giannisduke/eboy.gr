<?php
// WP-CLI run-once script: add parent product_cat to products in child cats excluding one child cat.

$parent_slug  = 'christoygenna-2';
$exclude_slug = 'christoygenniatika-dentra';

$taxonomy = 'product_cat';

$parent_term = get_term_by('slug', $parent_slug, $taxonomy);
if (!$parent_term || is_wp_error($parent_term)) {
	WP_CLI::error("Parent category not found: {$parent_slug}");
}

$exclude_term = get_term_by('slug', $exclude_slug, $taxonomy);
if (!$exclude_term || is_wp_error($exclude_term)) {
	WP_CLI::error("Excluded category not found: {$exclude_slug}");
}

// Excluded term + descendants
$excluded_ids = array_map('intval', (array) get_term_children((int)$exclude_term->term_id, $taxonomy));
$excluded_ids[] = (int) $exclude_term->term_id;

// All direct children of parent
$children = get_terms([
	'taxonomy'   => $taxonomy,
	'parent'     => (int) $parent_term->term_id,
	'hide_empty' => false,
	'fields'     => 'ids',
]);

if (is_wp_error($children)) {
	WP_CLI::error("Could not fetch child categories.");
}

$children = array_map('intval', $children);
$children = array_values(array_diff($children, $excluded_ids));

if (empty($children)) {
	WP_CLI::success("No eligible child categories found under {$parent_slug} (after excluding {$exclude_slug}). Nothing to do.");
	exit(0);
}

WP_CLI::log("Parent: {$parent_slug} (term_id {$parent_term->term_id})");
WP_CLI::log("Exclude: {$exclude_slug} (excluded ids: " . implode(',', $excluded_ids) . ")");
WP_CLI::log("Eligible child ids: " . implode(',', $children));

$total_updated = 0;
$total_skipped = 0;

$page = 1;
$per_page = 500;

while (true) {
	$q = new WP_Query([
		'post_type'      => 'product',
		'post_status'    => ['publish', 'private', 'draft'], // άλλαξε αν θες μόνο publish
		'fields'         => 'ids',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'tax_query'      => [
			'relation' => 'AND',
			[
				'taxonomy'         => $taxonomy,
				'field'            => 'term_id',
				'terms'            => $children,
				'operator'         => 'IN',
				'include_children' => true, // πιάνει και deeper levels κάτω από τις υποκατηγορίες
			],
			[
				'taxonomy'         => $taxonomy,
				'field'            => 'term_id',
				'terms'            => $excluded_ids,
				'operator'         => 'NOT IN',
				'include_children' => true,
			],
		],
	]);

	if (!$q->have_posts()) {
		break;
	}

	foreach ($q->posts as $product_id) {
		$product_id = (int) $product_id;

		// Αν ήδη έχει τη γονική, skip
		if (has_term((int)$parent_term->term_id, $taxonomy, $product_id)) {
			$total_skipped++;
			continue;
		}

		// Append parent category (δεν αφαιρεί τίποτα)
		wp_set_object_terms($product_id, [(int)$parent_term->term_id], $taxonomy, true);
		$total_updated++;
	}

	wp_reset_postdata();
	WP_CLI::log("Processed page {$page} (updated: {$total_updated}, skipped: {$total_skipped})");
	$page++;

	// safety
	if ($page > 10000) {
		WP_CLI::warning("Safety stop triggered.");
		break;
	}
}

WP_CLI::success("Done. Updated: {$total_updated}, Skipped (already had parent): {$total_skipped}");
WP_CLI::log("Tip: run `wp transient delete --all` or clear WooCommerce transients if price ranges/caches look stale.");