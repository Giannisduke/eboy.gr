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
                     'slug'  => 'block_1_message_2_v2',
                     'message' => 'How to enable Google Consent Mode V2',
                     'title' => 'Google Consent Mode V2',
                     'button_text' => 'Watch Now',
                     'button_url' => 'https://www.youtube.com/watch?v=uYfFesnKcW0'
                 ],
            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 1 of a series of 7 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"
        ],
        [
            'order' => '2',
            'wait' => 12,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'enabelLogo' => true,
            'color' => 'green',
            'multiMessage' => [
                [
                    'slug'  => 'block_2_v1',
                    'message' => 'What is Meta Event Match Quality score (EMQ) and how you can improve it. Watch this short video to find out.',
                    'title' => 'Improve EMQ score - Meta CAPI Events',
                    'button_text' => 'Watch Now',
                    'button_url' => 'https://www.youtube.com/watch?v=snUKcsTbvCk'
                ],
            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 2 of a series of 7 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"
        ],
        [
            'order' => '3',
            'wait' => 12,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'enabelLogo' => true,
            'color' => 'blue',
            'multiMessage' => [
                [
                    'slug'  => 'block_3_v1',
                    'message' => 'If you use WooCommerce or Easy Digital Downloads, there is an easy way to find out what products your Meta Ads sold. Watch this video to learn more.',
                    'title' => 'What products your Meta campaign sold',
                    'button_text' => 'Watch Now',
                    'button_url' => 'https://www.youtube.com/watch?v=b-eYdx9QK0Q',
                ],

            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 3 of a series of 7 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"

        ],
        [
            'order' => '4',
            'wait' => 24,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'color' => 'green',
            'enabelLogo' => true,
            'multiMessage' => [
                [
                    'slug'  => 'block_4_v2',
                    'title' => 'Microsoft Ads WooCommerce Conversion',
                    'message' => 'Find out how to track WooCommerce purchases on Microsoft Ads using Conversion Goals',
                    'button_text' => 'Watch Video',
                    'button_url' => 'https://www.youtube.com/watch?v=3hVUBjba_PA',
                ],
            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 4 of a series of 7 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"

        ],

        [
            'order' => '5',
            'wait' => 24,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'color' => 'green',
            'enabelLogo' => true,
            'multiMessage' => [
                [
                    'slug'  => 'block_5_v1',
                    'title' => 'Do you make this mistake?',
                    'message' => 'Nowadays, most website have a consent message as required by GDPR, CCPA, or other rules and regulations. And there is a very simple mistake that can RUIN your tracking. Find out if you"re doing it too, and how to fix it.',
                    'button_text' => 'Watch Video',
                    'button_url' => 'https://www.youtube.com/watch?v=eo-dpuAqZNg',
                ],
            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 5 of a series of 7 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"

        ],

        [
            'order' => '6',
            'wait' => 12,
            'type' => 'event chain',
            'enabelYoutubeLink' => false,
            'color' => 'blue',
            'enabelLogo' => true,
            'multiMessage' => [
                [
                    'slug'  => 'block_6_v1',
                    'title' => 'ConsentMagic',
                    'message' => 'Manage consent with our dedicated plugin. Learn more about how ConsentMagic can help your business to respect privacy rules like GDPR or CCPA.',
                    'button_text' => 'Find more',
                    'button_url' => 'https://www.pixelyoursite.com/plugins/consentmagic',
                ],
            ],
            'optoutEnabel' => true,
            'optoutMessage' => "This is message 6 of a series of 7 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"
        ],

		[
            'order' => '7',
            'wait' => 24,
            'type' => 'event chain',
            'enabelYoutubeLink' => true,
            'enabelLogo' => true,
            'multiMessage' => [
                [
                    'slug'  => 'block_7_message_1_v2',
                    'title' => 'Track WordPress forms',
                    'message' => 'Learn how to fire a Lead event that tracks WordPress form submission',
                    'button_text' => 'Watch this video',
                    'button_url' => 'https://www.youtube.com/watch?v=c4Hrb8WK5bw',
                ],
            ],
            'optoutEnabel' => false,
            'optoutMessage' => "This is message 7 of a series of 7 notifications containing tips and tricks about how to use our plugin.",
            'optoutButtonText' => "Don't show me more tips"
        ]


    ];
}
