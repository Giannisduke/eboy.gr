<?php

namespace PixelYourSite;

use PixelYourSite\Events;

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( isset( $_REQUEST[ 'id' ] ) ) {
	$id = sanitize_key( $_REQUEST[ 'id' ] );
	$event = CustomEventFactory::getById( $id );
} else {
	$event = new CustomEvent();
}

?>

<?php wp_nonce_field( 'pys_update_event' ); ?>
<input type="hidden" name="action" value="update">
<?php Events\renderHiddenInput( $event, 'post_id' ); ?>

<div class="card card-static">
    <div class="card-header">
        General
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col">
				<?php Events\renderSwitcherInput( $event, 'enabled' ); ?>
                <h4 class="switcher-label">Enable event</h4>
            </div>
        </div>
        <div class="row">
            <div class="col">
				<?php Events\renderTextInput( $event, 'title', 'Enter event title' ); ?>
                <input type="hidden" id="get_transform_title_wpnonce" value="<?=wp_create_nonce("get_transform_title_wpnonce")?>"/>
                <small class="form-text">This name will be used in the GTM data layer for the custom parameters object.</small>
            </div>
        </div>

        <div class="row mt-3" id="fire_event_once">
            <div class="col form-inline">
				<?php Events\renderSwitcherInput( $event, 'enable_time_window' ); ?>
                <label>Fire this event only once in</label>
				<?php Events\renderNumberInput( $event, 'time_window', '24' ); ?>
                <label>hours</label>
            </div>
        </div>

    </div>
</div>

