/**
 * Alpha Color Picker JS
 *
 * This file includes several helper functions and the core control JS.
 */

/**
 * Override the stock color.js toString() method to add support for
 * outputting RGBa or Hex.
 */
Color.prototype.toString = function( flag ) {

    // If our no-alpha flag has been passed in, output RGBa value with 100% opacity.
    // This is used to set the background color on the opacity slider during color changes.
    if ( 'no-alpha' == flag ) {
        return this.toCSS( 'rgba', '1' ).replace( /\s+/g, '' );
    }

    // If we have a proper opacity value, output RGBa.
    if ( 1 > this._alpha ) {
        return this.toCSS( 'rgba', this._alpha ).replace( /\s+/g, '' );
    }

    // Proceed with stock color.js hex output.
    var hex = parseInt( this._color, 10 ).toString( 16 );
    if ( this.error ) { return ''; }
    if ( hex.length < 6 ) {
        for ( var i = 6 - hex.length - 1; i >= 0; i-- ) {
            hex = '0' + hex;
        }
    }

    return '#' + hex;
};

/**
 * Given an RGBa, RGB, or hex color value, return the alpha channel value.
 */
function acp_get_alpha_value_from_color( value ) {
    var alphaVal;

    // Remove all spaces from the passed in value to help our RGBa regex.
    value = value.replace( / /g, '' );

    if ( value.match( /rgba\(\d+\,\d+\,\d+\,([^\)]+)\)/ ) ) {
        alphaVal = parseFloat( value.match( /rgba\(\d+\,\d+\,\d+\,([^\)]+)\)/ )[1] ).toFixed(2) * 100;
        alphaVal = parseInt( alphaVal );
    } else {
        alphaVal = 100;
    }

    return alphaVal;
}

/**
 * Force update the alpha value of the color picker object and maybe the alpha slider.
 */
function acp_update_alpha_value_on_color_control( alpha, $control, $alphaSlider, update_slider ) {
    var iris, colorPicker, color;

    iris = $control.data( 'a8cIris' );
    colorPicker = $control.data( 'wpWpColorPicker' );

    // Set the alpha value on the Iris object.
    iris._color._alpha = alpha;

    // Store the new color value.
    color = iris._color.toString();

    // Set the value of the input.
    $control.val( color );

    // Update the background color of the color picker.
    colorPicker.toggler.css({
        'background-color': color
    });

    // Maybe update the alpha slider itself.
    if ( update_slider ) {
        acp_update_alpha_value_on_alpha_slider( alpha, $alphaSlider );
    }

    // Update the color value of the color picker object.
    $control.wpColorPicker( 'color', color );
}

/**
 * Update the slider handle position and label.
 */
function acp_update_alpha_value_on_alpha_slider( alpha, $alphaSlider ) {
    $alphaSlider.slider( 'value', alpha );
    $alphaSlider.find( '.ui-slider-handle' ).text( alpha.toString() );
}

/**
 * Initialization trigger.
 */
