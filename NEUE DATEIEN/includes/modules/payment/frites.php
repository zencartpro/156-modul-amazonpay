<?php
/**
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: frites.php 2020-07-23 16:11:16Z webchills $
 */

/**
 *  ensure dependencies are loaded
 */
include_once (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/frites/frites_functions.php';

error_reporting(E_ALL & ~E_NOTICE);

class frites {
	public $code, $title, $description, $enabled;

// class constructor
	public function __construct() {
		global $order;

		if (!defined('MODULE_PAYMENT_FRITES_HANDLER')) define('MODULE_PAYMENT_FRITES_HANDLER', '');
		if (!defined('MODULE_PAYMENT_FRITES_ORDER_STATUS_ID')) define('MODULE_PAYMENT_FRITES_ORDER_STATUS_ID', 0);

		$this->code = 'frites';
		if (IS_ADMIN_FLAG === true) {
			$this->check_frites_fields();
			$this->title = MODULE_PAYMENT_FRITES_TEXT_ADMIN_TITLE;
			if (IS_ADMIN_FLAG === true && defined('MODULE_PAYMENT_FRITES_IPN_DEBUG') && MODULE_PAYMENT_FRITES_IPN_DEBUG != 'Off') $this->title .= '<span class="alert"> (debug mode active)</span>';
			if (IS_ADMIN_FLAG === true && MODULE_PAYMENT_FRITES_HANDLER == 'sandbox') $this->title .= '<span class="alert"> (Sandbox/Test mode active)</span>';
		} else {
			$this->title = MODULE_PAYMENT_FRITES_TEXT_ADMIN_TITLE;
		}

		$this->description = MODULE_PAYMENT_FRITES_TEXT_DESCRIPTION;
		$this->sort_order = defined('MODULE_PAYMENT_FRITES_SORT_ORDER') ? MODULE_PAYMENT_FRITES_SORT_ORDER : null;		
		$this->enabled = (defined('MODULE_PAYMENT_FRITES_STATUS') && MODULE_PAYMENT_FRITES_STATUS == 'True'); 

		if ((int)MODULE_PAYMENT_FRITES_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_ID;
		}

		if (MODULE_PAYMENT_FRITES_HANDLER == 'production' || !strstr(MODULE_PAYMENT_FRITES_HANDLER, 'sandbox')) {
			$this->form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
		} else {
			$this->form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, 'sandbox=1', 'SSL');
		}

		if (is_object($order)) $this->update_status();
	}

// class methods
	public function update_status() {
		global $order, $db;
		 

// disable the module if the order only contains virtual products
		if ($this->enabled == true && $order->content_type == 'virtual') {
			$this->enabled = false;
		}
		
		}

	/**
	* JS validation which does error-checking of data-entry if this module is selected for use
	* (Number, Owner, and CVV Lengths)
	*
	* @return string
	*/
	public function javascript_validation() {
		$frites_enabled = (defined('MODULE_PAYMENT_FRITES_STATUS') && MODULE_PAYMENT_FRITES_STATUS == 'True');
		if ($frites_enabled) {
			$links = fritesLinks();

			$frites_logged = fritesLogin();
			$frites_lang = frites_get_default_language();
			$js = 'if (payment_value == "' . $this->code . '") {' . "\n" ;



				$js .= "
					OffAmazonPayments.jQuery('#AmazonPayButtonHidden').remove();
					OffAmazonPayments.jQuery('#checkoutPayment').append('<div id=\'AmazonPayButtonHidden\' style=\'display:none;\'></div>');

					var authRequest;
					OffAmazonPayments.Button('AmazonPayButtonHidden', '<?php echo MODULE_PAYMENT_FRITES_MERCHANT_ID; ?>', {
						type:  'PwA',
						color: '".MODULE_PAYMENT_FRITES_BUTTON_STYLE."',
						size:  '".MODULE_PAYMENT_FRITES_BUTTON_SIZE."',
						language: '".$frites_lang['locale']."',
						useAmazonAddressBook: true,
						authorization: function() {
							var loginOptions = {scope: 'profile postal_code payments:widget payments:shipping_address', popup: ".strtolower(MODULE_PAYMENT_FRITES_POPUP)."};
							//authRequest = amazon.Login.authorize(loginOptions, '".$links['checkout_frites_login']."'); //go to shipping page again
							authRequest = amazon.Login.authorize(loginOptions, '".$links['checkout_frites_login']."&checkout_frites_confirmation=1'); //go to last page
						},
						onError: function(error) {
							// Write your custom error handling
						}
					});

					OffAmazonPayments.jQuery('#AmazonPayButtonHidden > img').click();

					/*
					console.log(OffAmazonPayments);
					amazon.Login.setClientId('".MODULE_PAYMENT_FRITES_CLIENT_ID."');
					var loginOptions = {scope: 'profile postal_code payments:widget payments:shipping_address', popup: ".strtolower(MODULE_PAYMENT_FRITES_POPUP)."};
					console.log(amazon);
					console.log(OffAmazonPayments.jQuery('#taglineWrapper'));
					amazon.Login.authorize(loginOptions, '".$links['checkout_frites_login']."');
					*/

				";


			$js .= "\nreturn false;";
			$js .="\n".'}' . "\n";
			return $js;
		}

		return false;
	}

	/**
	* Displays payment method name along with Credit Card Information Submission Fields (if any) on the Checkout Payment Page
	*
	* @return array
	*/
	public function selection() {
		return array(
			'id' => $this->code,
			'module' => $this->title
		);
	}

	/**
	* Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
	* Since amazon module is not collecting info, it simply skips this step.
	*
	* @return boolean
	*/
	public function pre_confirmation_check() {
		return false;
	}

	/**
	* Display Credit Card Information on the Checkout Confirmation Page
	* Since none is collected for amazon before forwarding to amazon site, this is skipped
	* Launches before checkout page
	* @return boolean
	*/
	public function confirmation() {
		if (isset($_SESSION['frites'])) {
			//unset($_SESSION['frites']);
		}
		//return false;
	}

	/**
	* Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
	* This sends the data to the payment gateway for processing.
	* (These are hidden fields on the checkout confirmation page)
	*
	* @return string
	*/
	public function process_button() {

		global $order;

		$html = '';

		$links = fritesLinks();

		$frites_logged = fritesLogin();


?>
		<div class="frites-payment">
		
			<input id="fritesOrderReferenceId" type="hidden" name="frites[OrderReferenceId]" value="<?php echo isset($_SESSION['frites']['OrderReferenceId'])?$_SESSION['frites']['OrderReferenceId']:'' ?>" />




		</div>


<?php
		return $html;
	}

	/**
	* Store transaction info to the order and process any results that come back from the payment gateway
	*/
	public function before_process() {
		global $order, $currencies, $messageStack, $order_total_modules;
		

		if (isset($_POST['frites']['OrderReferenceId']) && $_POST['frites']['OrderReferenceId']) {

			$_SESSION['frites'] = $_POST['frites'];
		}
	}

	/**
	* Post-processing activities
	* When the order returns from the processor, if PDT was successful, this stores the results in order-status-history and logs data for subsequent reference
	*
	* @return boolean
	*/
	public function after_process() {
		global $insert_id, $db, $order, $messageStack, $currencies, $zco_notifier;

		$errors = array();

		$redirect = null;

		$links = fritesLinks();

		if (isset($_POST['frites']['OrderReferenceId']) && $_POST['frites']['OrderReferenceId']) {
			$_SESSION['frites']['OrderReferenceId'] = $_POST['frites']['OrderReferenceId'];
		}

		if (isset($_SESSION['frites']['OrderReferenceId']) && $_SESSION['frites']['OrderReferenceId']) {

			$_SESSION['frites']['insert_id'] = (int)$insert_id;

			$fritesOrderReference = fritesGetOrderReferenceDetails($_SESSION['frites']['OrderReferenceId']);

			if (isset($fritesOrderReference['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints']['Constraint']['ConstraintID'])) {
				$_SESSION['frites_errors']['Constraints']['Constraint']['ConstraintID'] = $fritesOrderReference['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['Constraints']['Constraint']['ConstraintID'];
			}

			if (isset($fritesOrderReference['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['OrderReferenceStatus']['State'])
					&& $fritesOrderReference['GetOrderReferenceDetailsResult']['OrderReferenceDetails']['OrderReferenceStatus']['State'] == 'Draft') {

				//****SET ORDER INFO - Because a credit card issuer might decline a transaction when API operation parameter values are inconsistent, we set consistent amount values across all API operations and round order_total to 2 decimals
				$fritesOrderReference = fritesSetOrderReferenceDetails(
											$_SESSION['frites']['OrderReferenceId'],
											$currencies->value(round($order->info['total'], 2)),											
											$order->info['currency'],
											$insert_id,
											isset($order->info['comments'])?$order->info['comments']:''
				);

				if (fritesParseXmlErrors($fritesOrderReference)) {
					$errors['amazonXmlError'] = fritesParseXmlErrors($fritesOrderReference);
				}
			}

			if (MODULE_PAYMENT_FRITES_ORDER_STATUS_ID > 0) {
				$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_ID;
			} else {
				$order_status = $order->info['order_status'];
			}

			if (!$errors) {
				//****CONFIRM ORDER
				$fritesConfirmOrderReference = fritesConfirmOrderReference($_SESSION['frites']['OrderReferenceId']);

				if (fritesParseXmlErrors($fritesConfirmOrderReference)) {
					$errors['amazonXmlError'] = fritesParseXmlErrors($fritesConfirmOrderReference);
				}
			}

			if (!$errors) {
				

				$SellerAuthorizationNote = '';
				

				// Because a credit card issuer might decline a transaction when API operation parameter values are inconsistent, we set consistent amount values across all API operations and round order_total to 2 decimals
				$fritesAuthorize = fritesAuthorize(
											$_SESSION['frites']['OrderReferenceId'],
											$currencies->value(round($order->info['total'], 2)),
											$order->info['currency'],
											$insert_id,
											$SellerAuthorizationNote
				);

				if (fritesParseXmlErrors($fritesAuthorize)) {
					$errors['amazonXmlError'] = fritesParseXmlErrors($fritesAuthorize);
				}

				//Check Authorization status
				$AuthorizationStatus = isset($fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State'])?$fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['State']:'';
				$AuthorizationReasonCode = isset($fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode'])?' ReasonCode: '.$fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AuthorizationStatus']['ReasonCode']:'';

				$AuthorizationRightStatus = (bool)($AuthorizationStatus == 'Open' || $AuthorizationStatus == 'Pending');

				if ((!$errors && !$AuthorizationRightStatus) || !empty($InvalidPaymentMethod) || !empty($TransactionTimedOut) || !empty($AmazonRejected)) {
					
					$errors['AuthorizationStatus'] = MODULE_PAYMENT_FRITES_TEXT_ERROR_AUTHORIZATION.$AuthorizationStatus;
					$_SESSION['frites_errors']['AuthorizationStatus']['State'] = $AuthorizationStatus;
					$_SESSION['frites_errors']['AuthorizationStatus']['ReasonCode'] = $AuthorizationReasonCode;
					
         // Because a credit card issuer might decline a transaction when API operation parameter values are inconsistent, we set consistent amount values across all API operations and round order_total to 2 decimals
						$fritesOrderReference = fritesSetOrderReferenceDetails(
													$_SESSION['frites']['OrderReferenceId'],
													$currencies->value(round($order->info['total'], 2)),
													$order->info['currency'],
													$insert_id,
													isset($order->info['comments'])?$order->info['comments']:''
						);

						$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID;

						$sql = 'UPDATE ' . TABLE_ORDERS . ' SET ';
						$sql .= "`orders_status` = '".(int)$order_status."' ".
							"WHERE `orders_id` = '".(int)$insert_id."'";
						$db->Execute($sql);

						$_SESSION['frites']['shipping_selected'] = 1;
						$_SESSION['frites']['payment_selected'] = 0;
						$messageStack->add_session('checkout', $errors['AuthorizationStatus'].MODULE_PAYMENT_FRITES_TEXT_ERROR_SELECT_DIFFERENT_PAYMENT, 'error');
						
						zen_redirect($links['checkout_frites_shipping']);					
				}

				if (MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID > 0) {
					$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID;
				}
			}

			if (!$errors
					&& isset($fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'])
					) {

				//****CAPTURE - Because a credit card issuer might decline a transaction when API operation parameter values are inconsistent, we set consistent amount values across all API operations and round order_total to 2 decimals
				$fritesCapture = fritesCapture(
					$fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'],
					$currencies->value(round($order->info['total'], 2)),
					$order->info['currency'],
					$insert_id
				);

				if (fritesParseXmlErrors($fritesCapture)) {
					$errors['amazonXmlError'] = fritesParseXmlErrors($fritesCapture);
				}

				if (MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID > 0) {
					$order_status = MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID;
				}

				if (!$errors) {
					$fritesCloseOrderReference = fritesCloseOrderReference(
						$_SESSION['frites']['OrderReferenceId'],
						'CaptureNow'
					);
				}

			}

			if (!$errors) {
				$fritesOrderReference = fritesGetOrderReferenceDetails($_SESSION['frites']['OrderReferenceId']);

                $OrderReferenceDetails = isset($fritesOrderReference['GetOrderReferenceDetailsResult']['OrderReferenceDetails']) ? $fritesOrderReference['GetOrderReferenceDetailsResult']['OrderReferenceDetails'] : array();

				if (fritesParseXmlErrors($fritesCapture)) {
					$errors['amazonXmlError'] = fritesParseXmlErrors($fritesCapture);
				}
			}

			if (!$errors) {

				$order->info['order_status'] = $order_status;

				$status_text = '';

				if (isset($OrderReferenceDetails['AmazonOrderReferenceId'])) {
					$status_text .= "\nOrderReferenceId: ".$OrderReferenceDetails['AmazonOrderReferenceId'];
				}

				if (isset($OrderReferenceDetails['CreationTimestamp'])) {
					$status_text .= "\nCreationTimestamp: ".$OrderReferenceDetails['CreationTimestamp'];
				}

				if (isset($OrderReferenceDetails['ExpirationTimestamp'])) {
					$status_text .= "\nExpirationTimestamp: ".$OrderReferenceDetails['ExpirationTimestamp'];
				}

				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['Name'])) {
					$status_text .= "\nName: ".$OrderReferenceDetails['Destination']['PhysicalDestination']['Name'];
				} elseif (isset($_SESSION['frites_login']['user_profile']['name'])) {
					$status_text .= "\nName: ".$_SESSION['frites_login']['user_profile']['name'];
				}

				if (isset($OrderReferenceDetails['OrderTotal']['Amount'])) {
					$status_text .= "\nAmount: ".$OrderReferenceDetails['OrderTotal']['Amount'].
										$OrderReferenceDetails['OrderTotal']['CurrencyCode'];
				}

				
				if (isset($fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'])) {
					$status_text .= "\nAmazonAuthorizationId: ".$fritesAuthorize['AuthorizeResult']['AuthorizationDetails']['AmazonAuthorizationId'];
				}

				if (isset($fritesCapture['CaptureResult']['CaptureDetails']['AmazonCaptureId'])) {
					$status_text .= "\nAmazonCaptureId: ".$fritesCapture['CaptureResult']['CaptureDetails']['AmazonCaptureId'];
				}

				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['Phone'])) {
					$status_text .= "\nPhone: ".$OrderReferenceDetails['Destination']['PhysicalDestination']['Phone'];
				}


				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination'])) {
					$status_text .= "\nPhysicalDestination: ";

					if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['PostalCode'])) {
						$address[] = $OrderReferenceDetails['Destination']['PhysicalDestination']['PostalCode'];
					}
					if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'])) {
						$address[] = $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'];
					}
					if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2'])) {
						$address[] = $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2'];
					}
					if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['City'])) {
						$address[] = $OrderReferenceDetails['Destination']['PhysicalDestination']['City'];
					}
					if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['CountryCode'])) {
						$address[] = $OrderReferenceDetails['Destination']['PhysicalDestination']['CountryCode'];
					}
					$status_text .= implode(', ',$address);
				}

				if (isset($OrderReferenceDetails['ReleaseEnvironment']) && MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
					$status_text .= "\nReleaseEnvironment: ".$OrderReferenceDetails['ReleaseEnvironment'];
				}

				$sql_data_array = array(
								array('fieldName'=>'orders_id', 'value'=>$insert_id, 'type'=>'integer'),
								array('fieldName'=>'orders_status_id', 'value'=>$order_status, 'type'=>'integer'),
								array('fieldName'=>'date_added', 'value'=>'now()', 'type'=>'noquotestring'),
								array('fieldName'=>'customer_notified', 'value'=>0, 'type'=>'integer'),
								array('fieldName'=>'comments', 'value'=>'Amazon status: ' . frites_string_decode($status_text), 'type'=>'string')
				);

				$db->perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

				if (isset($OrderReferenceDetails['Buyer']['Name'])) {
					$users_name = $OrderReferenceDetails['Buyer']['Name'];
				}
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['Name'])) {
					$customers_name = $OrderReferenceDetails['Destination']['PhysicalDestination']['Name'];
				}
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['Phone'])) {
					$customers_telephone = $OrderReferenceDetails['Destination']['PhysicalDestination']['Phone'];
				}
                $customers_street_address = '';
                $customers_company = '';
				
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2']) && !isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'])) {
					$customers_street_address .= ' '. $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2'];
					$customers_company = '';
				}
				
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1']) && !isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2'])) {
					$customers_street_address .= ' '. $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'];
					$customers_company = '';
				}
				
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2']) && isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'])) {
					$customers_company .= $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'];
					$customers_street_address .= $OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2'];
				}
				
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['PostalCode'])) {
					$customers_postcode = $OrderReferenceDetails['Destination']['PhysicalDestination']['PostalCode'];
				}
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['City'])) {
					$customers_city = $OrderReferenceDetails['Destination']['PhysicalDestination']['City'];
				}
				
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['CountryCode'])) {
					$customers_country = $OrderReferenceDetails['Destination']['PhysicalDestination']['CountryCode'];
				}
				
				if (!$language_id) {
      $language_id = $_SESSION['languages_id'];
    }
    
    $countries_names_query = 'select c.countries_id, c.countries_iso_code_2, cn.language_id, cn.countries_name from ' . TABLE_COUNTRIES . ' c, ' . TABLE_COUNTRIES_NAME . " cn where cn.language_id = '" . $language_id . "' AND c.countries_iso_code_2 = '" . $customers_country . "' AND cn.countries_id = c.countries_id";
		$countries_names = $db->Execute($countries_names_query);   
    
				
		$customers_country_name = $countries_names->fields['countries_name'];		
		
		$countries_id_query = 'select countries_id, countries_iso_code_2 from ' . TABLE_COUNTRIES . " where countries_iso_code_2 = '" . $customers_country . "'";
		$countries_id = $db->Execute($countries_id_query);  
    
				
		$customers_country_id = $countries_id->fields['countries_id'];						

				$sql = 'UPDATE ' . TABLE_ORDERS . ' SET ';
				if (isset($customers_telephone)) {
					$sql .= "`customers_telephone` = '".zen_db_prepare_input($customers_telephone)."', ";
				}
				if (isset($users_name)) {
					//$sql .= "`customers_name` = '".zen_db_prepare_input($users_name)."', ";
				}
				if (isset($customers_name)) {
					$sql .= "`customers_name` = '".zen_db_prepare_input(frites_string_decode($customers_name))."', ";
					$sql .= "`delivery_name` = '".zen_db_prepare_input(frites_string_decode($customers_name))."', ";
					$sql .= "`billing_name` = '".zen_db_prepare_input(frites_string_decode($customers_name))."', ";
				}
				if (isset($customers_postcode)) {
					$sql .= "`customers_postcode` = '".zen_db_prepare_input($customers_postcode)."', ";
					$sql .= "`delivery_postcode` = '".zen_db_prepare_input($customers_postcode)."', ";
					$sql .= "`billing_postcode` = '".zen_db_prepare_input($customers_postcode)."', ";
				}
				if (isset($customers_city)) {
					$sql .= "`customers_city` = '".zen_db_prepare_input(frites_string_decode($customers_city))."', ";
					$sql .= "`delivery_city` = '".zen_db_prepare_input(frites_string_decode($customers_city))."', ";
					$sql .= "`billing_city` = '".zen_db_prepare_input(frites_string_decode($customers_city))."', ";
				}
				if (isset($customers_country)) {
					$sql .= "`customers_country` = '".zen_db_prepare_input($customers_country_name)."', ";
					$sql .= "`delivery_country` = '".zen_db_prepare_input($customers_country_name)."', ";
					$sql .= "`billing_country` = '".zen_db_prepare_input($customers_country_name)."', ";

					$address_format_query = 'select address_format_id from ' . TABLE_COUNTRIES . " where countries_iso_code_2 = '" . zen_db_prepare_input($customers_country) . "'";
					$address_format = $db->Execute($address_format_query);
					if ($address_format->RecordCount() > 0) {
						$address_format_id = $address_format->fields['address_format_id'];
					}
				}
				if (isset($customers_street_address)) {
					$sql .= "`customers_street_address` = '".zen_db_prepare_input(frites_string_decode($customers_street_address))."', ";
					$sql .= "`delivery_street_address` = '".zen_db_prepare_input(frites_string_decode($customers_street_address))."', ";
					$sql .= "`billing_street_address` = '".zen_db_prepare_input(frites_string_decode($customers_street_address))."', ";
				}
				if (isset($customers_company)) {
					$sql .= "`customers_company` = '".zen_db_prepare_input(frites_string_decode($customers_company))."', ";
					$sql .= "`delivery_company` = '".zen_db_prepare_input(frites_string_decode($customers_company))."', ";
					$sql .= "`billing_company` = '".zen_db_prepare_input(frites_string_decode($customers_company))."', ";
				}

				if (isset($address_format_id)) {
					$sql .= "`customers_address_format_id` = '".$address_format_id."', ";
					$sql .= "`delivery_address_format_id` = '".$address_format_id."', ";
					$sql .= "`billing_address_format_id` = '".$address_format_id."', ";
				}

				if (isset($OrderReferenceDetails['AmazonOrderReferenceId'])) {
					$sql .= "`frites_order_reference_id` = '".zen_db_prepare_input($OrderReferenceDetails['AmazonOrderReferenceId'])."', ";
				}


				$sql .= "`orders_status` = '".(int)$order_status."' ".
					"WHERE `orders_id` = '".(int)$insert_id."'";

				if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') {
					fritesWriteLog($sql, $fritesOrderReference);
				}

				$db->Execute($sql);
				$sql2 = 'UPDATE ' . TABLE_ADDRESS_BOOK . ' SET ';
				
				
				
				if (isset($customers_postcode)) {
					$sql2 .= "`entry_postcode` = '".zen_db_prepare_input($customers_postcode)."', ";
					
				}
				if (isset($customers_city)) {
					$sql2 .= "`entry_city` = '".zen_db_prepare_input(frites_string_decode($customers_city))."', ";
					
				}
				// reset entry_state
				$sql2 .= "`entry_state` = '', ";
					
				
				if (isset($customers_country)) {
					$sql2 .= "`entry_country_id` = '".zen_db_prepare_input($customers_country_id)."', ";			
				
				}
				
				if (isset($customers_street_address)) {
					$sql2 .= "`entry_street_address` = '".zen_db_prepare_input(frites_string_decode($customers_street_address))."', ";
					
				}
				
				if (isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine2']) && isset($OrderReferenceDetails['Destination']['PhysicalDestination']['AddressLine1'])) {
					$sql2 .= "`entry_company` = '".zen_db_prepare_input(frites_string_decode($customers_company))."', ";
				} else {
				$sql2 .= "`entry_company` = '', ";		
				}
			

				$sql2 .= "`entry_firstname` = '-', ";				

				
				$sql2 .= "`entry_lastname` = '".zen_db_prepare_input($customers_name)."' ".
				"WHERE `customers_id`  = '" . (int)$_SESSION['customer_id'] . "' and `address_book_id` = '" . (int)$_SESSION['sendto'] . "'";
				
				
				$db->Execute($sql2);
				if (isset($customers_telephone)) {
				
				$sql3 = 'UPDATE ' . TABLE_CUSTOMERS . ' SET ';
				
												
				$sql3 .= "`customers_telephone` = '".zen_db_prepare_input($customers_telephone)."' ".
				"WHERE `customers_id`  = '" . (int)$_SESSION['customer_id'] . "'";			
				
								
				$db->Execute($sql3);
				}
				
		
				
				// send order confirmation when the final address data are received
