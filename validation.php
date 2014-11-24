<?php

/**
 * iPay88 payment module for PrestaShop

 * $ Id: validation.php 06-04-2011
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/ipay88.php');

$ipay88 = new iPay88();
$ipay88_name = 'iPay88 Online Payment Gateway ';
$cart = new Cart(intval($_POST['RefNo']));
/*$amount_cart = number_format($cart->getOrderTotal(), 2, '.', '');

if (round($amount_cart)== $_POST['Amount']) {
    $amt_cart = $amount_cart;
}
*/
$r_amount = str_replace(",","",$_POST['Amount']);	//remove thousand symbol
$amount_cart = $r_amount;

$amount = $_POST['amount'];
$orderid = $_POST['RefNo'];
$tranID = $_POST['TransId'];
$status = $_POST['Status'];
$vkey = Configuration::get('IPAY88_VKEY');


function Requery() {

    $MerchantCode = $_REQUEST['MerchantCode'];
    $RefNo = $_REQUEST['RefNo'];
    $Amount = $_REQUEST['Amount'];

    $query = "http://www.mobile88.com/epayment/enquiry.asp?MerchantCode=" . $MerchantCode . "&RefNo=" . $RefNo . "&Amount=" . $Amount;

    $url = parse_url($query);
    $host = $url["host"];
    $path = $url["path"] . "?" . $url["query"];
    $timeout = 15;
    $fp = fsockopen ($host, 80, $errno, $errstr, $timeout);

    if ($fp) {
        fputs ($fp, "GET $path HTTP/1.0\nHost: " . $host . "\n\n");
        while (!feof($fp)) {
            $buf .= fgets($fp, 128);
        }
        $lines = split("\n", $buf);
        $Result = $lines[count($lines)-1];
        fclose($fp);
    } else {
        # enter error handing code here
    }
    return $Result;

}


if ($status == "1" && Requery()=="00") {  // successful transaction

    $ipay88->validateOrder(intval($orderid), _PS_OS_PAYMENT_, $amount_cart, $ipay88_name,$ipay88->getL('00').''.$tranID.')');
    Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$ipay88->id);
}

else { // failure transaction

    $ipay88->validateOrder(intval($orderid),_PS_OS_ERROR_,$amount_cart, $ipay88_name, $ipay88->getL('-1').''.$tranID.')');
    Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$ipay88->id);
}


?>