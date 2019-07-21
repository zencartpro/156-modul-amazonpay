<?php
define('MODULE_PAYMENT_FRITES_TEXT_TITLE', 'Pay with Amazon');
define('MODULE_PAYMENT_FRITES_TEXT_ADMIN_TITLE', 'Pay with Amazon');
define('MODULE_PAYMENT_FRITES_TEXT_DESCRIPTION', '<br/><img src="images/amazonpaylogo.png" alt="Amazon Pay"/><br/><br/>Version 2.2.4<br/>NOT SCA compliant<br/><br/><a href="https://pay.amazon.com/uk" target="_blank">Amazon Pay Info Site</a>');
define('MODULE_PAYMENT_FRITES_TEXT_LOGIN', 'Amazon Login:');
define('MODULE_PAYMENT_FRITES_TEXT_PASSWORD', 'Password:');

define('MODULE_PAYMENT_FRITES_TEXT_ORDER_REFERENCE_ID', 'OrderReferenceId:');
define('MODULE_PAYMENT_FRITES_TEXT_AUTHORIZATION_ID', 'AuthorizationId:');
define('MODULE_PAYMENT_FRITES_TEXT_CAPTURE_ID', 'CaptureId:');
define('MODULE_PAYMENT_FRITES_TEXT_REFUND_ID', 'RefundId:');

define('MODULE_PAYMENT_FRITES_BUTTON_REFRESH', 'Refresh');
define('MODULE_PAYMENT_FRITES_BUTTON_REFUND', 'Refund');
define('MODULE_PAYMENT_FRITES_BUTTON_CANCEL', 'Cancel');
define('MODULE_PAYMENT_FRITES_BUTTON_CLOSE', 'Close');
define('MODULE_PAYMENT_FRITES_BUTTON_COLLECT', 'Collect');
define('MODULE_PAYMENT_FRITES_BUTTON_AUTHORIZE', 'Authorize');

define('MODULE_PAYMENT_FRITES_TEXT_ERROR_LOGIN', 'ERROR: Please login with Amazon to continue!');
define('MODULE_PAYMENT_FRITES_TEXT_ERROR_ORDER_REFERENCE', 'ERROR: No OrderReferenceId!');
define('MODULE_PAYMENT_FRITES_TEXT_ERROR_AUTHORIZATION', 'ERROR: Authorization Failed! Status: ');
define('MODULE_PAYMENT_FRITES_TEXT_ERROR_SELECT_DIFFERENT_PAYMENT', '<br />There has been a problem with the selected payment method from your Amazon account, please update the payment method or choose another one.');

define('MODULE_PAYMENT_FRITES_BUTTON_SIZE_TITLE', 'Checkout Button Size');
define('MODULE_PAYMENT_FRITES_BUTTON_SIZE_DESC', 'Creates either a small (76px x 32px), medium (101px x 46px), large (152px x 64px) or x-large (202px x 92px) button');

define('MODULE_PAYMENT_FRITES_BUTTON_STYLE_TITLE', 'Button Style');
define('MODULE_PAYMENT_FRITES_BUTTON_STYLE_DESC', 'Choose from the style of Amazon buttons');


define('MODULE_PAYMENT_FRITES_ORDER_STATUS_ID_TITLE','Default order status');
define('MODULE_PAYMENT_FRITES_ORDER_STATUS_ID_DESC','Set the status of orders made with this payment module to this value');

define('MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID_TITLE','Authorized order status');
define('MODULE_PAYMENT_FRITES_ORDER_STATUS_AUTHORIZED_ID_DESC','Set the status of authorized orders made with this payment module to this value');

define('MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID_TITLE','Captured order status');
define('MODULE_PAYMENT_FRITES_ORDER_STATUS_CAPTURED_ID_DESC','Set the status of captured orders made with this payment module to this value');

define('MODULE_PAYMENT_FRITES_ORDER_STATUS_CLOSED_ID_TITLE','Closed order status');
define('MODULE_PAYMENT_FRITES_ORDER_STATUS_CLOSED_ID_DESC','Set the status of closed orders made with this payment module to this value');

define('MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID_TITLE','Canceled order status');
define('MODULE_PAYMENT_FRITES_ORDER_STATUS_CANCELED_ID_DESC','Set the status of canceled orders made with this payment module to this value');

define('MODULE_PAYMENT_FRITES_ORDER_STATUS_REFUNDED_ID_TITLE','Refunded order status');
define('MODULE_PAYMENT_FRITES_ORDER_STATUS_REFUNDED_ID_DESC','Set the status of refunded orders made with this payment module to this value');

