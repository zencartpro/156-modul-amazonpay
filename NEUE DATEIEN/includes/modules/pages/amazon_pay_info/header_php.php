<?php
/**
* @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
* @copyright Copyright 2003-2014 Webiprog
* @copyright Copyright 2003-2019 Zen Cart Development Team
* @copyright Portions Copyright 2003 osCommerce
* @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
* @version $Id: header_php.php 2019-05-24 16:29:16Z webchills $
*/

$_SESSION['navigation']->remove_current_page();
require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));

// include template specific file name defines
$define_page = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/html_includes/', FILENAME_DEFINE_AMAZON_PAY_INFO, 'false');
$breadcrumb->add(NAVBAR_TITLE);