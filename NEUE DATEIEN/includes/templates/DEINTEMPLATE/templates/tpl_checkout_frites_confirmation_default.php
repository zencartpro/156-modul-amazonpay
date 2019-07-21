<?php
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=checkout_frites_confirmation.<br />
 * Displays final checkout details, cart, payment and shipping info details for Amazon Checkout
 *
 * @package templateSystem
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: tpl_checkout_frites_confirmation_default.php 2018-04-04 17:08:16Z webchills $
 */
?>
<div class="centerColumn" id="checkoutConfirmDefault">

<h1 id="checkoutConfirmDefaultHeading"><?php echo HEADING_TITLE; ?></h1>
<div id="conditionslaststep"><?php echo TEXT_ZUSATZ_SCHRITT3; ?><br/><?php echo TEXT_CONDITIONS_ACCEPTED_IN_LAST_STEP; ?></div>
<br/>
<?php if ($messageStack->size('redemptions') > 0) echo $messageStack->output('redemptions'); ?>
<?php if ($messageStack->size('checkout_confirmation') > 0) echo $messageStack->output('checkout_confirmation'); ?>
<?php if ($messageStack->size('checkout') > 0) echo $messageStack->output('checkout'); ?>


<?php
	// get default language and code array
	$frites_lang = frites_get_default_language();
?>
<div align="center">
<script type='text/javascript'>
	window.onAmazonLoginReady = function() {
		amazon.Login.setClientId('<?php echo MODULE_PAYMENT_FRITES_CLIENT_ID; ?>');
	};
</script>
<script type='text/javascript' src='<?php echo $links['widget_link'] ?>?sellerId=<?php echo MODULE_PAYMENT_FRITES_MERCHANT_ID; ?>'></script>

<div id="walletWidgetDiv"></div>
<?php
	$wallet_ro = true;

	if (isset($_SESSION['frites_errors']['Constraints']['Constraint']['ConstraintID']) && $_SESSION['frites_errors']['Constraints']['Constraint']['ConstraintID'] == 'PaymentPlanNotSet') {
		$wallet_ro = false;
	}

	if (isset($_SESSION['frites_errors']['AuthorizationStatus']['ReasonCode']) && strpos($_SESSION['frites_errors']['AuthorizationStatus']['ReasonCode'],'InvalidPaymentMethod') !== false) {
		$wallet_ro = false;
	}
?>
<script>
	new OffAmazonPayments.Widgets.Wallet({
		sellerId: '<?php echo MODULE_PAYMENT_FRITES_MERCHANT_ID; ?>',
		<?php if ($wallet_ro) { ?>
		displayMode: 'Read',
		<?php } ?>
		language: '<?php echo $frites_lang['locale'] ?>',
		design: {
			size: {width:'<?php echo MODULE_PAYMENT_FRITES_PAYMENTMETHOD_WIDTH ?>', height:'<?php echo MODULE_PAYMENT_FRITES_PAYMENTMETHOD_HEIGHT ?>'}
		},
		onPaymentSelect: function(orderReference) {
			// Display your custom complete purchase button 
		},
		onError: function(error) {
			console.error(error);
		}
	}).bind("walletWidgetDiv");
</script>
</div>
<div class="buttonRow forward"><?php echo '<a href="' . $links['checkout_frites_payment'] . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
<br class="clearBoth" />
<hr />


<div align="center">
<div id="addressBookWidgetDiv"></div>
<script>
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: '<?php echo MODULE_PAYMENT_FRITES_MERCHANT_ID; ?>',
		displayMode: 'Read',
		language: '<?php echo $frites_lang['locale'] ?>',
		design: {
			size: {width:'<?php echo MODULE_PAYMENT_FRITES_ADDRESSBOOK_WIDTH ?>', height:'<?php echo MODULE_PAYMENT_FRITES_ADDRESSBOOK_HEIGHT ?>'}
		},
		onAddressSelect: function(orderReference) {
			// Optionally render the Wallet Widget
		},
		onError: function(error) {
			console.error(error);
		}
	}).bind("addressBookWidgetDiv");
</script>
</div>
<div class="buttonRow forward"><?php echo '<a href="' . $links['checkout_frites_shipping'] . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
<br class="clearBoth" />
<hr />

<h2 id="checkoutConfirmDefaultHeadingComments"><?php echo HEADING_ORDER_COMMENTS; ?></h2>
<div class="buttonRow forward"><?php echo  '<a href="' . $links['checkout_frites_payment'] . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
<div><?php echo (empty($order->info['comments']) ? NO_COMMENTS_TEXT : nl2br(zen_clean_html($order->info['comments'])) . zen_draw_hidden_field('comments', zen_clean_html($order->info['comments']))); ?></div>
<br class="clearBoth" />

<hr />
<div id="cartandsum">
<h2 id="checkoutConfirmDefaultHeadingCart"><?php echo HEADING_PRODUCTS; ?></h2>

