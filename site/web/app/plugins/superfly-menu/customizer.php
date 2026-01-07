<?php
add_action( 'customize_register', 'sf_customizer_settings' );
add_action( 'customize_controls_enqueue_scripts', 'sf_customizer_controls' );
add_action( 'customize_preview_init', 'sf_customizer_live_preview' );
add_action( 'customize_controls_print_footer_scripts', 'sf_customizer_footer' );
add_action( 'wp_ajax_sf_preview_action', 'sf_preview_action' );

function sf_customizer_settings( $wp_customize ) {

    // for menu items extension

    require_once dirname( __FILE__ ) . '/includes/vendor/menu-item-custom-fields/menu-item-custom-fields.php';

    // get data for all menus

    $menus = get_terms( 'nav_menu', array( 'hide_empty' => true ) );
    $sf_menu_data = array();

    foreach ( $menus as $menu ) {
//        sfm_debug_to_console( $menu );
        $sf_menu_data[ $menu->term_id ] = sfm_get_menus_data( $menu->term_id  );
    }

    add_action( 'customize_controls_print_footer_scripts' , function() use ( $sf_menu_data ) {
        writeMenuExtraData( $sf_menu_data );
    }, 10, 1);


    // custom controls

    if ( class_exists( 'WP_Customize_Control' ) ) {

        class SF_Custom_Control__Section_Title extends WP_Customize_Control
        {
            /**
             * The type of control being rendered
             */
            public $type = 'section_title';

            /**
             * Render the control in the customizer
             */
            public function render_content()
            {
                ?>
                <div class="image_checkbox_control">
                    <?php if (!empty($this->label)) { ?>
                        <span class="customize-control-title sf-customize-section-title"><?php echo esc_html($this->label); ?></span>
                    <?php } ?>
                </div>
                <?php
            }
        }

        class SF_Custom_Control__Text_Radio_Button extends WP_Customize_Control {
            /**
             * The type of control being rendered
             */
            public $type = 'sf-text-radio';

            /**
             * Render the control in the customizer
             */
            public function render_content() {
                ?>
                <div class="text_radio_button_control">
                    <?php if( !empty( $this->label ) ) { ?>
                        <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                    <?php } ?>
                    <?php if( !empty( $this->description ) ) { ?>
                        <span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
                    <?php } ?>

                    <div class="sfm-chooser">
                        <?php foreach ( $this->choices as $key => $value ) { ?>
                            <input type="radio" id="<?php echo esc_attr( $this->id ) . '-' . $key; ?>" class="sfm-switcher" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php $this->link(); ?> <?php checked( esc_attr( $key ), $this->value() ); ?>/>
                            <label class="radio-button-label" for="<?php echo esc_attr( $this->id ) . '-' . $key; ?>">
                                <span><?php echo $value; ?></span>
                            </label>
                        <?php	} ?>
                    </div>
                </div>
                <?php
            }
        }

        class SF_Custom_Control__Toggle_Switch extends WP_Customize_Control {
            /**
             * The type of control being rendered
             */
            public $type = 'sf-toggle-switch';

            /**
             * Render the control in the customizer
             */
            public function render_content() {
                ?>
                <div class="sfm_toggle_switch_control">
                    <?php if( !empty( $this->label ) ) { ?>
                        <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                    <?php } ?>
                    <?php if( !empty( $this->description ) ) { ?>
                        <span class="customize-control-description"><?php echo $this->description ; ?></span>
                    <?php } ?>

                    <label for="<?php echo esc_attr( $this->id ); ?>">
                        <input id="<?php echo esc_attr( $this->id ); ?>" name="<?php echo esc_attr( $this->id ); ?>" class="sfm-switcher" type="checkbox" value="yes" <?php $this->link(); ?>><div><div></div></div>
                    </label>
                </div>
                <?php
            }
        }

        class SF_Custom_Control__Alpha_Color extends WP_Customize_Control {
            /**
             * Official control name.
             */
            public $type = 'sf-alpha-color';
            /**
             * Add support for palettes to be passed in.
             *
             * Supported palette values are true, false, or an array of RGBa and Hex colors.
             */
            public $palette;
            /**
             * Add support for showing the opacity value on the slider handle.
             */
            public $show_opacity;

            /**
             * Render the control.
             */
            public function render_content() {
                // Process the palette
                if ( is_array( $this->palette ) ) {
                    $palette = implode( '|', $this->palette );
                } else {
                    // Default to true.
                    $palette = ( false === $this->palette || 'false' === $this->palette ) ? 'false' : 'true';
                }
                // Support passing show_opacity as string or boolean. Default to true.
                $show_opacity = ( false === $this->show_opacity || 'false' === $this->show_opacity ) ? 'false' : 'true';
                // Begin the output. ?>
                <label>
                    <?php // Output the label and description if they were passed in.
                    if ( isset( $this->label ) && '' !== $this->label ) {
                        echo '<span class="customize-control-title">' . sanitize_text_field( $this->label ) . '</span>';
                    }
                    if ( !empty( $this->description ) ) {
                        echo '<span class="description customize-control-description">' . esc_html( $this->description ) . '</span>';
                    } ?>
                    <input class="alpha-color-control" type="text" data-show-opacity="<?php echo $show_opacity; ?>" data-palette="<?php echo esc_attr( $palette ); ?>" data-default-color="<?php echo esc_attr( $this->settings['default']->default ); ?>" <?php $this->link(); ?>  />
                </label>
                <?php
            }
        }

        class SF_Custom_Control__TinyMCE_Control extends WP_Customize_Control {
            /**
             * The type of control being rendered
             */
            public $type = 'sf-tinymce-editor';

            /**
             * Pass our TinyMCE toolbar string to JavaScript
             */
            public function to_json() {
                parent::to_json();
                $this->json['skyrockettinymcetoolbar1'] = isset( $this->input_attrs['toolbar1'] ) ? esc_attr( $this->input_attrs['toolbar1'] ) : 'bold italic bullist numlist alignleft aligncenter alignright link';
                $this->json['skyrockettinymcetoolbar2'] = isset( $this->input_attrs['toolbar2'] ) ? esc_attr( $this->input_attrs['toolbar2'] ) : '';
            }
            /**
             * Render the control in the customizer
             */
            public function render_content(){
                // hack for square brackets not supported in IDs by tinymce
                ?>
                <div class="tinymce-control">
                    <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                    <?php if( !empty( $this->description ) ) { ?>
                        <span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
                    <?php } ?>
                    <textarea id="<?php echo str_replace('[', '_s_',  str_replace(']', '_e_', esc_attr( $this->id )) ); ?>" name="<?php echo esc_attr( $this->id ); ?>" class="sfm-customize-control-tinymce-editor" <?php $this->link(); ?>><?php echo esc_attr( $this->value() ); ?></textarea>
                </div>
                <?php
            }
        }

        class SF_Custom_Control__Google_Font extends WP_Customize_Control {

            public static $manifestLoaded = false;

            public $type = 'sf-google-font';

            public $fontsManifest;

            public $google_fonts = '[]';

            /**
             * Get our list of fonts from the json file
             */
            public function __construct( $manager, $id, $args = array(), $options = array() ) {
                parent::__construct( $manager, $id, $args );

                if ( self::manifestWasLoaded() ) return;

                add_action( 'customize_controls_print_footer_scripts' , 'writeGoogleFontsManifest');

                self::markManifestAsLoaded();
            }

            /**
             * Render the control.
             */
            public function render_content() {

                /*if ( !self::manifestWasLoaded() ) {

                    $fontsManifest = plugin_dir_path(__FILE__) . 'includes/vendor/looks_awesome/google_fonts/google-fonts-fallback.json';

                    if ( file_exists( $fontsManifest ) ){
                        $google_fonts = file_get_contents( $fontsManifest );
                        echo "<script>var GOOGLE_FONTS = " .  $google_fonts  . "</script>";
                        self::markManifestAsLoaded();
                    }
                }*/

                // Begin the output. ?>
                <label>
                    <?php // Output the label and description if they were passed in.
                    if ( isset( $this->label ) && '' !== $this->label ) {
                        echo '<span class="customize-control-title">' . sanitize_text_field( $this->label ) . '</span>';
                    }
                    if ( !empty( $this->description ) ) {
                        echo '<span class="description customize-control-description">' . esc_html( $this->description ) . '</span>';
                    } ?>
                <input class="sfm-google-font-input" type="text" id="<?php echo esc_attr( $this->id ); ?>" name="<?php echo esc_attr( $this->id ); ?>" value="<?php  $this->value(); ?>" <?php $this->link(); ?> />
                <?php
            }

            function markManifestAsLoaded() {
                self::$manifestLoaded = true;
            }

            function manifestWasLoaded() {
                return self::$manifestLoaded == true;
            }
        }

    }

    /* PANELS */

    $wp_customize->add_panel( 'sf_panel', array(
        'title' => 'Superfly Menu',
        'description' => 'This is a description of this panel',
        'priority' => 101,
    ) );

    /* SECTIONS */

    $wp_customize->add_section('sf_settings_panel', array(
        'title' => 'Menu Panel',
        'priority'   => 1,
        'panel'   => 'sf_panel'
    ));

    $wp_customize->add_section('sf_settings_styling', array(
        'title' => 'Menu Panel Styling',
        'priority'   => 2,
        'panel'   => 'sf_panel'
    ));

    $wp_customize->add_section('sf_settings_extra', array(
        'title' => 'Menu Extra Content',
        'priority'   => 2,
        'panel'   => 'sf_panel'
    ));

    $wp_customize->add_section('sf_settings_social', array(
        'title' => 'Menu Social',
        'priority'   => 2,
        'panel'   => 'sf_panel'
    ));

    $wp_customize->add_section('sf_settings_items', array(
        'title' => 'Menu Items',
        'priority'   => 4,
        'panel'   => 'sf_panel'
    ));

    $wp_customize->add_section('sf_settings_button', array(
        'title' => 'Button',
        'priority'   => 4,
        'panel'   => 'sf_panel'
    ));

    /* SETTINGS */

    // add_setting must be before add_control

    // Menu panel

    $wp_customize->add_setting('sf_options[sf_sidebar_pos]', array(
        'default'   => 'left',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_sidebar_pos]', array(
        'label'   => 'Menu side',
        'description' => 'For side panel and button.',
        'section' => 'sf_settings_panel',
        'type'    => 'radio',
        'choices' => array(
            'left' => esc_html__( 'Left' ),
            'right' => esc_html__( 'Right' )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_sidebar_style]', array(
        'default'   => 'side',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_sidebar_style]',
        array(
            'label' => esc_html__( 'Design Layout' ),
            'description' => 'Please notice some layouts don\'t support all menu settings due to natural reasons',
            'section' => 'sf_settings_panel',
            'choices' => array( // Optional.
                'side' => esc_html__( 'Panel' ),
                'toolbar' => esc_html__( 'Navbar' ),
                'full' => esc_html__( 'Full' ),
                'skew' => esc_html__( 'Skewed' )
            )
        )
    ));

    $wp_customize->add_setting('sf_options[sf_skew_type]', array(
        'default'   => 'top',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_skew_type]', array(
        'label'   => 'Skew style',
        'section' => 'sf_settings_panel',
        'type'    => 'radio',
        'choices' => array(
            'top' => esc_html__( 'Top' ),
            'bottom' => esc_html__( 'Bottom' )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_fs_layout]',
        array(
            'default' => 'single',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_fs_layout]',
        array(
            'label' => esc_html__( 'Fullscreen layout' ),
            'section' => 'sf_settings_panel',
            'type' => 'select',
            'input_attrs'  => array( 'data-settings-set'  => 'fullscreen'),
            'choices' => array(
                'single' => esc_html__( 'One column' ),
                'multibox' => esc_html__( 'Multibox' )
            )
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_sidebar_behaviour]', array(
        'default'   => 'slide',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_sidebar_behaviour]',
        array(
            'label' => esc_html__( 'Behavior' ),
            'section' => 'sf_settings_panel',
            'choices' => array(
                'slide' => esc_html__( 'Slide in' ),
                'push' => esc_html__( 'Push page' ),
                'always' => esc_html__( 'Always on' )
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_opening_type]', array(
        'default'   => 'click',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_opening_type]',
        array(
            'label' => esc_html__( 'Button interface' ),
            'description' => esc_html__( 'Choose how visitor opens menu.' ),
            'section' => 'sf_settings_panel',
            'choices' => array(
                'click' => esc_html__( 'Click' ),
                'hover' => esc_html__( 'Mouseover' )
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_sub_opening_type]', array(
        'default'   => 'click',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_sub_opening_type]',
        array(
            'label' => esc_html__( 'Submenus interface' ),
            'description' => esc_html__( 'How visitor interacts with submenus.' ),
            'section' => 'sf_settings_panel',
            'choices' => array(
                'click' => esc_html__( 'Click' ),
                'hover' => esc_html__( 'Mouseover' )
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_sub_type]',
        array(
            'default' => 'flyout',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_sub_type]',
        array(
            'label' => esc_html__( 'Submenus appearance' ),
            'description' => esc_html__( 'How submenu items expand.' ),
            'section' => 'sf_settings_panel',
            'type' => 'select',
            'choices' => array(
                'flyout' => esc_html__( 'Flyout' ),
                'dropdown' => esc_html__( 'Dropdown' ),
                'swipe' => esc_html__( 'Swipe' )
            )
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_fade_content]', array(
        'default'   => 'light',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_fade_content]',
        array(
            'label' => esc_html__( 'Fade effect' ),
            'description' => esc_html__( 'For page content when menu is opened.' ),
            'section' => 'sf_settings_panel',
            'choices' => array(
                'light' => esc_html__( 'Light' ),
                'dark' => esc_html__( 'Dark' ),
                'none' => esc_html__( 'None' )
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_fade_full]',
        array(
            'default'     => 'rgba(0,0,0,0.9)',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_fade_full]',
        array(
            'label' => esc_html__( 'Main color' ),
            'section' => 'sf_settings_panel',
            'show_opacity' => true, // Optional.
            'input_attrs'  => array( 'data-settings-set'  => 'fullscreen'),
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_full_head]',
        array(
            'default'     => 'rgb(255, 255, 255)',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_full_head]',
        array(
            'label' => esc_html__( 'Header color' ),
            'section' => 'sf_settings_panel',
            'show_opacity' => true, // Optional.
            'input_attrs'  => array( 'data-settings-set'  => 'fullscreen'),
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_full_sec]',
        array(
            'default'     => 'rgb(251, 101, 84)',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_full_sec]',
        array(
            'label' => esc_html__( 'Footer color' ),
            'section' => 'sf_settings_panel',
            'show_opacity' => true, // Optional.
            'input_attrs'  => array( 'data-settings-set'  => 'fullscreen'),
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_full_x]',
        array(
            'default'     => 'rgb(128, 128, 128)',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_full_x]',
        array(
            'label' => esc_html__( 'X button color' ),
            'section' => 'sf_settings_panel',
            'show_opacity' => true, // Optional.
            'input_attrs'  => array( 'data-settings-set'  => 'fullscreen'),
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_transition]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_transition]',
        array(
            'label' => esc_html__( 'Fading page transitions' ),
            'description' => esc_html__( 'When user clicks link.' ),
            'section' => 'sf_settings_panel'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_blur_content]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_blur_content]',
        array(
            'label' => esc_html__( 'Blur effect' ),
            'description' => esc_html__( 'When menu is exposed.' ),
            'section' => 'sf_settings_panel'
        )
    ));

    #SECTION Menu panel styling

    // first level
    $wp_customize->add_setting( 'sf_section_1',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_1',
        array(
            'label' => esc_html__( 'First level' ),
            'section' => 'sf_settings_styling'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_width_panel_1]', array(
        'default'   => '225',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_width_panel_1]', array(
        'description' => 'Panel width. Default is 250.',
        'section' => 'sf_settings_styling',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 5, 'max' => '400', 'min' => '100', 'step' => '5', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_bg_color_panel_1]',
        array(
            'default'     => '#212121',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_bg_color_panel_1]',
        array(
            'description' => esc_html__( 'Background' ),
            'section' => 'sf_settings_styling',
            'show_opacity' => true, // Optional.
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_color_panel_1]',
        array(
            'default' => '#aaaaaa',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_color_panel_1]',
        array(
            'description' => esc_html__( 'Text color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_scolor_panel_1]',
        array(
            'default' => '#eeeeee',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_scolor_panel_1]',
        array(
            'description' => esc_html__( 'Second line' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_chapter_1]',
        array(
            'default' => '#00ffb8',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_chapter_1]',
        array(
            'description' => esc_html__( 'Section heading' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_hover_panel_1]',
        array(
            'default' => '#008feb',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_hover_panel_1]',
        array(
            'description' => esc_html__( 'Hover color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_image_bg]',
        array(
            'default' => '',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'sf_options[sf_image_bg]',
        array(
            'label' => esc_html__( 'Custom image background' ),
            'section' => 'sf_settings_styling',
            'button_labels' => array( // Optional.
                'select' => esc_html__( 'Select Image' ),
                'change' => esc_html__( 'Change Image' ),
                'remove' => esc_html__( 'Remove' ),
                'default' => esc_html__( 'Default' ),
                'placeholder' => esc_html__( 'No image selected' ),
                'frame_title' => esc_html__( 'Select Image' ),
                'frame_button' => esc_html__( 'Choose Image' ),
            )
        )
    ) );

    $wp_customize->add_setting( 'sf_options[sf_video_bg]',
        array(
            'default' => '',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_video_bg]',
        array(
            'label' => esc_html__( 'Video background YouTube URL or ID' ),
            'description' => 'For example AgI7OcZ9g60',
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'text'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_video_preload]',
        array(
            'default' => 0,
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_video_preload]',
        array(
            'label' => esc_html__( 'Preload video (increases page load)' ),
            'section'  => 'sf_settings_styling',
            'priority' => 10, // Optional. Order priority to load the control. Default: 10
            'type'=> 'checkbox'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_video_mob]',
        array(
            'default' => 0,
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_video_mob]',
        array(
            'label' => esc_html__( 'Video is on desktops only' ),
            'section'  => 'sf_settings_styling',
            'priority' => 10, // Optional. Order priority to load the control. Default: 10
            'type'=> 'checkbox'
        )
    );

    // second level

    $wp_customize->add_setting( 'sf_section_2',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );
    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_2',
        array(
            'label' => esc_html__( 'Second Level' ),
            'section' => 'sf_settings_styling'
        )
    ) );

    $wp_customize->add_setting('sf_options[sf_width_panel_2]', array(
        'default'   => '250',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_width_panel_2]', array(
        'description' => 'Panel width. Default is 250.',
        'section' => 'sf_settings_styling',
        'input_attrs'  => array( 'size'  => 5, 'class' => 'sf-num sf-px-value' ),
        'type'    => 'text'
    ));


    $wp_customize->add_setting( 'sf_options[sf_bg_color_panel_2]',
        array(
            'default'     => '#767676',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_bg_color_panel_2]',
        array(
            'description' => esc_html__( 'Background' ),
            'section' => 'sf_settings_styling',
            'show_opacity' => true,
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_color_panel_2]',
        array(
            'default' => '#aaaaaa',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_color_panel_2]',
        array(
            'description' => esc_html__( 'Text color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_scolor_panel_2]',
        array(
            'default' => '#eeeeee',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_scolor_panel_2]',
        array(
            'description' => esc_html__( 'Second line' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_chapter_2]',
        array(
            'default' => '#00ffb8',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_chapter_2]',
        array(
            'description' => esc_html__( 'Section heading' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_hover_panel_2]',
        array(
            'default' => '#008feb',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_hover_panel_2]',
        array(
            'description' => esc_html__( 'Hover color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    // third level

    $wp_customize->add_setting( 'sf_section_3',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );
    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_3',
        array(
            'label' => esc_html__( 'Third Level' ),
            'section' => 'sf_settings_styling'
        )
    ) );

    $wp_customize->add_setting('sf_options[sf_width_panel_3]', array(
        'default'   => '250',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_width_panel_3]', array(
        'description' => 'Panel width. Default is 250.',
        'section' => 'sf_settings_styling',
        'input_attrs'  => array( 'size'  => 5, 'class' => 'sf-num sf-px-value' ),
        'type'    => 'text'
    ));


    $wp_customize->add_setting( 'sf_options[sf_bg_color_panel_3]',
        array(
            'default'     => '#9e466b',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_bg_color_panel_3]',
        array(
            'description' => esc_html__( 'Background' ),
            'section' => 'sf_settings_styling',
            'show_opacity' => true, // Optional.
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));
//
    $wp_customize->add_setting( 'sf_options[sf_color_panel_3]',
        array(
            'default' => '#aaaaaa',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_color_panel_3]',
        array(
            'description' => esc_html__( 'Text color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_scolor_panel_3]',
        array(
            'default' => '#eeeeee',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_scolor_panel_3]',
        array(
            'description' => esc_html__( 'Second line' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_chapter_3]',
        array(
            'default' => '#00ffb8',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_chapter_3]',
        array(
            'description' => esc_html__( 'Section heading' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_hover_panel_3]',
        array(
            'default' => '#008feb',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_hover_panel_3]',
        array(
            'description' => esc_html__( 'Hover color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    // forth level

    $wp_customize->add_setting( 'sf_section_4',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );
    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_4',
        array(
            'label' => esc_html__( 'Forth Level' ),
            'section' => 'sf_settings_styling'
        )
    ) );

    $wp_customize->add_setting('sf_options[sf_width_panel_4]', array(
        'default'   => '250',
        'capability' => 'manage_options',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_width_panel_4]', array(
        'description' => 'Panel width. Default is 250.',
        'section' => 'sf_settings_styling',
        'input_attrs'  => array( 'size'  => 5, 'class' => 'sf-num sf-px-value' ),
        'type'    => 'text'
    ));

    $wp_customize->add_setting( 'sf_options[sf_bg_color_panel_4]',
        array(
            'default'     => '#36939e',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_bg_color_panel_4]',
        array(
            'description' => esc_html__( 'Background' ),
            'section' => 'sf_settings_styling',
            'show_opacity' => true, // Optional.
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_color_panel_4]',
        array(
            'default' => '#aaaaaa',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_color_panel_4]',
        array(
            'description' => esc_html__( 'Text color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_scolor_panel_4]',
        array(
            'default' => '#eeeeee',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_scolor_panel_4]',
        array(
            'description' => esc_html__( 'Second line' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_chapter_4]',
        array(
            'default' => '#00ffb8',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_chapter_4]',
        array(
            'description' => esc_html__( 'Section heading' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );
    //
    $wp_customize->add_setting( 'sf_options[sf_hover_panel_4]',
        array(
            'default' => '#008feb',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_hover_panel_4]',
        array(
            'description' => esc_html__( 'Hover color' ),
            'section' => 'sf_settings_styling',
            'capability' => 'manage_options',
            'type' => 'color'
        )
    );

    #SECTION Menu extra

    $wp_customize->add_setting( 'sf_options[sf_tab_logo]',
        array(
            'default' => '',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'sf_options[sf_tab_logo]',
        array(
            'label' => 'Top Image',
            'description' => 'Logo or user profile photo. Recommended size 100x100',
            'section' => 'sf_settings_extra',
            'button_labels' => array( // Optional.
                'select' => esc_html__( 'Select Image' ),
                'change' => esc_html__( 'Change Image' ),
                'remove' => esc_html__( 'Remove' ),
                'default' => esc_html__( 'Default' ),
                'placeholder' => esc_html__( 'No image selected' ),
                'frame_title' => esc_html__( 'Select Image' ),
                'frame_button' => esc_html__( 'Choose Image' ),
            )
        )
    ) );


    $wp_customize->add_setting( 'sf_options[sf_logo_size]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_logo_size]', array(
        'label' => 'Image size',
        'description' => 'Max height of image in menu header. Leave empty to use original image size.',
        'section' => 'sf_settings_extra',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 3, 'max' => '100', 'min' => '6', 'step' => '10', 'class' => 'sf-num sf-px-value' )
    ));

    $wp_customize->add_setting( 'sf_options[sf_first_line]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_first_line]', array(
        'label'   => 'Image text first line',
        'description' => 'You can show this text at the top of sidebar under image, eg. your name or your company name.',
        'section' => 'sf_settings_extra',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_sec_line]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_sec_line]', array(
        'label'   => 'Image text second line',
        'description' => 'For example you can use first line for name and second line for your job title or company motto.',
        'section' => 'sf_settings_extra',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_search]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_search]',
        array(
            'label' => 'Search Field',
            'section' => 'sf_settings_extra'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_search_bg]',
        array(
            'default' => 'light',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_search_bg]',
        array(
            'label' => esc_html__( 'Search field background' ),
            'section' => 'sf_settings_extra',
            'type' => 'select',
            'choices' => array(
                'light' => esc_html__( 'Light' ),
                'dark' => esc_html__( 'Dark' )
            )
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_above_logo]',
        array(
            'default' => '',
            'type'      => 'option',
            'transport' => 'postMessage',
            'sanitize_callback' => 'wp_kses_post'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__TinyMCE_Control ( $wp_customize, 'sf_options[sf_above_logo]',
        array(
            'label' => esc_html__( 'Above logo content area' ),
            'description' => 'Widget area and custom content above menu main image',
            'section' => 'sf_settings_extra',
            'input_attrs' => array(
                'toolbar1' => 'bold italic bullist numlist alignleft aligncenter alignright link',
            )
        )
    ) );

    $wp_customize->add_setting( 'sf_options[sf_under_logo]',
        array(
            'default' => '',
            'type'      => 'option',
            'transport' => 'postMessage',
            'sanitize_callback' => 'wp_kses_post'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__TinyMCE_Control ( $wp_customize, 'sf_options[sf_under_logo]',
        array(
            'label' => esc_html__( 'Under logo content area' ),
            'section' => 'sf_settings_extra',
            'description' => 'Widget area and custom content under menu main image',
            'input_attrs' => array(
                'toolbar1' => 'bold italic bullist numlist alignleft aligncenter alignright link',
            )
        )
    ) );

    $wp_customize->add_setting( 'sf_options[sf_copy]',
        array(
            'default' => '',
            'type'      => 'option',
            'transport' => 'postMessage',
            'sanitize_callback' => 'wp_kses_post'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__TinyMCE_Control ( $wp_customize, 'sf_options[sf_copy]',
        array(
            'label' => esc_html__( 'Copyrights content area' ),
            'description' => 'Widget area and custom content in the bottom',
            'section' => 'sf_settings_extra',
            'input_attrs' => array(
                'toolbar1' => 'bold italic bullist numlist alignleft aligncenter alignright link',
            )
        )
    ) );

    #SECTION Menu social

    $wp_customize->add_setting( 'sf_options[sf_social_style]',
        array(
            'default' => 'icon',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_social_style]',
        array(
            'label' => esc_html__( 'Symbols Style' ),
            'section' => 'sf_settings_social',
            'type' => 'select',
            'choices' => array(
                'icon' => esc_html__( 'Icons' ),
                'abbr' => esc_html__( 'Abbreviations' )
            )
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_social_color]',
        array(
            'default' => '#aaaaaa',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_social_color]',
        array(
            'label' => esc_html__( 'Symbols color' ),
            'section' => 'sf_settings_social',
            'type' => 'color'
        )
    );


    $wp_customize->add_setting( 'sf_options[sf_social_align]', array(
        'default'   => 'center',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_social_align]',
        array(
            'label' => esc_html__( 'Alignment' ),
            'section' => 'sf_settings_social',
            'input_attrs'  => array( 'class' => 'sf-align-control' ),
            'choices' => array(
                'left' => '<span class="dashicons dashicons-editor-alignleft"></span>',
                'center' => '<span class="dashicons dashicons-editor-aligncenter"></span>',
                'right' => '<span class="dashicons dashicons-editor-alignright"></span>',
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_facebook]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_facebook]', array(
        'label'   => 'Facebook URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_instagram]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_instagram]', array(
        'label'   => 'Instagram URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_twitter]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_twitter]', array(
        'label'   => 'Twitter URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_youtube]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_youtube]', array(
        'label'   => 'Youtube URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_linkedin]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_linkedin]', array(
        'label'   => 'Linkedin URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_dribbble]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_dribbble]', array(
        'label'   => 'Dribbble URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_vimeo]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_vimeo]', array(
        'label'   => 'Vimeo URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_pinterest]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_pinterest]', array(
        'label'   => 'Pinterest URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_flickr]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_flickr]', array(
        'label'   => 'Flickr URL',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_rss]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_rss]', array(
        'label'   => 'RSS',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_skype]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_skype]', array(
        'label'   => 'Skype',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_email]', array(
        'default'   => '',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_email]', array(
        'label'   => 'Email',
        'section' => 'sf_settings_social',
        'type'    => 'text',
    ));

    #SECTION Menu items

    $wp_customize->add_setting( 'sf_section_items_1',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_items_1',
        array(
            'label' => esc_html__( 'Font settings' ),
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_font]',
        array(
            'default' => 'inherit',
            'type'      => 'option',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Google_Font( $wp_customize, 'sf_options[sf_font]',
        array(
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_font_size]', array(
        'default'   => '20',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_font_size]', array(
        'section' => 'sf_settings_items',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 3, 'max' => '100', 'min' => '6', 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_alignment]', array(
        'default'   => 'left',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_alignment]',
        array(
            'section' => 'sf_settings_items',
            'input_attrs'  => array( 'class' => 'sf-align-control' ),
            'choices' => array(
                'left' => '<span class="dashicons dashicons-editor-alignleft"></span>',
                'center' => '<span class="dashicons dashicons-editor-aligncenter"></span>',
                'right' => '<span class="dashicons dashicons-editor-alignright"></span>',
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_font_weight]', array(
        'default'   => 'normal',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_font_weight]',
        array(
            'section' => 'sf_settings_items',
            'choices' => array(
                'normal' => __( 'Normal' ),
                'bold' => __( '<strong>Bold</strong>' ),
                'lighter' => __( '<span style="font-weight:300">Light</span>' ),
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_uppercase]', array(
        'default'   => 'no',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_uppercase]',
        array(
            'section' => 'sf_settings_items',
            'choices' => array(
                'no' => esc_html__( 'Aa' ),
                'yes' => esc_html__( 'AA' )
            )
        )
    ));

    $wp_customize->add_setting( 'sf_section_items_2',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_items_2',
        array(
            'label' => esc_html__( 'Section headers styling' ),
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_c_font]',
        array(
            'default' => 'inherit',
            'type'      => 'option',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Google_Font( $wp_customize, 'sf_options[sf_c_font]',
        array(
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_c_fs]', array(
        'default'   => '15',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_c_fs]', array(
        'section' => 'sf_settings_items',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 3, 'max' => '100', 'min' => '6', 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_c_weight]', array(
        'default'   => 'normal',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_c_weight]',
        array(
            'section' => 'sf_settings_items',
            'choices' => array(
                'normal' => esc_html__( 'Normal' ),
                'bold' => ( '<strong>' . esc_html__( 'Bold' ) . '</strong>' ),
                'lighter' => ('<span style="font-weight:300">' . esc_html__( 'Light' ) . '</span>' ),
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_c_trans]', array(
        'default'   => 'no',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Text_Radio_Button( $wp_customize, 'sf_options[sf_c_trans]',
        array(
            'section' => 'sf_settings_items',
            'choices' => array(
                'no' => esc_html__( 'Aa' ),
                'yes' => esc_html__( 'AA' )
            )
        )
    ));

    $wp_customize->add_setting( 'sf_section_items_3',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_items_3',
        array(
            'label' => esc_html__( 'Customizing items' ),
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_spacing]', array(
        'default'   => '0',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_spacing]', array(
        'description' => 'Letter spacing. Can be positive or negative. Default is 0',
        'section' => 'sf_settings_items',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 3, 'max' => '100', 'min' => '6', 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_padding]', array(
        'default'   => '25',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_padding]', array(
        'section' => 'sf_settings_items',
        'description' => 'Padding of menu item',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'max' => '100', 'min' => '0', 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_padding_h]', array(
        'default'   => '10',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_padding_h]', array(
        'section' => 'sf_settings_items',
        'description' => 'Percentage margin (horizontal)',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'max' => '100', 'min' => '0', 'step' => '1', 'class' => 'sf-num sf-per-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_icon_size]', array(
        'default'   => '40',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_icon_size]', array(
        'section' => 'sf_settings_items',
        'description' => 'Icons & images size',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'max' => '100', 'min' => '6', 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_icon_color]',
        array(
            'default' => '#777777',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_icon_color]',
        array(
            'description' => esc_html__( 'Icons color (default, custom can apply)' ),
            'section' => 'sf_settings_items',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_ordered]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_ordered]',
        array(
            'description' => 'Order Numbers of Menu items order',
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_ind]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_ind]',
        array(
            'description' => 'Submenu indicators',
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_ind_s]', array(
        'default'   => '6',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_ind_s]', array(
        'section' => 'sf_settings_items',
        'description' => 'Indicators size',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_ind_st]', array(
        'default'   => '2',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_ind_st]', array(
        'section' => 'sf_settings_items',
        'description' => 'Indicators width',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_separators]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_separators]',
        array(
            'description' => 'Separators between menu items',
            'section' => 'sf_settings_items'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_separators_color]',
        array(
            'default'     => 'rgba(0, 0, 0, 0.15)',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Alpha_Color( $wp_customize, 'sf_options[sf_separators_color]',
        array(
            'description' => esc_html__( 'Separators color' ),
            'section' => 'sf_settings_items',
            'show_opacity' => true, // Optional.
            'palette'      => array(
                'rgb(150, 50, 220)',
                'rgba(50,50,50,0.8)',
                'rgba( 255, 255, 255, 0.2 )',
                '#00CC99' // Mix of color types = no problem
            )
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_separators_width]', array(
        'default'   => '100',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_separators_width]', array(
        'section' => 'sf_settings_items',
        'description' => 'Separators width relative to panel',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'step' => '1', 'class' => 'sf-num sf-per-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_highlight]',
        array(
            'default' => 'line',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_highlight]',
        array(
            'description' => esc_html__( 'Highlighting of menu items on hover' ),
            'section' => 'sf_settings_items',
            'type' => 'select',
            'choices' => array(
                'line' => esc_html__( 'Line' ),
                'solid' => esc_html__( 'Solid color highlighting' ),
                'semi' => esc_html__( 'Semitransparent highlight' ),
                'semi-dark' => esc_html__( 'Semitransparent highlight (dark)' ),
                'text' => esc_html__( 'Text color' ),
                'strike' => esc_html__( 'Strikethrough' ),
            )
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_hover_style]',
        array(
            'default' => 'text',
            'transport' => 'postMessage',
            'type'      => 'option'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_hover_style]',
        array(
            'description' => esc_html__( 'Shift on hover' ),
            'section' => 'sf_settings_items',
            'type' => 'select',
            'choices' => array(
                'text' => esc_html__( 'Text' ),
                'arrow' => esc_html__( 'Submenu indicator' ),
                'none' => esc_html__( 'None' )
            )
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_highlight_active]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_highlight_active]',
        array(
            'description' => 'Highlighting active page item',
            'section' => 'sf_settings_items'
        )
    ));

    #SECTION Menu button

    $wp_customize->add_setting( 'sf_section_btn_1',
        array(
            'default' => '',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( new SF_Custom_Control__Section_Title( $wp_customize, 'sf_section_btn_1',
        array(
            'label' => esc_html__( 'General' ),
            'section' => 'sf_settings_button'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_vis]', array(
        'default'   => 1,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_label_vis]',
        array(
            'label' => 'Button visibility',
            'description' => 'Turn it off to use your custom toggle element. <a href="http://superfly.looks-awesome.com/docs/Customize/Custom_Menu_Trigger">Guide</a>',
            'section' => 'sf_settings_button'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_fixed]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_fixed]',
        array(
            'description' => 'Button fixed on page',
            'section' => 'sf_settings_button'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_shift]', array(
        'default'   => '10px',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_label_shift]', array(
        'label'   => 'Horizontal shift',
        'description' => 'Please enter valid CSS value for ex. \'50%\' or \'200px\'. Will not take any affect on mobile!',
        'section' => 'sf_settings_button',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_top]', array(
        'default'   => '10px',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_label_top]', array(
        'label'   => 'Top Margin',
        'description' => 'Please enter valid CSS value for ex. \'50%\' or \'200px\'.',
        'section' => 'sf_settings_button',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_top_mobile]', array(
        'default'   => '10px',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_label_top_mobile]', array(
        'label'   => 'Top Margin On Mobiles',
        'section' => 'sf_settings_button',
        'type'    => 'text',
    ));

    $wp_customize->add_setting( 'sf_options[sf_mob_nav]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_mob_nav]',
        array(
            'description' => 'Navbar for mobiles. Ignores some other settings',
            'section' => 'sf_settings_button'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_threshold_point]', array(
        'default'   => '586',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_threshold_point]', array(
        'section' => 'sf_settings_button',
        'description' => 'Threshold point. Navbar will appear if screen width is smaller',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 4, 'step' => '50', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_size]', array(
        'default'   => '53',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control( 'sf_options[sf_label_size]', array(
        'label'   => 'Icon Size',
        'section' => 'sf_settings_button',
        'input_attrs'  => array( 'size'  => 2, 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'type'    => 'number',
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_style]',
        array(
            'default' => 'none',
            'type'      => 'option',
            'transport' => 'postMessage'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_label_style]',
        array(
            'description' => esc_html__( 'Button style' ),
            'section' => 'sf_settings_button',
            'type' => 'radio',
            'choices' => array(
                'none' => esc_html__( 'Just icon' ),
                'metro' => esc_html__( 'Metro-style icon' ),
                'square' => esc_html__( 'Icon in rectangle' ),
                'rsquare' => esc_html__( 'Icon in rounded rectangle' ),
                'circle' => esc_html__( 'Icon in circle' )
            )
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_label_width]', array(
        'default'   => '2',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_label_width]', array(
        'section' => 'sf_settings_button',
        'description' => 'Icon lines width',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_gaps]', array(
        'default'   => '7',
        'type'      => 'option',
        'transport' => 'postMessage'
    ));

    $wp_customize->add_control('sf_options[sf_label_gaps]', array(
        'section' => 'sf_settings_button',
        'description' => 'Icon lines gaps',
        'type'    => 'number',
        'input_attrs'  => array( 'size'  => 2, 'step' => '1', 'class' => 'sf-num sf-px-value' ),
        'sanitize_callback' => 'wp_filter_nohtml_kses',
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_color]',
        array(
            'default' => '#000000',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_label_color]',
        array(
            'description' => esc_html__( 'Button base color' ),
            'section' => 'sf_settings_button',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_label_icon_color]',
        array(
            'default' => '#ffffff',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_label_icon_color]',
        array(
            'description' => esc_html__( 'Button Icon color' ),
            'section' => 'sf_settings_button',
            'type' => 'color'
        )
    );

    $wp_customize->add_setting( 'sf_options[sf_label_text]', array(
        'default'   => 0,
        'type'      => 'option',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sf_switch_sanitization'
    ));

    $wp_customize->add_control( new SF_Custom_Control__Toggle_Switch( $wp_customize, 'sf_options[sf_label_text]',
        array(
            'description' => 'Show "Menu" under button',
            'section' => 'sf_settings_button'
        )
    ));

    $wp_customize->add_setting( 'sf_options[sf_label_text_color]',
        array(
            'default' => '#CA3C08',
            'transport' => 'postMessage',
            'type'      => 'option',
            'sanitize_callback' => 'sanitize_hex_color'
        )
    );

    $wp_customize->add_control( 'sf_options[sf_label_text_color]',
        array(
            'description' => esc_html__( 'Button text color' ),
            'section' => 'sf_settings_button',
            'type' => 'color'
        )
    );

    /* LIVE PREVIEW */

    /*$wp_customize->selective_refresh->add_partial( 'sf_options[sf_label_shift]',
        array(
            'selector' => '#superfly-dynamic',
            'container_inclusive' => false,
            'render_callback' => function() {
                echo sf_insert_dynamic_styles();
            },
            'fallback_refresh' => true
        )
    );*/
}

