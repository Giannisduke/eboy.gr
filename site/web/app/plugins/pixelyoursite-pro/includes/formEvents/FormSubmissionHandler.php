<?php

namespace PixelYourSite;

class FormSubmissionHandler {
	public function __construct() {
		add_filter( 'gform_confirmation', array( $this, 'my_gform_after_submission' ), 50, 4 );
		add_action( 'wpforms_process_complete', array( $this, 'my_wpforms_after_submission'), 10, 4 );
		add_action( 'fluentform/submission_inserted', array( $this, 'my_fluentform_after_submission'), 10, 3);
	}

	function my_gform_after_submission($confirmation, $form, $entry, $ajax) {
		if(!$ajax || (!empty($confirmation) && is_array($confirmation) && array_key_exists('redirect', $confirmation))) {
			$this->form_track = array('formType' => 'gravity', 'formId' => $form['id']);
			set_transient("form_track", $this->form_track, 60 * 5);
		}

		return $confirmation;
	}
	function my_wpforms_after_submission($fields, $entry, $form_data, $entry_id) {
		if ( !(isset( $form_data['settings']['ajax_submit'] ) && $form_data['settings']['ajax_submit'] == '1') ) {
			$this->form_track = array( 'formType' => 'wpforms', 'formId' => $form_data['id'] );
			set_transient( "form_track", $this->form_track, 60 * 5 );
		}
	}

	function my_fluentform_after_submission($entryId, $formData, $form) {
		if($form->settings['confirmation']['redirectTo'] !== 'samePage'){
			$this->form_track = array( 'formType' => 'fluentform', 'formId' => $form->id );
			set_transient( "form_track", $this->form_track, 60 * 5 );
		}
	}
}