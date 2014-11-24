<?php

/**
 * iPay88 payment module for PrestaShop 

 * $ Id: ipay88.php 06-04-2011
 */
 
class ipay88 extends PaymentModule
{
	private	$_html = '';
	private $_postErrors = array();

	public function __construct()
	{
		$this->name = 'ipay88';
		// check PrestaShop version and assign to correct tab/section
		if (_PS_VERSION_ < "1.4")
		{
		$this->tab = 'Payment';
		}
		else
		{
		$this->tab = 'payments_gateways';
		}
		
		$this->version = '1.0.1 (April 2011)';

		$this->currencies = true;
		$this->currencies_mode = 'radio';
		//$this->currencies_mode = 'checkbox';

        parent::__construct();

        /* The parent construct is required for translations */
		$this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('iPay88');
        $this->description = $this->l('Accepts payments by iPay88');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
	}

    // the submit url is here !!//
	public function getipay88Url()
	{
    return Configuration::get('IPAY88_SANDBOX') ? 'https://www.mobile88.com/epayment/entry.asp' : 'https://www.mobile88.com/epayment/entry.asp';
	}

	public function install()
	{
		if (!parent::install() OR !Configuration::updateValue('ipay88_merchantCode', '')
			OR !Configuration::updateValue('ipay88_merchantKey','') OR !Configuration::updateValue('IPAY88_SANDBOX', 1)
            OR !$this->registerHook('payment'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('ipay88_merchantCode') OR !Configuration::deleteByName('ipay88_merchantKey')
			OR !parent::uninstall())
			return false;
		return true;
	}

	public function getContent()
	{
		$this->_html = '<h2>iPay88</h2>';
		if (isset($_POST['submitipay88']))
		{
			if (empty($_POST['merchantCode']))
				$this->_postErrors[] = $this->l('iPay88 <u><b>Merchant Code</b></u> is required !');
            if (empty($_POST['merchantKey']))
				$this->_postErrors[] = $this->l('iPay88 <u><b>Merchant Key</b></u> is required !</br>');

			if (!sizeof($this->_postErrors))
			{
				Configuration::updateValue('ipay88_merchantCode', $_POST['merchantCode']);
				Configuration::updateValue('ipay88_merchantKey', $_POST['merchantKey']);
				$this->displayConf();
			}
			else
				$this->displayErrors();
		}

		$this->displayipay88();
		$this->displayFormSettings();
		return $this->_html;
	}

	public function displayConf()
	{
		$this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Settings updated').'
		</div>';
	}

	public function displayErrors()
	{
		$nbErrors = sizeof($this->_postErrors);
		$this->_html .= '
		<div class="alert error">
			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
			<ol>';
		foreach ($this->_postErrors AS $error)
			$this->_html .= '<li>'.$error.'</li>';
		$this->_html .= '
			</ol>
		</div>';
	}
	
	
	public function displayipay88()
	{
		$this->_html .= '
		<img src="../modules/ipay88/ipay88.gif" style="float:left; margin-right:15px;" />
		<b>'.$this->l('This module allows you to accept payments by iPay88.').'</b><br /><br />
		<br /><br /><br />';
	}

	public function displayFormSettings()
	{
		$conf = Configuration::getMultiple(array('ipay88_merchantCode', 'ipay88_merchantKey'));
		$merchantCode = array_key_exists('merchantCode', $_POST) ? $_POST['merchantCode'] : (array_key_exists('ipay88_merchantCode', $conf) ? $conf['ipay88_merchantCode'] : '');
		$merchantKey = array_key_exists('merchantKey', $_POST) ? $_POST['merchantKey'] : (array_key_exists('ipay88_merchantKey', $conf) ? $conf['ipay88_merchantKey'] : '');

		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>

            <label>'.$this->l('iPay88 Merchant Code : ').'</label>
			<div class="margin-form"><input type="text" size="33" name="merchantCode" value="'.htmlentities($merchantCode, ENT_COMPAT, 'UTF-8').'" /></div>

            <label>'.$this->l('iPay88 Merchant Key  : ').'</label>
			<div class="margin-form"><input type="text" size="33" name="merchantKey" value="'.htmlentities($merchantKey, ENT_COMPAT, 'UTF-8').'" /></div>



            <br /><center><input type="submit" name="submitipay88" value="'.$this->l('Update settings').'" class="button" /></center>
		</fieldset>
		</form><br /><br />
		<fieldset class="width3">
			<legend><img src="../img/admin/warning.gif" />'.$this->l('Information').'</legend>
			'.$this->l('In order to use your iPay88 payment module, you have to configure your iPay88 account. Website : http://www.ipay88.com').'<br /><br />

		</fieldset>';
	}

	
	function iPay88_signature($source)
	{
		return base64_encode(hex2bin(sha1($source)));
	}

	function hex2bin($hexSource){
	$strlen = strlen($hexSource);
	for ($i=0;$i<strlen($hexSource);$i=$i+2){
		$bin .= chr(hexdec(substr($hexSource,$i,2)));
	}
	return $bin;
	}

	
	public function hookPayment($params)
	{
		global $smarty,$cart, $cookie;

		$selected_currency = new Currency((int)($params['cart']->id_currency));
		$address = new Address(intval($params['cart']->id_address_invoice));
		$customer = new Customer(intval($params['cart']->id_customer));
		$merchantCode = Configuration::get('ipay88_merchantCode');
        $merchantKey = Configuration::get('ipay88_merchantKey');
		$currency = $this->getCurrency();
		

		//if (!Validate::isLoadedObject($address) OR !Validate::isLoadedObject($customer))
		if (!Validate::isLoadedObject($address) OR !Validate::isLoadedObject($customer) OR !Validate::isLoadedObject($selected_currency))
			return $this->l('iPay88 error: (invalid address or customer)');

		$products = $params['cart']->getProducts();

		foreach ($products as $key => $product)
		{
			$products[$key]['name'] = str_replace('"', '\'', $product['name']);
			if (isset($product['attributes']))
				$products[$key]['attributes'] = str_replace('"', '\'', $product['attributes']);
			$products[$key]['name'] = htmlentities(utf8_decode($product['name']));
			$products[$key]['description_short'] = htmlentities(utf8_decode($product['description_short']));
			$products[$key]['ipay88Amount'] = number_format(Tools::convertPrice($product['price_wt'], $currency), 2, '.', '');
		}
		
		$RN = intval($params['cart']->id);
        $AMT=number_format($params['cart']->getOrderTotal(), 2, '.', '');

		$HashAmount = str_replace(".","",str_replace(",","",$AMT));
		$iso_currency = $selected_currency->iso_code;
		$str = sha1($merchantKey . $merchantCode . $RN . $HashAmount . $iso_currency);
		
		for ($i=0;$i<strlen($str);$i=$i+2)
		{
        $ipaySignature .= chr(hexdec(substr($str,$i,2)));
		}
     
        $sg = base64_encode($ipaySignature);


        //Modified typing material



		
		$smarty->assign(array(
			'MerchantCode' 	=> $merchantCode,
            'MerchantKey' 	=> $merchantKey,
			'RefNo'			=> $RN,
			'Amount'		=> $AMT,
			'Currency' 		=> $iso_currency,
			'ProdDesc' 		=> $products[$key]['name'],
			'UserName' 		=> $customer->firstname.' '.$customer->lastname,
			'UserEmail' 	=> "xxxx".$customer->email,
			'UserContact' 	=> "xxxx".$address->phone,
			'Remark' 		=> $products[$key]['name'],
			'Lang' 			=> "UTF-8",
			'Signature' 	=> $sg,
			'ipay88Url' 	=> $this->getipay88Url(),
			'shipping' 		=> number_format(Tools::convertPrice(($params['cart']->getOrderShippingCost() + $params['cart']->getOrderTotal(true, 6)), $currency), 2, '.', ''),
			'discounts' 	=> $params['cart']->getDiscounts(),
			'id_cart' 		=> intval($params['cart']->id),
			'goBackUrl' 	=> 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.intval($params['cart']->id).'&id_module='.intval($this->id),
			'returnUrl' 	=> 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/ipay88/validation.php',
			'this_path' 	=> $this->_path
		));
		

		return $this->display(__FILE__, 'ipay88.tpl');
    }


	public function getL($key)
	{
		$translations = array(
			'mc_gross' => $this->l('ipay88 key \'mc_gross\' not specified, can\'t control amount paid.'),
			'payment_status' => $this->l('ipay88 key \'payment_status\' not specified, can\'t control payment validity'),
			'payment' => $this->l('Payment: '),
			'custom' => $this->l('ipay88 key \'custom\' not specified, can\'t rely to cart'),
			'txn_id' => $this->l('ipay88 key \'txn_id\' not specified, transaction unknown'),
			'mc_currency' => $this->l('ipay88 key \'mc_currency\' not specified, currency unknown'),
			'cart' => $this->l('Cart not found'),
			'order' => $this->l('Order has already been placed'),
			'transaction' => $this->l('iPay88 Transaction ID: '),
			'verified' => $this->l('The iPay88 transaction could not be VERIFIED.'),
			'connect' => $this->l('Problem connecting to the iPay88 server.'),
			'nomethod' => $this->l('No communications transport available.'),
			'socketmethod' => $this->l('Verification failure (using fsockopen). Returned: '),
			'curlmethod' => $this->l('Verification failure (using cURL). Returned: '),
			'curlmethodfailed' => $this->l('Connection using cURL failed'),
		);
		return $translations[$key];
	}
}

?>