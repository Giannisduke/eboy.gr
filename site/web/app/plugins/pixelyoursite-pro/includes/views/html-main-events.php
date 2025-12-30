<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$new_event_url = buildAdminUrl( 'pixelyoursite', 'events', 'edit' );
$export_url = buildAdminUrl( 'pixelyoursite', 'events', 'export' )."&_wpnonce=".wp_create_nonce("export_events_file_nonce")
?>

<input type="hidden" name="pys[bulk_event_action_nonce]" value="<?php echo wp_create_nonce( 'bulk_event_action' ); ?>">

<h2 class="section-title">User Defined Events</h2>

<div class="card card-static">
    <div class="card-header">
        General
    </div>
    <div class="card-body">
	    <?php PYS()->render_switcher_input( 'custom_events_enabled' ); ?>
        <h4 class="switcher-label">Enable Events</h4>
        <div class="mt-3">
            <input type="hidden" id="import_events_file_nonce" value="<?=wp_create_nonce("import_events_file_nonce")?>"/>
            <input type="file" id="import_events_file" name="import_events_file" accept="application/json"/>
            <label for="import_events_file" class="btn btn-sm btn-primary btn-events-import">Import Events</label>
            <a href="<?=$export_url?>" target="_blank" class="btn  ml-3 btn-sm btn-primary btn-events-export">Export Events</a>
        </div>
    </div>

</div>
<div class="card card-static">
    <div class="card-header">
        Recommeded videos
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <p><a href="https://www.youtube.com/watch?v=kEp5BDg7dP0" target="_blank">How to fire EVENTS with PixelYourSite (22:28) - watch now</a></p>
                <p><a href="https://www.youtube.com/watch?v=PcXYYGOvahc" target="_blank">Track URL tags as event parameters (8:15) - watch now</a></p>
                <p><a href="https://www.youtube.com/watch?v=ehi66agv1zo" target="_blank">Track YouTube or Vimeo Embedded Videos With Your Own Events (5:25) - watch now</a></p>
                <p><a href="https://www.youtube.com/watch?v=c4Hrb8WK5bw" target="_blank">Fire a LEAD event on form submit - WordPress & PixelYourSite (5:58) - watch video</a></p>
                <p><a href="https://www.youtube.com/watch?v=0IFHqI6itx8" target="_blank">Improve Meta EMQ score with when tracking Wordpress forms (4:52) - watch video</a></p>
                <p><a href="https://www.youtube.com/watch?v=Iyu-pSbqcFI" target="_blank">Mandatory: Verify your Meta CUSTOM Events (Pixel & CAPI) (5:10) - watch video</a></p>

            </div>
        </div>
    </div>
</div>

<div class="card card-static">
    <div class="card-header">
        About Parameters
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col">
                <p>All the events you configure here will automatically get the following parameters for all the tags:
                    <i>page_title, post_type, post_id, landing_page, event_URL, user_role, plugin, event_time (pro), event_day (pro), event_month (pro), traffic_source (pro), UTMs (pro).</i></p>
                <p>You can add other parameters when you configure the events.</p>
            </div>
        </div>
    </div>
</div>

