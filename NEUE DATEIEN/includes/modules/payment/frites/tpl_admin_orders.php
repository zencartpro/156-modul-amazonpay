<?php
/**
 * @package Amazon Pay for Zen Cart Deutsch (www.zen-cart-pro.at)
 * @copyright Copyright 2003-2014 Webiprog
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: tpl_admin_orders.php 2019-07-20 19:29:16Z webchills $
 */

include_once (IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/frites/frites_functions.php';

$links = fritesLinks();

$frites_enabled = (defined('MODULE_PAYMENT_FRITES_STATUS') && MODULE_PAYMENT_FRITES_STATUS == 'True');

if ($frites_enabled && isset($_SESSION['admin_id']) && (int)$_SESSION['admin_id'] && isset($_GET['oID']) && (int)$_GET['oID'] && isset($_GET['action']) && $_GET['action'] == 'edit') {

	if (!isset($order)) {
		$order = new order((int)$_GET['oID']);
	}
	
	if ($order->info['payment_module_code'] == 'frites') {
		$order_frites = $db->Execute('select frites_order_reference_id, frites_order_authorization_id, frites_order_capture_id, frites_order_refund_id
									from ' . TABLE_ORDERS . "
									where orders_id = '" . (int)$_GET['oID'] . "'");

		$order->info['frites_order_reference_id'] = $order_frites->fields['frites_order_reference_id'];
		$order->info['frites_order_authorization_id'] = $order_frites->fields['frites_order_authorization_id'];
		$order->info['frites_order_capture_id'] = $order_frites->fields['frites_order_capture_id'];
		$order->info['frites_order_refund_id'] = $order_frites->fields['frites_order_refund_id'];
	} else {
		$frites_enabled = false;
	}

} else {
	$frites_enabled = false;
}

if ($frites_enabled) {
	//echo '<pre>';print_r($order);echo '</pre>';
?>
	<style>
		#frites-info {
			padding: 10px 5px 20px 5px;
		}
		#frites-info.wait {
			/*background: url('images/loading.gif') right 10px top 10px no-repeat;*/
			background: url('images/loading.gif') center center no-repeat;
		}
		#frites-info a.button {
			padding: 5px 10px;
			margin: 3px;
			background: #F0F1FC;
			color: #000000;
			border: 1px solid #003C74;
			-webkit-border-radius: 5px;
			-moz-border-radius: 5px;
			-khtml-border-radius: 5px;
			border-radius: 5px;
			text-decoration: none;
			display: inline-block;
		}

		#frites-info a.button:hover {
			background: #D2D1E4;
			text-decoration: none;
		}

		#frites-info .frites_authorization_id, #frites-info .frites_order_reference_id, #frites-info .frites_capture_id, #frites-info .frites_refund_id, #frites-info .frites_buttons {
			border-top: 1px solid #CCCCCC;
			margin-top: 10px;
			padding-top: 10px;
		}

		#frites-info .frites_message {
			padding-top: 5px;
		}
		#frites-info .frites_message .error, #frites-info span.expiration {
			color: red;
		}
	</style>

	<script>
		if(!window.jQuery) {
			document.write(unescape('<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js">%3C/script%3E'));
			document.write(unescape('<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />'));
			document.write(unescape('<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js">%3C/script%3E'));
		}
	</script>

	<script>
		ajax_working = false;

		function fritesSendAjax(params) {
			if (ajax_working) return false;
			var result = [];
			$.ajax({
				url: 'orders.php'/*'<?php echo $links['checkout_frites_shipping'] ?>'*/,
				data: 'frites_ajax=1&order_id='+order_id+'&'+params,
				type: 'post',
				async: false,
				dataType: 'json',
				beforeSend: function() {
					$('div#frites-info').addClass('wait');
					ajax_working = true;
				},
				complete: function() {
					$('div#frites-info').removeClass('wait');
					ajax_working = false;
				},
				success: function(json) {
					result = json;

					if (typeof(json.Error) != 'undefined') {
						frites_message.html('<strong class="error">ERROR!</strong><br /><strong>Code:</strong> '+json.Error.Code+'<br />'+json.Error.Message);
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
				}
			});
			return result;
		}

		function fritesGetOrderReferenceDetails(fritesOrderReferenceId) {
			if (fritesOrderReferenceId) {
				fritesOrderReferenceDetails = fritesSendAjax('function=GetOrderReferenceDetails&OrderReferenceId='+fritesOrderReferenceId);
			}
		}

		function fritesCloseOrderReference(fritesOrderReferenceId) {
			if (fritesOrderReferenceId) {
				frites_message.html('');
				fritesSendAjax('function=CloseOrderReference&OrderReferenceId='+fritesOrderReferenceId/*+'&ClosureReason='*/);
			}
		}

		function fritesCancelOrderReference(fritesOrderReferenceId) {
			if (fritesOrderReferenceId) {
				frites_message.html('');
				fritesSendAjax('function=CancelOrderReference&OrderReferenceId='+fritesOrderReferenceId/*+'&CancelationReason='*/);
			}
		}

		function fritesAuthorize(fritesOrderReferenceId) {
			if (fritesOrderReferenceId) {
				frites_message.html('');
				fritesSendAjax('function=Authorize&OrderReferenceId='+fritesOrderReferenceId+
					'&AuthorizationAmount='+fritesOrder.OrderTotal+
					'&AuthorizationAmountCurrency='+fritesOrder.CurrencyCode);
			}
		}

		function fritesGetAuthorizationDetails(fritesAuthorizationId) {
			if (fritesAuthorizationId && typeof(fritesAuthorizationId) == 'object') {
				$.each(fritesAuthorizationId, function(key, value) {
					fritesAuthorizationDetails[key] = fritesSendAjax('function=GetAuthorizationDetails&AuthorizationId='+value);
				});
			}
		}

		function fritesCapture(fritesAuthorizationId) {
			if (fritesAuthorizationId && typeof(fritesAuthorizationId) == 'object') {
				frites_message.html('');
				$.each(fritesAuthorizationId, function(key, value) {
					if (fritesAuthorizationDetails[key].GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationStatus.State == 'Open') {
						fritesSendAjax('function=Capture&AuthorizationId='+value+
							'&CaptureAmount='+fritesOrder.OrderTotal+
							'&CaptureAmountCurrency='+fritesOrder.CurrencyCode);
					}
				});
			}
		}

		function fritesGetCaptureDetails(fritesCaptureId) {
			if (fritesCaptureId && typeof(fritesCaptureId) == 'object') {
				$.each(fritesCaptureId, function(key, value) {
					fritesCaptureDetails[key] = fritesSendAjax('function=GetCaptureDetails&CaptureId='+value);
				});
			}
		}

		function fritesRefund(fritesCaptureId) {
			if (fritesCaptureId && typeof(fritesCaptureId) == 'object') {
				$.each(fritesCaptureId, function(key, value) {
					fritesSendAjax('function=Refund&CaptureId='+value+
						'&RefundAmount='+fritesOrder.OrderTotal+
						'&RefundAmountCurrency='+fritesOrder.CurrencyCode);
				});
			}
		}

		function fritesGetRefundDetails(fritesRefundId) {
			if (fritesRefundId && typeof(fritesRefundId) == 'object') {
				$.each(fritesRefundId, function(key, value) {
					fritesRefundDetails[key] = fritesSendAjax('function=GetRefundDetails&RefundId='+value);
				});
			}
		}

		function fritesFillOrderInfo() {
			var order_reference_details = '';
			var authorization_details = '';
			var capture_details = '';
			var refund_details = '';

			fritesAuthorizationId = {};
			fritesAuthorizationDetails = {};
			fritesCaptureId = {};
			fritesCaptureDetails = {};
			fritesRefundId = {};
			fritesRefundDetails = {};

			//GetOrderReferenceDetails
			if (fritesOrderReferenceId) {
				try {
					order_reference_details += ' <strong>CreationTimestamp:</strong> '+fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.CreationTimestamp;
					order_reference_details += ' <strong>LastUpdateTimestamp:</strong> '+fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.OrderReferenceStatus.LastUpdateTimestamp;
					order_reference_details += ' <span class="expiration"><strong>ExpirationTimestamp:</strong> '+fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.ExpirationTimestamp+'</span>';
					order_reference_details += '<br /><strong>State:</strong> '+fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.OrderReferenceStatus.State;
					fritesOrder.OrderStatus = fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.OrderReferenceStatus.State;
				} catch(e) {}
				try {
					order_reference_details += '<br /><strong>OrderTotal:</strong> '+fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.OrderTotal.Amount
						+fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.OrderTotal.CurrencyCode;
					fritesOrder.OrderTotal = parseFloat(fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.OrderTotal.Amount);
					fritesOrder.CurrencyCode = fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.OrderTotal.CurrencyCode;
				} catch(e) {}
				try {
				if (typeof(fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.IdList) !== 'undefined'
				&& typeof(fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.IdList.member) !== 'undefined') {
					var fritesAuthId = fritesOrderReferenceDetails.GetOrderReferenceDetailsResult.OrderReferenceDetails.IdList.member;
					if (typeof(fritesAuthId) == 'object') {
						fritesAuthorizationId = fritesAuthId;
					} else {
						fritesAuthorizationId[0] = fritesAuthId;
					}
				}
				} catch(e) {}
			}


			//GetAuthorizationDetails
			if (fritesAuthorizationId) {
				try {
					fritesAuthorizationDetails.GetAuthorizationDetailsResult.AuthorizationDetails.AmazonAuthorizationId;
				} catch(e) {
					fritesGetAuthorizationDetails(fritesAuthorizationId);
				}

				$.each(fritesAuthorizationDetails, function(key, value) {
					authorization_details += '<div class="frites_authorization_id"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_AUTHORIZATION_ID ?></strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.AmazonAuthorizationId+'</div>';
					authorization_details += '<div class="frites_authorization_details">';
					try {
						authorization_details += ' <strong>CreationTimestamp:</strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.CreationTimestamp;
						authorization_details += ' <strong>LastUpdateTimestamp:</strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationStatus.LastUpdateTimestamp;
						authorization_details += ' <span class="expiration"><strong>ExpirationTimestamp:</strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.ExpirationTimestamp+'</span>';
						authorization_details += '<br /><strong>State:</strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationStatus.State;
						if (value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationStatus.State != 'Declined') {
							fritesOrder.AuthorizationStatus = value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationStatus.State;
						}
					} catch(e) {}
					try {
						authorization_details += '<br /><strong>AuthorizationAmount:</strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationAmount.Amount
							+value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationAmount.CurrencyCode;
						fritesOrder.AuthorizationAmount = parseFloat(value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationAmount.Amount);
					} catch(e) {}
					try {
						authorization_details += '<br /><strong>AuthorizationFee:</strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationFee.Amount
							+value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationFee.CurrencyCode;
						fritesOrder.AuthorizationFee = parseFloat(value.GetAuthorizationDetailsResult.AuthorizationDetails.AuthorizationFee.Amount);
					} catch(e) {}
					try {
						authorization_details += '<br /><strong>CapturedAmount:</strong> '+value.GetAuthorizationDetailsResult.AuthorizationDetails.CapturedAmount.Amount
							+value.GetAuthorizationDetailsResult.AuthorizationDetails.CapturedAmount.CurrencyCode;
						fritesOrder.CapturedAmount = fritesParseFloat(value.GetAuthorizationDetailsResult.AuthorizationDetails.CapturedAmount.Amount);
					} catch(e) {}
					try {
					if (typeof(value.GetAuthorizationDetailsResult.AuthorizationDetails.IdList) !== 'undefined'
					&& typeof(value.GetAuthorizationDetailsResult.AuthorizationDetails.IdList.member) !== 'undefined') {
						var fritesCaptId = value.GetAuthorizationDetailsResult.AuthorizationDetails.IdList.member;
						if (typeof(fritesCaptId) == 'object') {
							fritesCaptureId = fritesCaptId;
						} else {
							fritesCaptureId[0] = fritesCaptId;
						}
					}
					} catch(e) {}
					authorization_details += '</div>';
				});
			}

			//GetCaptureDetails
			if (fritesCaptureId) {
				try {
					fritesCaptureDetails.GetCaptureResult.CaptureDetails.AmazonCaptureId;
				} catch(e) {
					fritesGetCaptureDetails(fritesCaptureId);
				}

				$.each(fritesCaptureDetails, function(key, value) {
					capture_details += '<div class="frites_capture_id"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_CAPTURE_ID ?></strong> '+fritesCaptureId[key]+'</div>';
					capture_details += '<div class="frites_capture_details">';
					try {
						capture_details += ' <strong>CreationTimestamp:</strong> '+value.GetCaptureDetailsResult.CaptureDetails.CreationTimestamp;
					} catch(e) {}
					try {
						capture_details += ' <strong>LastUpdateTimestamp:</strong> '+value.GetCaptureDetailsResult.CaptureDetails.CaptureStatus.LastUpdateTimestamp;
						capture_details += '<br /><strong>State:</strong> '+value.GetCaptureDetailsResult.CaptureDetails.CaptureStatus.State;
						fritesOrder.CaptureStatus = value.GetCaptureDetailsResult.CaptureDetails.CaptureStatus.State;
					} catch(e) {}
					try {
						capture_details += '<br /><strong>CaptureAmount:</strong> '+value.GetCaptureDetailsResult.CaptureDetails.CaptureAmount.Amount
							+value.GetCaptureDetailsResult.CaptureDetails.CaptureAmount.CurrencyCode;
						fritesOrder.CapturedAmount = fritesParseFloat(value.GetCaptureDetailsResult.CaptureDetails.CaptureAmount.Amount);
					} catch(e) {}
					try {
					if (typeof(value.GetCaptureDetailsResult.CaptureDetails.IdList) !== 'undefined' 
					&& typeof(value.GetCaptureDetailsResult.CaptureDetails.IdList.member) !== 'undefined') {
						fritesRefId = value.GetCaptureDetailsResult.CaptureDetails.IdList.member;
						if (typeof(fritesRefId) == 'object') {
							fritesRefundId = fritesRefId;
						} else {
							fritesRefundId[0] = fritesRefId;
						}
					}
					} catch(e) {}
					capture_details += '</div>';
				});
			}

			//GetRefundDetails
			if (fritesRefundId) {
				try {
					fritesRefundDetails.GetRefundResult.RefundDetails.AmazonRefundId;
				} catch(e) {
					fritesGetRefundDetails(fritesRefundId);
				}

				$.each(fritesRefundDetails, function(key, value) {
					refund_details += '<div class="frites_refund_id"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_REFUND_ID ?></strong> '+fritesRefundId[key]+'</div>';
					refund_details += '<div class="frites_refund_details">';
					try {
						refund_details += ' <strong>CreationTimestamp:</strong> '+value.GetRefundDetailsResult.RefundDetails.CreationTimestamp;
					} catch(e) {}
					try {
						refund_details += ' <strong>LastUpdateTimestamp:</strong> '+value.GetRefundDetailsResult.RefundDetails.RefundStatus.LastUpdateTimestamp;
						refund_details += '<br /><strong>State:</strong> '+value.GetRefundDetailsResult.RefundDetails.RefundStatus.State;
						fritesOrder.RefundStatus = value.GetRefundDetailsResult.RefundDetails.RefundStatus.State;
					} catch(e) {}
					try {
						refund_details += '<br /><strong>RefundAmount:</strong> '+value.GetRefundDetailsResult.RefundDetails.RefundAmount.Amount
							+value.GetRefundDetailsResult.RefundDetails.RefundAmount.CurrencyCode;
						fritesOrder.RefundedAmount = fritesParseFloat(value.GetRefundDetailsResult.RefundDetails.RefundAmount.Amount);
					} catch(e) {}
					refund_details += '</div>';
				});
			}

console.log(fritesOrder);

			$('div#frites-info div.frites_authorization_id').remove();
			$('div#frites-info div.frites_authorization_details').remove();
			$('div#frites-info div.frites_capture_id').remove();
			$('div#frites-info div.frites_capture_details').remove();
			$('div#frites-info div.frites_refund_id').remove();
			$('div#frites-info div.frites_refund_details').remove();

			$('div#frites-info div.frites_order_reference_details').html(order_reference_details);
			$('div#frites-info div.frites_authorization_details').html(authorization_details);
			if (fritesAuthorizationId && authorization_details) {
				$('div#frites-info div.frites_order_reference_details').after(authorization_details);
			}
			if (fritesCaptureId && capture_details) {
				$('div#frites-info div.frites_authorization_details:last').after(capture_details);
			}
			if (fritesRefundId && refund_details) {
				$('div#frites-info div.frites_capture_details').after(refund_details);
			}

			//console.log(fritesOrder);

			var html_buttons = '';

			if (fritesOrderReferenceId) {
				html_buttons += frites_button_refresh;
			}

			if (fritesOrder.OrderStatus == 'Open' && !fritesAuthorizationId) { /*AUTHORIZE*/
				html_buttons += '<a href="javascript:void(0)" onclick="fritesAuthorize(fritesOrderReferenceId); fritesRefresh();" class="button"><?php echo MODULE_PAYMENT_FRITES_BUTTON_AUTHORIZE ?></a>';
			}

			if (fritesOrder.CapturedAmount > 0 && fritesAuthorizationId && fritesCaptureId && !fritesOrder.RefundedAmount) { /*REFUND*/
				html_buttons += '<a href="javascript:void(0)" onclick="fritesRefund(fritesCaptureId); fritesRefresh();" class="button"><?php echo MODULE_PAYMENT_FRITES_BUTTON_REFUND ?></a>';
			}

			if (fritesOrder.OrderStatus == 'Open') { /*CLOSE*/
				html_buttons += '<a href="javascript:void(0)" onclick="fritesCloseOrderReference(fritesOrderReferenceId); fritesRefresh();" class="button"><?php echo MODULE_PAYMENT_FRITES_BUTTON_CLOSE ?></a>';
			}

			if ((fritesOrder.OrderStatus == 'Open' && !fritesOrder.CapturedAmount) || !fritesAuthorizationId) { /*CANCEL*/
				html_buttons += '<a href="javascript:void(0)" onclick="fritesCancelOrderReference(fritesOrderReferenceId); fritesRefresh();" class="button"><?php echo MODULE_PAYMENT_FRITES_BUTTON_CANCEL ?></a>';
			}

			if ((/*fritesOrder.OrderStatus == 'Open' || */fritesOrder.AuthorizationStatus == 'Open') && fritesAuthorizationId) { /*COLLECT*/
				html_buttons += '<a href="javascript:void(0)" onclick="fritesCapture(fritesAuthorizationId); fritesRefresh();" class="button"><?php echo MODULE_PAYMENT_FRITES_BUTTON_COLLECT ?></a>';
			}

			frites_buttons.html(html_buttons); 
		}

		function fritesRefresh() {
			fritesGetOrderReferenceDetails(fritesOrderReferenceId);
			fritesFillOrderInfo();
		}

		function fritesParseFloat(val) {
			return parseFloat(val);
		}
	</script>

	<script>
		fritesOrderReferenceId = '<?php echo $order->info['frites_order_reference_id']; ?>';
		fritesOrderReferenceDetails = {};
		fritesOrder = {
			'OrderStatus': '',
			'AuthorizationStatus': '',
			'CaptureStatus': '',
			'RefundStatus': '',
			'OrderTotal': 0,
			'CapturedAmount': 0,
			'RefundedAmount': 0,
			'AuthorizationFee': 0,
			'AuthorizationAmount': 0,
			'CurrencyCode': ''
		};

		fritesAuthorizationId = '<?php echo $order->info['frites_order_authorization_id']; ?>';
		fritesAuthorizationDetails = {};
		fritesCaptureId = '<?php echo $order->info['frites_order_capture_id']; ?>';
		fritesCaptureDetails = {};
		fritesRefundId = '<?php echo $order->info['frites_order_refund_id']; ?>';
		fritesRefundDetails = {};

		order_id = '<?php echo (int)$_GET['oID'] ?>';

		frites_button_refresh = '<a href="javascript:void(0)" onclick="frites_message.html(\'\'); fritesRefresh();" class="button fritesGetOrderReferenceDetails"><?php echo MODULE_PAYMENT_FRITES_BUTTON_REFRESH ?></a>';

		fritesOrderStatus = '';
		fritesAuthorizationStatus = '';

		$(document).ready(function() {
			$('tr.dataTableHeadingRow:first').closest('table').parent().prepend('<div id="frites-info"></div>');

			frites_block = $('div#frites-info');

			frites_block.append('<div class="pageHeading"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_TITLE ?>:</strong></div>');
			frites_block.append('<div class="frites_order_reference_id"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_ORDER_REFERENCE_ID ?></strong> '+fritesOrderReferenceId+'</div>');
			frites_block.append('<div class="frites_order_reference_details"></div>');
			if (fritesAuthorizationId) {
				frites_block.append('<div class="frites_authorization_id"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_AUTHORIZATION_ID ?></strong> '+fritesAuthorizationId+'</div>');
				frites_block.append('<div class="frites_authorization_details"></div>');
			}
			if (fritesCaptureId) {
				frites_block.append('<div class="frites_capture_id"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_CAPTURE_ID ?></strong> '+fritesCaptureId+'</div>');
				frites_block.append('<div class="frites_capture_details"></div>');
			}
			if (fritesRefundId) {
				frites_block.append('<div class="frites_refund_id"><strong><?php echo MODULE_PAYMENT_FRITES_TEXT_REFUND_ID ?></strong> '+fritesRefundId+'</div>');
				frites_block.append('<div class="frites_refund_details"></div>');
			}
			frites_block.append('<div class="frites_message"></div>');
			frites_block.append('<div class="frites_buttons"></div>');

			frites_buttons = $('div#frites-info div.frites_buttons');
			frites_message = $('div#frites-info div.frites_message');

			//fritesRefresh();
			if (fritesOrderReferenceId) {
				frites_buttons.append(frites_button_refresh);
			}

			/*fritesGetOrderReferenceDetails(fritesOrderReferenceId);
			fritesGetAuthorizationDetails(fritesAuthorizationId);
			fritesFillOrderInfo();*/
		});
	</script>

<?php
}
