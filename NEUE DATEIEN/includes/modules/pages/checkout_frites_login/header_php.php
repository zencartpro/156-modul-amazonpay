<?php
/**
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: header_php.php 2019-07-20 21:29:16Z webchills $
 */
// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_LOGIN');

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
if ($session_started == false) {
    zen_redirect(zen_href_link(FILENAME_COOKIE_USAGE));
}
include_once (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/frites/frites_functions.php';

$links = fritesLinks();

$frites_logged = fritesLogin();



$loginAuthorized = false;

if (!empty($frites_logged['status']) && isset($frites_logged['user_profile']['email']) && $frites_logged['user_profile']['email'] && isset($frites_logged['user_profile']['user_id'])
) {

    // define
    $customer_email = $frites_logged['user_profile']['email'];
    $frites_md = md5($frites_logged['user_profile']['user_id']);

    // Check if email exists
    $check_customer_query = 'SELECT *
						   FROM ' . TABLE_CUSTOMERS . '
						   WHERE customers_email_address = :emailAddress';

    $check_customer_query = $db->bindVars($check_customer_query, ':emailAddress', $customer_email, 'string');
    $check_customer = $db->Execute($check_customer_query);

    //fixed by oppo webiprog.com  (oleg@webiprog.com)
// set the frites user_profile id If not logged in via Amazon
    if ($check_customer->RecordCount() && isset($check_customer->fields['customers_frites_userid']) && $check_customer->fields['customers_frites_userid'] == '' && $check_customer->fields['customers_email_address'] == $frites_logged['user_profile']['email'] && !empty($check_customer->fields['customers_id']) && is_numeric($check_customer->fields['customers_id'])
    ) {

        $customer_name = explode(' ', $frites_logged['user_profile']['name'], 2);

        // set the amazon user_profile id If not logged in via Amazon and update name
        $sql_customer_id = intval($check_customer->fields['customers_id']);
        $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
					SET 
					customers_frites_userid = :fritesUserid,
					customers_firstname = :fritesFirstName, 
					customers_lastname = :fritesLastName
					WHERE customers_id = :custID';
        $sql = $db->bindVars($sql, ':fritesUserid', $frites_md, 'string');
        $sql = $db->bindVars($sql, ':fritesFirstName', (!empty($customer_name[0]) ? $customer_name[0] : ''), 'string');
        $sql = $db->bindVars($sql, ':fritesLastName', (!empty($customer_name[1]) ? $customer_name[1] : ''), 'string');
        $sql = $db->bindVars($sql, ':custID', $sql_customer_id, 'integer');
        $db->Execute($sql);

    }


    if ($check_customer->RecordCount() && isset($check_customer->fields['customers_authorization']) && $check_customer->fields['customers_authorization'] == '4') {
        // this account is banned
        $zco_notifier->notify('NOTIFY_LOGIN_BANNED');
        $messageStack->add('login', TEXT_LOGIN_BANNED);
    } elseif ($check_customer->RecordCount() && !isset($check_customer->fields['customers_frites_userid'])) {
        //account exists but was not registered by amazon || user_id is wrong || fieldname `customers_frites_userid` not exists
        //redirect to original login
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));

    } elseif (!$check_customer->RecordCount()) {
        $new_acct_notify = true;
        $customer_name = explode(' ', $frites_logged['user_profile']['name'], 2);

        $acct_exists = false;
        // Create an account, if the account does not exist
        if (!$acct_exists) {
            // debug
            if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
                fritesWriteLog('Creating account for ' . $frites_logged['user_profile']['email']);
            }

            // Generate a random 12-char password
            $password = zen_create_random_value(12);

            $sql_data_array = array();

            // set the customer information in the array for the table insertion
            $sql_data_array = array('customers_firstname' => isset($customer_name[0]) ? trim($customer_name[0]) : '', 'customers_lastname' => isset($customer_name[1]) ? trim($customer_name[1]) : '', 'customers_email_address' => $frites_logged['user_profile']['email'], 'customers_email_format' => ACCOUNT_EMAIL_PREFERENCE == '1' ? 'HTML' : 'TEXT', 'customers_telephone' => '', 'customers_fax' => '', 'customers_gender' => 'm', 'customers_newsletter' => '0', 'customers_password' => zen_encrypt_password($password), 'customers_frites_userid' => $frites_md);

            // insert the data
            $result = zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);

            // grab the customer_id (last insert id)
            $customer_id = $db->Insert_ID();

            // set the Guest customer ID -- for PWA purposes
            $_SESSION['customer_guest_id'] = $customer_id;

			// TODO fix country codes and replace with currencies
			$country_id = frites_get_default_country_id();
            $state_id = 0;

            
            // set the customer address information in the array for the table insertion
            $sql_data_array = array('customers_id' => $customer_id, 'entry_gender' => 'm', 'entry_firstname' => isset($customer_name[0]) ? trim($customer_name[0]) : '', 'entry_lastname' => isset($customer_name[1]) ? trim($customer_name[1]) : '', 'entry_street_address' => '', 'entry_suburb' => '', 'entry_city' => '', 'entry_zone_id' => $state_id, 'entry_postcode' => $frites_logged['user_profile']['postal_code'], 'entry_country_id' => $country_id);

            // insert the data
            zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

            // grab the address_id (last insert id)
            $address_id = $db->Insert_ID();

            // set the address id lookup for the customer
            $sql = 'UPDATE ' . TABLE_CUSTOMERS . '
					SET customers_default_address_id = :addrID
					WHERE customers_id = :custID';
            $sql = $db->bindVars($sql, ':addrID', $address_id, 'integer');
            $sql = $db->bindVars($sql, ':custID', $customer_id, 'integer');
            $db->Execute($sql);

            // insert the new customer_id into the customers info table for consistency
            $sql = 'INSERT INTO ' . TABLE_CUSTOMERS_INFO . '
						   (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_date_of_last_logon)
					VALUES (:custID, 1, now(), now())';
            $sql = $db->bindVars($sql, ':custID', $customer_id, 'integer');
            $db->Execute($sql);

            // send Welcome Email if appropriate
            if ($new_acct_notify) {
                // require the language file
                global $language_page_directory, $template_dir;
                if (!isset($language_page_directory))
                    $language_page_directory = DIR_WS_LANGUAGES . $_SESSION['language'] . '/';
                if (file_exists($language_page_directory . $template_dir . '/create_account.php')) {
                    $template_dir_select = $template_dir . '/';
                } else {
                    $template_dir_select = '';
                }

                require $language_page_directory . $template_dir_select . '/create_account.php';

                // set the mail text
                $email_text = sprintf(EMAIL_GREET_NONE, $frites_logged['user_profile']['name']) . EMAIL_WELCOME . "\n\n" . EMAIL_TEXT;
                $email_text .= "\n\n" . EMAIL_EC_ACCOUNT_INFORMATION . "\nUsername: " . $frites_logged['user_profile']['email'] . "\nPassword: " . $password . "\n\n";
                $email_text .= EMAIL_CONTACT;
                // include create-account-specific disclaimer
                $email_text .= "\n\n" . sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, STORE_OWNER_EMAIL_ADDRESS) . "\n\n";
                $email_html = array();
                $email_html['EMAIL_GREETING'] = sprintf(EMAIL_GREET_NONE, $frites_logged['user_profile']['name']);
                $email_html['EMAIL_WELCOME'] = EMAIL_WELCOME;
                $email_html['EMAIL_MESSAGE_HTML'] = nl2br(EMAIL_TEXT . "\n\n" . EMAIL_EC_ACCOUNT_INFORMATION . "\nUsername: " . $frites_logged['user_profile']['email'] . "\nPassword: " . $password . "\n\n");
                $email_html['EMAIL_CONTACT_OWNER'] = EMAIL_CONTACT;
                $email_html['EMAIL_CLOSURE'] = nl2br(EMAIL_GV_CLOSURE);
                $email_html['EMAIL_DISCLAIMER'] = sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, '<a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">' . STORE_OWNER_EMAIL_ADDRESS . ' </a>');

                // send the mail
                if (trim(EMAIL_SUBJECT) != 'n/a')
                    zen_mail($frites_logged['user_profile']['name'], $frites_logged['user_profile']['email'], EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, $email_html, 'welcome');

            } else {
            }

            // hook notifier class vis a vis account-creation
            $zco_notifier->notify('NOTIFY_LOGIN_SUCCESS_VIA_CREATE_ACCOUNT');

        } else {
        }

        // Check if email exists
        $check_customer_query = 'SELECT customers_id, customers_firstname, customers_lastname, customers_password,
										customers_email_address, customers_default_address_id,
										customers_authorization, customers_referral
							   FROM ' . TABLE_CUSTOMERS . '
							   WHERE customers_email_address = :emailAddress';

        $check_customer_query = $db->bindVars($check_customer_query, ':emailAddress', $customer_email, 'string');
        $check_customer = $db->Execute($check_customer_query);

        $loginAuthorized = true;

    }

    if ($check_customer->RecordCount() && isset($check_customer->fields['customers_frites_userid']) && $check_customer->fields['customers_frites_userid'] == $frites_md
    ) {

        $loginAuthorized = true;
    }


    if (!$loginAuthorized) {
        $error = true;
        $messageStack->add('login', TEXT_LOGIN_ERROR);
    } else {
        if (SESSION_RECREATE == 'True') {
            zen_session_recreate();
        }

        $check_country_query = 'SELECT entry_country_id, entry_zone_id
							  FROM ' . TABLE_ADDRESS_BOOK . '
							  WHERE customers_id = :customersID
							  AND address_book_id = :addressBookID';

        $check_country_query = $db->bindVars($check_country_query, ':customersID', $check_customer->fields['customers_id'], 'integer');
        $check_country_query = $db->bindVars($check_country_query, ':addressBookID', $check_customer->fields['customers_default_address_id'], 'integer');
        $check_country = $db->Execute($check_country_query);

        $_SESSION['customer_id'] = $check_customer->fields['customers_id'];
        $_SESSION['customer_default_address_id'] = $check_customer->fields['customers_default_address_id'];
        $_SESSION['customers_authorization'] = $check_customer->fields['customers_authorization'];
        $_SESSION['customer_first_name'] = $check_customer->fields['customers_firstname'];
        $_SESSION['customer_last_name'] = $check_customer->fields['customers_lastname'];
        $_SESSION['customer_country_id'] = $check_country->fields['entry_country_id'];
        $_SESSION['customer_zone_id'] = $check_country->fields['entry_zone_id'];

        // enforce db integrity: make sure related record exists
        $sql = 'SELECT customers_info_date_of_last_logon FROM ' . TABLE_CUSTOMERS_INFO . ' WHERE customers_info_id = :customersID';
        $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
        $result = $db->Execute($sql);
        if ($result->RecordCount() == 0) {
            $sql = 'insert into ' . TABLE_CUSTOMERS_INFO . ' (customers_info_id) values (:customersID)';
            $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
            $db->Execute($sql);
        }

        // update login count
        $sql = 'UPDATE ' . TABLE_CUSTOMERS_INFO . '
			  SET customers_info_date_of_last_logon = now(),
				  customers_info_number_of_logons = IF(customers_info_number_of_logons, customers_info_number_of_logons+1, 1)
			  WHERE customers_info_id = :customersID';

        $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
        $db->Execute($sql);
        $zco_notifier->notify('NOTIFY_LOGIN_SUCCESS');

        // bof: contents merge notice
        // save current cart contents count if required
        if (SHOW_SHOPPING_CART_COMBINED > 0) {
            $zc_check_basket_before = $_SESSION['cart']->count_contents();
        }

        // bof: not require part of contents merge notice
        // restore cart contents
        $_SESSION['cart']->restore_contents();
        // eof: not require part of contents merge notice

        // check current cart contents count if required
        
            $zc_check_basket_after = $_SESSION['cart']->count_contents();
            if (($zc_check_basket_before != $zc_check_basket_after) && $_SESSION['cart']->count_contents() > 0 && SHOW_SHOPPING_CART_COMBINED > 0) {
                if (SHOW_SHOPPING_CART_COMBINED == 2) {
                    // warning only do not send to cart
                    $messageStack->add_session('header', WARNING_SHOPPING_CART_COMBINED, 'caution');
                }
                if (SHOW_SHOPPING_CART_COMBINED == 1) {
                    // show warning and send to shopping cart for review
            if (!(isset($_GET['gv_no']))) {
              $messageStack->add_session('shopping_cart', WARNING_SHOPPING_CART_COMBINED, 'caution');
              zen_redirect(zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'));
            } else {
              $messageStack->add_session('header', WARNING_SHOPPING_CART_COMBINED, 'caution');
                }
            }
        }
        // eof: contents merge notice

		if (isset($_GET['only_login']) && $_GET['only_login']) {
			zen_redirect(zen_href_link(FILENAME_ACCOUNT, '', 'NONSSL'));
		} elseif (!empty($_GET['checkout_frites_confirmation'])) {
			zen_redirect($links['checkout_frites_confirmation']);
		} else {
			zen_redirect($links['checkout_frites_shipping']);
		}
    
    }
} else {

   
    //redirect to original login
    // Amazon did not accept login
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
}

if (isset($_GET['only_login']) && $_GET['only_login']) {
	zen_redirect(zen_href_link(FILENAME_ACCOUNT, '', 'NONSSL'));
} elseif (!empty($_GET['checkout_frites_confirmation'])) {
	zen_redirect($links['checkout_frites_confirmation']);
} else {
	zen_redirect($links['checkout_frites_shipping']);
}



// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_LOGIN');