<div class="card card-static custom_events">
    <div class="card-header">
        Events List
    </div>
    <div class="card-body">
        <div class="row mb-3 bulk-events-block">
            <div class="col">
                <a href="<?php echo esc_url( $new_event_url ); ?>" class="btn btn-sm btn-primary mr-3">Add</a>
                <button class="btn btn-sm btn-light" name="pys[bulk_event_action]" value="enable" type="submit">Enable</button>
                <button class="btn btn-sm btn-light" name="pys[bulk_event_action]" value="disable" type="submit">Disable</button>
                <button class="btn btn-sm btn-light" name="pys[bulk_event_action]" value="clone" type="submit">Duplicate</button>
                <button class="btn btn-sm btn-danger ml-3 bulk-events-delete" name="pys[bulk_event_action]" value="delete" type="submit">Delete</button>

            </div>
        </div>
        <div class="row">
            <div class="col">
                <table class="table mb-0" id="table-custom-events">
                    <thead>
                    <tr>
                        <th style="width: 45px;">
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" id="pys_select_all_events" value="1" class="custom-control-input">
                                <span class="custom-control-indicator"></span>
                            </label>
                        </th>
                        <th>Name</th>
                        <th>Triggers</th>
                        <th>Networks</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach ( CustomEventFactory::get() as $event ) : ?>

                        <?php
                        $errorMessage = "";
                        /** @var CustomEvent $event */

                        $event_edit_url = buildAdminUrl( 'pixelyoursite', 'events', 'edit', array(
                            'id' => $event->getPostId()
                        ) );

                        $event_enable_url = buildAdminUrl( 'pixelyoursite', 'events', 'enable', array(
                            'pys'      => array(
                                'event' => array(
                                    'post_id' => $event->getPostId(),
                                )
                            ),
                            '_wpnonce' => wp_create_nonce( 'pys_enable_event' ),
                        ) );

                        $event_disable_url = buildAdminUrl( 'pixelyoursite', 'events', 'disable', array(
                            'pys'      => array(
                                'event' => array(
                                    'post_id' => $event->getPostId(),
                                )
                            ),
                            '_wpnonce' => wp_create_nonce( 'pys_disable_event' ),
                        ) );

                        $event_remove_url = buildAdminUrl( 'pixelyoursite', 'events', 'remove', array(
                            'pys'      => array(
                                'event' => array(
                                    'post_id' => $event->getPostId(),
                                )
                            ),
                            '_wpnonce' => wp_create_nonce( 'pys_remove_event' ),
                        ) );

                        $triggers = $event->getTriggers();
                        $event_types = array();

						if ( !empty( $triggers ) ) {
							foreach ( $triggers as $trigger ) {
								$trigger_type = $trigger->getTriggerType();
								switch ( $trigger_type ) {
									case 'number_page_visit':
										$event_types[] = 'Number of Page Visits';
										break;
									case 'post_type':
										{
											$event_types[] = 'Post Type';
											$selectedPostType = $trigger->getPostTypeValue();
											$errorMessage = "Post type not found";
											$types = get_post_types( null, "objects " );
											foreach ( $types as $type ) {
												if ( $type->name == $selectedPostType ) {
													$errorMessage = "";
													break;
												}
											}

										}
										break;

									case 'url_click':
										$event_types[] = 'Link Click';
										break;

									case 'css_click':
										$event_types[] = 'Element Click';
										break;

									case 'css_mouseover':
										$event_types[] = 'Element Mouseover';
										break;

									case 'scroll_pos':
										$event_types[] = 'Scroll Position';
										break;

									case 'video_view':
										$event_types[] = 'Embedded Video View';
										break;

									case 'email_link':
										$event_types[] = 'Email Link';
										break;

									case 'page_visit':
										$event_types[] = 'Page Visit';
										break;

                                    case 'home_page':
                                        $event_types[] = 'Home Page Visit';
                                        break;
								}
								if ( $trigger->isFormTriggerType( $trigger_type ) ) {
									$eventsFormFactory = apply_filters( "pys_form_event_factory", [] );
									foreach ( $eventsFormFactory as $activeFormPlugin ) :
										if ( $activeFormPlugin->getSlug() == $trigger_type ) {
											$event_types[] = $activeFormPlugin->getName();
										}
									endforeach;
								}
							}
						}

						if ( empty( $event_types ) ) {
							$event_types[] = 'No triggers';
						}
                        ?>

                        <tr data-post_id="<?php esc_attr_e( $event->getPostId() ); ?>"
                            class="<?php echo $event->isEnabled() ? '' : 'disabled'; ?>">
                            <td>
                                <label class="custom-control custom-checkbox">
                                    <input type="checkbox" name="pys[selected_events][]"
                                           value="<?php esc_attr_e( $event->getPostId() ); ?>"
                                           class="custom-control-input pys-select-event">
                                    <span class="custom-control-indicator"></span>
                                </label>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $event_edit_url ); ?>"><?php esc_html_e( $event->getTitle() ); ?></a>
                                <span class="event-actions">
                                    <?php if ( $event->isEnabled() ) : ?>
                                        <a href="<?php echo esc_url( $event_disable_url ); ?>">Disable</a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url( $event_enable_url ); ?>">Enable</a>
                                    <?php endif; ?>
                                    &nbsp;|&nbsp;
                                    <a href="<?php echo esc_url( $event_remove_url ); ?>" class="
                                    text-danger remove-custom-event">Remove</a>
                                </span>
                            </td>
                            <td>
                                <?php echo wp_kses_post( implode('<br>', $event_types) ); ?>
                                <?php if($errorMessage != "") : ?>
                                    <div class="event_error">
                                        <?=$errorMessage?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="networks">
                                <?php if ( Facebook()->enabled() && $event->isFacebookEnabled() ) : ?>
                                    <i class="fa fa-facebook-square"></i>
                                <?php else : ?>
                                    <i class="fa fa-facebook-square" style="opacity: .25;"></i>
                                <?php endif; ?>

                                <?php if ( $event->isUnifyAnalyticsEnabled() && $event->isGoogleAnalyticsPresent()) : ?>
                                    <i class="fa fa-area-chart"></i>
                                <?php else : ?>
                                    <i class="fa fa-area-chart" style="opacity: .25;"></i>
                                <?php endif; ?>

	                            <?php if ( $event->isUnifyAnalyticsEnabled() && $event->isGoogleAdsPresent()) : ?>
                                    <i class="fa fa-google"></i>
	                            <?php else : ?>
                                    <i class="fa fa-google" style="opacity: .25;"></i>
	                            <?php endif; ?>

                                <?php if ( $event->isGTMEnabled() && $event->isGTMPresent()) : ?>
                                    <img class="gtm-logo" src="<?php echo PYS_URL; ?>/dist/images/google-tag-manager.png">
                                <?php else : ?>
                                    <img class="gtm-logo" src="<?php echo PYS_URL; ?>/dist/images/google-tag-manager.png" style="opacity: 0.25">
                                <?php endif; ?>

                                <?php if ( Pinterest()->enabled() && $event->isPinterestEnabled() ) : ?>
                                    <i class="fa fa-pinterest-square"></i>
                                <?php else : ?>
                                    <i class="fa fa-pinterest-square" style="opacity: .25;"></i>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<hr>
