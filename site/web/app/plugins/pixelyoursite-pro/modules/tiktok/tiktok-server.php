<?php

namespace PixelYourSite;

require_once PYS_PATH . '/modules/tiktok/tiktok-server-async-task.php';

use DateTimeInterface;
use PixelYourSite;
use PYS_PRO_GLOBAL\GuzzleHttp\Client;

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TikTokServer {

	private static $_instance;
	private        $isEnabled;
	private        $isDebug;
	private        $access_token;
	private        $woo_order = 0;
	private        $edd_order = 0;
	private        $testCode;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		$this->isEnabled = Tiktok()->isServerApiEnabled();
		$this->isDebug = PYS()->getOption( 'debug_enabled' );

		if ( $this->isEnabled ) {
			add_action( 'wp_ajax_pys_tiktok_api_event', array(
				$this,
				"catchTikTokAjaxEvent"
			) );
			add_action( 'wp_ajax_nopriv_pys_tiktok_api_event', array(
				$this,
				"catchTikTokAjaxEvent"
			) );
			/*add_action( 'woocommerce_add_to_cart', array(
				$this,
				'trackAddToCartEvent'
			), 40, 4 );*/

			//initialize tiktok event async task
			new TikTokAsyncTask();
		}
	}

	/**
	 * Send event in shutdown hook (not work in ajax)
	 * @param SingleEvent[] $events
	 */
	public function sendEventsAsync( $events ) {

		$serverEvents = array();

		foreach ( $events as $event ) {
            $ids = $event->payload[ 'pixelIds' ];
            $serverEvents[] = [
                "pixelIds" => $ids,
                "event" => $this->mapEventToServerEvent($event)
            ];
		}

		if ( count( $serverEvents ) > 0 ) {
			do_action( 'pys_send_tiktok_server_event', $serverEvents );
		}
	}

	/**
	 * Send Event Now
	 *
	 * @param SingleEvent[] $events
	 */
	public function sendEventsNow( $events ) {

		foreach ( $events as $event ) {
			$serverEvent = $this->mapEventToServerEvent( $event );
			$ids = $event->payload[ 'pixelIds' ];

			$this->sendEvent( $ids, $serverEvent );
		}
	}


	/**
	 * Track Woo Add to cart events
	 *
	 * @param $cart_item_key
	 * @param $product_id
	 * @param $quantity
	 * @param $variation_id
	 * @return void
	 */
	function trackAddToCartEvent( $cart_item_key, $product_id, $quantity, $variation_id ) {
		if ( EventsWoo()->isReadyForFire( "woo_add_to_cart_on_button_click" ) && PYS()->getOption( 'woo_add_to_cart_catch_method' ) == "add_cart_js" ) {
			// it ok. We send server method after js, and take event id from cookies
			Tiktok()->getLog()->debug( ' trackAddToCartEvent send TikTok server without browser event' );

			if ( !empty( $variation_id ) && $variation_id > 0 && ( !Tiktok()->getOption( 'woo_variable_as_simple' ) ) ) {
				$_product_id = $variation_id;
			} else {
				$_product_id = $product_id;
			}

			$event = new SingleEvent( "woo_add_to_cart_on_button_click", EventTypes::$DYNAMIC, 'woo' );
			$event->args = [
				'productId' => $_product_id,
				'quantity'  => $quantity
			];
			$event->params[ 'uri' ] = self::getRequestUri( PYS()->getOption( 'enable_remove_source_url_params' ) );

			add_filter( 'pys_conditional_post_id', function ( $id ) use ( $product_id ) {
				return $product_id;
			} );
			$events = Tiktok()->generateEvents( $event );
			remove_all_filters( 'pys_conditional_post_id' );

			do_action( 'pys_send_tiktok_server_event', $events );
		}
	}

	/**
	 * If server message is blocked by gprg or it dynamic
	 * we send data by ajax request from js and send the same data like browser event
	 */
	function catchTikTokAjaxEvent() {

		Tiktok()->getLog()->debug( ' catchTikTokAjaxEvent send to TikTok server from ajax' );
		$event = $_POST[ 'event' ];
		$data = isset( $_POST[ 'data' ] ) ? $_POST[ 'data' ] : array();
		$ids = $_POST[ 'ids' ];
		$event_id = $_POST[ 'event_id' ];
		$wooOrder = isset( $_POST[ 'woo_order' ] ) ? $_POST[ 'woo_order' ] : null;
		$eddOrder = isset( $_POST[ 'edd_order' ] ) ? $_POST[ 'edd_order' ] : null;

		if ( empty( $_REQUEST[ 'ajax_event' ] ) || !wp_verify_nonce( $_REQUEST[ 'ajax_event' ], 'ajax-event-nonce' ) ) {
			wp_die();
			return;
		}

		if ( $event == "hCR" ) $event = "CompleteRegistration"; // de mask completer registration event if it was hidden

		$singleEvent = $this->dataToSingleEvent( $event, $data, $event_id, $ids, $wooOrder, $eddOrder );

		$this->sendEventsNow( [ $singleEvent ] );

		wp_die();
	}

	/**
	 * @param $eventName
	 * @param $params
	 * @param $event_id
	 * @param $ids
	 * @param $wooOrder
	 * @param $eddOrder
	 * @return SingleEvent
	 */
	private function dataToSingleEvent( $eventName, $params, $event_id, $ids, $wooOrder, $eddOrder ) {
		$singleEvent = new SingleEvent( "", "" );

		$payload = [
			'name'      => $eventName,
			'event_id'  => $event_id,
			'woo_order' => $wooOrder,
			'edd_order' => $eddOrder,
			'pixelIds'  => $ids
		];
		$singleEvent->addParams( $params );
		$singleEvent->addPayload( $payload );

		return $singleEvent;
	}


	/**
	 * Send event for each pixel id
	 * @param array $pixel_Ids //array of facebook ids
	 * @param $event //One Facebook event object
	 */
	function sendEvent( $pixel_Ids, $event ) {

		if ( !$event || apply_filters( 'pys_disable_server_event_filter', false ) ) {
			return;
		}

		if ( !$this->access_token ) {
			$this->access_token = Tiktok()->getApiTokens();
			$this->testCode = Tiktok()->getApiTestCode();
		}

		foreach ( $pixel_Ids as $pixel_Id ) {

			if ( !Tiktok()->enabled() || empty( $this->access_token[ $pixel_Id ] ) ) continue;

			$event->pixel_code = $pixel_Id;

			if ( $this->testCode[ $pixel_Id ] ) {
				$event->test_event_code = $this->testCode[ $pixel_Id ];
			}

			$url = "https://business-api.tiktok.com/open_api/v1.3/pixel/track/";
			$headers = array(
				'Content-Type' => 'application/json',
				'Access-Token' => $this->access_token[ $pixel_Id ]
			);

			Tiktok()->getLog()->debug( ' Send TikTok server event', $event );
			try {
				$client = new Client();
				$response = $client->request( 'POST', $url, [
					'headers' => $headers,
					'body'    => json_encode( $event )
				] );
				Tiktok()->getLog()->debug( ' Response from Tiktok server', $response );

			} catch ( \Exception $e ) {
				Tiktok()->getLog()->error( 'Error send TikTok server event ' . $e->getMessage() );
			}
		}
	}

	public function mapEventToServerEvent( $event ) {

		$eventData = $event->getData();

		$eventData = EventsManager::filterEventParams( $eventData, $event->getCategory(), [
			'event_id' => $event->getId(),
			'pixel'    => Tiktok()->getSlug()
		] );

		$eventName = $eventData[ 'name' ];
		$wooOrder = isset( $event->payload[ 'woo_order' ] ) ? $event->payload[ 'woo_order' ] : null;
		$eddOrder = isset( $event->payload[ 'edd_order' ] ) ? $event->payload[ 'edd_order' ] : null;

		$user_data = $this->getUserData( $wooOrder, $eddOrder );
		$custom_data = $this->paramsToCustomData( $eventData[ 'params' ] );

		if ( isset( $event->params[ 'uri' ] ) ) {
			$uri = $event->params[ 'uri' ];
		} else {
			$uri = self::getRequestUri( PYS()->getOption( 'enable_remove_source_url_params' ) );

			// set custom uri use in ajax request
			if ( isset( $_POST[ 'url' ] ) ) {
				if ( PYS()->getOption( 'enable_remove_source_url_params' ) ) {
					$list = explode( "?", $_POST[ 'url' ] );
					if ( is_array( $list ) && count( $list ) > 0 ) {
						$uri = $list[ 0 ];
					} else {
						$uri = $_POST[ 'url' ];
					}
				} else {
					$uri = $_POST[ 'url' ];
				}
			}
		}

		$user_data->page = new \stdClass;
		$user_data->page->url = $uri;
		$user_data->page->referrer = get_home_url();

		$serverEvent = new \stdClass;
		$serverEvent->event = $eventName;

		$datetime = new \DateTime( 'now' );
		$serverEvent->timestamp = $datetime->format( DateTimeInterface::ATOM );
		$serverEvent->context = $user_data;
		if ( count( get_object_vars( $custom_data ) ) > 0 ) {
			$serverEvent->properties = $custom_data;
		}


		if ( is_array( $event->params ) ) {
			foreach ( $event->params as $key => $param ) {
				$serverEvent->$key = $param;
			}
		}
		$serverEvent->event_id = $event->payload[ 'event_id' ] ?? '';

		return $serverEvent;
	}

	private function getUserData( $wooOrder = null, $eddOrder = null ) {

		$userData = new \stdClass;
		$userData->user = new \stdClass;

		/**
		 * Add purchase WooCommerce Advanced Matching params
		 */
		if ( PixelYourSite\isWooCommerceActive() && isEventEnabled( 'woo_purchase_enabled' ) && ( $wooOrder || ( PYS()->woo_is_order_received_page() && wooIsRequestContainOrderId() ) ) ) {
			if ( wooIsRequestContainOrderId() ) {
				$order_id = wooGetOrderIdFromRequest();
			} else {
				$order_id = $wooOrder;
			}

			$order = wc_get_order( $order_id );

			if ( $order ) {
				$this->woo_order = $order_id;

				if ( PixelYourSite\isWooCommerceVersionGte( '3.0.0' ) ) {
					$user_email = $order->get_billing_email();
					$user_phone = $order->get_billing_phone();

				} else {
					$user_email = $order->billing_email;
					$user_phone = $order->billing_phone;
				}

				$user_persistence_data = get_persistence_user_data( $user_email, '', '', $user_phone );
				if ( !empty( $user_persistence_data[ 'em' ] ) ) $userData->user->email = hash( 'sha256', mb_strtolower( $user_persistence_data[ 'em' ] ), false );
				if ( !empty( $user_persistence_data[ 'tel' ] ) ) $userData->user->phone_number = hash( 'sha256', preg_replace( '/[^0-9]/', '', $user_persistence_data[ 'tel' ] ), false );


				if ( isset( $_COOKIE[ '_ttp' ] ) && !empty( $_COOKIE[ '_ttp' ] ) ) {
					$userData->user->ttp = $_COOKIE[ '_ttp' ];
				}

			} else {
				$userData = $this->getRegularUserData();
			}

		} else {

			if ( PixelYourSite\isEddActive() && isEventEnabled( 'edd_purchase_enabled' ) && ( $eddOrder || edd_is_success_page() ) ) {

				$this->edd_order = $eddOrder;

				if ( $eddOrder ) $payment_id = $eddOrder; else {
					$payment_key = getEddPaymentKey();
					$payment_id = (int) edd_get_purchase_id_by_key( $payment_key );
				}

				$user_persistence_data = get_persistence_user_data( edd_get_payment_user_email( $payment_id ), '', '', '' );
				if ( !empty( $user_persistence_data[ 'em' ] ) ) $userData->user->email = hash( 'sha256', mb_strtolower( $user_persistence_data[ 'em' ] ), false );
				if ( !empty( $user_persistence_data[ 'tel' ] ) ) $userData->user->phone_number = hash( 'sha256', preg_replace( '/[^0-9]/', '', $user_persistence_data[ 'tel' ] ), false );

				if ( isset( $_COOKIE[ '_ttp' ] ) && !empty( $_COOKIE[ '_ttp' ] ) ) {
					$userData->user->ttp = $_COOKIE[ '_ttp' ];
				}

			} else {
				$userData = $this->getRegularUserData();
			}
		}

		$userData->ip = self::getIpAddress();
		$userData->user_agent = self::getHttpUserAgent();

		if ( PixelYourSite\EventsManager::isTrackExternalId() && isset( $_COOKIE[ 'pbid' ] ) ) {
			$userData->external_id = $_COOKIE[ 'pbid' ];
		}

		return apply_filters( "pys_tiktok_server_user_data", $userData );
	}

	private function getRegularUserData() {
		$user = wp_get_current_user();
		$userData = new \stdClass;
		$userData->user = new \stdClass;
		$user_email = $user_phone = '';

		if ( $user->ID ) {
			// get user regular data
			$user_email = $user->get( 'user_email' );

			/**
			 * Add common WooCommerce Advanced Matching params
			 */
			if ( PixelYourSite\isWooCommerceActive() ) {
				$user_phone = $user->get( 'billing_phone' );
			}
		}

		$user_persistence_data = get_persistence_user_data( $user_email, '', '', $user_phone );
		if ( !empty( $user_persistence_data[ 'em' ] ) ) $userData->user->email = hash( 'sha256', mb_strtolower( $user_persistence_data[ 'em' ] ), false );
		if ( !empty( $user_persistence_data[ 'tel' ] ) ) $userData->user->phone_number = hash( 'sha256', preg_replace( '/[^0-9]/', '', $user_persistence_data[ 'tel' ] ), false );

		if ( !empty( $_COOKIE[ '_ttp' ] ) ) {
			$userData->user->ttp = $_COOKIE[ '_ttp' ];
		}

		return $userData;
	}

	private function paramsToCustomData( $data ) {

		$custom_data = new \stdClass;
		if ( isset( $data[ 'quantity' ] ) ) {
			$data_line_items = array();
			$data_line_items[ 'quantity' ] = $data[ 'quantity' ];
			if ( isset( $data[ 'value' ] ) ) {
				$data_line_items[ 'price' ] = $data[ 'value' ];
			}
			if ( isset( $data[ 'content_id' ] ) ) {
				$data_line_items[ 'content_id' ] = $data[ 'content_id' ];
			}

			if ( isset( $data[ 'content_category' ] ) ) {
				$data_line_items[ 'content_category' ] = $data[ 'content_category' ];
			}

			if ( isset( $data[ 'content_name' ] ) ) {
				$data_line_items[ 'content_name' ] = $data[ 'content_name' ];
			}

			if ( isset( $data[ 'content_type' ] ) ) {
				$data_line_items[ 'content_type' ] = $data[ 'content_type' ];
			} else {
				$data_line_items[ 'content_type' ] = 'product';
			}

			$data[ 'contents' ][] = $data_line_items;
			if ( isset( $data[ 'currency' ] ) ) {
				$custom_data->currency = $data[ 'currency' ];
			}
		}

		if ( isset( $data[ 'contents' ] ) && is_array( $data[ 'contents' ] ) ) {
			$contents = array();
			$cost = 0;
			$custom_data->content_type = $data[ 'content_type' ];

			foreach ( $data[ 'contents' ] as $c ) {
				if ( isset( $c[ 'quantity' ] ) ) {
					$contents[] = array(
						'quantity'         => (int) $c[ 'quantity' ],
						'price'            => isset( $c[ 'price' ] ) ? strval( $c[ 'price' ] ) : '',
						'content_id'       => $c[ 'content_id' ],
						'content_category' => $c[ 'content_category' ] ?? '',
						'content_name'     => $c[ 'content_name' ] ?? '',
					);

					$cost += $c[ 'quantity' ] * ( $c[ 'price' ] ?? 0 );
				}
			}

			$custom_data->contents = $contents;
			$custom_data->value = $cost;
			if ( isset( $data[ 'currency' ] ) ) {
				$custom_data->currency = $data[ 'currency' ];
			}
		}

		if ( !empty( $_GET[ 's' ] ) ) {
			$custom_data->query = $_GET[ 's' ];
		}

		return apply_filters( "pys_tiktok_server_custom_data", $custom_data );
	}

	private static function getRequestUri( $removeQuery = false ) {
		$request_uri = null;

		if ( !empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
			$start = ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] === 'on' ? "https" : "http" ) . "://";
			$request_uri = $start . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
		}
		if ( $removeQuery && isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
			$request_uri = str_replace( "?" . $_SERVER[ 'QUERY_STRING' ], "", $request_uri );
		}

		return $request_uri;
	}

	private static function getIpAddress() {
		$HEADERS_TO_SCAN = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR'
		);

		foreach ( $HEADERS_TO_SCAN as $header ) {
			if ( array_key_exists( $header, $_SERVER ) ) {
				$ip_list = explode( ',', $_SERVER[ $header ] );
				foreach ( $ip_list as $ip ) {
					$trimmed_ip = trim( $ip );
					if ( self::isValidIpAddress( $trimmed_ip ) ) {
						return $trimmed_ip;
					}
				}
			}
		}

		return "127.0.0.1";
	}

	private static function isValidIpAddress( $ip_address ) {
		return filter_var( $ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	}

	private static function getHttpUserAgent() {
		$user_agent = null;

		if ( !empty( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
			$user_agent = $_SERVER[ 'HTTP_USER_AGENT' ];
		}

		return $user_agent;
	}
}

/**
 * @return TikTokServer
 */
function TikTokServer() {
	return TikTokServer::instance();
}

TikTokServer();