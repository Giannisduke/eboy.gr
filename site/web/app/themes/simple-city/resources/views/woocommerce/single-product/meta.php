<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.7.0
 */

use Automattic\WooCommerce\Enums\ProductType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

$shop_url  = add_query_arg( [ 'orderby' => 'date', 'order' => 'desc' ], home_url( '/' ) );
$cat_terms = get_the_terms( $product->get_id(), 'product_cat' );
$tag_terms = get_the_terms( $product->get_id(), 'product_tag' );
?>
<ul class="product_meta list-inline">

	<?php do_action( 'woocommerce_product_meta_start' ); ?>

	<?php if ( $cat_terms && ! is_wp_error( $cat_terms ) ) : ?>
		<?php foreach ( $cat_terms as $cat ) : ?>
			<li class="list-inline-item">
				<span class="posted_in">
					<a href="<?php echo esc_url( add_query_arg( 'category', $cat->term_id, $shop_url ) ); ?>">
						<?php echo esc_html( $cat->name ); ?>
					</a>
				</span>
			</li>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php if ( $tag_terms && ! is_wp_error( $tag_terms ) ) : ?>
		<?php foreach ( $tag_terms as $tag ) : ?>
			<li class="list-inline-item">
				<span class="tagged_as">
					<a href="<?php echo esc_url( add_query_arg( 'tags', $tag->term_id, $shop_url ) ); ?>">
						<?php echo esc_html( $tag->name ); ?>
					</a>
				</span>
			</li>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>

</ul>