<div class="row justify-content-center">
    <div class="col-4">
        <button class="btn btn-block btn-sm btn-save">Save Settings</button>
    </div>
</div>
    <?php function enableEventForEachPixel($formPlugin, $event, $fb = true, $ga = true, $ads = true, $bi = true, $tic = true, $pin = true)
{ ?>
    <?php if ($fb && Facebook()->enabled()) : ?>
    <div class="row">
        <div class="col">
            <?php $formPlugin->render_switcher_input($event.'_'.Facebook()->getSlug()); ?>
            <h4 class="switcher-label">Enable on Facebook</h4>
        </div>
    </div>
<?php endif; ?>
    <?php if ($ga && GA()->enabled()) : ?>
    <div class="row">
        <div class="col">
            <?php $formPlugin->render_switcher_input($event.'_'.GA()->getSlug()); ?>
            <h4 class="switcher-label">Enable on Google Analytics</h4>
        </div>
    </div>
<?php endif; ?>

    <?php if ($ads && Ads()->enabled()) : ?>
    <div class="row">
        <div class="col">
            <?php $formPlugin->render_switcher_input($event.'_'.Ads()->getSlug()); ?>
            <h4 class="switcher-label">Enable on Google Ads</h4>
        </div>
    </div>
<?php endif; ?>

    <?php if ($bi && Bing()->enabled()) : ?>
    <div class="row">
        <div class="col">
            <?php $formPlugin->render_switcher_input($event.'_'.Bing()->getSlug()); ?>
            <h4 class="switcher-label">Enable on Bing</h4>
        </div>
    </div>
<?php endif; ?>
    <?php if ($pin && Pinterest()->enabled()) : ?>
    <div class="row">
        <div class="col">
            <?php $formPlugin->render_switcher_input($event.'_'.Pinterest()->getSlug()); ?>
            <h4 class="switcher-label">Enable on Pinterest</h4>
        </div>
    </div>
<?php endif; ?>
    <?php if ($tic && Tiktok()->enabled()) : ?>
    <div class="row">
        <div class="col">
            <?php $formPlugin->render_switcher_input($event.'_'.Tiktok()->getSlug()); ?>
            <h4 class="switcher-label">Enable on TikTok</h4>
        </div>
    </div>
<?php endif; ?>
    <?php
}