define('EMAIL_EC_ACCOUNT_INFORMATION', 'Your account login details, which you can use to review your purchase, are as follows:');

define('MODULE_PAYMENT_FRITES_STATUS_TITLE', 'Enable Pay with Amazon Module');
define('MODULE_PAYMENT_FRITES_STATUS_DESC', 'Do you want to accept Amazon payments?');

define('MODULE_PAYMENT_FRITES_SORT_ORDER_TITLE', 'Sort order');
define('MODULE_PAYMENT_FRITES_SORT_ORDER_DESC', 'Sort order of display. Lowest is displayed first.');

define('MODULE_PAYMENT_FRITES_MERCHANT_ID_TITLE', 'MerchantID');
define('MODULE_PAYMENT_FRITES_MERCHANT_ID_DESC', '<span style="color:red;">*</span> Enter your Amazon MerchantID');
define('MODULE_PAYMENT_FRITES_CLIENT_ID_TITLE', 'ClientID');
define('MODULE_PAYMENT_FRITES_CLIENT_ID_DESC', '<span style="color:red;">*</span> Enter your Amazon Application ClientID');
define('MODULE_PAYMENT_FRITES_ACCESSKEY_ID_TITLE', 'MWS Access Key ID');
define('MODULE_PAYMENT_FRITES_ACCESSKEY_ID_DESC', '<span style="color:red;">*</span> Enter your MWS Access Key ID');
define('MODULE_PAYMENT_FRITES_SECRETKEY_ID_TITLE', 'MWS Secret Access Key');
define('MODULE_PAYMENT_FRITES_SECRETKEY_ID_DESC', '<span style="color:red;">*</span> Enter your MWS Secret Access Key');
define('MODULE_PAYMENT_FRITES_CURRENCY_TITLE', 'Payment region');
define('MODULE_PAYMENT_FRITES_CURRENCY_DESC', 'Select your payment region');
define('MODULE_PAYMENT_FRITES_REGION_TITLE', 'Country of your store');
define('MODULE_PAYMENT_FRITES_REGION_DESC', 'Select the country of your store');
define('MODULE_PAYMENT_FRITES_HANDLER_TITLE', 'Live or Sandbox');
define('MODULE_PAYMENT_FRITES_HANDLER_DESC', '<strong>Production: </strong> Used to process Live transactions<br><strong>Sandbox: </strong>For developers and testing');
define('MODULE_PAYMENT_FRITES_IPN_DEBUG_TITLE', 'Debug Mode');
define('MODULE_PAYMENT_FRITES_IPN_DEBUG_DESC', 'Enable debug logging? <br />NOTE: This can REALLY use up disk space!<br />Logging goes to the /includes/modules/payment/frites/logs folder<br /><strong>Leave OFF for normal operation.</strong>');
define('MODULE_PAYMENT_FRITES_ADDRESSBOOK_WIDTH_TITLE', 'Address Book Width');
define('MODULE_PAYMENT_FRITES_ADDRESSBOOK_WIDTH_DESC', 'in Pixel with px');
define('MODULE_PAYMENT_FRITES_ADDRESSBOOK_HEIGHT_TITLE', 'Address Book Height');
define('MODULE_PAYMENT_FRITES_ADDRESSBOOK_HEIGHT_DESC', 'in Pixel with px');
define('MODULE_PAYMENT_FRITES_PAYMENTMETHOD_WIDTH_TITLE', 'Payment Method Width');
define('MODULE_PAYMENT_FRITES_PAYMENTMETHOD_WIDTH_DESC', 'in Pixel with px');
define('MODULE_PAYMENT_FRITES_PAYMENTMETHOD_HEIGHT_TITLE', 'Payment Method Height');
define('MODULE_PAYMENT_FRITES_PAYMENTMETHOD_HEIGHT_DESC', 'in Pixel with px');
define('MODULE_PAYMENT_FRITES_ZONE_DENIED', 'Amazon Payments are not available!');
define('MODULE_PAYMENT_FRITES_PHONE_REQUIRED_TITLE', 'Phone Number required?');
define('MODULE_PAYMENT_FRITES_PHONE_REQUIRED_DESC', 'Is the phone number a required field in the customer data in your store?<br/>True if yes, False if no<br/><br/><b>Note:</b><br/>If you are using PayPal Express with Express Button as well set this setting to False to avoid that PayPal Express customers are forced to enter their phone number during an Express checkout.');