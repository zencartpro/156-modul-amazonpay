<?php
/**
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: header_php.php 2019-07-20 19:29:16Z webchills $
 */
// This should be first line of the script:
  
  $zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_PROCESS_FRITES');

  include_once zen_get_file_directory(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'].'/','checkout_process.php', 'false');


  require DIR_WS_MODULES . zen_get_module_directory('checkout_frites_process.php');

// load the after_process function from the payment modules
  $payment_modules->after_process();

  $_SESSION['cart']->reset(true);

// unregister session variables used during checkout
  unset($_SESSION['sendto']);
  unset($_SESSION['billto']);
  unset($_SESSION['shipping']);
  unset($_SESSION['payment']);
  unset($_SESSION['comments']);
  $order_total_modules->clear_posts();//ICW ADDED FOR CREDIT CLASS SYSTEM

  // This should be before the zen_redirect:
  $zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_PROCESS');

  zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, (isset($_GET['action']) && $_GET['action'] == 'confirm' ? 'action=confirm' : ''), 'SSL'));

  require DIR_WS_INCLUDES . 'application_bottom.php';
