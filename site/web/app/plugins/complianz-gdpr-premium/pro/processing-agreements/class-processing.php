<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists("cmplz_processing")) {
    class cmplz_processing
    {
        private static $_this;
        function __construct()
        {
            if (isset(self::$_this))
	            wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

            self::$_this = $this;
			add_filter( 'cmplz_do_action', array( $this, 'get_processor_data' ), 10, 3 );
	        add_filter("admin_init", array($this, 'upload_file'), 100, 3);
	        add_filter("cmplz_tools_processing_agreements", array($this, 'tools_processing_agreements') );
	        add_action('init', array($this, 'register_post_type' ), 99, 1);
	        add_action( 'init', array($this, 'register_regions') );

        }

        static function this()
        {
            return self::$_this;
        }

		public function get_document_elements($region){
			$elements = array();
			$region = strtoupper($region);
			require_once( __DIR__ . "/$region/processing-agreement.php" );
			return $elements;
		}

		public function tools_processing_agreements($processingAgreements){
			$docs = get_posts(
				array(
					'post_type' => 'cmplz-processing',
					'post_status' => 'publish',
				)
			);
			foreach ( $docs as $doc ) {
				$processingAgreements[] = [
					'label' => $doc->post_title,
					'value' => $this->download_url($doc->ID),
				];
			}
			return $processingAgreements;
		}

	    /**
	     * Process file upload for processing agreements
	     *
	     * @return array
	     */
	    public function upload_file() {
		    if (!isset($_GET['cmplz_upload_file'])) {
			    return [];
		    }

		    if (!isset($_GET['action']) || $_GET['action'] !== 'upload_processing_agreement') {
			    return [];
		    }

		    if (!isset($_FILES['data'])) {
			    return [];
		    }
		    if ( !cmplz_user_can_manage() ) {
			    return [];
		    }

		    $file = $_FILES['data'];
		    $error = false;
		    if ($file['type'] !== 'application/json' && $file['type'] !== 'application/doc' && $file['type'] !== 'application/docx') {
			    $error = __('This file does not have the correct format', 'complianz-gdpr');
		    }

		    $tmp = $file['tmp_name'];
			//get extension from $file
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
			//get filename from $file
			$filename = pathinfo($file['name'], PATHINFO_FILENAME);
			$random = get_option('cmplz_pdf_dir_token');
			if (!$random) {
				//generate random token of 20 characters
				$random = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(20/strlen($x)) )),1,20);
				update_option('cmplz_pdf_dir_token', $random);
			}
			$path = cmplz_upload_dir("processing-agreements/$random");
			copy($tmp, $path . $filename . '.' . $ext);
			if (file_exists($tmp)) unlink($tmp);

			$region = '';
			$serviceName = '';
		    if (isset($_POST['details'])) {
			    $json_string = stripslashes($_POST['details']);
			    $details = json_decode($json_string);
			    $region = sanitize_title($details->region);
			    $serviceName = sanitize_text_field($details->serviceName);
		    }

		    if ($region==='') $region = COMPLIANZ::$company->get_default_region();
		    if ($serviceName==='') $serviceName = $filename;
			$this->save([], $region, $serviceName, 0, $filename . '.' . $ext);

		    $response = [
			    'success' => !$error,
			    "error_message"  => $error,
		    ];
		    header( "Content-Type: application/json" );
		    echo json_encode($response);
		    exit;
	    }

	    /**
	     * Get the fields for the processing agreement
	     * @return array
	     */

		public function get_fields( $region ): array {
			$region = strtoupper($region);
			$questions = array();
			include( __DIR__ . "/$region/questions.php" );

			//add a value property for each field
			foreach ($questions as $key => $question) {
				$questions[$key]['value'] = '';
			}
			return $questions;
		}

		/**
		 * Get a list of processors
		 * @param array $data
		 * @param string $action
		 * @param WP_REST_Request $request
		 *
		 * @return []
		 */

		public function get_processor_data($data, $action, $request): array {
			if ( $action==='get_processing_agreements' ){
				$regions = cmplz_get_regions(false, 'full');
				$data = [
					'documents' => $this->processing_agreements(),
					'regions' => $regions,
				];
			} else if ($action === 'get_processing_agreement_fields'){
				$region = $request->get_param('region');
				$data = ['fields' => $this->get_fields($region)];
			} else if ($action === 'save_processing_agreement' ) {
				$fields = $request->get_param('fields');
				$post_id = (int) $request->get_param('post_id');

				$region = (string) $request->get_param('region');
				$serviceName = (string) $request->get_param('serviceName');
				$this->save($fields, $region, $serviceName, $post_id);
				$data = ['success' => true];
			} else if ($action === 'delete_processing_agreement' ) {
			    $documents = $request->get_param('documents');
			    foreach ($documents as $document) {
				    $this->delete( $document['id'] );
			    }
				$data = ['success' => true];
		    } else if ($action === 'load_processing_agreement') {
				$post_id = (int) $request->get_param('id');
				$region = $this->get_region($post_id);
				$field_region = $region === 'eu' ? '' : "-$region";
				$fields = $this->get_fields($region);
				foreach ($fields as $key => $field) {
					$fields[$key]['value'] = get_post_meta($post_id, $field['id'], true);
				}

				$serviceName = get_post_meta($post_id, 'name_of_processor'.$field_region, true);
				$data = [
					'fields' => $fields,
					'serviceName' => $serviceName,
					'region' => $region,
					'file_name' => get_the_title($post_id),
				];
			}
			return $data;
		}

	    /**
	     * Delete a post by post id
	     *
	     * @param int $post_id
	     *
	     * @r
	     * eturn void
	     */
		private function delete($post_id): void {
			if (!cmplz_user_can_manage()) {
				return;
			}
			$post = get_post($post_id);
			if ($post->post_type !== 'cmplz-processing') {
				return;
			}
			wp_delete_post($post_id, true);
		}

		/**
		 * Get list of processing agreements
		 * @return array
		 */
		public function processing_agreements(): array {
			if (!cmplz_user_can_manage()) {
				return [];
			}
            $args = array(
				'post_type' => 'cmplz-processing',
				'numberposts' => -1,
            );
            $posts =  get_posts($args);
			$output = [];
			foreach ($posts as $post ) {
				$region = $this->get_region( $post->ID );
				$fieldname_region = $region==='eu' ? '' : "_$region";
				$output[] = 					[
					'id'           => $post->ID,
					'title'        => $post->post_title,
					'region'       => $region,
					'service'      => get_post_meta( $post->ID, 'name_of_processor' . $fieldname_region, true ),
					'date'         => date( get_option( 'date_format' ), strtotime( $post->post_date ) ),
					'edit_url'     => get_edit_post_link( $post->ID ),
					'download_url' => $this->download_url( $post->ID ),
				];
			}
			return $output;
        }

		public function download_url($post_id): string {
			if (!cmplz_user_can_manage()) {
				return '';
			}
			$cmplz_uploaded_file = get_post_meta($post_id, 'cmplz_uploaded_file', true);
			if ( $cmplz_uploaded_file ) {
				$random = get_option('cmplz_pdf_dir_token');
				return cmplz_upload_url("processing-agreements/$random").'/'.$cmplz_uploaded_file.'?token=' . time();
			}

			return CMPLZ_URL . 'pro/pdf.php?nonce=' . wp_create_nonce("cmplz_pdf_nonce") .'&region=' . $this->get_region($post_id). '&post_id=' . $post_id . '&token=' . time();
		}

	    /**
	     * Get the region for a post id, based on the post type.
	     *
	     * @param int $post_id
	     *
	     * @return string|bool $region
	     * */

	    public function get_region( int $post_id ) {
		    $term = wp_get_post_terms( $post_id, 'cmplz-region' );
		    if ( is_wp_error( $term ) ) {
			    return false;
		    }

		    if ( isset( $term[0] ) ) {
			    return $term[0]->slug;
		    }

		    return false;
	    }


        /**
         * Check if sharing with processors is enabled.
         *
         *
         * */

        public function has_processors(): bool {
			return cmplz_get_option( 'share_data_other' ) !== 2;
		}

		/**
		 * Check if there are missing agreements for processors
		 *
		 * @return bool
		 */
        public function has_missing_agreements_for_processors(){

			if ( cmplz_get_option('share_data_other') !== '1' || !$this->has_processors()){
			      return false;
			}

            $count=0;

            $processors = cmplz_get_option('processor');
            if (!empty($processors)) {
                foreach ($processors as $processor) {
                    if ( !isset($processor['processing_agreement']) || (int) $processor['processing_agreement'] == 0) {
                        $count++;
                    }
                }
            }

            return $count>0;
        }


	    /**
	     * @param array  $fields
	     * @param string $region
	     * @param string $serviceName
	     * @param int    $post_id
	     * @param string $uploaded_file
	     *
	     * @return void
	     */
        public function save( array $fields, string $region, string $serviceName, int $post_id, string $uploaded_file=''): void {
            if (!cmplz_user_can_manage()) {
				return;
            }

            $date = cmplz_localize_date( time() );
            $args = array(
                'post_status' => 'publish',
                'post_title' => $serviceName,
                'post_type' => 'cmplz-processing',
            );
            if ($post_id===0) {
	            //create new post type processing, and add all wizard data as meta fields.
                $post_id = wp_insert_post($args);
            } else {
                $args['ID'] = $post_id;
                wp_update_post($args);
            }
            $this->set_region($post_id, $region);
	        $field_region = $region==='eu' ? '' : "_$region";
			update_post_meta($post_id, 'name_of_processor'.$field_region, $serviceName);
			if ($uploaded_file!=='') update_post_meta($post_id, 'cmplz_uploaded_file', sanitize_file_name($uploaded_file));
			//get all fields for this page
			foreach ( $fields as $field ) {
				$id    = sanitize_title( $field['id'] );
				$value = cmplz_sanitize_field( $field['value'], $field['type'], $id );
				update_post_meta( $post_id, $field['id'], $value );
			}

        }

	    /**
	     * Set the region in a post
	     *
	     * @param int    $post_id
	     * @param string $region
	     */

	    public function set_region( int $post_id, string $region ): void {
		    if (!cmplz_user_can_manage()) {
			    return;
		    }
			$region = sanitize_title( $region );
		    $term = get_term_by( 'slug', $region, 'cmplz-region' );
		    if ( ! $term ) {
			    wp_insert_term( COMPLIANZ::$config->regions[ $region ]['label'],
				    'cmplz-region', array(
					    'slug' => $region,
				    ) );
			    $term = get_term_by( 'slug', $region, 'cmplz-region' );
		    }

		    if ( empty( $term ) ) {
			    return;
		    }

		    $term_id = $term->term_id;
		    wp_set_object_terms( $post_id, array( $term_id ), 'cmplz-region' );
	    }

	    /**
	     * add custom post type
	     */
	    public function register_post_type()
	    {
		    register_post_type(
			    "cmplz-processing", //post name to use in code
			    array(
				    'labels' => array(
					    'name' => __('Processing Agreements', 'complianz-gdpr'),
					    'singular_name' => __('Processing Agreement (%s)', 'complianz-gdpr'),
					    'add_new' => __('Add new', 'complianz-gdpr'),
					    'add_new_item' => __('Add new', 'complianz-gdpr'),
					    'parent_item_colon' => __('Processing Agreement', 'complianz-gdpr'),
					    'parent' => 'Processing Agreement parent item',
				    ),

				    'rewrite' => array(
					    'slug' => "processing-agreement",
					    'pages' => true
				    ),
				    'exclude_from_search' => true,
				    'supports' => array(
					    'title',
					    'author',
					    'revisions',
					    //'page-attributes'
				    ),
				    'publicly_queryable' => false,
				    'query_var' => false,
				    'public' => true,
				    'has_archive' => false,
				    'taxonomies' => array('region'),
				    'hierarchical' => false,
				    'map_meta_cap' => true, //enable capability handling
				    'capabilities' => array(
					    'create_posts' => 'do_not_allow',
					    'delete_post' => true,
				    ),
				    'show_in_menu' => false
			    )
		    );
	    }
	    /**
	     * Register region taxonomy
	     */
	    public function register_regions() {
		    register_taxonomy(
			    'cmplz-region',
			    array('cmplz-processing'),
			    array(
				    'label' => __( 'Region', 'complianz-gdpr'),
				    'publicly_queryable' => false,
				    'hierarchical' => true,
				    'show_ui' => false,
				    'capabilities'      => array(
					    'assign_terms' => apply_filters('cmplz_capability','manage_privacy'),
					    'edit_terms'   => 'NOT_EXISTING_CAPABILITY',
					    'manage_terms' => 'NOT_EXISTING_CAPABILITY',
				    ),
				    'show_in_nav_menus' => false,
				    'show_in_rest' => false,
				    'rewrite' => array( 'slug' => 'region' ),
			    )
		    );
	    }

    }
} //class closure
