<?php
namespace PixelYourSite;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once PYS_PATH.'/modules/google_analytics/server/server_event_helper.php';

use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Common\UserAddress;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Service;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Request\BaseRequest;

use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Common\UserProperties;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Common\UserProperty;

use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Common\UserData;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Common\UserDataItem;

use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Common\ConsentProperty;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Enum\ConsentCode;


/**
 * The Measurement Protocol API wrapper class.
 *
 * A basic wrapper around the GA Measurement Protocol HTTP API used for making
 * server-side API calls to track events.
 *
 */
class GaMeasurementProtocolAPI
{
	static $uaMap = [
		'traffic_source',
		'event_category',
	];
	/** @var string endpoint for GA API */
	public $ga_url = 'https://www.google-analytics.com/collect';
	private $access_token = '';
	//public $ga_url = 'https://www.google-analytics.com/debug/collect'; //debug


	/**
	 * Send event in shutdown hook (not work in ajax)
	 * @param SingleEvent[] $events
	 */
	public function sendEventsAsync($events)
	{
		// not use
	}

	/**
	 * Send Event Now
	 *
	 * @param SingleEvent[] $events
	 */
	public function sendEventsNow($events)
	{
		foreach ($events as $event) {
			$ids = $event->payload['trackingIds'];
			$this->sendEvent($ids, $event);
		}
	}

	private function sendEvent($tags, $event)
	{
		if (!$this->access_token) {
			$this->access_token = GA()->getApiTokens();
		}
		foreach ($tags as $tag) {
			if($this->isGaV4($tag))
			{
				$data = $event->getData();
				$params = $data['params'];
				if(!empty($data['woo_order']))
				{
					$orderId = $data['woo_order'];
					$type = 'woo';
				}
				elseif (!empty($data['edd_order']))
				{
					$orderId = $data['edd_order'];
					$type = 'edd';
				}
				else
				{
					continue;
				}


				if (empty($this->access_token[$tag]) || empty($orderId)) {
					continue;
				}

				$clientId = GaServerEventHelper::getGAStatFromOrder('clientId', $orderId, $type);
				if (empty($clientId)) {
					continue;
				}

				$ga4Service = new Service($this->access_token[$tag]);
				$ga4Service->setMeasurementId($tag);

				$baseRequest = new BaseRequest();
				$baseRequest->setClientId($clientId);
				$eventData = GaServerEventHelper::mapSingleEventToServerDataGA4($event);
				GA()->getLog()->debug('Send for GA4', $tag);

				$consent = new ConsentProperty();
				$this->setConsentProperty($params, 'ad_user_data', $consent, 'setAdUserData');
				$this->setConsentProperty($params, 'ad_personalization', $consent, 'setAdPersonalization');

				$baseRequest->setConsent( $consent );

				$baseRequest->addEvent($eventData);
				if ( isset( $data[ 'woo_order' ] ) && !empty( $data[ 'woo_order' ] ) ) {
					$order = wc_get_order( $data[ 'woo_order' ] );
					if ($order) {
						$street = $order->get_billing_address_1();
						$city = $order->get_billing_city();
						$zip = $order->get_billing_postcode();
						$country = $order->get_billing_country();

						$user_persistence_data = get_persistence_user_data($order->get_billing_email(), $order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_billing_phone());

						$userData = new UserData();
						if (!empty($user_persistence_data['em'])) {
							$email = $this->processAndHash($user_persistence_data['em'], 'email');
							$userData->addUserDataItem(new UserDataItem('sha256_email_address', $email));
						}
						if (!empty($user_persistence_data['tel'])) {
							$phone = $this->processAndHash($user_persistence_data['tel'], 'phone');
							$userData->addUserDataItem(new UserDataItem('sha256_phone_number', $phone));
						}
						$userAdress = new UserAddress();
						if ( ! empty( $user_persistence_data['fn'] ) ) {
							$name = $this->processAndHash( $user_persistence_data['fn'], 'name' );
							$userAdress->addUserAddressItem( new UserDataItem( 'sha256_first_name', $name ) );
						}
						if ( ! empty( $user_persistence_data['ln'] ) ) {
							$surname = $this->processAndHash( $user_persistence_data['ln'], 'surname' );
							$userAdress->addUserAddressItem( new UserDataItem( 'sha256_last_name', $surname ) );
						}
						if ( $country && $city ) {
							$userAdress->addUserAddressItem( new UserDataItem( 'city', $city ) );
							$userAdress->addUserAddressItem( new UserDataItem( 'country', $country ) );
							if ( $zip ) {
								$userAdress->addUserAddressItem( new UserDataItem( 'postal_code', $zip ) );
							}
							if ( $street ) {
								$street = $this->processAndHash( $street, 'street' );
								$userAdress->addUserAddressItem( new UserDataItem( 'sha256_street', $street ) );
							}
						}
						if($userAdress->getUserAddressItemList()){
							$userData->addUserAddress( $userAdress );
						}

						$baseRequest->setUserData($userData);
					}
				} elseif ( isset( $data[ 'edd_order' ] ) && !empty( $data[ 'edd_order' ] ) ) {
					$order = new \EDD_Payment( $data[ 'edd_order' ] );
					if ( $order ) {
						$meta = $order->get_meta();
						if ( isset( $meta[ 'user_info' ] ) ) {
							//object UserProperties
							$userData = new UserData();
							$user_first_name = $meta[ 'user_info' ][ 'first_name' ] ?? '';
							$user_last_name = $meta[ 'user_info' ][ 'last_name' ] ?? '';
							$user_email = $meta[ 'user_info' ][ 'email' ] ?? '';
							$user_persistence_data = get_persistence_user_data( $user_email, $user_first_name, $user_last_name, '' );


							if (!empty($user_persistence_data['em'])) {
								$email = $this->processAndHash($user_persistence_data['em'], 'email');
								$userData->addUserDataItem(new UserDataItem('sha256_email_address', $email));
							}
							$userAdress = new UserAddress();
							if (!empty($user_persistence_data['fn'])) {
								$first_name = $this->processAndHash($user_persistence_data['fn'], 'name');
								$userAdress->addUserAddressItem(new UserDataItem('sha256_first_name', $first_name));
							}
							if (!empty($user_persistence_data['ln'])) {
								$last_name = $this->processAndHash($user_persistence_data['ln'], 'surname');
								$userAdress->addUserAddressItem(new UserDataItem('sha256_last_name', $last_name));
							}
							if (isset($meta['user_info']['address'])) {
								$street = $this->processAndHash($meta['user_info']['address'], 'street');
								$userAdress->addUserAddressItem(new UserDataItem('sha256_street', $street));
							}

							// Setting user properties for purchaseEventData
							$userData->addUserAddress($userAdress);
							$baseRequest->setUserData($userData);
						}
					}
				}
				GA()->getLog()->debug('Send GA4 server event request', $baseRequest);

// We have all the data we need. Just send the request.
				$ga4Service->send($baseRequest);
				$response2 = $ga4Service->sendDebug($baseRequest);
				GA()->getLog()->debug('Send GA4 server event response', $response2);
			}
			else
			{
				$eventData = GaServerEventHelper::mapSingleEventToServerData($event);
				$eventData['v'] = '1';// API version
				$eventData['tid'] = $tag; // tracking ID
				$eventData['z'] = time();

				$response = wp_safe_remote_request($this->ga_url, $this->prepareRequestArgs($eventData));
				if (is_wp_error($response)) {
					GA()->getLog()->debug('Send GA server event error', $response);
					return;
				}
				GA()->getLog()->debug('Send GA server event response', $response);
			}

//            $response_code     = wp_remote_retrieve_response_code( $response );
//            $response_message  = wp_remote_retrieve_response_message( $response );
//            $raw_response_body = wp_remote_retrieve_body( $response );


		}
	}

