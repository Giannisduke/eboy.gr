<?php
namespace PixelYourSite;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Event\RefundEvent;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Event\ViewItemEvent;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Event\PurchaseEvent;
use PYS_PRO_GLOBAL\Br33f\Ga4\MeasurementProtocol\Dto\Parameter\ItemParameter;


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


class GaServerEventHelper {
    static $uaMap = [
        'cn'    => 'traffic_source',
        'ec'    => 'event_category',
        'tt'    => 'tax',
        'tr'    => 'value',
        'ti'    => 'transaction_id',
        'cu'    => 'currency',
        'dr'    => 'traffic_source'
    ];
    /**
     * @param SingleEvent $singleEvent
     * @return array|null
     */
    static public function mapSingleEventToServerData($singleEvent) {
        switch ($singleEvent->payload['name']) {
            case 'purchase': {
                return self::mapPurchaseToServerData($singleEvent);
            }
            case 'refund': {
                return self::mapRefundToServerData($singleEvent);
            }
        }

        return null;
    }

    /**
     * @param SingleEvent $singleEvent
     * @return array|null
     */
    static public function mapSingleEventToServerDataGA4($singleEvent) {

        switch ($singleEvent->payload['name']) {
            case 'purchase': {
                return self::mapPurchaseToServerDataGA4($singleEvent);
            }
            case 'refund': {
                return self::mapRefundToServerDataGA4($singleEvent);
            }
        }

        return null;
    }

    /**
     * @param SingleEvent $singleEvent
     * @return array
     */
    static private function mapPurchaseToServerData($singleEvent) {
        $data = $singleEvent->getData();
        $params = $data['params'];

        $serverParams = [
            't'     => 'event',
            'pa'    => 'purchase',
            'ea'    => 'purchase',
            'el'    => "Server Purchase",
            'cid'   =>  EventIdGenerator::guidv4(),

            'ti'  => $params['transaction_id'],   // transaction ID, required
            'tr'  => $params['value'],          // revenue
            'tt'  => $params['tax'],      // tax
            'cu'  => $params['currency'],              // order currency
        ];
        if(isset( $params['coupon'])) {
            $serverParams['tcc'] = $params['coupon'];  // coupon code
        }

        if(isset($params['shipping'])) {
            $serverParams['ts'] = $params['shipping'];
        }

        foreach (self::$uaMap as $key => $val) {
            if(isset($params[$val])) {
                $serverParams[$key] = $params[$val];
            }
        }

        for($i = 1;$i <= count($params['items']);$i++) {
            $item = $params['items'][$i-1];
            $serverParams["pr{$i}id"] = $item['id'] ?? '';
            $serverParams["pr{$i}nm"] = $item['name'] ?? '';
            $serverParams["pr{$i}ca"] = $item['item_category'] ?? '';
            $serverParams["pr{$i}pr"] = $item['price'] ?? '';
            $serverParams["pr{$i}qt"] = $item['quantity'] ?? '';
        }

        return $serverParams;
    }
    static private function mapRefundToServerData($singleEvent) {
        $data = $singleEvent->getData();
        $params = $data['params'];

        $serverParams = [
            't'     => 'event',
            'ec'    => 'Ecommerce',
            'ea'    => 'Refund',
            'el'    => "Server Refund",
            'cid'   =>  EventIdGenerator::guidv4(),

            'ti'  => $params['transaction_id'],   // transaction ID, required
            'tr'  => - $params['value'],          // revenue
            'cu'  => $params['currency'],              // order currency
        ];

        return $serverParams;
    }

