<?php
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=checkout_frites_shipping.<br />
 * Displays shipping info selection for Amazon Checkout
 *
 * @package templateSystem
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: tpl_checkout_frites_shipping_default.php 2019-05-25 11:08:16Z webchills $
 */
?>
<div class="centerColumn" id="checkoutShipping">

<?php echo zen_draw_form('checkout_address', $links['checkout_frites_shipping']) . zen_draw_hidden_field('action', 'process')?>

<h1 id="checkoutShippingHeading"><?php echo HEADING_TITLE; ?></h1>
<p class="amazonpayinfo"><?php echo AMAZON_PAY_INFO_TEXT; ?><?php echo '<a href="' . zen_href_link(FILENAME_AMAZON_PAY_INFO, '', 'SSL') . '" target="_blank">' . AMAZON_PAY_INFO_LINK . '</a>'; ?></p>
<?php if ($messageStack->size('checkout_shipping') > 0) echo $messageStack->output('checkout_shipping'); ?>
<?php if ($messageStack->size('checkout') > 0) echo $messageStack->output('checkout'); ?>

<input id="fritesOrderReferenceId" type="hidden" name="frites[OrderReferenceId]" value="<?php echo isset($_SESSION['frites']['OrderReferenceId'])?$_SESSION['frites']['OrderReferenceId']:'' ?>" />

<?php if ($frites_logged['status']) { ?>

<br class="clearBoth" />
<div align="center">
<script type='text/javascript'>
	window.onAmazonLoginReady = function() {
		amazon.Login.setClientId('<?php echo MODULE_PAYMENT_FRITES_CLIENT_ID; ?>');
	};
</script>
<script type='text/javascript' src='<?php echo $links['widget_link'] ?>?sellerId=<?php echo MODULE_PAYMENT_FRITES_MERCHANT_ID; ?>'></script>

<div id="addressBookWidgetDiv"></div>

<?php
	// get default language and code array('link' => 'uk', 'code' => 'en', 'locale' => 'en_GB');
	$frites_lang = frites_get_default_language();
?>

<script type='text/javascript'>
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: '<?php echo MODULE_PAYMENT_FRITES_MERCHANT_ID; ?>',
		language: '<?php echo $frites_lang['locale'] ?>',
		design: {
			size: {width:'<?php echo MODULE_PAYMENT_FRITES_ADDRESSBOOK_WIDTH ?>', height:'<?php echo MODULE_PAYMENT_FRITES_ADDRESSBOOK_HEIGHT ?>'}
		},
		onOrderReferenceCreate: function(orderReference) {
			fritesOrderReferenceId = orderReference.getAmazonOrderReferenceId();
			document.getElementById('fritesOrderReferenceId').value = fritesOrderReferenceId;
			<?php if (MODULE_PAYMENT_FRITES_IPN_DEBUG == 'Log File') { ?>
				console.log(fritesOrderReferenceId);
			<?php } ?>
		},
		onAddressSelect: function(orderReference) {
			// Optionally render the Wallet Widget
			if (document.getElementById('fritesOrderReferenceId').value) {
				//document.getElementById('continue-box').style.display = "block";

				var shipping_method = OffAmazonPayments.jQuery('input[name=shipping]:checked', 'form[name=checkout_address]').val();

				OffAmazonPayments.jQuery.ajax({
					url: '<?php echo $links['checkout_frites_shipping'] ?>',
					data: 'function=GetOrderDestination&OrderReferenceId='+document.getElementById('fritesOrderReferenceId').value+'&shipping='+shipping_method,
					type: 'post',
					async: true,
					dataType: 'json',
					beforeSend: function() {
						OffAmazonPayments.jQuery('#shipping_modules_list').css('opacity', '0.5')
										.css('background',"url('https://images-na.ssl-images-amazon.com/images/G/01/ep/loading-large._V364197283_.gif') center center no-repeat");
					},
					complete: function() {},
					success: function(json) {
						if (!json.error) {
							OffAmazonPayments.jQuery('#shipping_modules_list').load('<?php echo $links['checkout_frites_shipping'] ?> #shipping_modules_list > *', function() {
								OffAmazonPayments.jQuery('#shipping_modules_list').css('opacity', '1').css('background', 'transparent');
								check_continue_box();
							});
						} else {
							OffAmazonPayments.jQuery('#shipping_modules_list').html('<div class="messageStackError larger">'+json.error+'</div>').css('opacity', '1').css('background', 'transparent');
						}

						shipping_method = OffAmazonPayments.jQuery('input[name=shipping]:checked', 'form[name=checkout_address]').val();
						check_continue_box();
					},
					error: function(xhr, ajaxOptions, thrownError) {
						console.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					}
				});
			}
		},
		onError: function(error) {
			// Write your custom error handling
		}
	}).bind("addressBookWidgetDiv");

function check_continue_box() {
	if (OffAmazonPayments.jQuery('input[name=shipping]:checked', 'form[name=checkout_address]').length && document.getElementById('fritesOrderReferenceId').value) {
		document.getElementById('continue-box').style.display = "block";
	} else {
		document.getElementById('continue-box').style.display = "none";
	}
}