	private function prepareRequestArgs($params)
	{
		$args = array(
			'method' => 'POST',
			'timeout' => MINUTE_IN_SECONDS,
			'redirection' => 0,
			// 'httpversion' => '1.0',
			'sslverify' => true,
			'blocking' => true,
			// 'user-agent'  => $this->get_request_user_agent(),
			'headers' => [],
			'body' => $this->paramsToString($params),
			'cookies' => array(),
		);

		return $args;
	}

	public function paramsToString($params)
	{

		return http_build_query($params, '', '&');
	}

	public function isGaV4($tag) {
		return strpos($tag, 'G') === 0;
	}
	function isSha256($str) {
		return preg_match('/^[a-f0-9]{64}$/', $str);
	}
	function processAndHash($value, $type) {
        if(is_array($value)){
            $value = $value[0];
        }
		if (!$this->isSha256($value)) {
			switch ($type) {
				case 'email':
					$value = strtolower($value);
					if (preg_match('/@(gmail\.com|googlemail\.com)$/', $value)) {
						$value = preg_replace('/\.(?=[^@]*@)/', '', $value);
					}
					$value = str_replace(' ', '', $value);
					break;
				case 'phone':
					$value = preg_replace('/\D/', '', $value);
					$value = '+' . $value;
					break;
				case 'name':
				case 'surname':
					$value = preg_replace('/[\d\W]/', '', $value);
					$value = strtolower($value);
					$value = trim($value);
					break;
				case 'street':
					$value = strtolower($value);
					$value = str_replace(' ', '', $value);
					break;
			}
			$value = hash('sha256', $value);
		}
		return $value;
	}

	function setConsentProperty($params, $key, $consent, $method) {
		if (isset($params[$key]) && !empty($params[$key])) {
			$consent->$method(strtoupper($params[$key]));
		} else {
			$consent->$method(ConsentCode::DENIED);
		}
	}
}