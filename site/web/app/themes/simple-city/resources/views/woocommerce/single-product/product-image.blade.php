<?php
/**
 * Single Product Image — Bootstrap carousel override
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
    return;
}

global $product;

$columns         = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$wrapper_classes = apply_filters(
    'woocommerce_single_product_image_gallery_classes',
    [
        'woocommerce-product-gallery',
        'woocommerce-product-gallery--' . ( $post_thumbnail_id ? 'with-images' : 'without-images' ),
        'woocommerce-product-gallery--columns-' . absint( $columns ),
        'images',
    ]
);

// Build image + thumbnail URL lists
$image_links = [];
$thumb_links  = [];

$featured_url   = get_the_post_thumbnail_url( '', 'woocommerce_single' );
$featured_thumb = get_the_post_thumbnail_url( '', 'woocommerce_gallery_thumbnail' );
if ( $featured_url ) {
    $image_links[] = $featured_url;
    $thumb_links[]  = $featured_thumb ?: $featured_url;
}
foreach ( $product->get_gallery_image_ids() as $attachment_id ) {
    $url   = wp_get_attachment_image_url( $attachment_id, 'woocommerce_single' );
    $thumb = wp_get_attachment_image_url( $attachment_id, 'woocommerce_gallery_thumbnail' );
    if ( $url ) {
        $image_links[] = $url;
        $thumb_links[]  = $thumb ?: $url;
    }
}

$total = count( $image_links );
$carousel_id = 'product-gallery-' . get_the_ID();
?>

<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>"
     data-columns="<?php echo esc_attr( $columns ); ?>"
>

    <div id="<?php echo esc_attr( $carousel_id ); ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">

        <div class="carousel-inner">
            <?php foreach ( $image_links as $i => $url ) : ?>
                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                    <img class="d-block prod-card-img"
                         src="<?php echo esc_url( $url ); ?>"
                         alt="<?php echo esc_attr( get_the_title() ); ?>" />
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ( $total > 1 ) : ?>
            <div class="carousel-indicators carousel-thumb-indicators">
                <?php foreach ( $thumb_links as $i => $thumb ) : ?>
                    <button type="button"
                            data-bs-target="#<?php echo esc_attr( $carousel_id ); ?>"
                            data-bs-slide-to="<?php echo $i; ?>"
                            <?php echo $i === 0 ? 'class="active" aria-current="true"' : ''; ?>
                            aria-label="<?php echo esc_attr( sprintf( __( 'Slide %d', 'woocommerce' ), $i + 1 ) ); ?>">
                        <img src="<?php echo esc_url( $thumb ); ?>"
                             alt="<?php echo esc_attr( sprintf( __( 'Slide %d', 'woocommerce' ), $i + 1 ) ); ?>" />
                    </button>
                <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button"
                    data-bs-target="#<?php echo esc_attr( $carousel_id ); ?>"
                    data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden"><?php esc_html_e( 'Previous', 'woocommerce' ); ?></span>
            </button>
            <button class="carousel-control-next" type="button"
                    data-bs-target="#<?php echo esc_attr( $carousel_id ); ?>"
                    data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden"><?php esc_html_e( 'Next', 'woocommerce' ); ?></span>
            </button>
        <?php endif; ?>

    </div>

</div>
