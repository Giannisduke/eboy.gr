<?php

namespace PixelYourSite;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/*
 * Notice structure
        [
            'order' => '1', // message display order
            'wait' => 0, // timeout after closing the previous message
            'type' => 'event chain', // Message type, if included in the message sequence then type MUST be 'event chain'
            'enabelYoutubeLink' => false, // enables or disables the link to the channel at the bottom of the block
            'enabelLogo' => false, // enable or disable the logo on the left in the block
            'enabelDismiss' => false, // enable or disable dismiss button, default enable
            'color' => 'orange', // color can be 'orange', 'green', 'blue'
            'multiMessage' => [
                [
                    'slug'  => 'new_message_1_v1', // unique slug for message "new_message_1" - unique title, '_v1' - version message
                    'message' => 'Hello I message 1 V 1',
                    'title' => 'Title V1',
                    'button_text' => 'Watch',
                    'button_url' => 'https://www.youtube.com/watch?v=snUKcsTbvCk'
                ],
                [
                    'slug'  => 'new_message_2_v1',
                    'message' => 'Hello I message 2 V 1',
                    'button_text' => 'Watch',
                    'button_url' => 'https://www.youtube.com/watch?v=snUKcsTbvCk',
                ],
                [
                    'slug'  => 'new_message_3_v1',
                    'title' => 'Title V1',
                    'message' => 'Hello I message 3 V 1',
                    'button_text' => 'Watch',
                    'button_url' => 'https://www.youtube.com/watch?v=snUKcsTbvCk',
                ]
            ]
        ],

If need fixed message
        [
            'type' => 'promo',
            'enabelDismiss' => false, // enable or disable dismiss button, default enable
            'plugins' =>[], // can be "woo","wcf","edd" or empty array
            'slug'  => '',// unique id
            'message' => '', // message with html tags
        ]
 * */

function adminGetFixedNotices() {
    return [
        [
            'order' => '1',
            'wait' => 0,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'enabelLogo' => true,
            'enabelDismiss' => true,
            'color' => 'orange',
            'multiMessage' => [
                [
                    'slug'  => 'block_1_message_1_v1',
                    'message' => 'Check our dedicated Help Sections and learn how to configure PixelYourSite Professional',
                    'title' => 'PixelYourSite Help',
                    'button_text' => 'Click here',
                    'button_url' => 'https://www.pixelyoursite.com/documentation'
                ],
                [
                    'slug'  => 'block_1_message_2_v1',
                    'message' => 'Check our YouTube Channel for useful tips and tricks',
                    'title' => 'Watch on YouTube',
                    'button_text' => 'Click here',
                    'button_url' => 'https://www.youtube.com/channel/UCnie2zvwAjTLz9B4rqvAlFQ',
                ],
                [
                     'slug'  => 'block_1_message_3_v3',
                     'message' => 'NEW: Learn how to track everything with our new GTM integration',
                     'title' => 'Google Tag Manager Integration!',
                     'button_text' => 'Watch Now',
                     'button_url' => 'https://www.youtube.com/watch?v=bEK3qaaRvNg'
                 ],
            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 1 of a series of 3 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"
        ],
        [
            'order' => '2',
            'wait' => 24,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'enabelLogo' => true,
            'color' => 'green',
            'multiMessage' => [
                [
                    'slug'  => 'block_2_v1',
                    'message' => 'Improve your Meta EMQ score with data from forms. Watch this short video to find out how.',
                    'title' => 'Improve EMQ score - Form Data',
                    'button_text' => 'Watch Now',
                    'button_url' => 'https://www.youtube.com/watch?v=snUKcsTbvCk'
                ],
            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 2 of a series of 3 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"
        ],
        [
            'order' => '3',
            'wait' => 24,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'enabelLogo' => true,
            'color' => 'blue',
            'multiMessage' => [
                [
                    'slug'  => 'block_3_v1',
                    'message' => 'Learn how to configure Google Consent Mode when using PixelYourSite.',
                    'title' => 'Google Consent Mode',
                    'button_text' => 'Watch Now',
                    'button_url' => 'https://www.youtube.com/watch?v=uYfFesnKcW0',
                ],

            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 3 of a series of 3 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"

        ]


    ];
}
