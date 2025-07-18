<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;

$items_to_show = apply_filters( 'woodmart_mini_cart_items_to_show', 30 );

do_action( 'woocommerce_before_mini_cart' ); ?>

<div class="shopping-cart-widget-body wd-scroll">
	<div class="wd-scroll-content">

		<?php if ( ! WC()->cart->is_empty() ) : ?>
			
			<ul class="cart_list product_list_widget woocommerce-mini-cart <?php echo esc_attr( $args['list_class'] ); ?>">

				<?php
					do_action( 'woocommerce_before_mini_cart_contents' );

					$_i = 0;
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_i++;
						if( $_i > $items_to_show ) break;
						
						$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

						if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );

							$product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
							?>
							<li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?>" data-key="<?php echo esc_attr( $cart_item_key ); ?>">
								<a href="<?php echo esc_url( $product_permalink ); ?>" class="cart-item-link wd-fill"><?php esc_html_e('Show', 'woocommerce'); ?></a>
								<?php
									echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
										'<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
										esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
										esc_attr__( 'Remove this item', 'woocommerce' ),
										esc_attr( $product_id ),
										esc_attr( $cart_item_key ),
										esc_attr( $_product->get_sku() )
									), $cart_item_key );
								?>
								<?php if ( empty( $product_permalink ) ) : ?>
									<?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ); ?>
								<?php else : ?>
									<a href="<?php echo esc_url( $product_permalink ); ?>" class="cart-item-image">
										<?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ); ?>
									</a>
								<?php endif; ?>
								
								<div class="cart-info">
									<span class="wd-entities-title">
										<?php echo $product_name; ?>
									</span>
									<?php if ( woodmart_get_opt( 'show_sku_in_mini_cart' ) ) : ?>
										<div class="wd-product-sku">
											<span class="wd-label">
												<?php esc_html_e( 'SKU:', 'woodmart' ); ?>
											</span>
											<span>
												<?php if ( $_product->get_sku() ) : ?>
													<?php echo esc_html( $_product->get_sku() ); ?>
												<?php else : ?>
													<?php esc_html_e( 'N/A', 'woocommerce' ); ?>
												<?php endif; ?>
											</span>
										</div>
									<?php endif; ?>
									<?php
										echo wc_get_formatted_cart_item_data( $cart_item );
									?>

									<?php
									if ( ! $_product->is_sold_individually() && $_product->is_purchasable() && woodmart_get_opt( 'mini_cart_quantity' ) ) {
										woocommerce_quantity_input(
											array(
												'input_value' => $cart_item['quantity'],
												'min_value' => 0,
												'max_value' => $_product->backorders_allowed() ? '' : $_product->get_stock_quantity(),
											),
											$_product
										);
									}
									?>
									<?php echo sprintf( '<span class="monoqtylabel">%s</span>', __('Quantity','woocommerce')); ?>
									<?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<span class="quantity">' . sprintf( '%s', $product_price ) . '</span>', $cart_item, $cart_item_key ); ?>
								</div>

							</li>
							<?php
						}
					}

					do_action( 'woocommerce_mini_cart_contents' );

				?>
			</ul><!-- end product list -->
			
		<?php else : ?>
			<!-- antzel empty cart child -->
			<div class="wd-empty-mini-cart">
				<span class="mono-singleline"></span>
				<p class="woocommerce-mini-cart__empty-message empty title"><?php esc_html_e( 'No products in the cart.', 'woocommerce' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</div>

<div class="shopping-cart-widget-footer<?php echo ( WC()->cart->is_empty() ? ' wd-cart-empty' : '' ); ?>">
	<?php if ( ! WC()->cart->is_empty() ) : ?>

			<p class="woocommerce-mini-cart__total total"><strong><?php esc_html_e( 'Total', 'woocommerce' ); ?>:</strong> <?php echo WC()->cart->get_cart_subtotal(); ?></p>

		<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

		<p class="woocommerce-mini-cart__buttons buttons"><?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?></p>

		<?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_mini_cart' ); ?>
</div>
