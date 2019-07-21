<?php
/**
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: frites_functions.php 2019-07-20 20:29:16Z webchills $
 */



include_once zen_get_file_directory(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/','frites.php', 'false');

class frites_functions {
	public function __construct() {
	}

	public static function get_allowed_countries() {
		return array('GB', 'AT', 'DE', 'FR', 'IT', 'ES', 'BE', 'DK', 'IE', 'LU', 'NL', 'PT', 'SE', 'HU', 'CY');
	}
	
	// allowed country ids from table countries_name
	public static function get_allowed_countries_names() {
		return array('222', '14', '81', '73', '105', '195', '21', '57', '103', '124', '150', '171', '203', '97', '55');
	}

	public static function get_allowed_languages() {
		return array('en', 'uk', 'de', 'fr', 'it', 'es');
	}

	public static function check_status($check_country_id = 0, $check_zone_id = 0) {
		$result = (bool)(MODULE_PAYMENT_FRITES_STATUS == 'True');

		$country_ids = array_unique(array(
			0,
			(int)$check_country_id
		));

		$zone_ids = array_unique(array(
			0,
			(int)$check_zone_id
		));

		$sql = "SELECT association_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = " . (int)MODULE_PAYMENT_FRITES_ZONE . " ";

		if ($result && (int)MODULE_PAYMENT_FRITES_ZONE && (int)$check_country_id && (int)$check_zone_id) {
			$sql .= " AND (zone_country_id IN (".implode(',', $country_ids).") OR zone_country_id IS NULL) AND (zone_id IN (".implode(',', $zone_ids).") OR zone_id IS NULL)";
		}
		elseif ($result && (int)MODULE_PAYMENT_FRITES_ZONE && (int)$check_country_id && !(int)$check_zone_id) {
			$sql .= " AND (zone_country_id IN (".implode(',', $country_ids).") OR zone_country_id IS NULL)";
		}

		if ($result && (int)MODULE_PAYMENT_FRITES_ZONE && (int)$check_country_id) {
			$zones = frites_db($sql);
			if ($zones->num_rows) {
				$result = true;
			} else {
				$result = false;
			}
		}

		

		return $result;
	}
}

	if (!defined('MODULE_PAYMENT_FRITES_POPUP')) define('MODULE_PAYMENT_FRITES_POPUP', 'True');
	if (!defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')) define('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN', '');
	if (!defined('MODULE_PAYMENT_FRITES_AUTHORIZATION')) define('MODULE_PAYMENT_FRITES_AUTHORIZATION', 'True');
	if (!defined('MODULE_PAYMENT_FRITES_CAPTURE')) define('MODULE_PAYMENT_FRITES_CAPTURE', 'When the order is shipped');

	function fritesSetOrderReferenceDetails($OrderReferenceId, $OrderTotalAmount, $OrderTotalCurrency, $SellerOrderId, $SellerNote='') {
		$links = fritesLinks();

		$currency = frites_get_currency();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'SetOrderReferenceDetails',
			'AmazonOrderReferenceId' => $OrderReferenceId,
			'OrderReferenceAttributes.OrderTotal.Amount' => $OrderTotalAmount,
			'OrderReferenceAttributes.OrderTotal.CurrencyCode' => $OrderTotalCurrency,
			
			'OrderReferenceAttributes.SellerNote' => $SellerNote,
			'OrderReferenceAttributes.SellerOrderAttributes.SellerOrderId' => $SellerOrderId,
			'OrderReferenceAttributes.SellerOrderAttributes.StoreName' => HTTP_SERVER,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',			
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		return $result;
	}

	function fritesGetOrderReferenceDetails($OrderReferenceId) {
		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'GetOrderReferenceDetails',
			'AmazonOrderReferenceId' => $OrderReferenceId,
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',			
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		return $result;
	}

	function fritesConfirmOrderReference($OrderReferenceId) {
		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'ConfirmOrderReference',
			'AmazonOrderReferenceId' => $_SESSION['frites']['OrderReferenceId'],
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',			
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		return $result;
	}

	function fritesCloseOrderReference($OrderReferenceId, $ClosureReason='', $SellerOrderId=0) {
		global $db;

		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'CloseOrderReference',
			'AmazonOrderReferenceId' => $OrderReferenceId,
			'ClosureReason' => $ClosureReason,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		$order_status = 0;
		if (MODULE_PAYMENT_FRITES_ORDER_STATUS_CLOSED_ID > 0) {
			$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_CLOSED_ID;
		}

		if (!isset($result['Error']) && (int)$SellerOrderId && $order_status) {
			$sql = 'UPDATE ' . TABLE_ORDERS . ' SET ';
			if ($order_status) {
				$sql .= "`orders_status` = '".(int)$order_status."' ";
			}
			$sql .= "WHERE `orders_id` = '".(int)$SellerOrderId."'";
			if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
				fritesWriteLog($sql);
			}
			$db->Execute($sql);

			$status_text = 'Amazon action: CloseOrderReference';

			$sql_data_array = array(
							array('fieldName'=>'orders_id', 'value'=>(int)$SellerOrderId, 'type'=>'integer'),
							array('fieldName'=>'orders_status_id', 'value'=>(int)$order_status, 'type'=>'integer'),
							array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
							array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
							array('fieldName'=>'comments', 'value'=>'' . $status_text, 'type'=>'string')
			);

			$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		}

		return $result;
	}

	function fritesCancelOrderReference($OrderReferenceId, $CancelationReason='', $SellerOrderId=0) {
		global $db;

		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'CancelOrderReference',
			'AmazonOrderReferenceId' => $OrderReferenceId,
			'CancelationReason' => $CancelationReason,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		$order_status = 0;
		if (MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID > 0) {
			$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID;
		}

		if (!isset($result['Error']) && (int)$SellerOrderId && $order_status) {
			$sql = 'UPDATE ' . TABLE_ORDERS . ' SET ';

			if ($order_status) {
				$sql .= "`orders_status` = '".(int)$order_status."' ";
			}

			$sql .= "WHERE `orders_id` = '".(int)$SellerOrderId."'";

			if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
				fritesWriteLog($sql);
			}

			$db->Execute($sql);

			$status_text = 'Amazon action: Cancel';

			$sql_data_array = array(
							array('fieldName'=>'orders_id', 'value'=>(int)$SellerOrderId, 'type'=>'integer'),
							array('fieldName'=>'orders_status_id', 'value'=>(int)$order_status, 'type'=>'integer'),
							array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
							array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
							array('fieldName'=>'comments', 'value'=>'' . $status_text, 'type'=>'string')
			);

			$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		}

		return $result;
	}

	function fritesAuthorize($OrderReferenceId, $AuthorizationAmount, $AuthorizationAmountCurrency, $SellerOrderId, $SellerAuthorizationNote='', $CaptureNow='False', $SoftDescriptor='') {
		global $db;

		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'Authorize',
			'AmazonOrderReferenceId' => $OrderReferenceId,
			'AuthorizationAmount.Amount' => $AuthorizationAmount,
			'AuthorizationAmount.CurrencyCode' => $AuthorizationAmountCurrency,
			'AuthorizationReferenceId' => 'A_'.abs(crc32(HTTP_SERVER)).'_'.time().'_'.$SellerOrderId,
			'SellerAuthorizationNote' => $SellerAuthorizationNote?$SellerAuthorizationNote:HTTP_SERVER.' - #'.$SellerOrderId.' - '.$AuthorizationAmount.$AuthorizationAmountCurrency,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'TransactionTimeout' => '0',
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		$order_status = 0;
		if (MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID > 0
			&& isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'])
			&& $result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'] == 'Open') {
			$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID;
		}

		if (!isset($result['Error']) && isset($result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId']) && (int)$SellerOrderId) {
			$sql = 'UPDATE ' . TABLE_ORDERS . ' SET ';

			if ($order_status) {
				$sql .= "`orders_status` = '".(int)$order_status."', ";
			}

			$sql .= "`frites_order_authorization_id` = '".zen_db_prepare_input($result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'])."' ".
					"WHERE `orders_id` = '".(int)$SellerOrderId."'";

			if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
				fritesWriteLog($sql);
			}

			$db->Execute($sql);

			$status_text = 'Amazon action: Authorize';
			$status_text .= "\nAuthorizationId: ".$result['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];

			if (isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'])) {
				$status_text .= "\nAuthorizationStatus: ".$result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'];
			}

			if (isset($result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode'])) {
				$status_text .= "\nReasonCode: ".$result['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode'];
			}

			$status_text .= "\nAmount: ".$AuthorizationAmount.$AuthorizationAmountCurrency;

			if ($SellerAuthorizationNote) {
				$status_text .= "\nSellerAuthorizationNote: ".$SellerAuthorizationNote;
			}

			$sql_data_array = array(
							array('fieldName'=>'orders_id', 'value'=>(int)$SellerOrderId, 'type'=>'integer'),
							array('fieldName'=>'orders_status_id', 'value'=>(int)$order_status, 'type'=>'integer'),
							array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
							array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
							array('fieldName'=>'comments', 'value'=>'' . $status_text, 'type'=>'string')
			);

			$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		}

		return $result;
	}

	function fritesGetAuthorizationDetails($AuthorizationId) {
		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'GetAuthorizationDetails',
			'AmazonAuthorizationId' => $AuthorizationId,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',			
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		return $result;
	}

	function fritesCapture($AuthorizationId, $CaptureAmount, $CaptureAmountCurrency, $SellerOrderId=0) {
		global $db;

		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'Capture',
			'AmazonAuthorizationId' => $AuthorizationId,
			'CaptureAmount.Amount' => $CaptureAmount,
			'CaptureAmount.CurrencyCode' => $CaptureAmountCurrency,
			'CaptureReferenceId' => 'C_'.abs(crc32(HTTP_SERVER)).'_'.time().'_'.$SellerOrderId,
			'SellerCaptureNote' => HTTP_SERVER.' - #'.$SellerOrderId.' - '.$CaptureAmount.$CaptureAmountCurrency,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		$order_status = 0;
		if (MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID > 0) {
			$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID;
		}

		if (!isset($result['Error']) && isset($result['CaptureResult']['CaptureDetails']['AmazonCaptureId']) && (int)$SellerOrderId) {
			$sql = 'UPDATE ' . TABLE_ORDERS . ' SET ';

			if ($order_status) {
				$sql .= "`orders_status` = '".(int)$order_status."', ";
			}

			$sql .= "`frites_order_capture_id` = '".zen_db_prepare_input($result['CaptureResult']['CaptureDetails']['AmazonCaptureId'])."' ".
					"WHERE `orders_id` = '".(int)$SellerOrderId."'";

			if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
				fritesWriteLog($sql);
			}

			$db->Execute($sql);

			$status_text = 'Amazon action: Capture';
			$status_text .= "\nCaptureId: ".$result['CaptureResult']['CaptureDetails']['AmazonCaptureId'];
			$status_text .= "\nAmount: ".$CaptureAmount.$CaptureAmountCurrency;

			$sql_data_array = array(
							array('fieldName'=>'orders_id', 'value'=>(int)$SellerOrderId, 'type'=>'integer'),
							array('fieldName'=>'orders_status_id', 'value'=>(int)$order_status, 'type'=>'integer'),
							array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
							array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
							array('fieldName'=>'comments', 'value'=>'' . $status_text, 'type'=>'string')
			);

			$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		}

		return $result;
	}

	function fritesGetCaptureDetails($CaptureId) {
		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'GetCaptureDetails',
			'AmazonCaptureId' => $CaptureId,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		return $result;
	}

	function fritesRefund($CaptureId, $RefundAmount, $RefundAmountCurrency, $SellerOrderId=0) {
		global $db;

		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'Refund',
			'AmazonCaptureId' => $CaptureId,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'RefundAmount.Amount' => $RefundAmount,
			'RefundAmount.CurrencyCode' => $RefundAmountCurrency,
			'RefundReferenceId' => 'R_'.abs(crc32(HTTP_SERVER)).'_'.time().'_'.$SellerOrderId,
			'SellerRefundNote' => '',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		$order_status = 0;
		if (MODULE_PAYMENT_FRITES_ORDER_STATUS_REFUNDED_ID > 0) {
			$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_REFUNDED_ID;
		}

		if (!isset($result['Error']) && isset($result['RefundResult']['RefundDetails']['AmazonRefundId']) && (int)$SellerOrderId) {
			$sql = 'UPDATE ' . TABLE_ORDERS . ' SET ';
			if ($order_status) {
				$sql .= "`orders_status` = '".(int)$order_status."', ";
			}
			$sql .= "`frites_order_refund_id` = '".zen_db_prepare_input($result['RefundResult']['RefundDetails']['AmazonRefundId'])."' ".
				"WHERE `orders_id` = '".(int)$SellerOrderId."'";

			if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
				fritesWriteLog($sql);
			}

			$db->Execute($sql);

			$status_text = 'Amazon action: Refund';
			$status_text .= "\nAuthorizationId: ".$result['RefundResult']['RefundDetails']['AmazonRefundId'];
			$status_text .= "\nAmount: ".$RefundAmount.$RefundAmountCurrency;

			$sql_data_array = array(
							array('fieldName'=>'orders_id', 'value'=>(int)$SellerOrderId, 'type'=>'integer'),
							array('fieldName'=>'orders_status_id', 'value'=>(int)$order_status, 'type'=>'integer'),
							array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
							array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
							array('fieldName'=>'comments', 'value'=>'' . $status_text, 'type'=>'string')
			);

			$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
		}

		return $result;
	}

	function fritesGetRefundDetails($RefundId) {
		$links = fritesLinks();

		$params = array(
			'AWSAccessKeyId' => MODULE_PAYMENT_FRITES_ACCESSKEY_ID,
			'Action' => 'GetRefundDetails',
			'AmazonRefundId' => $RefundId,
			'MWSAuthToken' => defined('MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN')?MODULE_PAYMENT_FRITES_MWSAUTH_TOKEN:'',
			'SellerId' => MODULE_PAYMENT_FRITES_MERCHANT_ID,
			'SignatureMethod' => 'HmacSHA256',
			'SignatureVersion' => '2',
			'Timestamp' => gmdate("Y-m-d\TH:i:s\\Z", time()),
			'Version' => '2013-01-01'
		);

		$result = fritesSendSignedCurl($links['mws_link'], $params, $links['mws_payments']);

		$result = fritesXmlToArray($result);

		return $result;
	}

	function fritesAjax() {
		if (isset($_POST['frites_ajax']) && $_POST['frites_ajax'] && isset($_POST['function'])) {

			$order_id = isset($_POST['order_id'])?(int)$_POST['order_id']:0;

			$json = array();

			if ($_POST['function'] == 'GetOrderReferenceDetails' && isset($_POST['OrderReferenceId'])) {
				$json = fritesGetOrderReferenceDetails($_POST['OrderReferenceId']);
			}

			if ($_POST['function'] == 'CloseOrderReference' && isset($_POST['OrderReferenceId'])) {
				$json = fritesCloseOrderReference($_POST['OrderReferenceId'], (isset($_POST['ClosureReason'])?$_POST['ClosureReason']:''), $order_id);
			}

			if ($_POST['function'] == 'CancelOrderReference' && isset($_POST['OrderReferenceId'])) {
				$json = fritesCancelOrderReference($_POST['OrderReferenceId'], (isset($_POST['CancelationReason'])?$_POST['CancelationReason']:''), $order_id);
			}

			if ($_POST['function'] == 'GetAuthorizationDetails' && isset($_POST['AuthorizationId'])) {
				$json = fritesGetAuthorizationDetails($_POST['AuthorizationId']);
			}

			if ($_POST['function'] == 'Authorize' && isset($_POST['OrderReferenceId']) && isset($_POST['AuthorizationAmount']) && isset($_POST['AuthorizationAmountCurrency']) && $order_id) {
				$json = fritesAuthorize($_POST['OrderReferenceId'], $_POST['AuthorizationAmount'], $_POST['AuthorizationAmountCurrency'], $order_id);
			}

			if ($_POST['function'] == 'Capture' && isset($_POST['AuthorizationId']) && isset($_POST['CaptureAmount']) && isset($_POST['CaptureAmountCurrency']) && $order_id) {
				$json = fritesCapture($_POST['AuthorizationId'], $_POST['CaptureAmount'], $_POST['CaptureAmountCurrency'], $order_id);
			}

			if ($_POST['function'] == 'GetCaptureDetails' && isset($_POST['CaptureId'])) {
				$json = fritesGetCaptureDetails($_POST['CaptureId']);
			}

			if ($_POST['function'] == 'Refund' && isset($_POST['CaptureId']) && isset($_POST['RefundAmount']) && isset($_POST['RefundAmountCurrency']) && isset($_POST['order_id'])) {
				$json = fritesRefund($_POST['CaptureId'], $_POST['RefundAmount'], $_POST['RefundAmountCurrency'], $order_id);
			}

			if ($_POST['function'] == 'GetRefundDetails' && isset($_POST['RefundId'])) {
				$json = fritesGetRefundDetails($_POST['RefundId']);
			}

			echo frites_json_encode($json);
			die();
		}
	}

	function fritesParseXmlErrors($respond) {
		$result = '';

		if (isset($respond['Error']['Message'])) {
			$result = 'AMAZON FRITES ERROR: '.$respond['Error']['Message'];
		}

		if (isset($respond['SetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints']['Constraint']['ConstraintID'])) {
			$result = 'AMAZON FRITES ERROR: '.$respond['SetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints']['Constraint']['ConstraintID'] . ' ' .
									$respond['SetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints']['Constraint']['Description'];
		}

		if ($result) {
			fritesWriteLog($result);
		}

		return $result;
	}

	function fritesXmlToArray($xml) {
		$xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $xml);

		$xml_arr = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

		$result = frites_json_decode(frites_json_encode((array)$xml_arr), true);



		return $result;
	}


	
	function fritesSendSignedCurl($host, $params, $uri = '/', $method = 'POST', $protocol = 'https://') {
		$host = strtolower($host);

		$params_str = '';

	

		foreach ($params as $param => $value) {
			if (strlen($value)) {
				$params_str .= ($params_str?'&':'') . $param . '=' . str_replace('%7E', '~',rawurlencode($value));
			}
		}

		$request = $method."\n".$host."\n".$uri."\n".$params_str;

		$signature = base64_encode(hash_hmac('sha256', $request, MODULE_PAYMENT_FRITES_SECRETKEY_ID, true));

		$signature = str_replace('%7E', '~', rawurlencode($signature));

		$link = $protocol.$host.$uri. '?' .$params_str.'&Signature='.$signature;

		$curl = curl_init($link);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		if ($method == 'POST') {
			curl_setopt($curl, CURLOPT_POST, true);
			
			curl_setopt($curl, CURLOPT_POSTFIELDS, '');
		}		

		$result = curl_exec($curl);		

		curl_close($curl);

		if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
			fritesWriteLog($link, "\n".$result);
		}

		return $result;
	}

	function fritesLinks() {
		$links = array(
			'mws_link' => '',
			'mws_payments' => '',
			'mws_sellers' => '',
			'widget_link' => '',
			'login_api_link' => '',
			'profile_api_link' => ''
		);

		$currency = frites_get_currency();

		// TODO fix countries and currences
		if ($currency == 'GBP' && MODULE_PAYMENT_FRITES_HANDLER == 'production') {
			$links = array(
				'mws_link' => 'mws-eu.amazonservices.com',
				'mws_payments' => '/OffAmazonPayments/2013-01-01',
				'mws_sellers' => '/Sellers/2011-07-01',
				'widget_link' => 'https://static-eu.payments-amazon.com/OffAmazonPayments/uk/lpa/js/Widgets.js',
				'login_api_link' => 'https://api.amazon.co.uk',
				'profile_api_link' => 'https://api.amazon.co.uk/user/profile'
			);
		}

		if ($currency == 'GBP' && MODULE_PAYMENT_FRITES_HANDLER == 'sandbox') {
			$links = array(
				'mws_link' => 'mws-eu.amazonservices.com',
				'mws_payments' => '/OffAmazonPayments_Sandbox/2013-01-01',
				'mws_sellers' => '/Sellers/2011-07-01',
				'widget_link' => 'https://static-eu.payments-amazon.com/OffAmazonPayments/uk/sandbox/lpa/js/Widgets.js',
				'login_api_link' => 'https://api.sandbox.amazon.co.uk',
				'profile_api_link' => 'https://api.sandbox.amazon.co.uk/user/profile'
			);
		}

		if ($currency == 'EUR' && MODULE_PAYMENT_FRITES_HANDLER == 'production') {
			$links = array(
				'mws_link' => 'mws-eu.amazonservices.com',
				'mws_payments' => '/OffAmazonPayments/2013-01-01',
				'mws_sellers' => '/Sellers/2011-07-01',
				'widget_link' => 'https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js',
				'login_api_link' => 'https://api.amazon.de',
				'profile_api_link' => 'https://api.amazon.de/user/profile'
			);
		}

		if ($currency == 'EUR' && MODULE_PAYMENT_FRITES_HANDLER == 'sandbox') {
			$links = array(
				'mws_link' => 'mws-eu.amazonservices.com',
				'mws_payments' => '/OffAmazonPayments_Sandbox/2013-01-01',
				'mws_sellers' => '/Sellers/2011-07-01',
				'widget_link' => 'https://static-eu.payments-amazon.com/OffAmazonPayments/eur/sandbox/lpa/js/Widgets.js',
				
				'login_api_link' => 'https://api.sandbox.amazon.de',
				'profile_api_link' => 'https://api.sandbox.amazon.de/user/profile'
			);
		}
		
		define('ENABLE_SSL', 'true');

		$links['checkout_frites_shipping'] = (ENABLE_SSL == 'true' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG .'index.php?main_page=checkout_frites_shipping';
		$links['checkout_frites_payment'] = (ENABLE_SSL == 'true' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG .'index.php?main_page=checkout_frites_payment';
		$links['checkout_frites_login'] = (ENABLE_SSL == 'true' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG .'index.php?main_page=checkout_frites_login';
		$links['checkout_frites_confirmation'] = (ENABLE_SSL == 'true' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG .'index.php?main_page=checkout_frites_confirmation';
		$links['checkout_frites_process'] = (ENABLE_SSL == 'true' ? HTTPS_SERVER : HTTP_SERVER) . DIR_WS_CATALOG .'index.php?main_page=checkout_frites_process';

		return $links;
	}

	function fritesLogout() {
		if (isset($_SESSION['frites_login'])) {
			unset($_SESSION['frites_login']);
		}
		setcookie('amazon_Login_state_cache', '', time()-3600, '/', ''/*$_SERVER['HTTP_HOST']*/, true);
	}

	function fritesLogin() {
		$links = fritesLinks();
		$login_results = array();
		$errors = array();
		$logged = false;

		if (isset($_COOKIE['amazon_Login_state_cache'])) {
			$login_state = frites_json_decode($_COOKIE['amazon_Login_state_cache'], true);
			
			$login_results['login_state'] = $login_state;

			if (!$logged && isset($login_state['client_id'])
				&& isset($_SESSION['frites_login']['login_state']['client_id']) && isset($_SESSION['frites_login']['token_info']['aud'])
				&& isset($_SESSION['frites_login']['token_info']['user_id']) && isset($_SESSION['frites_login']['user_profile']['user_id'])
				&& $_SESSION['frites_login']['login_state']['client_id'] == $_SESSION['frites_login']['token_info']['aud']
				&& $_SESSION['frites_login']['token_info']['aud'] == $login_state['client_id']
				&& $_SESSION['frites_login']['token_info']['user_id'] == $_SESSION['frites_login']['user_profile']['user_id']) {

				$logged = true;
				$login_results = $_SESSION['frites_login'];
			} else {
				if (isset($_SESSION['frites_login'])) {
					unset($_SESSION['frites_login']);
				}
			}

			if (!$logged && isset($login_state['access_token']) && $login_state['access_token']) {
				// verify that the access token belongs to us

				$link = $links['login_api_link'].'/auth/o2/tokeninfo?access_token=' . urlencode($login_state['access_token']);

				if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
					fritesWriteLog($link);
				}

				$c = curl_init($link);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

				$r = curl_exec($c);
				curl_close($c);

				if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
					fritesWriteLog("\n".$r);
				}

				$d = frites_json_decode($r, true);

				$login_results['token_info'] = $d;

				if (isset($d['aud']) && $d['aud'] == MODULE_PAYMENT_FRITES_CLIENT_ID) {
					// the access token belong to us
					// exchange the access token for user profile
					$link = $links['profile_api_link'];

					if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
						fritesWriteLog($link);
					}

					$c = curl_init($link);
					curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $login_state['access_token']));
					curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

					$r = curl_exec($c);
					curl_close($c);

					if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
						fritesWriteLog("\n".$r);
					}

					$d = frites_json_decode($r, true);

					if (isset($d['user_id']) && $d['user_id']) {
						$login_results['user_profile'] = $d;
					} else {
						// error reporting
						$errors[] = 'No profile information';
					}
				} else {
					// error reporting
					$errors[] = 'No or wrong ClientID';
				}
			}
		}

		if (!$errors && isset($login_results['user_profile']) && $login_results['user_profile']) {
			$login_results['status'] = true;
			$_SESSION['frites_login'] = $login_results;
		} else {
			if (isset($_SESSION['frites_login'])) {
				unset($_SESSION['frites_login']);
			}
			$login_results['status'] = false;
		}

		$login_results['errors'] = $errors;

		return $login_results;
	}

	function frites_get_currency() {
		$currency = '';

		if (strtolower(MODULE_PAYMENT_FRITES_CURRENCY) == 'united kingdom') {
			$currency = 'GBP';
		} elseif (strtolower(MODULE_PAYMENT_FRITES_CURRENCY) == 'euro region') {
			$currency = 'EUR';
		}

		return $currency;
	}


	function frites_define_currency() {
		
		$currency = frites_get_currency();

		if (!defined('PAYMENT_FRITES_CURRENCY')) {
			define('PAYMENT_FRITES_CURRENCY', $currency);
		}

		$_SESSION['currency'] = $currency;
	}

	function frites_get_default_language() {
		$frites_allowed_languages = frites_functions::get_allowed_languages();

		$current_code = !empty($_SESSION['languages_code']) ? strtolower($_SESSION['languages_code']) : strtolower(MODULE_PAYMENT_FRITES_REGION);

		if (!in_array($current_code, $frites_allowed_languages)) {
			$current_code = strtolower(MODULE_PAYMENT_FRITES_REGION);
		}

		if (!$current_code) {
			$current_code = 'en';
		}

		if ($current_code == 'uk') $current_code = 'en';

		switch ($current_code) {
			case 'en':
				$current_language = array(
					'link' => 'uk',
					'code' => 'en',
					'locale' => 'en-GB'
				);
				break;
			case 'de':
				$current_language = array(
					'link' => 'de',
					'code' => 'de',
					'locale' => 'de-DE'
				);
				break;
			case 'fr':
				$current_language = array(
					'link' => 'uk/fr_FR',
					'code' => 'fr',
					'locale' => 'fr-FR'
				);
				break;
			case 'it':
				$current_language = array(
					'link' => 'uk/it_IT',
					'code' => 'it',
					'locale' => 'it-IT'
				);
				break;
			case 'es':
				$current_language = array(
					'link' => 'uk/es_ES',
					'code' => 'es',
					'locale' => 'es-ES'
				);
				break;
		}

		return $current_language;
	}

	function frites_get_store_country() {
		$sql = 'SELECT * FROM ' . TABLE_COUNTRIES . ' WHERE countries_id = ' .(int)STORE_COUNTRY;

		$results = frites_db($sql);

		return $results->row;
	}

	function frites_get_frites_countries() {
		$frites_allowed_countries = frites_functions::get_allowed_countries();

		$countries = array();

		$sql = 'SELECT * FROM ' . TABLE_COUNTRIES . " WHERE countries_iso_code_2 IN ('" . implode("','", $frites_allowed_countries) . "')";

		$countries_query = frites_db($sql);

		foreach ($countries_query->rows as $c) {
			$countries[$c['countries_iso_code_2']] = $c;
		}



		return $countries;
	}
	function frites_get_frites_countries_names() {
		
		 if (!$language_id) {
      $language_id = $_SESSION['languages_id'];
    }
		$frites_allowed_countries_names = frites_functions::get_allowed_countries_names();

		$countries_names = array();

		$sql = 'SELECT * FROM ' . TABLE_COUNTRIES_NAME . " WHERE language_id = '" . (int)$language_id . "' AND countries_id IN ('" . implode("','", $frites_allowed_countries_names) . "')";

		$countries_names_query = frites_db($sql);

		foreach ($countries_names_query->rows as $c) {
			$countries_names[$cn['countries_name']] = $cn;
		}

		

		return $countries_names;
	
}

	function frites_get_default_country_id() {
		$frites_allowed_countries = frites_functions::get_allowed_countries();

		$result = frites_get_store_country();

		// check if a store default country is allowed in amazon frites
		if (!empty($result['countries_iso_code_2']) && in_array($result['countries_iso_code_2'], $frites_allowed_countries)) {
			return (int)$result['countries_id'];
		}

		// if no match try to use default amazon frites country
		$frites_countries = frites_get_frites_countries();
		if ($frites_countries && defined('MODULE_PAYMENT_FRITES_REGION') && isset($frites_countries[MODULE_PAYMENT_FRITES_REGION]['countries_id'])) {
			return $frites_countries[MODULE_PAYMENT_FRITES_REGION]['countries_id'];
		}

		// if no match try to use first available amazon country
		$frites_countries = array_shift($frites_countries);
		if (isset($frites_countries['countries_id'])) {
			return $frites_countries['countries_id'];
		}

		return 0;
	}

	function frites_obj2array($obj) {
		return frites_json_decode(frites_json_encode($obj), true);
	}

	function frites_db($sql) {
		global $db;

		$results = new stdClass();
		$results->row = array();
		$results->rows = array();
		$results->count = 0;
		$results->num_rows = 0;

		$row = $db->Execute($sql);

		while (!$row->EOF) {
			if (isset($row->fields)) {
				$results->rows[] = frites_obj2array($row->fields);
			}

			$row->MoveNext();
		}

		$results->count = count($results->rows);
		$results->num_rows = $results->count;
		$results->row = !empty($results->rows) ? reset($results->rows) : array();

		return $results;
	}

	function frites_json_decode($json, $assoc = false, $depth = 512, $options = 0) {
		$json = frites_string_decode($json);

		if (PHP_VERSION_ID < 50300) {
			$json = stripcslashes($json);
			$result = json_decode($json, $assoc);
		} elseif (PHP_VERSION_ID < 50400) {
			$json = stripcslashes($json);
			$result = json_decode($json, $assoc, $depth);
		} elseif (PHP_VERSION_ID < 50500) {
			$result = json_decode($json, $assoc, $depth, $options);
		} else {
			$result = json_decode($json, $assoc, $depth, $options);
		}

		return $result;
	}

	function frites_json_encode($value, $options = 0, $depth = 512) {
		if (PHP_VERSION_ID < 50300) {
			$result = json_encode($value);
		} elseif (PHP_VERSION_ID < 50400) {
			$result = json_encode($value, $options);
		} elseif (PHP_VERSION_ID < 50500) {
			$result = json_encode($value, $options);
		} else {
			$result = json_encode($value, $options, $depth);
		}
		

		return $result;
	}

	function frites_string_decode($str) {
		

		$i=65535;
		while ($i > 0) {
			$hex = dechex($i);
			$str = str_replace("\u$hex", "&#$i;", $str);
			$i--;
		}

		

		return $str;
	}

	function frites_string_encode($str) {
		return $str;
	}


	// Send as many paramters of any type here as you wish - they all will be written to log
	function fritesWriteLog()
	{
		// unique name of file with hash to make it impossible to download by hackers
		$logfilename = 'frites_' . date('Y-m-d') . '_' . abs(crc32(date('Y-m-d').__FUNCTION__)) . '.log';

		$logdir = (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/frites/logs/';

		
		if (!is_dir($logdir)) {
			mkdir($logdir, 0777,true);
			chmod($logdir, 0777);
		}

		$numargs = func_num_args();
		$arg_list = func_get_args();

		$fhandle = fopen($logdir.$logfilename, 'ab');
		fwrite($fhandle, date('Y-m-d H:i:s').': *****************************************************************************************************************'."\n");

		// save a backtrace of called functions excluding this function
		$backtrace = debug_backtrace();
		if (!empty($backtrace[0])) unset($backtrace[0]);
		fwrite($fhandle, print_r($backtrace, true)."\n");

		for ($i = 0; $i < $numargs; $i++) {
			if ($numargs > 1) {
				fwrite($fhandle, "\n".$i.': ');
			}
			fwrite($fhandle, print_r($arg_list[$i], true)."\n\n");
		}

		fclose($fhandle);
	}


	
	function fritesWriteLog_oppo($message) {
		$logfilename = '/frites_' . date('Y-m-d') . '.log';

		if (defined('DIR_FS_LOGS')) {
			$logdir = DIR_FS_LOGS;
		} else {
			$logdir = 'includes/modules/payment/frites/logs';
		}

		if (!is_dir($logdir)) {
			@mkdir($logdir,0777,true);
			@chmod($logDir,0777);
		}

		$debugFile=$logdir.$logfilename;

		if (!is_writable($debugFile)) {
			return false;
		}

		$var=$message;

		$bt = debug_backtrace();
		$info = pathinfo($bt[0]['file']);

		$output = 'File: ' . $info['basename'] . PHP_EOL . 'Line: ' . $bt[0]['line'] . PHP_EOL . 'Time: ' . date('Y/m/d H:i:s') . PHP_EOL . 'Type: ' . gettype($var) . PHP_EOL . PHP_EOL;

		if (is_array($var) || is_bool($var) || is_object($var)) {
			$output .= var_export($var, true);
		} else {
			$output .= $var;
		}

		$output .= PHP_EOL . PHP_EOL . '-------------------------------------------------------' . PHP_EOL . PHP_EOL;

		file_put_contents($debugFile, $output, FILE_APPEND);


		return $logfilename;
	}