</script>
</div>
<br class="clearBoth" />
<div id="shipping_modules_list" style="opacity: 0.5">
<?php
//echo '<pre>';print_r($order);echo '</pre>';
?>
<?php if (zen_count_shipping_modules() > 0) { ?>

<h2 id="checkoutShippingHeadingMethod"><?php echo TABLE_HEADING_SHIPPING_METHOD; ?></h2>

<?php if (sizeof($quotes) > 1 && sizeof($quotes[0]) > 1) { ?>

<div id="checkoutShippingContentChoose" class="important"><?php echo TEXT_CHOOSE_SHIPPING_METHOD; ?></div>

<?php } elseif ($free_shipping == false) { ?>
<div id="checkoutShippingContentChoose" class="important"><?php echo TEXT_ENTER_SHIPPING_INFORMATION; ?></div>

<?php } ?>
<?php
    if ($free_shipping == true) {
?>
<div id="freeShip" class="important" ><?php echo FREE_SHIPPING_TITLE; ?>&nbsp;<?php echo $quotes[$i]['icon']; ?></div>
<div id="defaultSelected"><?php echo sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) . zen_draw_hidden_field('shipping', 'free_free'); ?></div>

<?php
    } else {
      $radio_buttons = 0;
      for ($i=0, $n=sizeof($quotes); $i<$n; $i++) {
      // bof: field set
// allows FedEx to work comment comment out Standard and Uncomment FedEx
//      if ($quotes[$i]['id'] != '' || $quotes[$i]['module'] != '') { // FedEx
      if ($quotes[$i]['module'] != '') { // Standard
?>
<fieldset>
<legend><?php echo $quotes[$i]['module']; ?>&nbsp;<?php if (isset($quotes[$i]['icon']) && zen_not_null($quotes[$i]['icon'])) { echo $quotes[$i]['icon']; } ?></legend>

<?php if (isset($quotes[$i]['error'])) { ?>
      <div><?php echo $quotes[$i]['error']; ?></div>
<?php
        } else {
          for ($j=0, $n2=sizeof($quotes[$i]['methods']); $j<$n2; $j++) {
// set the radio button to be checked if it is the method chosen
            $checked = FALSE;
            if (isset($_SESSION['shipping']) && isset($_SESSION['shipping']['id'])) {
              $checked = ($quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'] == $_SESSION['shipping']['id']);
            }
            if ( ($checked == true) || ($n == 1 && $n2 == 1) ) {
              //echo '      <div id="defaultSelected" class="moduleRowSelected">' . "\n";
            //} else {
              //echo '      <div class="moduleRow">' . "\n";
            }
?>
<?php if ( ($n > 1) || ($n2 > 1) ) { ?>
<div class="important forward"><?php echo $currencies->format(zen_add_tax($quotes[$i]['methods'][$j]['cost'], (isset($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))); ?></div>
<?php } else { ?>
<div class="important forward"><?php echo $currencies->format(zen_add_tax($quotes[$i]['methods'][$j]['cost'], $quotes[$i]['tax'])) . zen_draw_hidden_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id']); ?></div>
<?php } ?>

<?php echo zen_draw_radio_field('shipping', $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'], $checked, 'id="ship-'.$quotes[$i]['id'] . '-' . str_replace(' ', '-', $quotes[$i]['methods'][$j]['id']) .'" onchange="check_continue_box();"'); ?>
<label for="ship-<?php echo $quotes[$i]['id'] . '-' . str_replace(' ', '-', $quotes[$i]['methods'][$j]['id']); ?>" class="checkboxLabel" ><?php echo $quotes[$i]['methods'][$j]['title']; ?></label>
<!--</div>-->
<br class="clearBoth" />
<?php
            $radio_buttons++;
          }
        }
?>

</fieldset>
<?php
    }
// eof: field set
      }
    }
?>

<?php } else { ?>
<h2 id="checkoutShippingHeadingMethod"><?php echo TITLE_NO_SHIPPING_AVAILABLE; ?></h2>
<div id="checkoutShippingContentChoose" class="important"><?php echo zen_count_shipping_modules().' - '.TEXT_NO_SHIPPING_AVAILABLE; ?></div>
<?php } ?>



<?php } ?>

</div>

<br class="clearBoth" />







<fieldset class="shipping" id="comments">
<legend><?php echo TABLE_HEADING_COMMENTS; ?></legend>
<?php echo zen_draw_textarea_field('comments', '45', '3'); ?>
</fieldset>
<?php if ($free_shipping == true) { ?>    
<div id="continue-box-free" style="display:inline">
  <div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_CONTINUE_CHECKOUT, BUTTON_CONTINUE_ALT, 'id="checkout_by_frites_continue"'); ?></div>
  <div class="buttonRow back"><?php echo '<strong>' . TITLE_CONTINUE_CHECKOUT_PROCEDURE . '</strong><br />' . TEXT_CONTINUE_CHECKOUT_PROCEDURE; ?></div>
</div>
<?php } else { ?>
    <div id="continue-box" style="display:none">
  <div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_CONTINUE_CHECKOUT, BUTTON_CONTINUE_ALT, 'id="checkout_by_frites_continue"'); ?></div>
  <div class="buttonRow back"><?php echo '<strong>' . TITLE_CONTINUE_CHECKOUT_PROCEDURE . '</strong><br />' . TEXT_CONTINUE_CHECKOUT_PROCEDURE; ?></div>
</div>
<?php } ?>
</form>
</div>