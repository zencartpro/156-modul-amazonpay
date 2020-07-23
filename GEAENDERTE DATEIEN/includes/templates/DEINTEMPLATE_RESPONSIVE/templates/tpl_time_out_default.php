<?php
/**
 * Page Template
 *
 * @package templateSystem
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: tpl_time_out_default.php for Amazon Pay 2019-07-20 19:49:16Z webchills $
 */
?>
<div class="centerColumn" id="timeoutDefault">
<?php
    if (zen_is_logged_in()) {
?>
<h1 id="timeoutDefaultHeading"><?php echo HEADING_TITLE_LOGGED_IN; ?></h1>
<div id="timeoutDefaultContent" class="content"><?php echo TEXT_INFORMATION_LOGGED_IN; ?></div>
<?php
  } else {
?>
<h1 id="timeoutDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

<div id="timeoutDefaultContent" class="content"><?php echo TEXT_INFORMATION; ?></div>
<?php echo zen_draw_form('login', zen_href_link(FILENAME_LOGIN, 'action=process', 'SSL')); ?>
<fieldset>
<legend><?php echo HEADING_RETURNING_CUSTOMER; ?></legend>

<label class="inputLabel" for="login-email-address"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
<?php echo zen_draw_input_field('email_address', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_email_address', '40') . ' id="login-email-address" autocomplete="off"', 'email'); ?>
<br class="clearBoth" />

<label class="inputLabel" for="login-password"><?php echo ENTRY_PASSWORD; ?></label>
<?php echo zen_draw_password_field('password', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_password', 40) . ' id="login-password" autocomplete="off"'); ?>
<br class="clearBoth" />
<?php echo zen_draw_hidden_field('securityToken', $_SESSION['securityToken']); ?>
</fieldset>

<div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_LOGIN, BUTTON_LOGIN_ALT); ?></div>
<div class="buttonRow back important"><?php echo '<a href="' . zen_href_link(FILENAME_PASSWORD_FORGOTTEN, '', 'SSL') . '">' . TEXT_PASSWORD_FORGOTTEN . '</a>'; ?></div>
</form>
<br class="clearBoth" />
<?php // ** BEGIN AMAZON FRITES LOGIN ** ?>
	<?php
	if (defined('MODULE_PAYMENT_FRITES_STATUS') && MODULE_PAYMENT_FRITES_STATUS == 'True') {
		include DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/frites/tpl_login_button.php';
	}
	?>
	<?php // ** END AMAZON FRITES LOGIN ** ?>
<?php
 }
 ?>
</div>
