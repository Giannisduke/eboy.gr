;( function( $, api ) {
	"use strict";
    
    console.log('superfly customizer init')

    $( function() {

        if ( !window.SF_Opts ) return; // no superfly menu on page

        var current, setting;

        var controls = {
            'sf_sidebar_pos' : {
                'fullRefreshNeeded': true
            },

            'sf_sidebar_style' : {
                'fullRefreshNeeded': true
            },

            'sf_skew_type' : {
                'fullRefreshNeeded': true
            },

            'sf_fs_layout' : {
                'fullRefreshNeeded': true
            },

            'sf_sidebar_behaviour' : {
                'fullRefreshNeeded': true
            },

            'sf_opening_type' : {
                'fullRefreshNeeded': true
            },

            'sf_sub_opening_type' : {
                'fullRefreshNeeded': true
            },

            'sf_sub_type' : {
                'fullRefreshNeeded': true
            },

            'sf_fade_content' : {
                'cssRefreshNeeded': true
            },

            'sf_fade_full' : {
                'cssRefreshNeeded': true
            },

            'sf_full_head' : {
                'cssRefreshNeeded': true
            },

            'sf_full_sec' : {
                'cssRefreshNeeded': true
            },

            'sf_full_x' : {
                'cssRefreshNeeded': true
            },

            'sf_blur_content' : {
                'fullRefreshNeeded': true
            },

            'sf_transition' : {
                'fullRefreshNeeded': true
            },

            'sf_label_shift' : {
                'cssRefreshNeeded' : true
            },

            'sf_width_panel_1' : {
                'cssRefreshNeeded' : true
            },

            'sf_bg_color_panel_1' : {
                'cssRefreshNeeded' : true
            },

            'sf_color_panel_1' : {
                'cssRefreshNeeded' : true
            },

            'sf_scolor_panel_1' : {
                'cssRefreshNeeded' : true
            },

            'sf_chapter_1' : {
                'cssRefreshNeeded' : true
            },

            'sf_hover_panel_1' : {
                'cssRefreshNeeded' : true
            },

            'sf_image_bg' : {
                'cssRefreshNeeded' : true
            },

            'sf_video_bg' : {
                'fullRefreshNeeded' : true,
                'callbackAfter':  function ( controlValue ) {

                    if ( window.LM && window.LM.sfmPlayer && window.LM.getState() === 'open' ) {
                        window.LM.sfmPlayer.play();
                    }
                }
            },

            'sf_video_preload' : {
            },

            'sf_video_mob' : {
            },

            'sf_width_panel_2' : {
                'cssRefreshNeeded' : true
            },

            'sf_bg_color_panel_2' : {
                'cssRefreshNeeded' : true
            },

            'sf_color_panel_2' : {
                'cssRefreshNeeded' : true
            },

            'sf_scolor_panel_2' : {
                'cssRefreshNeeded' : true
            },

            'sf_chapter_2' : {
                'cssRefreshNeeded' : true
            },

            'sf_hover_panel_2' : {
                'cssRefreshNeeded' : true
            },

            'sf_width_panel_3' : {
                'cssRefreshNeeded' : true
            },

            'sf_bg_color_panel_3' : {
                'cssRefreshNeeded' : true
            },

            'sf_color_panel_3' : {
                'cssRefreshNeeded' : true
            },

            'sf_scolor_panel_3' : {
                'cssRefreshNeeded' : true
            },

            'sf_chapter_3' : {
                'cssRefreshNeeded' : true
            },

            'sf_hover_panel_3' : {
                'cssRefreshNeeded' : true
            },

            'sf_width_panel_4' : {
                'cssRefreshNeeded' : true
            },

            'sf_bg_color_panel_4' : {
                'cssRefreshNeeded' : true
            },

            'sf_color_panel_4' : {
                'cssRefreshNeeded' : true
            },

            'sf_scolor_panel_4' : {
                'cssRefreshNeeded' : true
            },

            'sf_chapter_4' : {
                'cssRefreshNeeded' : true
            },

            'sf_hover_panel_4' : {
                'cssRefreshNeeded' : true
            },

            'sf_tab_logo' : {
                'fullRefreshNeeded' : true
            },

            'sf_logo_size' : {
                'fullRefreshNeeded' : true
            },

            'sf_first_line' : {
                'fullRefreshNeeded' : true
            },

            'sf_sec_line' : {
                'fullRefreshNeeded' : true
            },

            'sf_search' : {
                'fullRefreshNeeded' : true
            },

            'sf_search_bg' : {
                'cssRefreshNeeded' : true
            },

            'sf_above_logo' : {
                'fullRefreshNeeded' : true
            },

            'sf_under_logo' : {
                'fullRefreshNeeded' : true
            },

            'sf_copy' : {
                'fullRefreshNeeded' : true
            },

            'sf_social_style' : {
                'fullRefreshNeeded' : true
            },

            'sf_social_color' : {
                'cssRefreshNeeded' : true
            },

            'sf_social_align' : {
                'cssRefreshNeeded' : true
            },

            'sf_facebook' : {
                'fullRefreshNeeded' : true
            },

            'sf_twitter' : {
                'fullRefreshNeeded' : true
            },

            'sf_youtube' : {
                'fullRefreshNeeded' : true
            },

            'sf_instagram' : {
                'fullRefreshNeeded' : true
            },

            'sf_linkedin' : {
                'fullRefreshNeeded' : true
            },

            'sf_dribbble' : {
                'fullRefreshNeeded' : true
            },

            'sf_vimeo' : {
                'fullRefreshNeeded' : true
            },

            'sf_pinterest' : {
                'fullRefreshNeeded' : true
            },

            'sf_flickr' : {
                'fullRefreshNeeded' : true
            },

            'sf_rss' : {
                'fullRefreshNeeded' : true
            },

            'sf_skype' : {
                'fullRefreshNeeded' : true
            },

            'sf_email' : {
                'fullRefreshNeeded' : true
            },

            'sf_font' : {
                'cssRefreshNeeded' : true
            },

            'sf_font_size' : {
                'cssRefreshNeeded' : true
            },

            'sf_alignment' : {
                'cssRefreshNeeded' : true
            },

            'sf_font_weight' : {
                'cssRefreshNeeded' : true
            },

            'sf_uppercase' : {
                'cssRefreshNeeded' : true
            },

            'sf_spacing' : {
                'cssRefreshNeeded' : true
            },

            'sf_c_font' : {
                'cssRefreshNeeded' : true
            },

            'sf_c_fs' : {
                'cssRefreshNeeded' : true
            },

            'sf_c_weight' : {
                'cssRefreshNeeded' : true
            },

            'sf_c_trans' : {
                'cssRefreshNeeded' : true
            },

            'sf_padding' : {
                'cssRefreshNeeded' : true
            },

            'sf_padding_h' : {
                'cssRefreshNeeded' : true
            },

            'sf_icon_size' : {
                'cssRefreshNeeded' : true
            },

            'sf_icon_color' : {
                'cssRefreshNeeded' : true
            },

            'sf_ordered' : {
                'cssRefreshNeeded' : true
            },

            'sf_ind' : {
                'fullRefreshNeeded' : true
            },

            'sf_ind_s' : {
                'cssRefreshNeeded' : true
            },

            'sf_ind_st' : {
                'cssRefreshNeeded' : true
            },

            'sf_separators' : {
                'cssRefreshNeeded' : true
            },

            'sf_separators_color' : {
                'cssRefreshNeeded' : true
            },

            'sf_separators_width' : {
                'cssRefreshNeeded' : true
            },

            'sf_highlight' : {
                'fullRefreshNeeded' : true
            },

            'sf_hover_style' : {
                'fullRefreshNeeded' : true
            },

            'sf_highlight_active' : {
                'cssRefreshNeeded' : true
            },

            'sf_fixed' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_vis' : {
                'fullRefreshNeeded' : true
            },

            'sf_label_top' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_size' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_top_mobile' : {
                'cssRefreshNeeded' : true
            },

            'sf_mob_nav' : {
                'fullRefreshNeeded' : true
            },

            'sf_threshold_point' : {
                'fullRefreshNeeded' : true
            },

            'sf_label_style' : {
                'fullRefreshNeeded' : true
            },

            'sf_label_width' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_gaps' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_color' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_icon_color' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_text_color' : {
                'cssRefreshNeeded' : true
            },

            'sf_label_text' : {
                'fullRefreshNeeded' : true
            }
        }

        for ( setting in controls) {

            current = controls[ setting ];

            api('sf_options[' + setting + ']', function( control ) {

                actionHandler ( control, current, 'sf_options[' + setting + ']' );

            });
        }

        api.preview.on( 'sfm-menu-extra-changed', function ( event ) {
            console.log( 'sfm change', event )

            if ( event.serialized ) {
                // updating object with data for extra in case menu will be reloaded
                window.SF_Opts.menuData[ event.menuItemId ] = event.serialized;
            }

            var $item  = $( '.sfm-menu-item-' + event.menuItemId );
            var $secondLine, $prev, $custom, firstLine, secondLine, $icon;

            if ( !$item.length ) return;

            switch ( event.name ) {

                case 'menu-item-title': {

                    if ( event.val === '' ) break;

                    secondLine = $item.find( '> a span .sfm-sl' ).text();
                    if ( secondLine.length ) {
                        $item.find( '> a span' ).html( event.val + '<br><i class="sfm-sl">' + secondLine + '</i>' )
                    } else {
                        $item.find( '> a span' ).html( event.val );
                    }
                    break;
                }

                case 'chapter': {
                    $prev = $item.prev();

                    if ( $prev.is( '.sfm-chapter' ) ) {
                        if ( event.val === '' ) {
                            $prev.remove();
                        } else {
                            $prev.find( 'div' ).html( event.val );
                        }
                    }
                    else {
                        // ignore then
                        if ( event.val === '' ) break;

                        // adding anyway
                        $item.before('<li class="sfm-chapter"><div>' + event.val + '</div></li>');
                        // update size
                        LM.setSize( null, true );
                    }
                    break;
                }

                case 'sline': {
                    $secondLine = $item.find( '> a span .sfm-sl' );
                    if ( $secondLine.length ) {
                        if ( event.val === '' ) {
                            $item.find( '> a span' ).find( 'br, .sfm-sl' ).remove();
                        } else {
                            $item.find( '> a span .sfm-sl' ).html( event.val );
                        }
                    } else {
                        // ignore then
                        if ( event.val === '' ) break;

                        firstLine = $item.find( '> a span' ).text();
                        $item.find( '> a span' ).html( firstLine + '<br><i class="sfm-sl">' + event.val + '</i>' )
                    }
                    break;
                }

                case 'img': {
                    var https = location.protocol === 'https:'

                    var $cont = $item.find('> a');

                    if ( event.val === '' ) {
                        $cont.find( 'img' ).remove();
                        break;
                    }

                    if ( window.SF_Opts.sidebar_pos == 'right' && window.SF_Opts.sidebar_style == 'skew' ) {

                        if ( $cont.find( 'img' ).length ) {
                            $cont.find( 'img' ).attr('src', ( https ? event.val.replace('http:', 'https:') : event.val ) )
                        }
                        else {
                            $cont.append('<img src="' + ( https ? event.val.replace('http:', 'https:') : event.val ) + '"/>')
                        }
                    } else {
                        if ( $cont.find( 'img' ).length ) {
                            $cont.find( 'img' ).attr('src', ( https ? event.val.replace('http:', 'https:') : event.val ) )
                        }
                        else {
                            $cont.prepend('<img src="' + ( https ? event.val.replace('http:', 'https:') : event.val ) + '"/>')
                        }
                    }
                    break;
                }

                case 'icon': {
                    $icon = $item.find( '> a > i' );
                    var icon = event.val;
                    var set = LAIconManagerUtil.getSet( icon ) ? LAIconManagerUtil.getSet( icon ) : 'Font Awesome';
                    var direction = window.SF_Opts.sidebar_pos;

                    if ( $icon.length ) {
                        if ( !icon ) {
                            $icon.remove();
                            break;
                        }

                        if ( set === '####' ) {
                            icon = LAIconManagerUtil.getIcon( icon )
                            $icon.css('background-image',  "url(" + icon + ")")
                            icon = "la_icon_manager_custom";
                        } else {
                            $icon.css('background-image',  "");
                            icon = icon.indexOf('fa-') !== -1 ? 'la' + md5('Font Awesome') + '-' + icon.substr(3) : LAIconManagerUtil.getIconClass( icon );
                        }
                        $icon.removeClass().addClass( 'la_icon ' + icon );
                    } else {
                        if ( !icon ) {
                            break;
                        }
                        var style = event.params.icon_color ? 'color: ' + event.params.icon_color + ';' : '';
                        if ( set === '####' ) {
                            icon = LAIconManagerUtil.getIcon( icon )
                            if ( direction == 'right' && window.SF_Opts.sidebar_style == 'skew' ) {
                                $item.find('> a').append('<i style="background-image:url(' + icon + ')" class="la_icon la_icon_manager_custom">');
                            } else {
                                $item.find('> a').prepend('<i style="background-image:url(' + icon + ')" class="la_icon la_icon_manager_custom">');
                            }
                        } else {
                            icon = icon.indexOf('fa-') !== -1 ? 'la' + md5('Font Awesome') + '-' + icon.substr(3) : LAIconManagerUtil.getIconClass(icon);
                            if ( direction == 'right' && window.SF_Opts.sidebar_style == 'skew' ) {
                                $item.find('> a').append('<i style="' + style + '" class="la_icon ' + icon + '">');
                            } else {
                                $item.find('> a').prepend('<i style="' + style + '" class="la_icon ' + icon + '">');
                            }
                        }
                    }
                    break;
                }

                case 'icon_color': {
                    $item.find( '> a i' ).css( 'color', event.val );
                    break;
                }

                case 'content': {
                    $custom = $( '#sfm-cc-' + event.menuItemId );

                    var data = {
                        settings: event.serialized,
                        action: 'sf_get_custom_content'
                    };

                    var request = $.post( wp.ajax.settings.url, data );

                    $( 'html' ).addClass( 'sfmp-waiting-preview-update' );

                    request.done( function( response ) {
                        console.log('Got this from the server: ' , response );
                        if ( response == -1 ){

                        }
                        else{
                            $custom.find( '.sfm-content-wrapper' ).html( response.content );
                        }
                        $( 'html' ).removeClass( 'sfmp-waiting-preview-update' );
                    }, 'json' ).fail( function( d ){
                        console.log( d.responseText );
                        console.log( d );
                        $( 'html' ).removeClass( 'sfmp-waiting-preview-update' );
                    });

                    break;
                }

                case 'bg': {
                    $( '.sfm-view-level-custom' ).css( 'backgroundColor', event.val );
                    break;
                }

                case 'width': {
                    $custom = $( '#sfm-cc-' + event.menuItemId );
                    if ( parseInt( event.val ) > 0) {
                        $( '.sfm-view-level-custom' ).add( $custom ).width( event.val );
                    } else {
                        $( '.sfm-view-level-custom' ).add( $custom ).width( '' );
                    }
                    break;
                }

                case 'hidemob': {
                    if ( event.val === 'yes' ) {

                    }
                }
            }


        });

        // todo full reset setting apply
        var $originalMenu, $clonedMenu;

        api.selectiveRefresh.on( 'render-partials-response', function ( data ) {
            console.log( 'selectiveRefresh render', data )
            // clone and detach original and let WP to replace clone with own

            $originalMenu = $( '.sfm-menu-level-0' ); // will be replaced by
            $clonedMenu = $originalMenu.clone();

            $originalMenu.hide().after( $clonedMenu );

            // $originalMenu.detach();


        } );

        api.selectiveRefresh.on( 'partial-content-rendered', function ( data ) {
            console.log( 'selectiveRefresh partial rendered', data )
            

            if ( data.context && data.context.menu_id === "sfm-nav" && data.removedNodes ) {
                console.log( '%cselectiveRefresh partial superfly', "color: #333; font-size: large" );

                // re-build menu

                // attach original and delete WP generated
                $( '#' + data.context.menu_id ).hide()//.after( data.removedNodes ).remove();
                
                // request re-rendering of menu

                var action = resetSupeflyInDocument( null, null, null, $( '#' + data.context.menu_id ));
                $( '.sfm-menu-level-0' ).show();
                
                action.done( function () {
                    console.log( 'superfly reset after partial refresh' )
                })

                /*setTimeout( function () {
                    data.removedNodes.removeClass( 'customize-partial-refreshing' )
                }, 0)*/
            }

        } );

        // mount loading overlay

        $( 'body' ).append( $('<div id="sfm-loader-overlay" class="sfm-loader"></div>') );


        setTimeout( function () {
            api.preview.send( 'sfm-active-menu', SF_Opts.wp_menu_id );
        }, 0)


    });

    function actionHandler( control, opts, controlId ) {

        control.bind( function( controlValue ) {

            console.log( 'exec control change handler...', control, controlValue);

            if ( opts.callbackBefore ) opts.callbackBefore( controlValue );

            if ( opts.fullRefreshNeeded ) {
                resetSupeflyInDocument( controlValue, controlId, opts.callbackAfter )
            }
            else if ( opts.cssRefreshNeeded ) {
                refreshDynamicStyles( controlValue, controlId, opts.callbackAfter );
            }
        });
    }

    function refreshDynamicStyles ( controlValue, controlId, callback ) {

        console.log( 'refreshing styles...');

        var data = {
            action: 'sf_preview_action',
            job: 'get_css',
            menu: window.SFM_current_page_menu,
            security: window.SFM_customizer_obj.ajax_nonce
        };

        var $html = $( 'html' );
        var $parentHtml = $( 'html',  window.parent.document );
        var $ctrl = $( '[id$="' + controlId + '"]',  window.parent.document );

        $html.addClass( 'sfmp-disable-transitions sfmp-waiting-preview-update' );
        // $parentHtml.addClass( 'sfmp-waiting-preview-update' );

        $.post( SFM_customizer_obj.ajax_url, data, function( response ) {
            // console.log('Got this from the server: ' , response );
            var cssStr;
            if( response == -1 ){

            }
            else{
                cssStr = response;
                $('#superfly-dynamic').html( cssStr );

                $html.removeClass( 'sfmp-waiting-preview-update' );

                if ( callback ) callback( controlValue );

                setTimeout( function () {
                    $html.removeClass( 'sfmp-disable-transitions' );
                }, 2000)
            }
        } ).fail( function( d ){
            console.log( d.responseText );
            console.log( d );
        });
    }

    function resetSupeflyInDocument ( controlValue, controlId, callback, $defmenu ) {

        // cache
        var $body = $( 'body' );
        var $html = $( 'html' );

        var data = {
            action: 'sf_preview_action',
            job: 'get_superfly',
            menu: window.SFM_current_page_menu,
            dataType: 'json',
            security: window.SFM_customizer_obj.ajax_nonce
        }

        $html.addClass( 'sfmp-disable-transitions sfmp-waiting-preview-update' );

        var request = $.post( SFM_customizer_obj.ajax_url, data, function( response ) {
            // console.log('Got this from the server: ' , response );
            var state, scrollTop;
            if( response == -1 ){

            }
            else{
                // remove prefix classes
                $body.removeClassPrefix( 'sfm-' );
                $html.removeClassPrefix( 'sfm-' );

                $html.removeClass( 'sfmp-waiting-preview-update' );

                // save current state
                state = window.LM.getState();
                scrollTop = $('.sfm-scroll-main .sfm-scroll').scrollTop();

                if ( window.sfmPlayer && window.sfmPlayer.YTPGetPlayer() ) {
                    window.sfmPlayer.YTPPlayerDestroy();
                }

                $body.find( '#sfm-sidebar, .sfm-rollback, #sfm-overlay-wrapper' ).remove();

                // remove all global events

                // apply new dynamic CSS

                $( '#superfly-dynamic' ).html( response.css );

                // adding new HTML classes

                var mob_bar = response.opts.sf_mob_nav === 'yes';
                var pos = response.opts.sf_sidebar_pos;
                var iconbar = response.opts.sf_sidebar_style === 'toolbar';

                var SFM_skew_disabled = ( function( ) {
                    var window_width = window.innerWidth;
                    var sfm_width = response.panelWidth;
                    if ( sfm_width * 2 >= window_width ) {
                        return true;
                    }
                    return false;
                } )( );

                var classes = SFM_is_mobile ? 'sfm-mobile' : 'sfm-desktop';
                var html = document.getElementsByTagName('html')[0]; // pointer
                classes += mob_bar ? ' sfm-mob-nav' : '';
                classes += ' sfm-pos-' + pos;
                classes += iconbar ? ' sfm-bar' : '';
                classes += SFM_skew_disabled ? ' sfm-skew-disabled' : '';

                html.className = html.className == '' ?  classes : html.className + ' ' + classes;

                // regenerate menu with JS, broadcast event to main public script to trigger reflow

                window.SFM_template = response.html;
                window.SF_Opts = response.js_opts;

                $( document ).trigger( 'sfm_preview_full_refresh', [ state, scrollTop, $defmenu ] );

                if ( callback ) callback( controlValue );

                setTimeout( function () {
                    $html.removeClass( 'sfmp-disable-transitions' );
                }, 1000)
            }
        } ).fail( function( d ){
            console.log( d.responseText );
            console.log( d );
        });

        return request;

    }

    $.fn.removeClassPrefix = function(prefix) {
        this.each(function(i, el) {
            var classes = el.className.split(" ").filter(function(c) {
                return c.lastIndexOf(prefix, 0) !== 0;
            });
            el.className = $.trim(classes.join(" "));
        });
        return this;
    };

} )( jQuery, wp.customize );