    /**
     * @param SingleEvent $singleEvent
     * @return PurchaseEvent
	 */
    static private function mapPurchaseToServerDataGA4($singleEvent) {
        $data = $singleEvent->getData();

        $data = EventsManager::filterEventParams( $data, $singleEvent->getCategory(), [
            'event_id' => $singleEvent->getId(),
            'pixel'    => GA()->getSlug()
        ] );

        $params = $data['params'];
        $purchaseEventData = new PurchaseEvent();
        $purchaseEventData->setValue($params['value'])
            ->setCurrency($params['currency'])
            ->setTransactionId($params['transaction_id']);

	    if(isset($params['tax'])){
		    $purchaseEventData->setTax($params['tax']);
	    }
        if(isset($params['shipping'])){
            $purchaseEventData->setShipping($params['shipping']);
        }
        foreach (self::$uaMap as $val) {
            if(isset($params[$val])) {
                $purchaseEventData->setParamValue($val, $params[$val]);
            }
        }
        foreach ($params['items'] as $item) {
            $purchasedItem = new ItemParameter();
            $purchasedItem
                ->setItemId($item['id'])
                ->setItemName($item['name'])
                ->setCurrency($params['currency'])
                ->setPrice($item['price'])
                ->setQuantity($item['quantity']);
            if (isset($item['item_category'])) {
                $purchasedItem->setItemCategory($item['item_category']);
            }
            if (isset($item['item_category2'])) {
                $purchasedItem->setItemCategory2($item['item_category2']);
            }
            if (isset($item['item_category3'])) {
                $purchasedItem->setItemCategory3($item['item_category3']);
            }
            if (isset($item['item_category4'])) {
                $purchasedItem->setItemCategory4($item['item_category4']);
            }
            if (isset($item['item_category5'])) {
                $purchasedItem->setItemCategory5($item['item_category5']);
            }
            if(isset($item['variant'])) {
                $purchasedItem->setItemVariant($item['variant']);
            }
            if(isset($item['item_list_name'])) {
                $purchasedItem->setItemListName($item['item_list_name']);
            }
            if(isset($item['item_list_id'])) {
                $purchasedItem->setItemListId($item['item_list_id']);
            }

            if (isset($item['item_brand'])) {
                // Установка бренда с помощью метода setItemBrand
                $purchasedItem->setItemBrand($item['item_brand']);
            }
            $purchaseEventData->addItem($purchasedItem);
        }
        if(isset($params['advanced_purchase_tracking']) && !empty($params['advanced_purchase_tracking'])) {
            $purchaseEventData->setParamValue('advanced_purchase_tracking', $params['advanced_purchase_tracking']);
        }

        //get filter value
        $custom_filter_data_event = apply_filters('pys_event_data',array(),$singleEvent->getCategory(),[
            'event_id'=>$singleEvent->getId(),
            'pixel'=>GA()->getSlug()
        ]);

        if(isset($custom_filter_data_event['params']) && !empty($custom_filter_data_event['params'])) {
            foreach ($custom_filter_data_event['params'] as $key => $value) {
                $purchaseEventData->setParamValue($key, $value);
            }
        }
        $customProperties = [];
        if(PYS()->getOption( 'track_traffic_source' )){
            $customProperties['traffic_source'] = getTrafficSource();
        }

        if(PYS()->getOption( 'track_utms' )) {
            $customProperties = array_merge($customProperties,getUtms());
        }

        if(!empty($customProperties)) {
            foreach ($customProperties as $key => $value) {
                $purchaseEventData->setParamValue($key, $value);
            }
        }

        return $purchaseEventData;
    }
    static private function mapRefundToServerDataGA4($singleEvent) {
        $data = $singleEvent->getData();
        $params = $data['params'];
        $refundEventData = new RefundEvent();
        $refundEventData->setValue($params['value'])
            ->setCurrency($params['currency'])
            ->setTransactionId($params['transaction_id']);

        foreach (self::$uaMap as $val) {
            if(isset($params[$val])) {
                $refundEventData->setParamValue($val, $params[$val]);
            }
        }

        return $refundEventData;
    }

    public static function getClientId(){
        $clientID = null;

        if (isset($_COOKIE['_ga']) && !empty($_COOKIE['_ga'])) {
            $cookieValue = $_COOKIE['_ga'];
            $cookieParts = explode('.', $cookieValue);
            $clientID = $cookieParts[2] . '.' . $cookieParts[3];
        }
        return $clientID;
    }

    public static function getGAStatFromOrder($key, $order_id, $type) {
        if($type == 'woo')
        {
            $order = wc_get_order( $order_id );
            if ($order) {
                $gaCookie = $order->get_meta('pys_ga_cookie', true);
                if (isset($gaCookie[$key]) && !empty($gaCookie[$key])) {
                    return (string) $gaCookie[$key];
                } else {
                    $randomBytes = random_bytes(16);
                    $clientID = bin2hex($randomBytes);
                    $clientID = substr($clientID, 0, 8) . '-' . substr($clientID, 8, 4) . '-4' . substr($clientID, 12, 3) . '-a' . substr($clientID, 15, 3) . '-' . substr($clientID, 18);
                    $data['clientId'] = $clientID;
                    $order->update_meta_data('pys_ga_cookie', $data);
                    $order->save();
                    return $clientID;
                }
            }
        }
        if($type == 'edd')
        {
            $order = edd_get_order_meta( $order_id, 'pys_ga_cookie', true );
            if ($order) {
                $gaCookie = $order;
                if (isset($gaCookie[$key]) && !empty($gaCookie[$key])) {
                    return (string)$gaCookie[$key];
                }
            }
            else {
                $randomBytes = random_bytes(16);
                $clientID = bin2hex($randomBytes);
                $clientID = substr($clientID, 0, 8) . '-' . substr($clientID, 8, 4) . '-4' . substr($clientID, 12, 3) . '-a' . substr($clientID, 15, 3) . '-' . substr($clientID, 18);
                $data['clientId'] = $clientID;
                edd_update_payment_meta( $order_id, 'pys_ga_cookie',$data );
                return $clientID;
            }
        }
        return '';
    }


}