$order->send_order_email($insert_id, 2);
$zco_notifier->notify('NOTIFY_CHECKOUT_PROCESS_AFTER_SEND_ORDER_EMAIL');
	
			}
		}

		if (!$errors) {
			if (isset($_SESSION['frites'])) {
				unset($_SESSION['frites']);
			}

			if (isset($_SESSION['frites_login'])) {
				unset($_SESSION['frites_login']);
			}



			return true;
		} else {
			if ($redirect) {
				zen_redirect($redirect);
			}

			
			foreach ($errors as $error) {
				$messageStack->add_session('checkout', $error, 'error');
			}

			
			zen_redirect($links['checkout_frites_confirmation']);

			

			return false;
		}

		
	}

	public function get_error() {
		return false;
	}

	public function check() {
		global $db;
		if (!isset($this->_check)) {
			$check_query = $db->Execute('select configuration_value from ' . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_FRITES_STATUS'");
			$this->_check = $check_query->RecordCount();
		}
		return $this->_check;
	}

	public function check_frites_fields() {
		global $db, $messageStack;

		$sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '".TABLE_CUSTOMERS."' AND COLUMN_NAME = 'customers_frites_userid'";
		$result = $db->Execute($sql);
		if ($result->RecordCount() == 0) {
			$db->Execute('ALTER TABLE `' .TABLE_CUSTOMERS."` ADD `customers_frites_userid` VARCHAR(96) NOT NULL DEFAULT '';");
		}

		$sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '".TABLE_ORDERS."' AND COLUMN_NAME = 'frites_order_reference_id'";
		$result = $db->Execute($sql);
		if ($result->RecordCount() == 0) {
			$db->Execute('ALTER TABLE `' .TABLE_ORDERS."` ADD `frites_order_reference_id` VARCHAR(96) NOT NULL DEFAULT '';");
		}

		$sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '".TABLE_ORDERS."' AND COLUMN_NAME = 'frites_order_authorization_id'";
		$result = $db->Execute($sql);
		if ($result->RecordCount() == 0) {
			$db->Execute('ALTER TABLE `' .TABLE_ORDERS."` ADD `frites_order_authorization_id` VARCHAR(96) NOT NULL DEFAULT '';");
		}

		$sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '".TABLE_ORDERS."' AND COLUMN_NAME = 'frites_order_capture_id'";
		$result = $db->Execute($sql);
		if ($result->RecordCount() == 0) {
			$db->Execute('ALTER TABLE `' .TABLE_ORDERS."` ADD `frites_order_capture_id` VARCHAR(96) NOT NULL DEFAULT '';");
		}

		$sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '".TABLE_ORDERS."' AND COLUMN_NAME = 'frites_order_refund_id'";
		$result = $db->Execute($sql);
		if ($result->RecordCount() == 0) {
			$db->Execute('ALTER TABLE `' .TABLE_ORDERS."` ADD `frites_order_refund_id` VARCHAR(96) NOT NULL DEFAULT '';");
		}

		$sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '".TABLE_ORDERS."' AND COLUMN_NAME = 'frites_debug_info'";
		$result = $db->Execute($sql);
		if ($result->RecordCount() == 0) {
			$db->Execute('ALTER TABLE `' .TABLE_ORDERS. '` ADD `frites_debug_info` LONGTEXT NULL;');
		}

		$sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '".DB_DATABASE."' AND TABLE_NAME = '".TABLE_ADDRESS_BOOK."' AND COLUMN_NAME = 'frites_debug_info'";
		$result = $db->Execute($sql);
		if ($result->RecordCount() == 0) {
			$db->Execute('ALTER TABLE `' .TABLE_ADDRESS_BOOK. '` ADD `frites_debug_info` LONGTEXT NULL;');
		}
	}

	public function install() {
		global $db, $messageStack;
		if (defined('MODULE_PAYMENT_FRITES_STATUS')) {
			$messageStack->add_session('AMAZON FRITES module already installed.', 'error');
			zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=frites', 'NONSSL'));
			return 'failed';
		}

		$this->check_frites_fields();
    $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) values ('Version', 'MODULE_PAYMENT_FRITES_MODULE_VERSION', '2.2.6', 'Version installed:', '6', 0, NOW(), NOW(), NULL, 'zen_cfg_read_only(');");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_STATUS_TITLE)."', 'MODULE_PAYMENT_FRITES_STATUS', 'True', '".zen_db_input(MODULE_PAYMENT_FRITES_STATUS_DESC)."', '2', '2', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, last_modified, date_added, use_function, set_function) values ('Zoneneinschränkung', 'MODULE_PAYMENT_FRITES_ZONE', '0', 'nicht änderbar', '6', '1', NOW(), NOW(), NULL, 'zen_cfg_read_only(');");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_ORDER_STATUS_ID', '1', '".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_ID_DESC)."', '6', '3', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID', '1', '".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID_DESC)."', '6', '4', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID', '2', '".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID_DESC)."', '6', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_CLOSED_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_ORDER_STATUS_CLOSED_ID', '2', '".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_CLOSED_ID_DESC)."', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID', '5', '".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID_DESC)."', '6', '7', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_REFUNDED_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_ORDER_STATUS_REFUNDED_ID', '5', '".zen_db_input(MODULE_PAYMENT_FRITES_ORDER_STATUS_REFUNDED_ID_DESC)."', '6', '8', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_SORT_ORDER_TITLE)."', 'MODULE_PAYMENT_FRITES_SORT_ORDER', '0', '".zen_db_input(MODULE_PAYMENT_FRITES_SORT_ORDER_DESC)."', '6', '9', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_MERCHANT_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_MERCHANT_ID','', '".zen_db_input(MODULE_PAYMENT_FRITES_MERCHANT_ID_DESC)."', '6', '10', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_CLIENT_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_CLIENT_ID','', '".zen_db_input(MODULE_PAYMENT_FRITES_CLIENT_ID_DESC)."', '6', '11', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ACCESSKEY_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_ACCESSKEY_ID','', '".zen_db_input(MODULE_PAYMENT_FRITES_ACCESSKEY_ID_DESC)."', '6', '12', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_SECRETKEY_ID_TITLE)."', 'MODULE_PAYMENT_FRITES_SECRETKEY_ID','', '".zen_db_input(MODULE_PAYMENT_FRITES_SECRETKEY_ID_DESC)."', '6', '13', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_CURRENCY_TITLE)."', 'MODULE_PAYMENT_FRITES_CURRENCY', 'Euro Region', '".zen_db_input(MODULE_PAYMENT_FRITES_CURRENCY_DESC)."', '6', '14', 'zen_cfg_select_option(array(\'Euro Region\', \'United Kingdom\'), ', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_REGION_TITLE)."', 'MODULE_PAYMENT_FRITES_REGION', 'AT', '".zen_db_input(MODULE_PAYMENT_FRITES_REGION_DESC)."', '6', '15', 'zen_cfg_select_option(array(\'GB\', \'DE\', \'AT\', \'FR\', \'IT\', \'ES\', \'BE\', \'DK\', \'IE\', \'LU\', \'NL\', \'PT\', \'SE\', \'HU\', \'CY\'), ', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_HANDLER_TITLE)."', 'MODULE_PAYMENT_FRITES_HANDLER', 'sandbox', '".zen_db_input(MODULE_PAYMENT_FRITES_HANDLER_DESC)."', '6', '17', 'zen_cfg_select_option(array(\'production\', \'sandbox\'), ', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_IPN_DEBUG_TITLE)."', 'MODULE_PAYMENT_FRITES_IPN_DEBUG', 'Off', '".zen_db_input(MODULE_PAYMENT_FRITES_IPN_DEBUG_DESC)."', '6', '18', 'zen_cfg_select_option(array(\'Off\',\'Log File\'), ', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_BUTTON_SIZE_TITLE)."', 'MODULE_PAYMENT_FRITES_BUTTON_SIZE', 'small', '".zen_db_input(MODULE_PAYMENT_FRITES_BUTTON_SIZE_DESC)."', '6', '19', 'zen_cfg_select_option(array(\'small\',\'medium\',\'large\',\'x-large\'), ', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_BUTTON_STYLE_TITLE)."', 'MODULE_PAYMENT_FRITES_BUTTON_STYLE', 'Gold', '".zen_db_input(MODULE_PAYMENT_FRITES_BUTTON_STYLE_DESC)."', '6', '20', 'zen_cfg_select_option(array(\'Gold\',\'DarkGray\',\'LightGray\'), ', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ADDRESSBOOK_WIDTH_TITLE)."', 'MODULE_PAYMENT_FRITES_ADDRESSBOOK_WIDTH','700px', '".zen_db_input(MODULE_PAYMENT_FRITES_ADDRESSBOOK_WIDTH_DESC)."', '6', '21', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_ADDRESSBOOK_HEIGHT_TITLE)."', 'MODULE_PAYMENT_FRITES_ADDRESSBOOK_HEIGHT','260px', '".zen_db_input(MODULE_PAYMENT_FRITES_ADDRESSBOOK_HEIGHT_DESC)."', '6', '22', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_PAYMENTMETHOD_WIDTH_TITLE)."', 'MODULE_PAYMENT_FRITES_PAYMENTMETHOD_WIDTH','700px', '".zen_db_input(MODULE_PAYMENT_FRITES_PAYMENTMETHOD_WIDTH_DESC)."', '6', '23', now())");
		$db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_PAYMENTMETHOD_HEIGHT_TITLE)."', 'MODULE_PAYMENT_FRITES_PAYMENTMETHOD_HEIGHT','260px', '".zen_db_input(MODULE_PAYMENT_FRITES_PAYMENTMETHOD_HEIGHT_DESC)."', '6', '24', now())");
    $db->Execute('insert into ' . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".zen_db_input(MODULE_PAYMENT_FRITES_PHONE_REQUIRED_TITLE)."', 'MODULE_PAYMENT_FRITES_PHONE_REQUIRED', 'False', '".zen_db_input(MODULE_PAYMENT_FRITES_PHONE_REQUIRED_DESC)."', '6', '25', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
		
	}

	public function remove() {
		global $db;
		$db->Execute('delete from ' . TABLE_CONFIGURATION . " where configuration_key like 'MODULE\_PAYMENT\_FRITES\_%'");
	}

	public function keys() {
		global $db;

		$keys_list = array();

		$check = $db->Execute('select configuration_key from ' . TABLE_CONFIGURATION . " where configuration_key like 'MODULE\_PAYMENT\_FRITES\_%' order by sort_order");
		while (!$check->EOF) {
			$keys_list[] = $check->fields['configuration_key'];
			$check->MoveNext();
		}

		return $keys_list;
	}
}