<?php
/**
 * module to process a completed Amazon checkout
 *
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: checkout_frites_process.php 2020-05-06 09:44:20Z webchills $
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}
$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEGIN');

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

include_once (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/frites/frites_functions.php';
$frites_logged = fritesLogin();
$links = fritesLinks();

// if the customer is not logged on, redirect them to the time out page
  if (!$_SESSION['customer_id']) {
    zen_redirect(zen_href_link(FILENAME_TIME_OUT));
  } else {
    // validate customer
    if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
      $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_SHIPPING));
      zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
  }


// BEGIN CC SLAM PREVENTION
if (!isset($_SESSION['payment_attempt'])) $_SESSION['payment_attempt'] = 0;
$_SESSION['payment_attempt']++;
$zco_notifier->notify('NOTIFY_CHECKOUT_SLAMMING_ALERT');
if ($_SESSION['payment_attempt'] > 3) {
  $zco_notifier->notify('NOTIFY_CHECKOUT_SLAMMING_LOCKOUT');
  $_SESSION['cart']->reset(TRUE);
  zen_session_destroy();
  zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}
// END CC SLAM PREVENTION

if (!isset($credit_covers)) $credit_covers = FALSE;

// load selected payment module
require DIR_WS_CLASSES . 'payment.php';
$payment_modules = new payment($_SESSION['payment']);
// load the selected shipping module
require DIR_WS_CLASSES . 'shipping.php';
$shipping_modules = new shipping($_SESSION['shipping']);

require DIR_WS_CLASSES . 'order.php';
$order = new order;

// prevent 0-entry orders from being generated/spoofed
if (sizeof($order->products) < 1) {
  zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
}

//Check Authorization status reason code and do not create order if it fails

// InvalidPaymentMethod
				
if (isset($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode']) && strpos($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode'],'InvalidPaymentMethod') == true) {
						$messageStack->add_session('checkout', $errors['AuthorizationStatus'].MODULE_PAYMENT_FRITES_TEXT_ERROR_SELECT_DIFFERENT_PAYMENT, 'error');
						zen_redirect($links['checkout_frites_payment']);					
				}

// AmazonRejected				
if (isset($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode']) && strpos($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode'],'AmazonRejected') == true) {
						$messageStack->add_session('checkout', $errors['AuthorizationStatus'].MODULE_PAYMENT_FRITES_TEXT_ERROR_SELECT_DIFFERENT_PAYMENT, 'error');
						zen_redirect($links['checkout_frites_payment']);					
				}
// TransactionTimedOut		
if (isset($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode']) && strpos($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode'],'TransactionTimedOut') == true) {
						$messageStack->add_session('checkout', $errors['AuthorizationStatus'].MODULE_PAYMENT_FRITES_TEXT_ERROR_SELECT_DIFFERENT_PAYMENT, 'error');
						zen_redirect($links['checkout_frites_payment']);					
				}
// ProcessingFailure
if (isset($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode']) && strpos($_SESSION['amazon_pay_errors']['AuthorizationStatus']['ReasonCode'],'ProcessingFailure') == true) {
						$messageStack->add_session('checkout', $errors['AuthorizationStatus'].MODULE_PAYMENT_FRITES_TEXT_ERROR_SELECT_DIFFERENT_PAYMENT, 'error');
						zen_redirect($links['checkout_frites_payment']);					
				}

require DIR_WS_CLASSES . 'order_total.php';
$order_total_modules = new order_total;

$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PRE_CONFIRMATION_CHECK');
$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_BEFORE_ORDER_TOTALS_PROCESS');
$order_totals = $order_total_modules->process();
$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS');

if (!isset($_SESSION['payment']) && $credit_covers === FALSE) {
  zen_redirect(zen_href_link(FILENAME_DEFAULT));
}


if (isset($_SESSION['frites']['insert_id']) && $_SESSION['frites']['insert_id']) {
	$insert_id = $_SESSION['frites']['insert_id'];
	// load the before_process function from the payment modules
	$payment_modules->before_process();
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_BEFOREPROCESS');
	//$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE');
	$payment_modules->after_order_create($insert_id);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_AFTER_ORDER_CREATE');
	$_SESSION['order_number_created'] = $insert_id;
} else {
	// load the before_process function from the payment modules
	$payment_modules->before_process();
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_BEFOREPROCESS');
	// create the order record
	$insert_id = $order->create($order_totals, 2);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE');
	$payment_modules->after_order_create($insert_id);
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_PAYMENT_MODULES_AFTER_ORDER_CREATE');
	// store the product info to the order
	$order->create_add_products($insert_id);
	$_SESSION['order_number_created'] = $insert_id;
	$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_CREATE_ADD_PRODUCTS');
	//do NOT send email notifications as address data are not correct yet, send email functions moved to frites.php
	//$order->send_order_email($insert_id, 2);
	//$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_SEND_ORDER_EMAIL');
}

// clear slamming protection since payment was accepted
if (isset($_SESSION['payment_attempt'])) unset($_SESSION['payment_attempt']);

/**
 * Calculate order amount for display purposes on checkout-success page as well as adword campaigns etc
 * Takes the product subtotal and subtracts all credits from it
 */
  $ototal = $order_subtotal = $credits_applied = 0;
  for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
    if ($order_totals[$i]['code'] == 'ot_subtotal') $order_subtotal = $order_totals[$i]['value'];
    if ($$order_totals[$i]['code']->credit_class == true) $credits_applied += $order_totals[$i]['value'];
    if ($order_totals[$i]['code'] == 'ot_total') $ototal = $order_totals[$i]['value'];
    if ($order_totals[$i]['code'] == 'ot_tax') $otax = $order_totals[$i]['value'];
    if ($order_totals[$i]['code'] == 'ot_shipping') $oshipping = $order_totals[$i]['value'];
  }
  $commissionable_order = ($order_subtotal - $credits_applied);
  $commissionable_order_formatted = $currencies->format($commissionable_order);
  $_SESSION['order_summary']['order_number'] = $insert_id;
  $_SESSION['order_summary']['order_subtotal'] = $order_subtotal;
  $_SESSION['order_summary']['credits_applied'] = $credits_applied;
  $_SESSION['order_summary']['order_total'] = $ototal;
  $_SESSION['order_summary']['commissionable_order'] = $commissionable_order;
  $_SESSION['order_summary']['commissionable_order_formatted'] = $commissionable_order_formatted;
  $_SESSION['order_summary']['coupon_code'] = $order->info['coupon_code'];
  $_SESSION['order_summary']['currency_code'] = $order->info['currency'];
  $_SESSION['order_summary']['currency_value'] = $order->info['currency_value'];
  $_SESSION['order_summary']['payment_module_code'] = $order->info['payment_module_code'];
  $_SESSION['order_summary']['shipping_method'] = $order->info['shipping_method'];
  $_SESSION['order_summary']['orders_status'] = $order->info['orders_status'];
  $_SESSION['order_summary']['tax'] = $otax;
  $_SESSION['order_summary']['shipping'] = $oshipping;
  $zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_HANDLE_AFFILIATES');