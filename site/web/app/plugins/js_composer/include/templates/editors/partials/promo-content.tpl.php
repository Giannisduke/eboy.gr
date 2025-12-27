<?php
/**
 * Promo content template.
 *
 * @var bool $is_about_page
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>
<img class="vc-featured-img" src="<?php echo esc_url( vc_asset_url( 'vc/wpb-8-2-about.png' ) ); ?>"/>

<div class="vc-feature-text">
	<h3><?php esc_html_e( 'Introducing New Features in 8.2 Release', 'js_composer' ); ?></h3>

	<p><?php esc_html_e( 'This update introduces powerful improvements to the frontend editor, giving you more control over pages and posts, along with access to beta versions for compatibility tests and introduction to new features before the official releases.', 'js_composer' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Manage WordPress default post type settings (e.g. slug, title, excerpt, featured image) directly from the frontend editor page settings', 'js_composer' ); ?></li>
		<li><?php esc_html_e( 'Enable automatic updates for beta versions (use only on a development or staging site)', 'js_composer' ); ?></li>
	</ul>
	<?php
	$tabs = vc_settings()->getTabs();
	$is_license_tab_access = isset( $tabs['vc-updater'] ) && vc_user_access()->part( 'settings' )->can( 'vc-updater-tab' )->get();
	if ( $is_about_page && ! vc_license()->isActivated() && $is_license_tab_access ) :
		?>
		<div class="vc-feature-activation-section">
			<?php $url = 'admin.php?page=vc-updater'; ?>
			<a href="<?php echo esc_attr( is_network_admin() ? network_admin_url( $url ) : admin_url( $url ) ); ?>" class="vc-feature-btn" id="vc_settings-updater-button" data-vc-action="activation"><?php esc_html_e( 'Activate License', 'js_composer' ); ?></a>
			<p class="vc-feature-info-text">
				<?php esc_html_e( 'Direct plugin activation only.', 'js_composer' ); ?>
				<a href="https://wpbakery.com/wpbakery-page-builder-license/?utm_source=wpdashboard&utm_medium=wpb-settings-about-whats-new&utm_content=text" target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'Don\'t have a license?', 'js_composer' ); ?></a>
			</p>
		</div>
	<?php endif; ?>
</div>
