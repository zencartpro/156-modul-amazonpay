<?php
/**
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: header_php.php for Datenweitergabe an Versandunternehmen 2019-07-21 08:49:16Z webchills $
 */
// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_PAYMENT');
include_once (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/frites/frites_functions.php';

include_once zen_get_file_directory(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'].'/','checkout_payment.php', 'false');

	$frites_logged = fritesLogin();

	$links = fritesLinks();

// Check if customer passed all checkout pages
if (empty($_SESSION['frites']['shipping_selected'])) {
	zen_redirect($links['checkout_frites_shipping']);
}

// if there is nothing in the customers cart, redirect them to the shopping cart page
if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}

if (zen_not_null($_POST['conditions'])) {
     $_SESSION['conditions'] = 'accepted';
}
    
// if no shipping method has been selected, redirect the customer to the shipping method selection page
if ((!isset($_SESSION['shipping']) || (!$_SESSION['shipping'])) && $_SESSION['cart']->get_content_type() != 'virtual') {
  zen_redirect($links['checkout_frites_shipping']);
}
if (isset($_SESSION['shipping']['id']) && $_SESSION['shipping']['id'] == 'free_free' && $_SESSION['cart']->get_content_type() != 'virtual' && defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true' && defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER') && $_SESSION['cart']->show_total() < MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
  zen_redirect($links['checkout_frites_shipping']);
}

// avoid hack attempts during the checkout procedure by checking the internal cartID
if (isset($_SESSION['cart']->cartID) && $_SESSION['cartID']) {
  if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
  zen_redirect($links['checkout_frites_shipping']);
}
}

// Stock Check
if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
  $products = $_SESSION['cart']->get_products();
  for ($i=0, $n=sizeof($products); $i<$n; $i++) {
    $qtyAvailable = zen_get_products_stock($products[$i]['id']);
    // compare against product inventory, and against mixed=YES
    if ($qtyAvailable - $products[$i]['quantity'] < 0 || $qtyAvailable - $_SESSION['cart']->in_cart_mixed($products[$i]['id']) < 0) {
      zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
      break;
    }
  }
}

// get coupon code
if (!empty($_SESSION['cc_id'])) {
  $discount_coupon_query = "SELECT coupon_code
                            FROM " . TABLE_COUPONS . "
                            WHERE coupon_id = :couponID";

  $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
  $discount_coupon = $db->Execute($discount_coupon_query);
}

// if no billing destination address was selected, use the customers own address as default
if (empty($_SESSION['billto'])) {
  $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
} else {
  // verify the selected billing address
  $check_address_query = "SELECT count(*) AS total FROM " . TABLE_ADDRESS_BOOK . "
                          WHERE customers_id = :customersID
                          AND address_book_id = :addressBookID";

  $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
  $check_address_query = $db->bindVars($check_address_query, ':addressBookID', $_SESSION['billto'], 'integer');
  $check_address = $db->Execute($check_address_query);

  if ($check_address->fields['total'] != '1') {
    $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
    $_SESSION['payment'] = '';
  }
}

require_once DIR_WS_CLASSES . 'order.php';
$order = new order;
// Load the selected shipping module(needed to calculate tax correctly)
require_once DIR_WS_CLASSES . 'shipping.php';
$shipping_modules = new shipping($_SESSION['shipping']);
require_once DIR_WS_CLASSES . 'order_total.php';
$order_total_modules = new order_total;
$order_total_modules->collect_posts();
$order_total_modules->pre_confirmation_check();

if (zen_not_null($_POST['comments'])) {
		$_SESSION['comments'] = zen_db_prepare_input (zen_clean_html($_POST['comments']));
	}

	$comments = $_SESSION['comments'];


if (isset($_SESSION['carrier'])) {
    $carrier = $_SESSION['carrier'];
  }


if (zen_not_null($_POST['carrier'])) {
      $_SESSION['carrier'] = zen_db_prepare_input($_POST['carrier']);
    }
    $carrier = $_SESSION['carrier'];
$total_weight = $_SESSION['cart']->show_weight();
$total_count = $_SESSION['cart']->count_contents();

// set payment
$_SESSION['payment'] = 'frites';

if (isset($_GET['payment_error']) && is_object(${$_GET['payment_error']}) && ($error = ${$_GET['payment_error']}->get_error())) {
  $messageStack->add('checkout_payment', $error['error'], 'error');
}

require_once DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

if (isset($_POST['action']) && $_POST['action'] == 'submit') {
	if (isset($_POST['frites']['OrderReferenceId']) && $_POST['frites']['OrderReferenceId'] /*&& !empty($_SESSION['frites']['shipping_selected'])*/) {
		$_SESSION['frites'] = $_POST['frites'];
		$_SESSION['frites']['shipping_selected'] = 1;
		$_SESSION['frites']['payment_selected'] = 1;
	}

	zen_redirect($links['checkout_frites_confirmation']);
}

$breadcrumb->add(NAVBAR_TITLE_1, $links['checkout_frites_shipping']);
$breadcrumb->add(NAVBAR_TITLE_2);

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_PAYMENT');