jQuery( function( $ ) {
    
    "use strict";

    // Loop over each control and transform it into our color picker.
    $( '.alpha-color-control' ).each( function() {

        // Scope the vars.
        var $control, startingColor, paletteInput, showOpacity, defaultColor, palette,
            colorPickerOptions, $container, $alphaSlider, alphaVal, sliderOptions;

        // Store the control instance.
        $control = $( this );

        // Get a clean starting value for the option.
        startingColor = $control.val().replace( /\s+/g, '' );

        // Get some data off the control.
        paletteInput = $control.attr( 'data-palette' );
        showOpacity  = $control.attr( 'data-show-opacity' );
        defaultColor = $control.attr( 'data-default-color' );

        // Process the palette.
        if ( paletteInput.indexOf( '|' ) !== -1 ) {
            palette = paletteInput.split( '|' );
        } else if ( 'false' == paletteInput ) {
            palette = false;
        } else {
            palette = true;
        }

        // Set up the options that we'll pass to wpColorPicker().
        colorPickerOptions = {
            change: function( event, ui ) {
                var key, value, alpha, $transparency;

                key = $control.attr( 'data-customize-setting-link' );
                value = $control.wpColorPicker( 'color' );

                // Set the opacity value on the slider handle when the default color button is clicked.
                if ( defaultColor == value ) {
                    alpha = acp_get_alpha_value_from_color( value );
                    $alphaSlider.find( '.ui-slider-handle' ).text( alpha );
                }

                // Send ajax request to wp.customize to trigger the Save action.
                wp.customize( key, function( obj ) {
                    obj.set( value );
                });

                $transparency = $container.find( '.transparency' );

                // Always show the background color of the opacity slider at 100% opacity.
                $transparency.css( 'background-color', ui.color.toString( 'no-alpha' ) );
            },
            palettes: palette // Use the passed in palette.
        };

        // Create the colorpicker.
        $control.wpColorPicker( colorPickerOptions );

        $container = $control.parents( '.wp-picker-container:first' );

        // Insert our opacity slider.
        $( '<div class="alpha-color-picker-container">' +
            '<div class="min-click-zone click-zone"></div>' +
            '<div class="max-click-zone click-zone"></div>' +
            '<div class="alpha-slider"></div>' +
            '<div class="transparency"></div>' +
            '</div>' ).appendTo( $container.find( '.wp-picker-holder' ) );

        $alphaSlider = $container.find( '.alpha-slider' );

        // If starting value is in format RGBa, grab the alpha channel.
        alphaVal = acp_get_alpha_value_from_color( startingColor );

        // Set up jQuery UI slider() options.
        sliderOptions = {
            create: function( event, ui ) {
                var value = $( this ).slider( 'value' );

                // Set up initial values.
                $( this ).find( '.ui-slider-handle' ).text( value );
                $( this ).siblings( '.transparency ').css( 'background-color', startingColor );
            },
            value: alphaVal,
            range: 'max',
            step: 1,
            min: 0,
            max: 100,
            animate: 300
        };

        // Initialize jQuery UI slider with our options.
        $alphaSlider.slider( sliderOptions );

        // Maybe show the opacity on the handle.
        if ( 'true' == showOpacity ) {
            $alphaSlider.find( '.ui-slider-handle' ).addClass( 'show-opacity' );
        }

        // Bind event handlers for the click zones.
        $container.find( '.min-click-zone' ).on( 'click', function() {
            acp_update_alpha_value_on_color_control( 0, $control, $alphaSlider, true );
        });
        $container.find( '.max-click-zone' ).on( 'click', function() {
            acp_update_alpha_value_on_color_control( 100, $control, $alphaSlider, true );
        });

        // Bind event handler for clicking on a palette color.
        $container.find( '.iris-palette' ).on( 'click', function() {
            var color, alpha;

            color = $( this ).css( 'background-color' );
            alpha = acp_get_alpha_value_from_color( color );

            acp_update_alpha_value_on_alpha_slider( alpha, $alphaSlider );

            // Sometimes Iris doesn't set a perfect background-color on the palette,
            // for example rgba(20, 80, 100, 0.3) becomes rgba(20, 80, 100, 0.298039).
            // To compensante for this we round the opacity value on RGBa colors here
            // and save it a second time to the color picker object.
            if ( alpha != 100 ) {
                color = color.replace( /[^,]+(?=\))/, ( alpha / 100 ).toFixed( 2 ) );
            }

            $control.wpColorPicker( 'color', color );
        });

        // Bind event handler for clicking on the 'Clear' button.
        $container.find( '.button.wp-picker-clear' ).on( 'click', function() {
            var key = $control.attr( 'data-customize-setting-link' );

            // The #fff color is delibrate here. This sets the color picker to white instead of the
            // defult black, which puts the color picker in a better place to visually represent empty.
            $control.wpColorPicker( 'color', '#ffffff' );

            // Set the actual option value to empty string.
            wp.customize( key, function( obj ) {
                obj.set( '' );
            });

            acp_update_alpha_value_on_alpha_slider( 100, $alphaSlider );
        });

        // Bind event handler for clicking on the 'Default' button.
        $container.find( '.button.wp-picker-default' ).on( 'click', function() {
            var alpha = acp_get_alpha_value_from_color( defaultColor );

            acp_update_alpha_value_on_alpha_slider( alpha, $alphaSlider );
        });

        // Bind event handler for typing or pasting into the input.
        $control.on( 'input', function() {
            var value = $( this ).val();
            var alpha = acp_get_alpha_value_from_color( value );

            acp_update_alpha_value_on_alpha_slider( alpha, $alphaSlider );
        });

        // Update all the things when the slider is interacted with.
        $alphaSlider.slider().on( 'slide', function( event, ui ) {
            var alpha = parseFloat( ui.value ) / 100.0;

            acp_update_alpha_value_on_color_control( alpha, $control, $alphaSlider, false );

            // Change value shown on slider handle.
            $( this ).find( '.ui-slider-handle' ).text( ui.value );
        });

        // customization

        $container.parents( '.customize-control-sf-alpha-color:first' ).prepend( $container.find('.customize-control-description') ).prepend( $container.find('.customize-control-title') );

    });
});

