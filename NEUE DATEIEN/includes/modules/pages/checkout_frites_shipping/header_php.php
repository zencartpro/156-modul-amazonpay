<?php
/**
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: header_php.php 2019-07-20 20:57:16Z webchills $
 */
 
// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_SHIPPING');

include_once (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/frites/frites_functions.php';
include_once zen_get_file_directory(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'] . '/', 'checkout_shipping.php', 'false');

global $order;

$frites_logged = fritesLogin();
$links = fritesLinks();

if (isset($_POST['function']) && $_POST['function'] == 'GetOrderReferenceDetails' && isset($_POST['OrderReferenceId'])) {
	$json = fritesGetOrderReferenceDetails($_POST['OrderReferenceId']);
	echo frites_json_encode($json);
	die();
}

if (isset($_POST['function']) && $_POST['function'] == 'GetOrderDestination' && isset($_POST['OrderReferenceId'])) {

	//echo '<pre>'.__METHOD__.' ['.__LINE__.']: '; print_r($_POST); echo '</pre>';die();

	if (isset($_POST['shipping']) && $_POST['shipping'] != 'undefined') {
		$_SESSION['shipping']['id'] = $_POST['shipping'];
	}
	$json = fritesGetOrderReferenceDetails($_POST['OrderReferenceId']);
	$destination = !empty($json['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination']) ? $json['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Destination'] : array();
//echo '<pre>'.__METHOD__.': '; print_r($json); echo '</pre>';

	// update delivery address in db
	$entry_country_id = 0;
	$entry_country_name = '';
	$entry_zone_id = 0;
	$entry_zone_name = '';

	$response = new stdClass();

	if (isset($destination['PhysicalDestination']['CountryCode'])) {
		$country_iso_code_2 = $destination['PhysicalDestination']['CountryCode'];
		$frites_city = !empty($destination['PhysicalDestination']['City']) ? $destination['PhysicalDestination']['City'] : '';
		$frites_zip = !empty($destination['PhysicalDestination']['PostalCode']) ? $destination['PhysicalDestination']['PostalCode'] : '';
		$frites_state = !empty($destination['PhysicalDestination']['StateOrRegion']) ? $destination['PhysicalDestination']['StateOrRegion'] : '';
if (!$language_id) {
      $language_id = $_SESSION['languages_id'];
    }
		$countries_query = 'select c.countries_id, c.countries_iso_code_2, cn.language_id, cn.countries_name from ' . TABLE_COUNTRIES . ' c, ' . TABLE_COUNTRIES_NAME . " cn where cn.language_id = '" . (int)$language_id . "' AND c.countries_iso_code_2 = '" . $country_iso_code_2 . "'";
		$countries = $db->Execute($countries_query);

		if (!empty($_SESSION['customer_id']) && empty($_SESSION['sendto'])) {
			$customer_query = 'SELECT `customers_default_address_id` FROM ' . TABLE_CUSTOMERS . " WHERE customers_id = '" . (int)$_SESSION['customer_id'] . "'";
			$customer = $db->Execute($customer_query);
			$_SESSION['sendto'] = (int)$customer->fields['customers_default_address_id'];
		}

		$entry_country_id = !empty($countries->fields['countries_id']) ? (int)$countries->fields['countries_id'] : 0;
		$entry_country_name = !empty($countries->fields['countries_id']) ? (int)$countries->fields['countries_id'] : '';
//echo '<pre>'.__METHOD__.' ['.__LINE__.']: '; print_r($frites_city); echo '</pre>';
		if (!empty($frites_city) && !empty($countries->fields['countries_id'])) {
			$entry_country_id = (int)$countries->fields['countries_id'];
			$entry_country_name = $countries->fields['countries_name'];
			$states = frites_db('SELECT zone_id, zone_name FROM ' .TABLE_ZONES. ' WHERE zone_country_id = ' .(int)$entry_country_id." AND LOWER(zone_name) = LOWER('".zen_db_prepare_input($frites_city)."')");
			if (!empty($states->row['zone_id'])) {
				$entry_zone_name = $states->row['zone_name'];
				$entry_zone_id = (int)$states->row['zone_id'];
			}
		}
//echo '<pre>'.__METHOD__.' ['.__LINE__.']: '; print_r($entry_country_id); echo '</pre>';die();
		if ($entry_country_id)
		{
			$shipping_address_query = 'UPDATE ' . TABLE_ADDRESS_BOOK . ' SET
										entry_country_id = ' . (int)$entry_country_id . ",
										entry_city = '" . zen_db_prepare_input($frites_city) . "',
										entry_state = '" . zen_db_prepare_input($frites_state) . "',
										entry_postcode = '" . zen_db_prepare_input($frites_zip) . "',
										entry_zone_id = ".(int)$entry_zone_id;

			$shipping_address_query .= " where customers_id = '" . (int)$_SESSION['customer_id'] . "'
									";

			fritesWriteLog('checkout_frites_shipping::GetOrderDestination POST', $_POST, $json, $shipping_address_query);

			$shipping_address = $db->Execute($shipping_address_query);
		}
	}

	//$response->status = frites_functions::check_status((int)$entry_country_id, (int)$entry_zone_id);
	if (((int)$entry_country_id || (int)$entry_zone_id) && (int)MODULE_PAYMENT_FRITES_ZONE) {
		$response->status = frites_functions::check_status((int)$entry_country_id, (int)$entry_zone_id);
	} elseif (!(int)MODULE_PAYMENT_FRITES_ZONE) {
		$response->status = true;
	} else {
		$response->status = false;
	}

	if (!$response->status) {
		$response->error = MODULE_PAYMENT_FRITES_ZONE_DENIED.' ('.($entry_country_name ? $entry_country_name : $country_iso_code_2).', '.$frites_city.', '.$frites_zip.')';
	}
	echo frites_json_encode($response);

	die();
}


// if there is nothing in the customers cart, redirect them to the shopping cart page
if ($_SESSION['cart']->count_contents() <= 0) {
	zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}

if (!$frites_logged['status']) {
	foreach ($frites_logged['errors'] as $message) {
		$messageStack->add('checkout', 'AMAZON: ' . $message, 'error');
	}

	zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
}
// Validate Cart for checkout
$_SESSION['valid_to_checkout'] = true;
$_SESSION['cart']->get_products(true);
if ($_SESSION['valid_to_checkout'] == false) {
	$messageStack->add('header', ERROR_CART_UPDATE, 'error');
	zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
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
	// if no shipping destination address was selected, use the customers own address as default
  if (empty($_SESSION['sendto'])) {
    $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
  } else {
// verify the selected shipping address
    $check_address_query = "SELECT count(*) AS total
                            FROM   " . TABLE_ADDRESS_BOOK . "
                            WHERE  customers_id = :customersID
                            AND    address_book_id = :addressBookID";

    $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $check_address_query = $db->bindVars($check_address_query, ':addressBookID', $_SESSION['sendto'], 'integer');
    $check_address = $db->Execute($check_address_query);

    if ($check_address->fields['total'] != '1') {
      $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
      unset($_SESSION['shipping']);
	}
}

if (isset($_POST['frites']['OrderReferenceId']) && $_POST['frites']['OrderReferenceId']) {
	$_SESSION['frites'] = $_POST['frites'];
	$_SESSION['frites']['shipping_selected'] = 1;
}


// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
if (isset($_SESSION['cart']->cartID)) {
	if (!isset($_SESSION['cartID']) || $_SESSION['cart']->cartID != $_SESSION['cartID']) {
		$_SESSION['cartID'] = $_SESSION['cart']->cartID;
	}
} else {
	zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

// TODO fix countries and currencies
//Set order currency
frites_define_currency();

if (isset($_SESSION['comments'])) {
	$comments = $_SESSION['comments'];
}

  require DIR_WS_CLASSES . 'order.php';
  $order = new order;

// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
if (isset($_SESSION['cart']->cartID)) {
	if (!isset($_SESSION['cartID']) || $_SESSION['cart']->cartID != $_SESSION['cartID']) {
		$_SESSION['cartID'] = $_SESSION['cart']->cartID;
	}
} else {
	zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}

// if the order contains only virtual products, forward the customer to the normal checkout as only physical goods are supported by this Amazon Pay module
  if ($order->content_type == 'virtual') {
    
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
  }

$total_weight = $_SESSION['cart']->show_weight();
$total_count = $_SESSION['cart']->count_contents();



// load all enabled shipping modules
require DIR_WS_CLASSES . 'shipping.php';
$shipping_modules = new shipping;

$pass = true;
if (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true')) {
	$pass = false;
	switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
		case 'national':
			if ($order->delivery['country_id'] == STORE_COUNTRY) {
				$pass = true;
			}
			break;
		case 'international':
			if ($order->delivery['country_id'] != STORE_COUNTRY) {
				$pass = true;
			}
			break;
		case 'both':
			$pass = true;
			break;
	}
	$free_shipping = false;
	if (($pass == true) && ($_SESSION['cart']->show_total() >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) {
		$free_shipping = true;
	}
} else {
	$free_shipping = false;
}


// process the selected shipping method
if (isset($_POST['action']) && ($_POST['action'] == 'process') && isset($_POST['frites']['OrderReferenceId']) && $_POST['frites']['OrderReferenceId']) {


	if (zen_not_null($_POST['comments'])) {
		$_SESSION['comments'] = zen_db_prepare_input (zen_clean_html($_POST['comments']));
	}

	$comments = $_SESSION['comments'];
	$quote = array();

	$shipping = array();

	if ((zen_count_shipping_modules() > 0) || ($free_shipping == true)) {
		if (isset($_POST['shipping']) && strpos($_POST['shipping'], '_')) {
			/**
			 * check to be sure submitted data hasn't been tampered with
			 */
			if ($_POST['shipping'] == 'free_free' && ($order->content_type != 'virtual' && !$pass)) {
				$quote['error'] = 'Invalid input. Please make another selection.';
			} else {
				$_SESSION['shipping'] = $_POST['shipping'];
			}

			list($module, $method) = explode('_', $_SESSION['shipping']);

			if (is_object($$module) || ($_SESSION['shipping'] == 'free_free')) {
				if ($_SESSION['shipping'] == 'free_free') {
					$quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
					$quote[0]['methods'][0]['cost'] = '0';
				} else {
					$quote = $shipping_modules->quote($method, $module);
				}
				if (isset($quote['error'])) {
					$_SESSION['shipping'] = '';
				} else {
					if (isset($quote[0]['methods'][0]['title']) && isset($quote[0]['methods'][0]['cost'])) {
						$_SESSION['shipping'] = array('id' => $_SESSION['shipping'], 'title' => ($free_shipping == true) ? $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')', 'cost' => $quote[0]['methods'][0]['cost']);

						$_SESSION['frites']['shipping_selected'] = 1;
						zen_redirect($links['checkout_frites_payment']);
					}
				}
			} else {
				$_SESSION['shipping'] = false;
			}
		} else {
			if (!$_SESSION['shipping'] || ($_SESSION['shipping'] && ($_SESSION['shipping'] == false) && (zen_count_shipping_modules() > 1)))
				$_SESSION['shipping'] = $shipping_modules->cheapest();
			//$_SESSION['shipping'] = false;
			$_SESSION['frites']['shipping_selected'] = 1;
			zen_redirect($links['checkout_frites_payment']);
		}
	} else {
		$_SESSION['shipping'] = false;
		$_SESSION['frites']['shipping_selected'] = 1;
		zen_redirect($links['checkout_frites_payment']);
	}
}



// get all available shipping quotes
$quotes = $shipping_modules->quote();

  // check that the currently selected shipping method is still valid (in case a zone restriction has disabled it, etc)
  if (isset($_SESSION['shipping'])) {
    $checklist = array();
    foreach ($quotes as $key=>$val) {
      if ($val['methods'] != '') {
        foreach($val['methods'] as $key2=>$method) {
          $checklist[] = $val['id'] . '_' . $method['id'];
        }
      } else {
        // skip
      }
    }
    $checkval = $_SESSION['shipping']['id'];
    if (!in_array($checkval, $checklist)) {
      $messageStack->add('checkout_shipping', ERROR_PLEASE_RESELECT_SHIPPING_METHOD, 'error');
      unset($_SESSION['shipping']); // Prepare $_SESSION to determine lowest available price/force a default selection mc12345678 2018-04-03
    }
  }
// if no shipping method has been selected, automatically select the cheapest method.
// If the module's status was changed when none were available, to save on implementing
// a javascript force-selection method, also automatically select the cheapest shipping
// method if more than one module is now enabled
  if ((!isset($_SESSION['shipping']) || (!isset($_SESSION['shipping']['id']) || $_SESSION['shipping']['id'] == '') && zen_count_shipping_modules() >= 1)) $_SESSION['shipping'] = $shipping_modules->cheapest();





$breadcrumb->add(NAVBAR_TITLE_1, zen_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2);

// This should be last line of the script:
  $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_SHIPPING');