<div class="card card-static">
    <div class="card-header">
        Event Triggers
    </div>
    <div class="pys_triggers_wrapper">

		<?php
		$event_triggers = $event->getTriggers();
		if ( !empty( $event_triggers ) ) :
			foreach ( $event_triggers as $event_trigger ) :
				$i = $event_trigger->getTriggerIndex();
				$trigger_type = $event_trigger->getTriggerType()
				?>
                <div class="card-body trigger_group" data-trigger_id="<?php echo esc_attr( $i ); ?>">
                    <div class="pys_remove_trigger">
                        <button type="button" class="btn remove-row">
                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                        </button>
                    </div>
					<?php
					if ( $trigger_type == "post_type" ) :
						$selectedPostType = $event_trigger->getPostTypeValue();
						$errorMessage = "Post type " . $selectedPostType . " not found: the post type that triggers this event is not found on the website. This event can't fire.";
						$types = get_post_types( null, "objects " );
						foreach ( $types as $type ) {
							if ( $type->name == $selectedPostType ) {
								$errorMessage = "";
								break;
							}
						}
						if ( $errorMessage != "" ) :?>
                            <div class="row mb-3 post_type_error">
                                <div class="col event_error"><?= $errorMessage ?>  </div>
                            </div>
						<?php endif;
					endif; ?>
                    <div class="row mb-3">
                        <div class="col form-inline">
                            <label>Fire event when</label>
							<?php Events\renderTriggerTypeInput( $event_trigger, 'trigger_type' ); ?>

							<?php if ( $trigger_type == "post_type" ) : ?>
                                <div class="trigger_post_type form-inline">
									<?php Events\renderPostTypeSelect( $event_trigger, 'post_type_value' ); ?>
                                </div>
							<?php endif; ?>
                            <div class="insert-marker-trigger post_type_marker"></div>

                            <div class="event-delay form-inline">
                                <label>with delay</label>
								<?php Events\renderTriggerNumberInput( $event_trigger, 'delay', '0' ); ?>
                                <label>seconds</label>
                            </div>

							<?php if ( $trigger_type == "number_page_visit" ) : ?>
                                <div class="event_triggers_panel number_page_visit_panel number_page_visit_conditional_panel d-flex form-inline"
                                     data-trigger_type="number_page_visit"
                                     style="display: none;">
                                    <div class="trigger_number_page_visit conditional_number_visit form-inline">
										<?php Events\renderTriggerConditionalNumberPage( $event_trigger, 'conditional_number_visit' ); ?>
                                    </div>

                                    <div class="trigger_number_page_visit number_visit form-inline">
										<?php Events\renderTriggerNumberInput( $event_trigger, 'number_visit', '0', 3 ); ?>
                                        <label>visited page</label>
                                    </div>
                                </div>
							<?php endif; ?>

                            <div class="insert-marker-trigger number_page_visit_conditional_marker"></div>
                        </div>
                    </div>

					<?php if ( $trigger_type == "page_visit" ) : ?>
                        <div class="event_triggers_panel page_visit_panel" data-trigger_type="page_visit"
                             style="display: none;">
                            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-4">
                                            <select class="form-control-sm" name="" autocomplete="off"
                                                    style="width: 100%;">
                                                <option value="contains">URL Contains</option>
                                                <option value="match">URL Match</option>
                                                <option value="param_contains">URL Parameters Contains</option>
                                                <option value="param_match">URL Parameters Match</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input name="" placeholder="Enter URL" class="form-control"
                                                   type="text">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php foreach ( $event_trigger->getPageVisitTriggers() as $key => $trigger ) : ?>

                                <div class="row mt-3 event_trigger"
                                     data-trigger_id="<?php echo esc_attr( $key ); ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-control-sm"
                                                        name='<?php echo esc_attr( "pys[event][triggers][$i][page_visit_triggers][$key][rule]" ); ?>'
                                                        autocomplete="off" style="width: 100%;">
                                                    <option value="contains" <?php selected( $trigger[ 'rule' ], 'contains' ); ?>>
                                                        URL Contains
                                                    </option>
                                                    <option value="match" <?php selected( $trigger[ 'rule' ], 'match' ); ?>>
                                                        URL Match
                                                    </option>
                                                    <option value="param_contains" <?php selected( $trigger[ 'rule' ], 'param_contains' ); ?>>
                                                        URL Parameters Contains
                                                    </option>
                                                    <option value="param_match" <?php selected( $trigger[ 'rule' ], 'param_match' ); ?>>
                                                        URL Parameters Match
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <input type="text" placeholder="Enter URL" class="form-control"
                                                       name='<?php echo esc_attr( "pys[event][triggers][$i][page_visit_triggers][$key][value]" ); ?>'
                                                       value="<?php esc_attr_e( $trigger[ 'value' ] ); ?>">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

							<?php endforeach; ?>

                            <div class="insert-marker"></div>
                            <div class="mt-3">
                                <small>You can also use <b>*</b> as the trigger URL on all pages</small>
                            </div>
                            <div class="row mt-3">
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-event-trigger"
                                            type="button">Add
                                        another
                                        URL
                                    </button>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger page_visit_marker"></div>

					<?php if ( $trigger_type == "number_page_visit" ) : ?>
                        <div class="event_triggers_panel number_page_visit_panel number_page_visit_url_panel"
                             data-trigger_type="number_page_visit"
                             style="display: none;">
                            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-4">
                                            <select class="form-control-sm pys_number_page_visit_triggers"
                                                    name=""
                                                    autocomplete="off" style="width: 100%;">
                                                <option value="any">Any URL`s</option>
                                                <option value="contains">URL Contains</option>
                                                <option value="match">URL Match</option>
                                                <option value="param_contains">URL Parameters Contains</option>
                                                <option value="param_match">URL Parameters Match</option>
                                            </select>
                                        </div>
                                        <div class="col-6 trigger_url" style="display: none">
                                            <input name="" placeholder="Enter URL"
                                                   class="form-control pys_number_page_visit_triggers"
                                                   type="text">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php foreach ( $event_trigger->getNumberPageVisitTriggers() as $key => $trigger ) : ?>

                                <div class="row mt-3 event_trigger"
                                     data-trigger_id="<?php echo esc_attr( $key ); ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-control-sm pys_number_page_visit_triggers"
                                                        name='<?php echo esc_attr( "pys[event][triggers][$i][number_page_visit_triggers][$key][rule]" ); ?>'
                                                        autocomplete="off" style="width: 100%;">
                                                    <option value="any" <?php selected( $trigger[ 'rule' ], 'any' ); ?>>
                                                        Any
                                                        URL`s
                                                    </option>
                                                    <option value="contains" <?php selected( $trigger[ 'rule' ], 'contains' ); ?>>
                                                        URL Contains
                                                    </option>
                                                    <option value="match" <?php selected( $trigger[ 'rule' ], 'match' ); ?>>
                                                        URL Match
                                                    </option>
                                                    <option value="param_contains" <?php selected( $trigger[ 'rule' ], 'param_contains' ); ?>>
                                                        URL Parameters Contains
                                                    </option>
                                                    <option value="param_match" <?php selected( $trigger[ 'rule' ], 'param_match' ); ?>>
                                                        URL Parameters Match
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-6 trigger_url" <?php if ( $trigger[ 'rule' ] === "any" ) : ?> style="display:none;"  <?php endif; ?>>
                                                <input type="text" placeholder="Enter URL"
                                                       class="form-control pys_number_page_visit_triggers"
                                                       name='<?php echo esc_attr( "pys[event][triggers][$i][number_page_visit_triggers][$key][value]" ); ?>'
                                                       value="<?php if ( $trigger[ 'rule' ] !== "any" ) : esc_attr_e( $trigger[ 'value' ] ); endif; ?>">
                                            </div>

                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

							<?php endforeach; ?>

                            <div class="insert-marker"></div>
                            <div class="mt-3">
                                <small>You can also use <b>*</b> as the trigger URL on all pages</small>
                            </div>

                            <div class="row mt-3">
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-event-trigger"
                                            type="button">Add
                                        another
                                        URL
                                    </button>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger number_page_visit_url_marker"></div>

					<?php if ( $trigger_type == "url_click" ) : ?>
                        <div class="event_triggers_panel url_click_panel" data-trigger_type="url_click"
                             style="display: none;">
                            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-4">
                                            <select class="form-control-sm" name="" autocomplete="off"
                                                    style="width: 100%;">
                                                <option value="contains">URL Contains</option>
                                                <option value="match">URL Match</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input name="" placeholder="Enter URL" class="form-control"
                                                   type="text">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php foreach ( $event_trigger->getURLClickTriggers() as $key => $trigger ) : ?>

                                <div class="row mt-3 event_trigger"
                                     data-trigger_id="<?php echo esc_attr( $key );; ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-control-sm" title=""
                                                        name='<?php echo esc_attr( "pys[event][triggers][$i][url_click_triggers][$key][rule]" ); ?>'
                                                        autocomplete="off" style="width: 100%;">
                                                    <option value="contains" <?php selected( $trigger[ 'rule' ], 'contains' ); ?>>
                                                        URL Contains
                                                    </option>
                                                    <option value="match" <?php selected( $trigger[ 'rule' ], 'match' ); ?>>
                                                        URL Match
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <input type="text" placeholder="Enter URL" class="form-control"
                                                       name='<?php echo esc_attr( "pys[event][triggers][$i][url_click_triggers][$key][value]" ); ?>'
                                                       value="<?php esc_attr_e( $trigger[ 'value' ] ); ?>">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

							<?php endforeach; ?>

                            <div class="insert-marker"></div>

                            <div class="row mt-3 mb-5">
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-event-trigger"
                                            type="button">Add
                                        another
                                        URL
                                    </button>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger url_click_marker"></div>


					<?php if ( $trigger_type == "css_click" ) : ?>
                        <div class="event_triggers_panel css_click_panel" data-trigger_type="css_click"
                             style="display: none;">
                            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-10">
                                            <input name="" placeholder="Enter CSS selector" class="form-control"
                                                   type="text">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php foreach ( $event_trigger->getCSSClickTriggers() as $key => $trigger ) : ?>

                                <div class="row mt-3 event_trigger"
                                     data-trigger_id="<?php echo esc_attr( $key );; ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-10">
                                                <input type="text" placeholder="Enter CSS selector"
                                                       class="form-control"
                                                       name='<?php echo esc_attr( "pys[event][triggers][$i][css_click_triggers][$key][value]" ); ?>'
                                                       value="<?php esc_attr_e( $trigger[ 'value' ] ); ?>">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

							<?php endforeach; ?>

                            <div class="insert-marker"></div>

                            <div class="row mt-3 mb-5">
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-event-trigger"
                                            type="button">Add
                                        another
                                        selector
                                    </button>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger css_click_marker"></div>

					<?php if ( $trigger_type == "css_mouseover" ) : ?>
                        <div class="event_triggers_panel css_mouseover_panel" data-trigger_type="css_mouseover"
                             style="display: none;">
                            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-10">
                                            <input name="" placeholder="Enter CSS selector" class="form-control"
                                                   type="text">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php foreach ( $event_trigger->getCSSMouseOverTriggers() as $key => $trigger ) : ?>

                                <div class="row mt-3 event_trigger"
                                     data-trigger_id="<?php echo esc_attr( $key );; ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-10">
                                                <input type="text" placeholder="Enter CSS selector"
                                                       class="form-control"
                                                       name='<?php echo esc_attr( "pys[event][triggers][$i][css_mouseover_triggers][$key][value]" ); ?>'
                                                       value="<?php esc_attr_e( $trigger[ 'value' ] ); ?>">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

							<?php endforeach; ?>

                            <div class="insert-marker"></div>

                            <div class="row mt-3 mb-5">
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-event-trigger"
                                            type="button">Add
                                        another
                                        selector
                                    </button>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger css_mouseover_marker"></div>

					<?php if ( $trigger_type == "scroll_pos" ) : ?>
                        <div class="event_triggers_panel scroll_pos_panel" data-trigger_type="scroll_pos"
                             style="display: none;">
                            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-3">
                                            <input name="" class="form-control" type="number" min="0" max="100">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

							<?php foreach ( $event_trigger->getScrollPosTriggers() as $key => $trigger ) : ?>

                                <div class="row mt-3 event_trigger"
                                     data-trigger_id="<?php echo esc_attr( $key );; ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-3">
                                                <input type="number" min="0" max="100" class="form-control"
                                                       name='<?php echo esc_attr( "pys[event][triggers][$i][scroll_pos_triggers][$key][value]" ); ?>'
                                                       value="<?php esc_attr_e( (int) $trigger[ 'value' ] ); ?>">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

							<?php endforeach; ?>

                            <div class="insert-marker"></div>

                            <div class="row mt-3 mb-5">
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-event-trigger"
                                            type="button">Add
                                        another
                                        threshold
                                    </button>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger scroll_pos_marker"></div>

					<?php $eventsFormFactory = apply_filters( "pys_form_event_factory", [] );
					foreach ( $eventsFormFactory as $activeFormPlugin ) : ?>
						<?php if ( $trigger_type == $activeFormPlugin->getSlug() ) : ?>
                            <?php if ( $activeFormPlugin->getSlug() == "elementor_form" ) : ?>
                                <div class="event_triggers_panel <?php echo $activeFormPlugin->getSlug(); ?>_panel elementor_form"
                                     data-trigger_type="<?php echo $activeFormPlugin->getSlug(); ?>"
                                     style="display: none;">

                                    <?php $data = $event_trigger->getElementorFormData();
                                    $urls = $event_trigger->getElementorFormUrls();
                                    $urls = array_combine( $urls, $urls );
                                    ?>
                                    <div class="row mt-3 event_trigger" data-trigger_id="0">
                                        <div class="col">
                                            <div class="row">
                                                <div class="col-10">
                                                    <small class="form-text mb-1">Enter Elementor form pages URL</small>
                                                    <input type="hidden" class="pys_event_elementor_form_data"
                                                           name="<?php echo esc_attr( "pys[event][triggers][$i][elementor_form_data]" ); ?>"
                                                           value="<?php echo htmlspecialchars( json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ), ENT_QUOTES, 'UTF-8' ) ?>">
                                                    <?php Events\render_multi_select_trigger_input( $event_trigger, 'elementor_form_urls', $urls, $event_trigger->getElementorFormUrls(), false, '', 'pys-tags-pysselect2 pys_elementor_form_urls_event' ); ?>

                                                </div>
                                                <div class="col-2 d-flex align-items-center pt-3">
                                                    <button class="btn btn-sm btn-block btn-primary pys-scan-elementor-form"
                                                            type="button"
                                                            value="Scan forms">Scan forms
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pys_elementor_form_triggers" data-trigger_id="0"
                                         style="<?php echo empty( $data ) ? 'display: none;' : ''; ?>">
                                        <div class="row mt-3 event_trigger" data-trigger_id="0">
                                            <div class="col">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <small class="form-text mb-1">Select forms</small>
                                                        <?php Events\render_multi_select_trigger_form_input( $event_trigger, $activeFormPlugin, false, '', true, 'pys_elementor_form_triggers_event' ); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-3 event_trigger" data-trigger_id="0">
                                            <div class="col switcher_event_disabled_form_action">
                                                <?php Events\renderSwitcherTriggerFormInput( $event_trigger, $activeFormPlugin ); ?>
                                                <h4 class="switcher-label">Disable the Form event for the same forms</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3 elementor_form_error" style="display: none">
                                        <div class="col event_error"></div>
                                    </div>
                                </div>

					        <?php else : ?>
                                <div class="event_triggers_panel <?php echo $activeFormPlugin->getSlug(); ?>_panel"
                                     data-trigger_type="<?php echo $activeFormPlugin->getSlug(); ?>"
                                     style="display: none;">
                                    <div class="row mt-3 event_trigger" data-trigger_id="0">
                                        <div class="col select_event_trigger_form_wrapper">
                                            <?php Events\render_multi_select_trigger_form_input( $event_trigger, $activeFormPlugin ); ?>
                                        </div>

                                    </div>
                                    <small class="form-text">Select Forms to Trigger the Event.</small>
                                    <div class="row mt-3 event_trigger" data-trigger_id="0">
                                        <div class="col switcher_event_form_disable_event">
                                            <?php Events\renderSwitcherTriggerFormInput( $event_trigger, $activeFormPlugin ); ?>
                                            <h4 class="switcher-label">Disable the Form event for the same
                                                forms</h4>
                                        </div>
                                    </div>

                                </div>
							<?php endif; ?>
						<?php endif; ?>

                        <div class="insert-marker-trigger <?php echo $activeFormPlugin->getSlug(); ?>_marker"></div>

					<?php endforeach; ?>

                    <div class="event_triggers_panel url_filter_panel" style="display: none;">
                        <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                            <div class="col">
                                <div class="row">
                                    <div class="col-10">
                                        <input name="" placeholder="Enter URL" class="form-control" type="text">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-sm remove-row">
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

						<?php foreach ( $event_trigger->getURLFilters() as $key => $trigger ) : ?>

                            <div class="row mt-3 event_trigger" data-trigger_id="<?php echo esc_attr( $key ); ?>">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-10">
                                            <input type="text" placeholder="Enter URL" class="form-control"
                                                   name='<?php echo esc_attr( "pys[event][triggers][$i][url_filter_triggers][$key][value]" ); ?>'
                                                   value="<?php esc_attr_e( $trigger[ 'value' ] ); ?>">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

						<?php endforeach; ?>

                        <div class="insert-marker"></div>

                        <div class="row mt-3">
                            <div class="col-4">
                                <button class="btn btn-sm btn-block btn-primary add-url-filter" type="button">
                                    Add URL
                                    filter
                                </button>
                            </div>
                        </div>
                    </div>

					<?php if ( $trigger_type == 'video_view' ) : ?>
                        <div class="event_triggers_panel embedded_video_view video_view_panel"
                             data-trigger_type="video_view"
                             style="display: none;">
							<?php $data = $event_trigger->getVideoViewData();
							$urls = $event_trigger->getVideoViewUrls();
							$urls = array_combine( $urls, $urls );
							$selected = $event_trigger->getVideoViewTriggers();
							$triggers = !empty( $data ) ? array_combine( array_column( $data, 'id' ), array_column( $data, 'title' ) ) : array();
							$play_options = array(
								'0%'   => 'Play',
								'10%'  => '10%',
								'50%'  => '50%',
								'90%'  => '90%',
								'100%' => '100%',
							);
							?>
                            <div class="row mt-3 event_trigger" data-trigger_id="0">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-10">
                                            <small class="form-text mb-1">Enter video pages URL</small>
                                            <input type="hidden" class="pys_event_video_view_data"
                                                   name="<?php echo esc_attr( "pys[event][triggers][$i][video_view_data]" ); ?>"
                                                   value="<?php echo htmlspecialchars( json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ), ENT_QUOTES, 'UTF-8' ) ?>">
											<?php Events\render_multi_select_trigger_input( $event_trigger, 'video_view_urls', $urls, $event_trigger->getVideoViewUrls(), false, '', 'pys-tags-pysselect2 pys_video_view_urls_event' ); ?>
                                        </div>
                                        <div class="col-2 d-flex align-items-center pt-3">
                                            <button class="btn btn-sm btn-block btn-primary pys-scan-video"
                                                    type="button"
                                                    value="Scan videos">Scan videos
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pys_video_view_triggers" data-trigger_id="0"
                                 style="<?php echo empty( $data ) ? 'display: none;' : ''; ?>">
                                <div class="row mt-3 event_trigger" data-trigger_id="0">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-12">
                                                <small class="form-text mb-1">Select videos</small>
												<?php Events\render_multi_select_trigger_input( $event_trigger, 'video_view_triggers', $triggers, $selected, false, '', 'pys-pysselect2 pys_video_view_triggers_event' ); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3 event_trigger" data-trigger_id="0">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-12">
                                                <label class="mb-0">Select trigger </label>
												<?php Events\renderTriggerSelectInput( $event_trigger, 'video_view_play_trigger', $play_options, false, 'pys_video_view_play_trigger' ); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3 event_trigger" data-trigger_id="0">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-12 switcher_event_disable_watch_video">
												<?php Events\renderSwitcherTriggerInput( $event_trigger, 'video_view_disable_watch_video' ); ?>
                                                <h4 class="switcher-label">Disable the automatic WatchVideo
                                                    events for
                                                    the
                                                    video</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3 video_view_error" style="display: none">
                                <div class="col event_error"></div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger video_view_marker"></div>

					<?php if ( $trigger_type == 'email_link' ) : ?>
                        <div class="event_triggers_panel email_link_panel" data-trigger_type="email_link" style="display: none;">
                            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-4">
                                            <select class="form-control-sm pys_email_link_triggers" name="" autocomplete="off" style="width: 100%;">
                                                <option value="any">All links</option>
                                                <option value="match">Link Match</option>
                                                <option value="contains">Link Include</option>
                                            </select>
                                        </div>
                                        <div class="col-6 trigger_url" style="display: none">
                                            <input name="" placeholder="Enter Link" class="form-control" type="text">
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm remove-row">
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php foreach ( $event_trigger->getEmailLinkTriggers() as $key => $trigger ) : ?>
                                <div class="row mt-3 event_trigger" data-trigger_id="<?php echo esc_attr( $key ); ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-control-sm pys_email_link_triggers"
                                                        name='<?php echo esc_attr( "pys[event][triggers][$i][email_link_triggers][$key][rule]" ); ?>'
                                                        autocomplete="off" style="width: 100%;">
                                                    <option value="any" <?php selected( $trigger['rule'], 'any' ); ?>>All links</option>
                                                    <option value="match"  <?php selected( $trigger['rule'], 'match' ); ?>>Link Match</option>
                                                    <option value="contains" <?php selected( $trigger['rule'], 'contains' ); ?>>Link Include</option>
                                                </select>
                                            </div>

                                            <div class="col-6 trigger_url" <?php if( $trigger['rule'] === "any") : ?> style="display:none;"  <?php endif; ?>>
                                                <input type="text" placeholder="Enter Link" class="form-control"
                                                       name='<?php echo esc_attr( "pys[event][triggers][$i][email_link_triggers][$key][value]" ); ?>'
                                                       value="<?php if( $trigger['rule'] !== "any") :  esc_attr_e( $trigger['value'] ); endif;?>">
                                            </div>

                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>

                            <div class="insert-marker"></div>

                            <div class="row mt-3">
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add another
                                        Link</button>
                                </div>
                            </div>

                            <div class="row mt-3" data-trigger_id="0">
                                <div class="col">
                                    <div class="row">
                                        <div class="col-12 switcher_event_email_link_event">
                                            <?php Events\renderSwitcherTriggerInput( $event_trigger, 'email_link_disable_email_event' ); ?>
                                            <h4 class="switcher-label">Disable the default Email event for the same action</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>
                    <div class="insert-marker-trigger email_link_marker"></div>

                </div>
			<?php endforeach; ?>
		<?php endif; ?>
    </div>

    <hr class="m-0">
    <div id="pys-add-trigger" class="mt-4 mb-4">
        <div class="row d-flex justify-content-center">
            <div class="col-6 justify-content-center">
                <button class="btn btn-block btn-primary add-trigger" type="button">Add trigger
                </button>
            </div>
        </div>
    </div>

	<?php // Add new event trigger
	?>
    <div id="pys_add_event_trigger" style="display: none;">

		<?php $new_trigger = new TriggerEvent();
		$new_index = $new_trigger->getTriggerIndex();
		?>

        <input type="hidden" name="<?php echo esc_attr( "pys[event][triggers][$new_index][cloned_event]" ); ?>"
               value="1">

        <div class="card-body trigger_group" data-trigger_id="0"
             data-new_trigger_index="<?php esc_attr_e( $new_index ); ?>" style="display: none;">
            <div class="pys_remove_trigger">
                <button type="button" class="btn remove-row">
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                </button>
            </div>

            <div class="row mb-3">
                <div class="col form-inline">
                    <label>Fire event when</label>
					<?php Events\renderTriggerTypeInput( $new_trigger, 'trigger_type' ); ?>

                    <div class="insert-marker-trigger post_type_marker"></div>

                    <div class="event-delay form-inline">
                        <label>with delay</label>
						<?php Events\renderTriggerNumberInput( $new_trigger, 'delay', '0' ); ?>
                        <label>seconds</label>
                    </div>

                    <div class="insert-marker-trigger number_page_visit_conditional_marker"></div>
                </div>
            </div>

            <div class="insert-marker-trigger page_visit_marker"></div>
            <div class="insert-marker-trigger number_page_visit_url_marker"></div>
            <div class="insert-marker-trigger url_click_marker"></div>
            <div class="insert-marker-trigger css_click_marker"></div>
            <div class="insert-marker-trigger css_mouseover_marker"></div>
            <div class="insert-marker-trigger scroll_pos_marker"></div>
            <div class="insert-marker-trigger email_link_marker"></div>

			<?php $eventsFormFactory = apply_filters( "pys_form_event_factory", [] );
			foreach ( $eventsFormFactory as $activeFormPlugin ) : ?>
                <div class="insert-marker-trigger <?php echo $activeFormPlugin->getSlug(); ?>_marker"></div>
			<?php endforeach; ?>

            <div class="event_triggers_panel url_filter_panel" style="display: none;">
                <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                    <div class="col">
                        <div class="row">
                            <div class="col-10">
                                <input name="" placeholder="Enter URL" class="form-control" type="text">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-sm remove-row">
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="insert-marker"></div>

                <div class="row mt-3">
                    <div class="col-4">
                        <button class="btn btn-sm btn-block btn-primary add-url-filter" type="button">Add URL
                            filter
                        </button>
                    </div>
                </div>
            </div>

            <div class="insert-marker-trigger video_view_marker"></div>

        </div>

        <div class="event_triggers_panel page_visit_panel" data-trigger_type="page_visit"
             style="display: none;">
            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                <div class="col">
                    <div class="row">
                        <div class="col-4">
                            <select class="form-control-sm" name="" autocomplete="off" style="width: 100%;">
                                <option value="contains">URL Contains</option>
                                <option value="match">URL Match</option>
                                <option value="param_contains">URL Parameters Contains</option>
                                <option value="param_match">URL Parameters Match</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input name="" placeholder="Enter URL" class="form-control" type="text">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm remove-row">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insert-marker"></div>
            <div class="mt-3">
                <small>You can also use <b>*</b> as the trigger URL on all pages</small>
            </div>
            <div class="row mt-3">
                <div class="col-4">
                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add
                        another
                        URL
                    </button>
                </div>
            </div>
        </div>


        <div class="event_triggers_panel number_page_visit_panel number_page_visit_url_panel"
             data-trigger_type="number_page_visit"
             style="display: none;">
            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                <div class="col">
                    <div class="row">
                        <div class="col-4">
                            <select class="form-control-sm pys_number_page_visit_triggers" name=""
                                    autocomplete="off" style="width: 100%;">
                                <option value="any">Any URL`s</option>
                                <option value="contains">URL Contains</option>
                                <option value="match">URL Match</option>
                                <option value="param_contains">URL Parameters Contains</option>
                                <option value="param_match">URL Parameters Match</option>
                            </select>
                        </div>
                        <div class="col-6 trigger_url" style="display: none">
                            <input name="" placeholder="Enter URL"
                                   class="form-control pys_number_page_visit_triggers" type="text">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm remove-row">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insert-marker"></div>
            <div class="mt-3">
                <small>You can also use <b>*</b> as the trigger URL on all pages</small>
            </div>

            <div class="row mt-3">
                <div class="col-4">
                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add
                        another
                        URL
                    </button>
                </div>
            </div>
        </div>


        <div class="event_triggers_panel post_type_panel" data-trigger_type="post_type"
             style="display: none;">
            <div class="trigger_post_type form-inline">
				<?php Events\renderPostTypeSelect( $new_trigger, 'post_type_value' ); ?>
            </div>
        </div>

        <div class="event_triggers_panel number_page_visit_panel number_page_visit_conditional_panel d-flex form-inline"
             data-trigger_type="number_page_visit"
             style="display: none;">
            <div class="trigger_number_page_visit conditional_number_visit form-inline">
				<?php Events\renderTriggerConditionalNumberPage( $new_trigger, 'conditional_number_visit' ); ?>
            </div>
            <div class="trigger_number_page_visit number_visit form-inline">
				<?php Events\renderTriggerNumberInput( $new_trigger, 'number_visit', '0', 3 ); ?>
                <label>visited page</label>
            </div>
        </div>

        <div class="event_triggers_panel url_click_panel" data-trigger_type="url_click"
             style="display: none;">
            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                <div class="col">
                    <div class="row">
                        <div class="col-4">
                            <select class="form-control-sm" name="" autocomplete="off" style="width: 100%;">
                                <option value="contains">URL Contains</option>
                                <option value="match">URL Match</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input name="" placeholder="Enter URL" class="form-control" type="text">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm remove-row">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insert-marker"></div>

            <div class="row mt-3 mb-5">
                <div class="col-4">
                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add
                        another
                        URL
                    </button>
                </div>
            </div>
        </div>

        <div class="event_triggers_panel css_click_panel" data-trigger_type="css_click"
             style="display: none;">
            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                <div class="col">
                    <div class="row">
                        <div class="col-10">
                            <input name="" placeholder="Enter CSS selector" class="form-control"
                                   type="text">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm remove-row">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insert-marker"></div>

            <div class="row mt-3 mb-5">
                <div class="col-4">
                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add
                        another
                        selector
                    </button>
                </div>
            </div>
        </div>

        <div class="event_triggers_panel css_mouseover_panel" data-trigger_type="css_mouseover"
             style="display: none;">
            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                <div class="col">
                    <div class="row">
                        <div class="col-10">
                            <input name="" placeholder="Enter CSS selector" class="form-control"
                                   type="text">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm remove-row">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insert-marker"></div>

            <div class="row mt-3 mb-5">
                <div class="col-4">
                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add
                        another
                        selector
                    </button>
                </div>
            </div>
        </div>

        <div class="event_triggers_panel scroll_pos_panel" data-trigger_type="scroll_pos"
             style="display: none;">
            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                <div class="col">
                    <div class="row">
                        <div class="col-3">
                            <input name="" class="form-control" type="number" min="0" max="100">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm remove-row">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="insert-marker"></div>

            <div class="row mt-3 mb-5">
                <div class="col-4">
                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add
                        another
                        threshold
                    </button>
                </div>
            </div>
        </div>

		<?php $eventsFormFactory = apply_filters( "pys_form_event_factory", [] );
		foreach ( $eventsFormFactory as $activeFormPlugin ) : ?>
            <?php if ( $activeFormPlugin->getSlug() == "elementor_form" ) : ?>
                <div class="event_triggers_panel <?php echo $activeFormPlugin->getSlug(); ?>_panel elementor_form"
                     data-trigger_type="<?php echo $activeFormPlugin->getSlug(); ?>"
                     style="display: none;">

                    <?php $data = $new_trigger->getElementorFormData();
                    $urls = $new_trigger->getElementorFormUrls();
                    $urls = array_combine( $urls, $urls );
                    ?>
                    <div class="row mt-3 event_trigger" data-trigger_id="0">
                        <div class="col">
                            <div class="row">
                                <div class="col-10">
                                    <small class="form-text mb-1">Enter Elementor form pages URL</small>
                                    <input type="hidden" class="pys_event_elementor_form_data"
                                           name="<?php echo esc_attr( "pys[event][triggers][$new_index][elementor_form_data]" ); ?>"
                                           value="<?php echo htmlspecialchars( json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ), ENT_QUOTES, 'UTF-8' ) ?>">
                                    <?php Events\render_multi_select_trigger_input( $new_trigger, 'elementor_form_urls', $urls, $new_trigger->getElementorFormUrls(), false, '', 'pys_elementor_form_urls_event', false ); ?>

                                </div>
                                <div class="col-2 d-flex align-items-center pt-3">
                                    <button class="btn btn-sm btn-block btn-primary pys-scan-elementor-form"
                                            type="button"
                                            value="Scan forms">Scan forms
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pys_elementor_form_triggers" data-trigger_id="0"
                         style="<?php echo empty( $data ) ? 'display: none;' : ''; ?>">
                        <div class="row mt-3 event_trigger" data-trigger_id="0">
                            <div class="col">
                                <div class="row">
                                    <div class="col-12">
                                        <small class="form-text mb-1">Select forms</small>
                                        <?php Events\render_multi_select_trigger_form_input( $new_trigger, $activeFormPlugin, false, '', false, 'pys_elementor_form_triggers_event' ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3 event_trigger" data-trigger_id="0">
                            <div class="col switcher_event_disabled_form_action">
                                <?php Events\renderSwitcherTriggerFormInput( $new_trigger, $activeFormPlugin ); ?>
                                <h4 class="switcher-label">Disable the Form event for the same forms</h4>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3 elementor_form_error" style="display: none">
                        <div class="col event_error"></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="event_triggers_panel <?php echo $activeFormPlugin->getSlug(); ?>_panel"
                     data-trigger_type="<?php echo $activeFormPlugin->getSlug(); ?>" style="display: none;">
                    <div class="row mt-3 event_trigger" data-trigger_id="0">
                        <div class="col select_event_trigger_form_wrapper">
                            <?php Events\render_multi_select_trigger_form_input( $new_trigger, $activeFormPlugin, false, '', false ); ?>
                        </div>

                    </div>
                    <small class="form-text">Select Forms to Trigger the Event.</small>
                    <div class="row mt-3 event_trigger" data-trigger_id="0">
                        <div class="col switcher_event_form_disable_event">
                            <?php Events\renderSwitcherTriggerFormInput( $new_trigger, $activeFormPlugin ); ?>
                            <h4 class="switcher-label">Disable the Form event for the same forms</h4>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
		<?php endforeach; ?>

        <div class="event_triggers_panel embedded_video_view video_view_panel" data-trigger_type="video_view"
             style="display: none;">
			<?php $data = $new_trigger->getVideoViewData();
			$urls = $new_trigger->getVideoViewUrls();
			$urls = array_combine( $urls, $urls );
			$selected = $new_trigger->getVideoViewTriggers();
			$triggers = !empty( $data ) ? array_combine( array_column( $data, 'id' ), array_column( $data, 'title' ) ) : array();
			$play_options = array(
				'0%'   => 'Play',
				'10%'  => '10%',
				'50%'  => '50%',
				'90%'  => '90%',
				'100%' => '100%',
			);
			?>
            <div class="row mt-3 event_trigger" data-trigger_id="0">
                <div class="col">
                    <div class="row">
                        <div class="col-10">
                            <small class="form-text mb-1">Enter video pages URL</small>
                            <input type="hidden" class="pys_event_video_view_data"
                                   name="<?php echo esc_attr( "pys[event][triggers][$new_index][video_view_data]" ); ?>"
                                   value="<?php echo htmlspecialchars( json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ), ENT_QUOTES, 'UTF-8' ) ?>">
							<?php Events\render_multi_select_trigger_input( $new_trigger, 'video_view_urls', $urls, $new_trigger->getVideoViewUrls(), false, '', 'pys_video_view_urls_event', false ); ?>
                        </div>
                        <div class="col-2 d-flex align-items-center pt-3">
                            <button class="btn btn-sm btn-block btn-primary pys-scan-video" type="button"
                                    value="Scan videos">Scan videos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pys_video_view_triggers" data-trigger_id="0"
                 style="<?php echo empty( $data ) ? 'display: none;' : ''; ?>">
                <div class="row mt-3 event_trigger" data-trigger_id="0">
                    <div class="col">
                        <div class="row">
                            <div class="col-12">
                                <small class="form-text mb-1">Select videos</small>
								<?php Events\render_multi_select_trigger_input( $new_trigger, 'video_view_triggers', $triggers, $selected, false, '', 'pys_video_view_triggers_event', false ); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 event_trigger" data-trigger_id="0">
                    <div class="col">
                        <div class="row">
                            <div class="col-12">
                                <label class="mb-0">Select trigger </label>
								<?php Events\renderTriggerSelectInput( $new_trigger, 'video_view_play_trigger', $play_options, false, 'pys_video_view_play_trigger' ); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3 event_trigger" data-trigger_id="0">
                    <div class="col">
                        <div class="row">
                            <div class="col-12 switcher_event_disable_watch_video">
								<?php Events\renderSwitcherTriggerInput( $new_trigger, 'video_view_disable_watch_video' ); ?>
                                <h4 class="switcher-label">Disable the automatic WatchVideo events for the
                                    video</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3 video_view_error" style="display: none">
                <div class="col event_error"></div>
            </div>
        </div>

        <div class="event_triggers_panel email_link_panel" data-trigger_type="email_link" style="display: none;">
            <div class="row mt-3 event_trigger" data-trigger_id="-1" style="display: none;">
                <div class="col">
                    <div class="row">
                        <div class="col-4">
                            <select class="form-control-sm pys_email_link_triggers" name="" autocomplete="off" style="width: 100%;">
                                <option value="any">All links</option>
                                <option value="match">Link Match</option>
                                <option value="contains">Link Include</option>
                            </select>
                        </div>
                        <div class="col-6 trigger_url" style="display: none">
                            <input name="" placeholder="Enter Link" class="form-control" type="text">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm remove-row">
                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insert-marker"></div>

            <div class="row mt-3">
                <div class="col-4">
                    <button class="btn btn-sm btn-block btn-primary add-event-trigger" type="button">Add another
                        Link</button>
                </div>
            </div>

            <div class="row mt-3" data-trigger_id="0">
                <div class="col">
                    <div class="row">
                        <div class="col-12 switcher_event_email_link_event">
							<?php Events\renderSwitcherTriggerInput( $new_trigger, 'email_link_disable_email_event' ); ?>
                            <h4 class="switcher-label">Disable the default Email event for the same action</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ( Facebook()->enabled() ) : ?>
    <div class="card card-static">
        <div class="card-header">
            Facebook
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col">
					<?php Events\renderSwitcherInput( $event, 'facebook_enabled' ); ?>
                    <h4 class="switcher-label">Enable on Facebook</h4>
                </div>
            </div>
            <div id="facebook_panel">
                <div class="row mt-3">
                    <label class="col-5 control-label">Fire for:</label>
                    <div class="col-4">
						<?php Events\renderFacebookEventId( $event, 'facebook_pixel_id' ); ?>
                    </div>
                </div>
                <div class="row mt-3">
                    <label class="col-5 control-label">Event type:</label>
                    <div class="col-4  form-inline">
                        <p><?php Events\renderFacebookEventTypeInput( $event, 'facebook_event_type' ); ?></p>
                        <div class="facebook-custom-event-type form-inline">
							<?php Events\renderTextInput( $event, 'facebook_custom_event_type', 'Enter name' ); ?>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col col-offset-left">
						<?php Events\renderSwitcherInput( $event, 'facebook_params_enabled' ); ?>
                        <h4 class="indicator-label">Add Parameters</h4>
                    </div>
                </div>
                <div id="facebook_params_panel">
                    <div class="row mt-3">
                        <div class="col">

                            <div class="row mb-3 ViewContent Search AddToCart AddToWishlist InitiateCheckout AddPaymentInfo Purchase Lead CompleteRegistration Subscribe StartTrial">
                                <label class="col-5 control-label">value</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'value' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 ViewContent Search AddToCart AddToWishlist InitiateCheckout AddPaymentInfo Purchase Lead CompleteRegistration Subscribe StartTrial">
                                <label class="col-5 control-label">currency</label>
                                <div class="col-4">
									<?php Events\renderCurrencyParamInput( $event, 'currency' ); ?>
                                </div>
                                <div class="col-2 facebook-custom-currency">
									<?php Events\renderFacebookParamInput( $event, 'custom_currency' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 ViewContent AddToCart AddToWishlist InitiateCheckout Purchase Lead CompleteRegistration">
                                <label class="col-5 control-label">content_name</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'content_name' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 ViewContent AddToCart AddToWishlist InitiateCheckout Purchase Lead CompleteRegistration">
                                <label class="col-5 control-label">content_ids</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'content_ids' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 ViewContent AddToCart InitiateCheckout Purchase">
                                <label class="col-5 control-label">content_type</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'content_type' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 Search AddToWishlist InitiateCheckout AddPaymentInfo Lead">
                                <label class="col-5 control-label">content_category</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'content_category' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 InitiateCheckout Purchase">
                                <label class="col-5 control-label">num_items</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'num_items' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 Purchase">
                                <label class="col-5 control-label">order_id</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'order_id' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 Search">
                                <label class="col-5 control-label">search_string</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'search_string' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 CompleteRegistration">
                                <label class="col-5 control-label">status</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'status' ); ?>
                                </div>
                            </div>
                            <div class="row mb-3 Subscribe StartTrial">
                                <label class="col-5 control-label">predicted_ltv</label>
                                <div class="col-4">
									<?php Events\renderFacebookParamInput( $event, 'predicted_ltv' ); ?>
                                </div>
                            </div>

                            <!-- Custom Facebook Params -->
                            <div class="row mt-3 facebook-custom-param" data-param_id="0"
                                 style="display: none;">
                                <div class="col-1"></div>
                                <div class="col-4">
                                    <input name="" placeholder="Enter name"
                                           class="form-control custom-param-name"
                                           type="text">
                                </div>
                                <div class="col-4">
                                    <input name="" placeholder="Enter value"
                                           class="form-control custom-param-value"
                                           type="text">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-sm remove-row">
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

							<?php foreach ( $event->getFacebookCustomParams() as $key => $custom_param ) : ?>

								<?php $param_id = $key + 1; ?>

                                <div class="row mt-3 facebook-custom-param"
                                     data-param_id="<?php echo $param_id; ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-1"></div>
                                            <div class="col-4">
                                                <input type="text" placeholder="Enter name"
                                                       class="form-control custom-param-name"
                                                       name="pys[event][facebook_custom_params][<?php echo $param_id; ?>][name]"
                                                       value="<?php esc_attr_e( $custom_param[ 'name' ] ); ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="text" placeholder="Enter value"
                                                       class="form-control custom-param-value"
                                                       name="pys[event][facebook_custom_params][<?php echo $param_id; ?>][value]"
                                                       value="<?php esc_attr_e( $custom_param[ 'value' ] ); ?>">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

							<?php endforeach; ?>

                            <div class="insert-marker"></div>

                            <div class="row mt-3">
                                <div class="col-5"></div>
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-facebook-parameter"
                                            type="button">Add
                                        Custom Parameter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <p>
                <b>Important:</b> verify your custom events inside your Ads Manager:
                <a href="https://www.youtube.com/watch?v=Iyu-pSbqcFI" target="_blank">watch this video to learn
                    how</a>
            </p>
        </div>
    </div>
<?php endif; ?>

<div class="card card-static">
    <div class="card-header">
        Google Tags
    </div>
    <div class="card-body">
        <div class="row mb-2">
            <div class="col">
				<?php Events\renderSwitcherInput( $event, 'ga_ads_enabled' ); ?>
                <h4 class="switcher-label">Enable on Google Tags</h4>
            </div>
        </div>
        <div id="merged_analytics_panel">
            <div class="row mt-3">
                <label class="col-5 control-label">Fire for:</label>
                <div class="col-4"><?php Events\renderMergedGaEventId( $event, 'ga_ads_pixel_id' ); ?></div>
            </div>
            <div class="row mt-3 conversion_label">
                <label class="col-5 control-label">Conversion Label</label>
                <div class="col-4">
					<?php Events\renderTextInput( $event, 'ga_ads_conversion_label' ); ?>
                    <small class="form-text">Optional</small>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col ">
                    <!-- v4 Google params  -->
                    <div class="col g4">
                        <div class="row mb-3 g4">

                            <script>
								<?php
								$fields = array();
								foreach ( $event->GAEvents as $group => $items ) {
									foreach ( $items as $name => $elements ) {
										$fields[] = array(
											"name"   => $name,
											'fields' => $elements
										);
									}
								}

								?>
                                var ga_fields = <?=json_encode( $fields )?>
                            </script>
                            <label class="col-5 control-label">Event</label>
                            <div class="col-4">
								<?php Events\renderGoogleAnalyticsMergedActionInput( $event, 'ga_ads_event_action' ); ?>
                            </div>
                            <div class="col-3">
                                <div id="ga-ads-custom-action_g4">
									<?php Events\renderTextInput( $event, 'ga_ads_custom_event_action', 'Enter name' ); ?>
                                </div>
                            </div>


                        </div>

                        <div class="ga-ads-param-list">
							<?php
							foreach ( $event->getMergedGaParams() as $key => $val ) : ?>
                                <div class="row mb-3 ga_ads_param">
                                    <label class="col-5 control-label"><?= $key ?></label>
                                    <div class="col-4">
										<?php Events\renderMergedGAParamInput( $key, $val ); ?>
                                    </div>
                                </div>
							<?php endforeach; ?>
                        </div>

                        <div class="ga-ads-custom-param-list">
							<?php
							foreach ( $event->getGAMergedCustomParams() as $key => $custom_param ) : ?>
								<?php $param_id = $key + 1; ?>

                                <div class="row mt-3 ga-ads-custom-param"
                                     data-param_id="<?php echo $param_id; ?>">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-1"></div>
                                            <div class="col-4">
                                                <input type="text" placeholder="Enter name"
                                                       class="form-control custom-param-name"
                                                       name="pys[event][ga_ads_custom_params][<?php echo $param_id; ?>][name]"
                                                       value="<?php esc_attr_e( $custom_param[ 'name' ] ); ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="text" placeholder="Enter value"
                                                       class="form-control custom-param-value"
                                                       name="pys[event][ga_ads_custom_params][<?php echo $param_id; ?>][value]"
                                                       value="<?php esc_attr_e( $custom_param[ 'value' ] ); ?>">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm remove-row">
                                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
							<?php endforeach; ?>

                        </div>

                        <div class="row mt-3">
                            <div class="col-5"></div>
                            <div class="col-4">
                                <button class="btn btn-sm btn-block btn-primary add-ga-ads-custom-parameter"
                                        type="button">Add
                                    Custom Parameter
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                The following parameters are automatically tracked: content_name, event_url,
                                post_id,
                                post_type. The paid version tracks the event_hour, event_month, and event_day.
                            </div>
                        </div>
                        <div class="row mt-3 ga_woo_info" style="display: none">
                            <div class="col-12">
                                <strong>ATTENTION</strong>:​ the plugin automatically tracks ecommerce specific
                                events
                                for WooCommerce and Easy Digital Downloads. Make sure you really need this
                                event.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>


<?php if ( Tiktok()->enabled() ) : ?>
    <div class="card card-static">
        <div class="card-header">
            TikTok
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col">
					<?php Events\renderSwitcherInput( $event, 'tiktok_enabled' ); ?>
                    <h4 class="switcher-label">Enable on TikTok</h4>
                </div>
            </div>
            <div id="tiktok_panel">
                <div class="row mt-3">
                    <label class="col-5 control-label">Fire for:</label>
                    <div class="col-4">
						<?php Events\renderTikTokEventId( $event, 'tiktok_pixel_id' ); ?>
                    </div>
                </div>
                <div class="row mt-3">
                    <label class="col-5 control-label">Event type:</label>
                    <div class="col-4  form-inline">
                        <p><?php Events\renderTikTokEventTypeInput( $event, 'tiktok_event_type' ); ?></p>
                        <div class="tiktok-custom-event-type form-inline">
							<?php Events\renderTextInput( $event, 'tiktok_custom_event_type', 'Enter name' ); ?>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col col-offset-left">
						<?php Events\renderSwitcherInput( $event, 'tiktok_params_enabled' ); ?>
                        <h4 class="indicator-label">Add Parameters</h4>
                    </div>
                </div>

                <div id="tiktok_params_panel">
                    <div class="row mt-3">
                        <div class="col standard">
							<?php

							$fields = CustomEvent::$tikTokEvents[ $event->tiktok_event_type ];
							foreach ( $fields as $field ) : ?>
                                <div class="row mb-3">
                                    <label class="col-5 control-label"><?= $field[ 'label' ] ?></label>
                                    <div class="col-4">
                                        <input type="text"
                                               name="pys[event][tiktok_params][<?= $field[ 'label' ] ?>]"
                                               value="<?= $event->tiktok_params[ $field[ 'label' ] ] ?>"
                                               placeholder=""
                                               class="form-control"/>
                                    </div>
                                </div>
							<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ( Pinterest()->enabled() ) : ?>
	<?php Pinterest()->renderCustomEventOptions( $event ); ?>
<?php endif; ?>

<?php if ( Bing()->enabled() ) : ?>
	<?php Bing()->renderCustomEventOptions( $event ); ?>
<?php endif; ?>
<hr class="mb-4 mt-4">
<?php if ( GTM()->enabled() ) : ?>
    <div class="card card-static mb-4">
        <div class="card-header">
            GTM DataLayer
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col">
					<?php Events\renderSwitcherInput( $event, 'gtm_enabled' ); ?>
                    <h4 class="switcher-label">Enable on GTM</h4>
                </div>
            </div>


            <div id="gtm_panel" class="mt-3">
                <hr>
                <div class="row mt-4 mb-3">
                    <div class="col">
                        <?php Events\render_checkbox_input( $event, 'gtm_automated_param', 'Add the automated parameters in the dataLayer' ); ?>
                    </div>
                </div>
                <div class="row mt-4 mb-3">
                    <div class="col">
                        <?php Events\render_checkbox_input( $event, 'gtm_remove_customTrigger', 'Remove the customTrigger object' ); ?>
                    </div>
                </div>
                <hr>
                <div class="row align-items-center mb-2">
                    <label class="col-5 control-label" style="padding-top:0;">Fire for:</label>
                    <div class="col-4">
                        <?php
                            $mainPixels = GTM()->getPixelIDs();
                            if(!empty($mainPixels) && strpos($mainPixels[0], 'GTM-') === 0 && strpos($mainPixels[0], 'GTM-') !== false) {
                                echo $mainPixels[0];
                                echo '<input type="hidden" name="pys[event][gtm_pixel_id][]" value="'.$mainPixels[0].'"/>';
                            }
                            else{
                                _e('No container ID is configured', 'pys');
                            }
                        ?>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col ">
                        <!-- v4 Google params  -->
                        <div class="col g4">
                            <div class="row mb-3 g4">

                                <script>
									<?php
									$fields = array();
									foreach ($event->GAEvents as $group => $items) {
										foreach ($items as $name => $elements) {
											$fields[] = array("name"=>$name,'fields'=>$elements);
										}
									}

									?>
                                    var gtm_fields = <?=json_encode($fields)?>
                                </script>
                                <label class="col-5 control-label">Event</label>
                                <div class="col-4">
									<?php  Events\renderGTMActionInput( $event, 'gtm_event_action' ); ?>
                                </div>
                                <div class="col-3">
                                    <div id="gtm-custom-action_g4">
										<?php Events\renderTextInput( $event, 'gtm_custom_event_action', 'Enter name' ); ?>
                                    </div>
                                </div>


                            </div>

                            <div class="gtm-param-list">
								<?php
								foreach($event->getGTMParams() as $key=>$val) : ?>
                                    <div class="row mb-3 gtm_param">
                                        <label class="col-5 control-label"><?=$key?></label>
                                        <div class="col-4">
											<?php Events\renderGTMParamInput( $key, $val ); ?>
                                        </div>
                                    </div>
								<?php endforeach; ?>
                            </div>

                            <div class="gtm-custom-param-list">
								<?php
								foreach ( $event->getGTMCustomParamsAdmin() as $key => $custom_param ) : ?>
									<?php $param_id = $key + 1; ?>

                                    <div class="row mt-3 gtm-custom-param" data-param_id="<?php echo $param_id; ?>">
                                        <div class="col">
                                            <div class="row">
                                                <div class="col-1"></div>
                                                <div class="col-4">
                                                    <input type="text" placeholder="Enter name" class="form-control custom-param-name"
                                                           name="pys[event][gtm_custom_params][<?php echo $param_id; ?>][name]"
                                                           value="<?php esc_attr_e( $custom_param['name'] ); ?>">
                                                </div>
                                                <div class="col-4">
                                                    <input type="text" placeholder="Enter value" class="form-control custom-param-value"
                                                           name="pys[event][gtm_custom_params][<?php echo $param_id; ?>][value]"
                                                           value="<?php esc_attr_e( $custom_param['value'] ); ?>">
                                                </div>
                                                <div class="col-2">
                                                    <button type="button" class="btn btn-sm remove-row">
                                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
								<?php endforeach; ?>

                            </div>

                            <div class="row mt-3">
                                <div class="col-5"></div>
                                <div class="col-4">
                                    <button class="btn btn-sm btn-block btn-primary add-gtm-custom-parameter" type="button">Add
                                        Custom Parameter</button>
                                </div>
                            </div>

                            <hr>
                            <div class="row mt-4 mb-2 gtm_use_custom_object_name">
                                <div class="col">
                                    <?php Events\render_checkbox_input( $event, 'gtm_use_custom_object_name', 'Use a custom value for the custom paramers object' ); ?>
                                </div>
                            </div>
                            <div class="row mt-2 mb-2">
                                <div class="col">
                                    <?php Events\renderTextInput( $event, 'gtm_custom_object_name', $event->getManualCustomObjectName() );?>
                                </div>
                            </div>
                            <hr>

                            <div class="row mt-3">
                                <div class="col-12">
                                    When configuring GTM variables for these parameters, use this key: <span id="manual_custom_object_name"><?= $event->getManualCustomObjectName(); ?></span>
                                </div>
                            </div>
                            <div class="row mt-3 gtm_woo_info" style="display: none">
                                <div class="col-12">
                                    <strong>ATTENTION</strong>:​ the plugin automatically tracks ecommerce specific events for WooCommerce and Easy Digital Downloads. Make sure you really need this event.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php do_action( 'pys_superpack_dynamic_params_help' ); ?>

<hr>
<div class="row justify-content-center">
    <div class="col-4">
        <button class="btn btn-block btn-sm btn-save save-custom-event">Save Event</button>
    </div>
</div>
