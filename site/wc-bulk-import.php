<?php
/**
 * WooCommerce Fast Bulk Import Script
 *
 * Usage: wp eval-file wc-bulk-import.php --url=simple-city.eboy.gr /path/to/file.csv
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    die( 'This script can only be run via WP-CLI' );
}

// Get CSV file path from command line arguments
$csv_file = $args[0] ?? null;

if ( ! $csv_file || ! file_exists( $csv_file ) ) {
    WP_CLI::error( 'Please provide a valid CSV file path as argument.' );
}

WP_CLI::line( '==================================================' );
WP_CLI::line( 'WooCommerce Fast Bulk Import' );
WP_CLI::line( '==================================================' );
WP_CLI::line( 'CSV File: ' . $csv_file );
WP_CLI::line( 'Site URL: ' . home_url() );
WP_CLI::line( '' );

// Disable FacetWP indexing temporarily
$facet_disabled = false;
if ( class_exists( 'FacetWP' ) ) {
    WP_CLI::line( '✓ Disabling FacetWP indexing...' );
    add_filter( 'facetwp_indexer_is_enabled', '__return_false' );
    $facet_disabled = true;
}

// Disable emails
add_filter( 'woocommerce_email_enabled_new_order', '__return_false' );
add_filter( 'woocommerce_email_enabled_customer_processing_order', '__return_false' );

// Increase time limit
set_time_limit( 0 );

WP_CLI::line( '✓ Starting import...' );
WP_CLI::line( '' );

// Load WooCommerce importer
if ( ! class_exists( 'WC_Product_CSV_Importer' ) ) {
    require_once WP_PLUGIN_DIR . '/woocommerce/includes/import/class-wc-product-csv-importer.php';
}

if ( ! class_exists( 'WC_Product_CSV_Importer_Controller' ) ) {
    require_once WP_PLUGIN_DIR . '/woocommerce/includes/admin/importers/class-wc-product-csv-importer-controller.php';
}

// Import settings
$params = array(
    'delimiter'       => ',',
    'prevent_timeouts' => false,
    'lines'           => 999999,
    'parse'           => true,
    'update_existing' => true,
);

try {
    $importer = new WC_Product_CSV_Importer( $csv_file, $params );

    $results = array(
        'imported' => 0,
        'updated'  => 0,
        'skipped'  => 0,
        'failed'   => 0,
        'errors'   => array(),
    );

    $position = 0;
    $step = 1;

    while ( ! $importer->is_complete() ) {
        $items = $importer->import();

        foreach ( $items as $item ) {
            $position++;

            if ( is_wp_error( $item ) ) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    'Row %d: %s',
                    $position,
                    $item->get_error_message()
                );
                WP_CLI::warning( "✗ Row {$position}: " . $item->get_error_message() );
            } else {
                if ( isset( $item['updated'] ) && $item['updated'] ) {
                    $results['updated']++;
                    WP_CLI::line( "↻ Updated product ID: {$item['id']}" );
                } else {
                    $results['imported']++;
                    WP_CLI::line( "✓ Imported product ID: {$item['id']}" );
                }
            }
        }

        $step++;
    }

    WP_CLI::line( '' );
    WP_CLI::line( '==================================================' );
    WP_CLI::success( 'Import completed!' );
    WP_CLI::line( '==================================================' );
    WP_CLI::line( sprintf( '✓ Imported: %d products', $results['imported'] ) );
    WP_CLI::line( sprintf( '↻ Updated: %d products', $results['updated'] ) );
    WP_CLI::line( sprintf( '✗ Failed: %d products', $results['failed'] ) );
    WP_CLI::line( '' );

    if ( ! empty( $results['errors'] ) ) {
        WP_CLI::warning( 'Errors encountered:' );
        foreach ( $results['errors'] as $error ) {
            WP_CLI::line( "  - {$error}" );
        }
        WP_CLI::line( '' );
    }

    // Re-enable FacetWP and reindex
    if ( $facet_disabled && class_exists( 'FacetWP' ) ) {
        WP_CLI::line( '✓ Re-enabling FacetWP...' );
        remove_filter( 'facetwp_indexer_is_enabled', '__return_false' );

        WP_CLI::line( '✓ Rebuilding FacetWP index (this may take a while)...' );

        // Trigger full reindex
        do_action( 'facetwp_refresh' );

        // Run indexer
        if ( method_exists( 'FacetWP', 'get_instance' ) ) {
            $fwp = FacetWP::get_instance();
            if ( isset( $fwp->indexer ) ) {
                $fwp->indexer->index();
            }
        }

        WP_CLI::success( 'FacetWP index rebuilt!' );
    }

    // Clear WooCommerce caches
    wc_delete_product_transients();
    wc_delete_shop_order_transients();

    WP_CLI::success( 'All done! Products imported successfully.' );

} catch ( Exception $e ) {
    WP_CLI::error( 'Import failed: ' . $e->getMessage() );
}