jQuery( function ( $ ) {

    var menuData = window.SFM_MENU_DATA || {};

    var api = window.wp.customize;

    var metaChangeset = {};

    var activePreviewMenu;

    var $iconManagerModal;

    var $doc = $( document );

    $( '.sf-px-value' ).after( ' px' );
    $( '.sf-per-value' ).after( ' %' );

    $( '.sfm-google-font-input' ).fontselect( {
        fonts: window.GOOGLE_FONTS,
        placeholder: 'Site default font',
        empty: 'inherit',
        lookahead: 2
    } ).trigger( 'change' );

    $doc.on( 'google-font-added', function ( ev, font, link ) {

        var $iframeDoc = $( this ).find('iframe[name*="customize-preview"]').contents();

        if ( $iframeDoc.find("link[href*='" + font + "']").length === 0) {
            $iframeDoc.find('link:last').after('<link href="' + link + '" rel="stylesheet" type="text/css">');
        }

    })

    /**
     * TinyMCE Custom Control
     *
     * @author Anthony Hortin <http://maddisondesigns.com>
     * @license http://www.gnu.org/licenses/gpl-2.0.html
     * @link https://github.com/maddisondesigns
     */

    $('.sfm-customize-control-tinymce-editor').each(function(){
        // Get the toolbar strings that were passed from the PHP Class
        var tinyMCEToolbar1String = _wpCustomizeSettings.controls[ $(this).attr('id').replace('_s_', '[').replace('_e_', ']') ].skyrockettinymcetoolbar1;
        var tinyMCEToolbar2String = _wpCustomizeSettings.controls[ $(this).attr('id').replace('_s_', '[').replace('_e_', ']') ].skyrockettinymcetoolbar2;

        wp.editor.initialize( $(this).attr('id'), {
            tinymce: {
                wpautop: true,
                toolbar1: tinyMCEToolbar1String,
                toolbar2: tinyMCEToolbar2String
            },
            quicktags: true
        });
    });

    $doc.on( 'tinymce-editor-init', function( event, editor ) {
        editor.on( 'change', function(e) {
            tinyMCE.triggerSave();
            $( '#'+editor.id ).trigger('change');
        });
    });

    // menus changes handling

    $doc.on( 'customize-preview-menu-refreshed', function( e, params ) {
        console.log( params );
    });

    /* Find all menus and add custom fields */

    // template for custom fields
    var template =
        '<p class="customize-control-title">Superfly Rich Content settings</p>' +
        '<p class="description description-thin">' +
        'Attached image</br>' +
        '<div class="sf-media"><input class="sf-media-input" type="hidden" name="menu-item-sfm-%ID%-img"/><span class="sf-media-cont sf-image-cont"></span><button type="button" style="" class="button remove sfm-img-remove-button" aria-label="Remove image">Remove image</button><button type="button" class="button new sfm-img-button" id="sfm-img-button_%ID%" aria-label="Add image">Add image</button></div><br>' +
        '<p class="description description-thin">Or custom icon</p>'+
        '<div class="sf-media sf-media-icon"><input class="sf-icon-input" type="hidden" name="menu-item-sfm-%ID%-icon"/><span class="sf-media-cont sf-icon-cont"></span><button type="button" style="" class="button remove sfm-icon-remove-button" aria-label="Remove image">Remove icon</button><button type="button" class="button new sfm-icon-button" id="sfm-icon-button_%ID%" aria-label="Add image">Add icon</button></div>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Icon color for panel</label>' +
        '<input class="sf-short" type="text" name="menu-item-sfm-%ID%-icon_color"/>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Menu item second line<br>' +
        '<input type="text" name="menu-item-sfm-%ID%-sline"/></label>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Section heading above menu item<br>' +
        '<input class="" type="text" name="menu-item-sfm-%ID%-chapter"/></label>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Custom HTML / shortcode for panel<br>' +
        '<textarea class="" type="text" name="menu-item-sfm-%ID%-content"></textarea></label>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Custom panel background color</label>' +
        '<input class="sf-short" type="text" name="menu-item-sfm-%ID%-bg"/></label>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Custom panel width<br>' +
        '<input class="sf-num sf-px-value" type="number" name="menu-item-sfm-%ID%-width"/> px</label>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Item tabindex (default -1)</label><br>' +
        '<input class="sf-num" type="number" name="menu-item-sfm-%ID%-tabindex"/></label>' +
        '</p>' +
        '<p class="description description-thin">' +
        '<label>Visibility<br><input id="menu-item-sfm-%ID%-hidemob" name="menu-item-sfm-%ID%-hidemob" class="switcher" type="checkbox" value="yes" /> Hide item on mobiles</label>' +
        '</p>';

    $( '[id*="sub-accordion-section-nav_menu"]' ).each( function () {

        var menuItems;
        var menuId;
        var matches = this.id.match(/\[(\d+)\]$/);

        var $t = $( this );

        if (!matches) return;

        menuId = matches[ 1 ]; // regexp parenthesis

        menuItems = menuData[ menuId ]; // object of menu items { 36: 'data', 145: 'data'}

        $t.attr( 'data-sfm-menu-id', menuId ).data( 'sfm-extra', menuItems );
    })

    // wp.customize.Menus.data


    // adding extra fields
    // disabling default event handlers for title inputs

    api.control.on( 'change', function ( control ) {

        var data, menuId, itemId, extra, params, name;
        var $container, $parent, $inp;

        if ( control.params && control.params.menu_item_id && control.container && !control.container.data('sfm-extra-added') ) {

            $container = control.container;
            $parent = $container.closest( '[data-sfm-menu-id]' );
            data = $parent.data();
            extra = data.sfmExtra; // converted to camelcase by jQuery
            itemId = control.params.menu_item_id;
            menuId = data.sfmMenuId;

            if ( menuId == activePreviewMenu ) {
                $container.find( '.edit-menu-item-title' ).off( 'input change' );
                // todo off drag n drop changes
            }

            // console.log( 'adding extra fields', extra, itemId )

            // add and fill custom inputs

            $container.find( '.menu-item-actions' ).before ( $( template.replace(/%ID%/g, itemId ) ) );

            if ( extra[ itemId ] ) {

                params = deparam( extra[ itemId ] );

                for ( name in params ) {

                    $inp = $container.find('[name=menu-item-sfm-' + itemId + '-' + name +']');

                    // if ( name == 'content' && itemId == 126 ) debugger

                    if ( $inp.is( ':checkbox' ) ) {
                        if ( params[ name ] === 'yes' ) $inp.attr('checked', true);
                    } else {
                        if ( name === 'icon' ){
                            var icon = params[ name ];
                            icon = icon.indexOf('fa-') + 1 ? 'Font-Awesome_####_' + icon.substr(3) : icon;
                            $inp.val( icon );
                        } else {
                            $inp.val( params[ name ] );
                        }
                    }
                    if ( name === 'img' && params[ name ] !== '') {
                        $container.find('.sf-image-cont').html('<img src="' + params[ name ] + '"/>')
                    }
                    if ( name === 'icon' && params[ name ] !== '') {

                        var icon = params[ name ];
                        var set = LAIconManagerUtil.getSet( icon ) ? LAIconManagerUtil.getSet( icon ) : 'Font Awesome';

                        if ( set === '####' ) {
                            icon = LAIconManagerUtil.getIcon( icon );
                            $container.find('.sf-icon-cont').append('<i style="background-image:url(' + icon + ')" class="la_icon la_icon_manager_custom">');
                        } else {
                            icon = icon.indexOf('fa-') !== -1 ? 'la' + md5('Font Awesome') + '-' + icon.substr(3) : LAIconManagerUtil.getIconClass( icon );
                            $container.find('.sf-icon-cont').append('<i class="la_icon ' + icon + '">');
                        }
                    }

                }
            }

            $container.find( '[name="menu-item-sfm-' + itemId + '-bg"]' ).wpColorPicker({
                'change' :
                function( event, ui ) {
                    var $tar = $( event.target );
                    setTimeout( function () {
                        $tar.trigger( 'sfm-change' );
                    }, 17 );
                }
            });

            $container.find( '[name="menu-item-sfm-' + itemId + '-icon_color"]' ).wpColorPicker({
                'change' :
                    function( event, ui ) {
                        var $tar = $( event.target );
                        setTimeout( function () {
                            $tar.trigger( 'sfm-change' );
                        }, 17 );
                    }
            });

            var $imgBtn = $container.find( '.sfm-img-button' ),
                $iconBtn = $container.find( '.sfm-icon-button' ),
                $imgInput = $container.find( '.sf-media-input' ),
                $iconInput = $container.find( '.sf-icon-input' ),
                attachment,
                file_frame;

            if ( $imgInput.val() ) {
                $container.addClass( 'sfm-img-added' );
                $imgBtn.html( 'Change image' );
            } else {
                $container.removeClass( 'sfm-img-added' );
            }

            if ( $iconInput.val() ) {
                $container.addClass( 'sfm-icon-added' );
                $iconBtn.html( 'Change icon' );
            } else {
                $container.removeClass( 'sfm-icon-added' );
            }

            // attach image handle
            $imgBtn.on( 'click', function () {

                // If the media frame already exists, reopen it.
                if ( file_frame ) {
                    file_frame.open();
                    return;
                }

                // Create the media frame.
                file_frame = wp.media.frames.file_frame = wp.media({
                    multiple: false  // Set to true to allow multiple files to be selected
                });

                // When an image is selected, run a callback.
                file_frame.on( 'select', function() {
                    // We set multiple to false so only get one image from the uploader

                    attachment = file_frame.state().get('selection').first().toJSON();
                    $container.find( '.sf-image-cont' ).html( '<img src="' + attachment.url + '"/>' );
                    // $imgInput.val( attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.sizes.full.url );
                    $imgInput.val( attachment.sizes.full.url );
                    $container.addClass( 'sfm-img-added' );
                    $imgBtn.html( 'Change image' );

                    $imgInput.trigger( 'sfm-change' );
                });

                // Finally, open the modal
                file_frame.open();
            })

            $container.find( '.sfm-img-remove-button' ).on( 'click', function( event ){
                $imgInput.val('');
                $container.removeClass( 'sfm-img-added' ).find( '.sf-image-cont' ).html( '' );
                $imgBtn.html( 'Add image' );

                $imgInput.trigger( 'sfm-change' );
            })

            // attach icon handle
            $iconBtn.on( 'click', function () {

                // open icon manager and pass item ID
                $iconManagerModal.addClass( 'sfm-modal-opened' ).data( 'active-menu-item', itemId );

            })

            $container.find( '.sfm-icon-remove-button' ).on( 'click', function( event ){
                $container.removeClass( 'sfm-icon-added' ).find( '.sf-icon-cont' ).html( '' );
                $iconBtn.html( 'Add icon' );
                $iconInput.val('');
                $iconInput.trigger( 'sfm-change' );
            })

            // listen to icon apply event
            $container.on( 'sfm-icon-applied', function ( event, menuId, value, previewHTML ) {
                $iconInput.val( value );
                $container.addClass( 'sfm-icon-added' );
                $iconBtn.html( 'Change icon' );
                $container.find( '.sf-icon-cont' ).html( previewHTML );
                $iconInput.trigger( 'sfm-change' );
            })

            // assign sfm attr
            $container.attr( 'sfm-item-id', itemId )

            control.container.data('sfm-extra-added', true);
        }
    })

    // listening to extra fields change events and updating changeset and preview
    $( '#customize-theme-controls' ).on( 'input sfm-change', '.menu-item-settings [name*="menu-item-sfm"], .menu-item-settings .edit-menu-item-title', function () {
        console.log( this.value );

        // get item ID
        var $t = $( this );
        var id;
        var $container = $t.closest( '[sfm-item-id]');
        var re;

        id = $container.attr( 'sfm-item-id' );
        re = new RegExp('menu-item-sfm-' + id + '-',"g");

        // overwrite anyway
        metaChangeset[ id ] = $container.find( ('[name*="menu-item-sfm"]') ).serialize().replace( re, '' );

        // trigger UI change
        api.state( 'saved' ).set( false );
        api.previewer.send( 'sfm-menu-extra-changed', {
            serialized: metaChangeset[ id ],
            params: deparam( metaChangeset[ id ] ),
            menuItemId: id,
            name: $t.attr( 'name' ).replace( re, '' ),
            val: $t.val()
        } );

    })

    // on settings save
    api.on( 'save-request-params', function( query ) {
        console.log( query );
        
        // send meta changeset
        
        var data = {
            changeset: metaChangeset,
            action: 'sf_save_item'
        };

        var request = $.post( wp.ajax.settings.url, data );

        request.done( function ( response ) {
            console.log('Got this from the server: ' , response );

            if ( response == -1 ){

            }
            else {

                // reset
                metaChangeset = {};
            }
        }, 'json' ).fail( function( d ) {
            console.log( d.responseText );
            console.log( d );
        } );
    });

    // listen to preview ready and getting active menu ID
    api.previewer.on( 'sfm-active-menu', function ( id ) {
        activePreviewMenu = id;
    } );

    // icon manager

    $doc.on( "iconManagerCollectionLoaded", function() {

        console.log( 'iconManagerCollectionLoaded' )

        // сreate icon container
        $( 'body' ).append( '<div id="sfm-icon-container-wrapper"><div id="sfm-icon-container"><button type="button" class="media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text">Close</span></span></button><div class="sfm-modal-desc">You can add more icons on <a href="/wp-admin/admin.php?page=superfly-menu-options" target="_blank">Superfly settings page</a></div><div id="sfm-icon-select"></div><input type="hidden" id="sfm-icons-input"/></div><div id="sfm-icons-modal-backdrop"></div></div>' )

        // sf_menu_icon_90
        window[ "la_icon_manager_select" ] = new LAIconManager(
            'sfm-icons-container',
            "#sfm-icon-select",
            window["la_icon_manager_collection"],
            "#sfm-icons-input"
        );
        window["la_icon_manager_select"].showIconSelect();

        $iconManagerModal = $( '#sfm-icon-container-wrapper' );

        // broadcast icon apply event
        $iconManagerModal.on( 'click', '.icons-list li', function () {
            var id = $iconManagerModal.data( 'active-menu-item' );

            setTimeout( function () {
                $( '[sfm-item-id="' + id + '"]' ).trigger( 'sfm-icon-applied', [ id, $iconManagerModal.find('#sfm-icons-input').val(), $iconManagerModal.find( '.preview' ).html() ] );
                $iconManagerModal.removeClass( 'sfm-modal-opened' ).data( 'active-menu-item', '' );
            }, 100)

        })

        $iconManagerModal.find( '.media-modal-close' ).on( 'click', function () {
            $iconManagerModal.removeClass( 'sfm-modal-opened' ).data( 'active-menu-item', '' );
        })

    });

    // handling superfly yes/no switchers

    var pluginOpts = JSON.parse( laim_localize.plugin_opts );

    $('.sfm_toggle_switch_control').each( function () {
        var $t = $( this );
        var $inp = $t.find('input');
        var id = $inp.attr('id').replace('sf_options[', '').replace(']', '');

        if ( pluginOpts[ id ] ) {
            $inp.prop('checked', pluginOpts[ id ] === 'yes' ? true : false );
        }
    })

    // setting group toggle

    var $settingsGroup = {
        fullscreen: $('#customize-control-sf_options-sf_fs_layout, #customize-control-sf_options-sf_fade_full, #customize-control-sf_options-sf_full_sec, #customize-control-sf_options-sf_full_head, #customize-control-sf_options-sf_full_x'),
        skewed: $('#customize-control-sf_options-sf_sidebar_behaviour, #customize-control-sf_options-sf_skew_type, #customize-control-sf_options-sf_fade_content, #customize-control-sf_options-sf_blur_content'),
        side: $('#customize-control-sf_options-sf_sidebar_behaviour, #customize-control-sf_options-sf_fade_content, #customize-control-sf_options-sf_blur_content, #customize-control-sf_options-sf_sub_type, #customize-control-sf_options-sf_sub_opening_type'),
        toolbar: $('#customize-control-sf_options-sf_sidebar_behaviour, #customize-control-sf_options-sf_fade_content, #customize-control-sf_options-sf_blur_content, #customize-control-sf_options-sf_sub_type, #customize-control-sf_options-sf_sub_opening_type'),
        separators: $('#customize-control-sf_options-sf_separators_color, #customize-control-sf_options-sf_separators_width'),
        indicators: $('#customize-control-sf_options-sf_ind_s, #customize-control-sf_options-sf_ind_st'),
    }

    var $panelSettingsCont = $( '#sub-accordion-section-sf_settings_panel, #sub-accordion-section-sf_settings_items' );

    for ( var group in $settingsGroup ) {
        $settingsGroup[ group ].each( function () {
            var $t = $( this );
            var oldVal = $t.attr( 'data-settings-group' );
            var arr;

            if ( oldVal ) {
                arr = oldVal.split( ',' );
                arr.push( group );
                $t.attr( 'data-settings-group' , arr.join( ',' ) );
            } else {
                $t.attr( 'data-settings-group' , group )
            }
        })
    }

    $('#customize-control-sf_options-sf_sidebar_style [name="sf_options[sf_sidebar_style]"]').change( onChangeStyle )
    $('#customize-control-sf_options-sf_ind [name="sf_options[sf_ind]"]').change( onChangeIndicators );
    $('#customize-control-sf_options-sf_separators [name="sf_options[sf_separators]"]').change( onChangeSeparators );

    $('#customize-control-sf_options-sf_sidebar_style [name="sf_options[sf_sidebar_style]"]:checked').each( onChangeStyle );
    $('#customize-control-sf_options-sf_ind [name="sf_options[sf_ind]"]:checked').each( onChangeIndicators );
    $('#customize-control-sf_options-sf_separators [name="sf_options[sf_separators]"]:checked').each( onChangeSeparators );

    function onChangeStyle(){

        $panelSettingsCont.find( '[data-settings-group]' ).hide();

        $panelSettingsCont.find( '[data-settings-group*=' + this.value + ']' ).show();

        if ( this.value == 'side' ) {

        }

    }

    function onChangeSeparators(){
        if ( this.checked ) {
            $settingsGroup.separators.show();
        }
        else {
            $settingsGroup.separators.hide();
        }
    }

    function onChangeIndicators(){
        if ( this.checked ) {
            $settingsGroup.indicators.show();
        }
        else {
            $settingsGroup.indicators.hide();
        }
    }

    // monitor_events('wp.customize');
    // monitor_events('wp.customize.previewer');
    // monitor_events('wp.customize.control');
    // monitor_events('wp.customize.section');
    // monitor_events('wp.customize.panel');
    // monitor_events('wp.customize.state');

    // utility functions

    function monitor_events( object_path ) {
        var p = eval(object_path);
        var k = _.keys(p.topics);
        console.log( object_path + " has events ", k);
        _.each(k, function(a) {
            p.on(a, function() {
                console.log( object_path + ' event ' + a, arguments );
            });
        });
    }

    function deparam( query ) {

        var pairs, i, keyValuePair, key, value, map = {};

        // safe
        if ( !query || typeof query !== 'string') return;

        // remove leading question mark if its there
        if ( query.slice(0, 1 ) === '?' ) {
             query = query.slice(1);
        }
        if ( query !== '' ) {
             pairs = query.split('&');
             for ( i = 0; i < pairs.length; i += 1 ) {
                keyValuePair = pairs[i].split('=');
                key = decodeURIComponent( keyValuePair[0] );
                value = (keyValuePair.length > 1) ? decodeURIComponent( keyValuePair[1] ) : undefined;
                map[key] = value;
                if(key !== 'icon'){ // void icon manager
                    map[key] = value.replace(/\+/g, ' ');
                }
            }
        }
        return map;
    }
})