function validate_num ( $validity, $value ) {
    $value = intval( $value );
    if ( ! is_numeric( $value ) ) {
        $validity->add( 'required', esc_html__( 'You must supply a valid value.' ) );
    }
    return $validity;
}

function sf_sanitize ( $input ) {
    return $input;
}

function sf_switch_sanitization ( $input ) {
    //return $input == 1 ? 'yes' : 'no';
    if ( true === $input ) {
        return 'yes';
    } else {
        return 'no';
    }
}

function sf_preview_action () {

    global $current_page_sf_menu;
    global $sf_menu_data;

    if ( !check_ajax_referer( 'superfly_nonce', 'security' ) ) {
        echo ( 'not_allowed' );
        wp_die();
    }

    $job = sanitize_text_field( $_POST['job'] );
    $current_page_sf_menu = sanitize_text_field( $_POST['menu'] );

    // configs

    $options = sf_get_options();

    $width_panel_1 = !empty($options['sf_width_panel_1']) ? $options['sf_width_panel_1'] : 250;
    $width_panel_2 = !empty($options['sf_width_panel_2']) ? $options['sf_width_panel_2'] : 250;
    $width_panel_3 = !empty($options['sf_width_panel_3']) ? $options['sf_width_panel_3'] : 250;
    $width_panel_4 = !empty($options['sf_width_panel_4']) ? $options['sf_width_panel_4'] : 250;
    if ($options['sf_sidebar_style'] == 'toolbar' && !wp_is_mobile()) $width_panel_1 = 100;
    $panel1_adjusted = $width_panel_1;
    $sum1 = $width_panel_1 + $width_panel_2;
    $sum2 = $width_panel_1 + $width_panel_2 + $width_panel_3;
    $sum2a = $width_panel_1 + $width_panel_2 + $width_panel_3 + $width_panel_4;
    $sum3 = $width_panel_2 + $width_panel_3;
    $sum4 = $width_panel_2 + $width_panel_3 + $width_panel_4;
    $color_panel_1 = $options['sf_color_panel_1'];
    $color_panel_2 = $options['sf_color_panel_2'];
    $color_panel_3 = $options['sf_color_panel_3'];
    $color_panel_4 = $options['sf_color_panel_4'];
    $bg_color_panel_1 = !empty($options['sf_bg_color_panel_1']) ? $options['sf_bg_color_panel_1'] : '#202b2d';
    $bg_color_panel_2 = !empty($options['sf_bg_color_panel_2']) ? $options['sf_bg_color_panel_2'] : '#b5b5b5';
    $bg_color_panel_3 = !empty($options['sf_bg_color_panel_3']) ? $options['sf_bg_color_panel_3'] : '#36939e';
    $bg_color_panel_4 = !empty($options['sf_bg_color_panel_4']) ? $options['sf_bg_color_panel_4'] : '#9e466b';
    $icon_color = !empty($options['sf_icon_color']) ? $options['sf_icon_color'] : '#777';
    $font = $options['sf_font'];
    $font_c = $options['sf_c_font'];
    $weight = $options['sf_font_weight'];
    $opacityLevel = $options['sf_fade_content'] === 'light' ? 0.6 : ($options['sf_fade_content'] === 'dark' ? 0.9 : 0);
    $width_panel_23 = $width_panel_1 / 2;
    $width_panel_skew_shift = $width_panel_1;
    $width_panel_skew_stroke = 0;
    $width_panel_skew = $width_panel_1*2;
    $margin = isset( $options['sf_padding_h'] ) ? intval( $options['sf_padding_h'] ) : 10;
    $label_size = strpos( $options['sf_label_size'], 'px' ) !== false ? $options['sf_label_size'] : $options['sf_label_size'] . 'px';
    $line = $options['sf_label_width'];
    $transform = $options['sf_uppercase'] === 'yes' ? 'uppercase' : 'capitalize';
    $searchbg = $options['sf_search_bg'] === 'light' ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.3)';
    if ( isset( $options['sf_ind_s'] ) && isset( $options['sf_ind_st'] ) ) {
        $ind_mg = ( intval( $options['sf_ind_s'] ) + intval( $options['sf_ind_st'] )) / 2;
        $ind_mg -= 1;
    } else {
        $ind_mg = 3;
    }

    $social = array();
    $networks = array(
        'facebook',
        'twitter',
        'linkedin',
        'gplus',
        'instagram',
        'pinterest',
        'flickr',
        'dribbble',
        'youtube',
        'vimeo',
        'soundcloud',
        'email',
        'skype',
        'rss',
    );

    foreach ($networks as $network) {
        if (!empty($options['sf_' . $network])) {
            $social[$network] = $options['sf_' . $network];
        }
    }

    $sf_menu_data = sfm_get_menus_data( $current_page_sf_menu );

    $js_opts = array(
        'social' => $social,
        'search' => $options['sf_search'],
        'blur' => $options['sf_blur_content'],
        'fade' => $options['sf_transition'],
        'test_mode' => $options['sf_test_mode'],
        'hide_def' => $options['sf_hide_def'],
        'mob_nav' => $options['sf_mob_nav'],
        'dynamic' => $options['sf_fs_dynamic'],
        'parent_ignore' => $options['sf_parent_ignore'],
        'sidebar_style' => $options['sf_sidebar_style'],
        'sidebar_behaviour' => $options['sf_sidebar_behaviour'],
        'alt_menu' => $options['sf_alternative_menu'],
        'sidebar_pos' => $options['sf_sidebar_pos'],
        'width_panel_1' => ($options['sf_sidebar_style'] == 'toolbar' ? 100 : $options['sf_width_panel_1']),
        'width_panel_2' => $options['sf_width_panel_2'],
        'width_panel_3' => $options['sf_width_panel_3'],
        'width_panel_4' => $options['sf_width_panel_4'],
        'base_color' => $options['sf_bg_color_panel_1'],
        'opening_type' => $options['sf_opening_type'],
        'sub_type' => $options['sf_sub_type'],
        'video_bg' => $options['sf_video_bg'],
        'video_mob' => $options['sf_video_mob'],
        'video_preload' => $options['sf_video_preload'],
        'sub_mob_type' => $options['sf_sub_mob_type'],
        'sub_opening_type' => ( $options['sf_sub_type'] != 'flyout' ? 'click' : $options['sf_sub_opening_type'] ),
        'label' => $options['sf_label_style'],
        'label_top' => $options['sf_label_top'],
        'label_size' => $options['sf_label_size'],
        'label_vis' => $options['sf_label_vis'],
        'item_padding' => $options['sf_padding'],
        'bg' => $options['sf_image_bg'],
        'path' => plugins_url('/img/', __FILE__),
        'menu' => $options['sf_active_menu'],
        'togglers' => $options['sf_togglers'],
        'subMenuSupport' => (( wp_is_mobile() && !$options['sf_submenu_mob'] || $options['sf_sidebar_style'] === 'skew') ? false : $options['sf_submenu_support']),
        'subMenuSelector' => $options['sf_submenu_classes'],
        'eventsInterval' => $options['sf_interval'],
        'activeClassSelector' => 'current-menu-item',
        'allowedTags' => 'DIV, NAV, UL, OL, LI, A, P, H1, H2, H3, H4, SPAN',
        'menuData' => $sf_menu_data,
        'siteBase' => site_url(),
        'plugin_ver' => SFM_VERSION_NUM
    );

    if ( isset( $job ) ) {

        if ( $job === 'get_css' ) {

            include_once(dirname( __FILE__) . '/superfly-dynamic-styles.php' );
            wp_die();

        }
        else if ( $job === 'get_js_settings' ) {

        }
        else if ( $job === 'get_superfly' ) {

            ob_start();
            include_once( dirname(__FILE__) . '/superfly-dynamic-styles.php' );
            $css = ob_get_clean();

            ob_start();
            include_once( dirname(__FILE__) . '/superfly-menu.php' );
            $html = ob_get_clean();

            $response = array(
                css => $css,
                html => $html,
                js_opts => $js_opts,
                opts => $options,
                panelWidth => $width_panel_1
            );

            header('Content-Type: application/json');
            echo json_encode( $response );
            wp_die();
        }
    }

    wp_die();
}