<div class="buttonRow forward"><?php echo '<a href="' . zen_href_link(FILENAME_SHOPPING_CART, '', 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
<br class="clearBoth" />

<?php  if ($flagAnyOutOfStock) { ?>
<?php    if (STOCK_ALLOW_CHECKOUT == 'true') {  ?>
<div class="messageStackError"><?php echo OUT_OF_STOCK_CAN_CHECKOUT; ?></div>
<?php    } else { ?>
<div class="messageStackError"><?php echo OUT_OF_STOCK_CANT_CHECKOUT; ?></div>
<?php    } //endif STOCK_ALLOW_CHECKOUT ?>
<?php  } //endif flagAnyOutOfStock ?>


<table id="cartContentsDisplay">
<tr class="cartTableHeading">
<th scope="col" id="ccQuantityHeading"><?php echo TABLE_HEADING_QUANTITY; ?></th>
<th scope="col" id="ccProductsHeading"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
<th scope="col" id="ccProductsHeading"><?php echo TABLE_HEADING_PRODUCTIMAGE; ?></th>
<?php
  // If there are tax groups, display the tax columns for price breakdown
  if (sizeof($order->info['tax_groups']) > 1) {
?>
          <th scope="col" id="ccTaxHeading"><?php echo HEADING_TAX; ?></th>
<?php
  }
?>
 <th scope="col" id="ccSinglePriceHeading" width="60"><?php echo TABLE_HEADING_SINGLEPRICE; ?></th>
          <th scope="col" id="ccTotalHeading"><?php echo TABLE_HEADING_TOTAL; ?></th>
        </tr>
<?php // now loop thru all products to display quantity and price ?>
<?php for ($i=0, $n=sizeof($order->products); $i<$n; $i++) { ?>
        <tr class="<?php echo $order->products[$i]['rowClass']; ?>">
          <td  class="cartQuantity"><?php echo $order->products[$i]['qty']; ?>&nbsp;x</td>
          <td class="cartProductDisplay"><?php echo $order->products[$i]['name']; ?>
          	<br/><?php echo $order->products[$i]['merkmale']; ?>
          <?php  echo $stock_check[$i]; ?>

<?php // if there are attributes, loop thru them and display one per line
    if (isset($order->products[$i]['attributes']) && sizeof($order->products[$i]['attributes']) > 0 ) {
    echo '<ul class="cartAttribsList">';
      for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
?>
      <li><?php echo $order->products[$i]['attributes'][$j]['option'] . ': ' . nl2br(zen_output_string_protected($order->products[$i]['attributes'][$j]['value'])); ?></li>
<?php
      } // end loop
      echo '</ul>';
    } // endif attribute-info
?>
        </td>
        <td class="cartProductImg">
<?php echo zen_image(DIR_WS_IMAGES . $order->products[$i]['image'], $order->products[$i]['name'], IMAGE_SHOPPING_CART_WIDTH, IMAGE_SHOPPING_CART_HEIGHT);?>
 </td>

<?php // display tax info if exists ?>
<?php if (sizeof($order->info['tax_groups']) > 1)  { ?>
        <td class="cartTotalDisplay">
          <?php echo zen_display_tax_value($order->products[$i]['tax']); ?>%</td>
<?php    }  // endif tax info display  ?>
        <td class="cartTotalDisplay">
          <?php echo $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], 1);?>
         
 </td>

        <td class="cartTotalDisplay" valign="top">
          <?php echo $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']);
          if ($order->products[$i]['onetime_charges'] != 0 ) echo '<br /> ' . $currencies->display_price($order->products[$i]['onetime_charges'], $order->products[$i]['tax'], 1);
?>
        </td>
      </tr>
<?php  }  // end for loopthru all products ?>
      </table>


<?php
  if (MODULE_ORDER_TOTAL_INSTALLED) {
    $order_totals = $order_total_modules->process();
?>
<div id="orderTotals"><?php $order_total_modules->output(); ?></div>
<?php
  }
?>

<?php
  echo zen_draw_form('checkout_confirmation', $form_action_url, 'post', 'id="checkout_confirmation" onsubmit="submitonce();"');

  if (is_array($payment_modules->modules)) {
    echo $payment_modules->process_button();
  }
?>

<?php
 // zollhinweis für nicht EU
        $dest_country = isset ($order->delivery['country']['iso_code_2']) ? $order->delivery['country']['iso_code_2'] : 0 ;
        $dest_zone = 0;
        $error = false;
        $countries_table = EU_COUNTRIES_FOR_LAST_STEP; 
        $country_zones = explode(',', $countries_table);
        if ((!in_array($dest_country, $country_zones))&& ($order->delivery['country']['id'] != '')) {
            $dest_zone = $i;
            echo TEXT_NON_EU_COUNTRIES;
        } else {
            // do nothing
        }
        ?>

</div>
<div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_CONFIRM_ORDER, BUTTON_CONFIRM_ORDER_ALT, 'name="btn_submit" id="btn_submit"') ;?></div>
</form>

<?php if (isset($_SESSION['frites_errors'])) unset($_SESSION['frites_errors']); ?>
</div>