function sf_customizer_controls () {
    global $sf_la_icon_manager;

    if ( SF_MODE === 'dev' ) {

        wp_enqueue_script('awesome-ajax', plugins_url('/js/vendor/looks_awesome/common/ajax.js', __FILE__));
        wp_enqueue_script('awesome-util', plugins_url('/js/vendor/looks_awesome/common/util.js', __FILE__));
        wp_enqueue_style('sf-admin-font-awesome', plugins_url('/css/fa.min.css', __FILE__));

        wp_enqueue_script('la-icon-manager-md5', plugins_url('/includes/vendor/looks_awesome/icon_manager/js/md5.js', __FILE__));
        wp_enqueue_script('la-icon-manager-util', plugins_url('/includes/vendor/looks_awesome/icon_manager/js/util.js', __FILE__));

        wp_enqueue_script(
            'fontselect',
            plugins_url('/js/vendor/tommoor/fontselect-jquery-plugin/jquery.fontselect.js', __FILE__)
        );

        wp_enqueue_style(
            'fontselect-css',
            plugins_url('/js/vendor/tommoor/fontselect-jquery-plugin/fontselect.css', __FILE__)
        );

        wp_enqueue_script(
            'sf-customizer-js',
            plugins_url('/js/customizer.js', __FILE__),
            array('jquery', 'wp-color-picker'),
            SFM_VERSION_NUM,
            true
        );

        wp_enqueue_style(
            'sf-customizer-css',
            plugins_url('/css/customizer.css', __FILE__),
            array( 'wp-color-picker' ),
            SFM_VERSION_NUM,
            'all'
        );
    } else {
        wp_enqueue_script(
            'sf-customizer-js',
            plugins_url('/js/customizer.min.js', __FILE__),
            array('jquery', 'wp-color-picker'),
            SFM_VERSION_NUM,
            true
        );

        wp_enqueue_style(
            'sf-customizer-css',
            plugins_url('/css/customizer.min.css', __FILE__),
            array( 'wp-color-picker' ),
            SFM_VERSION_NUM,
            'all'
        );
    }

    // Icon Manager
    wp_localize_script(
        'sf-customizer-js',
        'laim_localize',
        array(
            'ajax_nonce' => wp_create_nonce('sf'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'plugin_opts' => json_encode( sf_get_options() )
        )
    );

    $fonts = trailingslashit( plugin_dir_path(__FILE__) . 'fonts' );
    $sf_la_icon_manager = LA_IconManager::getInstance( $fonts );
    $sf_la_icon_manager->enqueueAdminScripts();

    wp_enqueue_editor();
    wp_enqueue_media();
}

function sf_customizer_footer () {
    global $sf_la_icon_manager;
    $sf_la_icon_manager->loadCollection();
}

function sf_customizer_live_preview() {
    wp_enqueue_script(
        'sf-customizer-preview-js',
        plugins_url('/js/customizer-preview.js', __FILE__),
        array( 'jquery' ),
        SFM_VERSION_NUM,
        true
    );

    wp_enqueue_style(
        'sf-customizer-preview-css',
        plugins_url('/css/customizer-preview.css', __FILE__),
        array(),
        SFM_VERSION_NUM,
        'all'
    );

    wp_localize_script(
        'sf-customizer-preview-js',
        'SFM_customizer_obj',
        array(
            'ajax_nonce' => wp_create_nonce('superfly_nonce'),
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );

}

function sf_insert_dynamic_styles()
{
     return '
        body {
          background: red !important;
        }
     ';
}

function writeGoogleFontsManifest() {
    $fontsManifest = plugin_dir_path(__FILE__) . 'includes/vendor/looks_awesome/google_fonts/google-fonts-fallback.json';

    if ( file_exists( $fontsManifest ) ){
        $google_fonts = file_get_contents( $fontsManifest );
        echo "<script>var GOOGLE_FONTS = " .  $google_fonts  . "</script>";
    }
}

function writeMenuExtraData( $data ) {

    echo "<script>
        var SFM_MENU_DATA = " .  json_encode( $data ) . ";
        </script>";
}

