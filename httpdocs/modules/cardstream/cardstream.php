<?php
/**
 * 2018 Cardstream
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *  @author    Matthew James <support@cardstream.com>
 *  @copyright 2018 Cardstream Ltd
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;


 if (!defined('_PS_VERSION_')) {
	exit;
}

class Cardstream extends PaymentModule {

	const DEBUG_DESCRIPTION = 'This mode is insecure and will slow performance. Intended for debugging ONLY.';
	const CALLBACK_DESCRIPTION = 'Callbacks should be enabled by default on production so that customers who ' .
		'accidentally close the window during a successful payment will still have the order come through. '   .
		'However, on local network testing this can give a useful speed boost and can be disabled for ' .
		'developers and debuggers ONLY';
	const FORM_RESPONSIVE_DESCRIPTION = 'Allow the hosted form to automatically size depending on the device ' .
		'and screen resolution used';
	const GATEWAY_URL_DESCRIPTION = 'Allows the use of custom forms. Leave blank to use default';
	const INSECURE_ERROR = 'The %s module cannot be used under an insecure host and has been hidden for user protection';
	const REDIRECT_NOTICE = 'You will be redirected to the Payment Gateway for payment';
	const FORMFILL_NOTICE = 'Please fill in your payment details';
	const INVALID_REQUEST = 'INVALID REQUEST';
	const DIRECT_URL = 'https://gateway.cardstream.com/direct/';
	const HOSTED_URL = 'https://gateway.cardstream.com/hosted/';
	const THREEDS_TWO_HOSTED = 'https://test.3ds-pit.com/hosted/';
	const THREEDS_TWO_DIRECT = 'https://test.3ds-pit.com/direct/';
	const HOSTED_URL_MODAL = 'https://gateway.cardstream.com/hosted/modal';

	public $gateway_url;

	/**
	 * Default construction of the Payment Module
	 *
	 * The information of the payment module is described in the
	 * constructor and extracted by Prestashop automatically in
	 * the modules admin area
	 */
	public function __construct() {
		$this->bootstrap   = true;
		$this->name        = 'cardstream';
		$this->tab         = 'payments_gateways';
		$this->version     = '2.0.2';
		$this->author      = 'Cardstream';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		parent::__construct();

		$this->displayName            = 'Cardstream Payment Gateway';
		$this->description            = $this->l('Process payments with Cardstream');
		$this->ps_versions_compliancyCARDSTREAM_INTEGRATION_TYPE = array('min' => '1.7', 'max' => _PS_VERSION_);


		$this->gateway_url = Configuration::get('CARDSTREAM_GATEWAY_URL');
		$type = Configuration::get('CARDSTREAM_INTEGRATION_TYPE');

		if (
			// Make sure we're given an valid URL
			!empty($this->gateway_url) &&
			preg_match('/(http[s]?:\/\/[a-z0-9\.]+(?:\/[a-z]+\/?)+)/i', $this->gateway_url) != false
		) {
			// Prevent insecure requests
			$this->gateway_url = str_ireplace('http://', 'https://', $this->gateway_url);
			// Always append end slash
			if (preg_match('/(\.php|\/)$/', $this->gateway_url) == false) {
				$this->gateway_url .= '/';
			}
			// Prevent direct requests using hosted
			if ($type && $type == 'hosted' && preg_match('/(\/direct\/)$/i', $this->gateway_url) != false) {
				$this->gateway_url = self::HOSTED_URL;
			}
		} else {
		    switch ($type) {
				case 'direct_v2':
					$this->gateway_url = self::THREEDS_TWO_DIRECT;
                    break;
                case 'direct':
                    $this->gateway_url = self::DIRECT_URL;
                    break;
                case 'hosted_v3':
                    $this->gateway_url = self::HOSTED_URL_MODAL;
                    break;
                case 'iframe_v2':
                case 'iframe':
                case 'hosted_v2':
                    $this->gateway_url = self::THREEDS_TWO_HOSTED;
                    break;
                case 'hosted':
                default:
                    $this->gateway_url = self::HOSTED_URL;
                    break;

            }
		}
	}

	/**
	 * Hook from prestashop to allow module installation
	 * @return		boolean		Whether the install was successful
	 */
	public function install() {
		// Log will likely never occur due to missing settings.
		$this->log('Running install hook.');
		// If prestashop multi store is active, change context to global
		if (Shop::isFeatureActive()) {
			Shop::setContext(Shop::CONTEXT_ALL);
		}

		if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
			return false;
		}

		return true;
	}

	/**
	 * Hook from prestashop to allow module uninstallation
	 *
	 * We attempt to remove all configuration during this process so that
	 * nothing is left behind that could create a dirty install.
	 *
	 * @return		boolean		Whether the uninstall was successful
	 */
	public function uninstall() {
		$this->log('Running uninstall hook.');
		return (
			Configuration::deleteByName('CARDSTREAM_MERCHANT_ID') &&
			Configuration::deleteByName('CARDSTREAM_CURRENCY_ID') &&
			Configuration::deleteByName('CARDSTREAM_COUNTRY_ID') &&
			Configuration::deleteByName('CARDSTREAM_FRONTEND') &&
			Configuration::deleteByName('CARDSTREAM_MERCHANT_PASSPHRASE') &&
			Configuration::deleteByName('CARDSTREAM_DEBUG') &&
			Configuration::deleteByName('CARDSTREAM_CALLBACK') &&
			Configuration::deleteByName('CARDSTREAM_INTEGRATION_TYPE') &&
			Configuration::deleteByName('CARDSTREAM_FORM_RESPONSIVE') &&
			Configuration::deleteByName('CARDSTREAM_GATEWAY_URL') &&
			parent::uninstall()
		);
	}

	/**
	 * Hook from Prestashop to allow the module to display payment options
	 * @return		PaymentOption[]		A list of payment options
	 */
	public function hookPaymentOptions($params) {
		$this->log('Running payment options hook');
		$type = Configuration::get('CARDSTREAM_INTEGRATION_TYPE');

		if (!$this->active) {
			return false;
		}
//
//		if ($type == 'direct' && !$this->isSecure()) {
//			$this->log(sprintf(static::INSECURE_ERROR, $this->displayName));
//			return false;
//		}

		$paymentOption = new PaymentOption();
		$paymentOption->setCallToActionText($this->l(Configuration::get('CARDSTREAM_FRONTEND')))
			->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
			->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.gif'));

		switch ($type) {
			case 'direct':
			case 'direct_v2':
				$paymentOption->setForm($this->generateDirectForm())
					->setAdditionalInformation(static::FORMFILL_NOTICE);
				break;
			case 'iframe':
			case 'hosted':
			default:
				$this->context->smarty->assign(
					array(
						'frontend'      => Configuration::get('CARDSTREAM_FRONTEND'),
						'this_path'     => $this->_path,
						'this_path_bw'  => $this->_path,
						'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
						'tpl_dir'       => _THEME_DIR_,
					)
				);
				$paymentOption->setAdditionalInformation(
					$this->l(static::REDIRECT_NOTICE)
				);
		};

		// If we were to return a bunch of payment options
		// they would all show up so instead just generate
		// the right form and give it as the only option.
		return array($paymentOption);
	}

	/**
	 * Generate the direct form with card fields
	 */
	private function generateDirectForm() {
		$this->log('Generating the direct form');
        $in_array = in_array(Configuration::get('CARDSTREAM_INTEGRATION_TYPE'), ['hosted_v2', 'hosted_v3', 'iframe_v2', 'direct_v2'], true);
        $this->context->smarty->assign(
			array(
				'url' => $this->context->link->getModuleLink($this->name, 'payment', array(), true),
                'include_device_data'  => $in_array,
                'device_data'  => $deviceData,
            )
		);
        $str = $this->context->smarty->fetch('module:cardstream/views/templates/front/direct_payment.tpl');
        return $str;
	}

	/**
	 * Hook from Prestashop to show the confirmed order after validation
	 */
	public function hookPaymentReturn($params) {
		$this->log('Running payment return hook');
		if (!$this->active) {
			return;
		}

		if ($params['order']->module != $this->name) {
			return '';
		}
		if ($params['order']->getCurrentState() != _PS_OS_ERROR_) {
			$this->context->smarty->assign(
				array(
					'shop_name' => Configuration::get('PS_SHOP_NAME'),
					'status'    => 'ok',
					'id_order'  => (int)$params['order']->id,
				)
			);
		} else {
			$this->context->smarty->assign('status', 'failed');
		}

		return $this->context->smarty->fetch('module:cardstream/views/templates/front/payment_confirmation.tpl');
	}

	/**
	 * Duplicate fallback hook from Prestashop to show the confirmed order
	 * after validation
	 */
	public function hookOrderConfirmation($params) {
		$this->log('Running order confirmation hook');
		if ($params['order']->module != $this->name) {
			return "";
		}

		if ($params['order']->getCurrentState() != _PS_OS_ERROR_) {
			$this->context->smarty->assign(
				array(
					'shop_name' => Configuration::get('PS_SHOP_NAME'),
					'status'   => 'ok',
					'id_order' => (int)$params['order']->id
				)
			);
		} else {
			$this->context->smarty->assign('status', 'failed');
		}

		return $this->context->smarty->fetch('module:cardstream/views/templates/front/payment_confirmation.tpl');
	}

	/**
	 * Update configuration for any changes made in the module admin section
	 */
	public function getContent() {
		$this->log('Updating module configuration');
		$output = null;

		if (Tools::isSubmit('submit' . $this->name)) {
			Configuration::updateValue('CARDSTREAM_MERCHANT_ID', Tools::getvalue('cardstream_merchant_id'));
			Configuration::updateValue('CARDSTREAM_INTEGRATION_TYPE', Tools::getvalue('cardstream_integration_type'));
			Configuration::updateValue('CARDSTREAM_CURRENCY_ID', Tools::getvalue('cardstream_currency_id'));
			Configuration::updateValue('CARDSTREAM_COUNTRY_ID', Tools::getvalue('cardstream_country_id'));
			Configuration::updateValue('CARDSTREAM_FRONTEND', Tools::getvalue('cardstream_frontend'));
			Configuration::updateValue('CARDSTREAM_MERCHANT_PASSPHRASE', Tools::getvalue('cardstream_passphrase'));
			Configuration::updateValue('CARDSTREAM_CALLBACK', Tools::getvalue('cardstream_callback'));
			Configuration::updateValue('CARDSTREAM_DEBUG', Tools::getvalue('cardstream_debug'));
			Configuration::updateValue('CARDSTREAM_FORM_RESPONSIVE', Tools::getvalue('cardstream_form_responsive'));
			Configuration::updateValue('CARDSTREAM_GATEWAY_URL', Tools::getvalue('cardstream_gateway_url'));
			$output .= $this->displayConfirmation($this->l('Settings updated'));

		}

		return $output . $this->displayForm();
	}

	/**
	 * Display the modules configuration settings using a HelperForm
	 */
	public function displayForm() {
		$this->log('Displaying module settings');
		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

		$fields_form  = array();
		// Init Fields form array
		$fields_form[0]['form'] = array(
			'legend' => array(
				'title' => $this->l('Cardstream Settings'),
			),
			'input'  => array(
				array(
					'type'     => 'text',
					'label'    => $this->l('Merchant ID'),
					'name'     => 'cardstream_merchant_id',
					'class'    => 'fixed-width-md',
					'required' => true,
				),
				array(
					'type'     => 'select',
					'label'    => $this->l('Integration Type'),
					'name'     => 'cardstream_integration_type',
					'class'    => 'fixed-width-xl',
					'required' => true,
					'options'  => array(
						'query' =>  array(
							array(
								'value' => 'hosted',
								'label' => $this->l('Hosted'),
							),
                            array(
								'value' => 'hosted_v2',
								'label' => $this->l('Hosted 3-D Secure'),
							),
							array(
								'value' => 'hosted_v3',
								'label' => $this->l('Hosted (Modal)'),
							),
							array(
								'value' => 'iframe',
								'label' => $this->l('Hosted (Embedded)'),
							),
							array(
								'value' => 'direct',
								'label' => $this->l('Direct'),
							),
							array(
								'value' => 'direct_v2',
								'label' => $this->l('Direct 3-D Secure'),
							),
						),
						'id'    => 'value',
						'name'  => 'label',
					),
				),
				array(
					'type'     => 'text',
					'label'    => $this->l('Currency Code'),
					'name'     => 'cardstream_currency_id',
					'class'    => 'fixed-width-xs',
					'required' => true,
				),
				array(
					'type'     => 'text',
					'label'    => $this->l('Country Code'),
					'name'     => 'cardstream_country_id',
					'class'    => 'fixed-width-xs',
					'required' => true,
				),
				array(
					'type'     => 'text',
					'label'    => $this->l('Passphrase / Shared Secret'),
					'name'     => 'cardstream_passphrase',
					'class'    => 'fixed-width-xl',
					'required' => true,
				),
				array(
					'type'     => 'text',
					'label'    => $this->l('Frontend Text'),
					'name'     => 'cardstream_frontend',
					'class'    => 'fixed-width-xl',
					'required' => true,
				),
				array(
					'type'     => 'select',
					'label'    => $this->l('Callback'),
					'desc'     => $this->l(static::CALLBACK_DESCRIPTION),
					'name'     => 'cardstream_callback',
					'class'    => 'fixed-width-xs',
					'options'  => array(
						'query' =>  array(
							array(
								'value' => 'Y',
								'label' => $this->l('Enabled'),
							),
							array(
								'value' => 'N',
								'label' => $this->l('Disabled'),
							),
						),
						'id'    => 'value',
						'name'  => 'label',
					),
				),
				array(
					'type'     => 'select',
					'label'    => $this->l('Form Responsive'),
					'desc'     => $this->l(static::FORM_RESPONSIVE_DESCRIPTION),
					'name'     => 'cardstream_form_responsive',
					'class'    => 'fixed-width-xs',
					'options'  => array(
						'query' =>  array(
							array(
								'value' => 'Y',
								'label' => $this->l('Enabled'),
							),
							array(
								'value' => 'N',
								'label' => $this->l('Disabled'),
							),
						),
						'id'    => 'value',
						'name'  => 'label',
					),
				),
				array(
					'type'     => 'text',
					'label'    => $this->l('Gateway URL'),
					'desc'     => $this->l(static::GATEWAY_URL_DESCRIPTION),
					'name'     => 'cardstream_gateway_url',
					'class'    => 'fixed-width-xl',
					'required' => true,
				),
				array(
					'type'     => 'select',
					'label'    => $this->l('Debug'),
					'desc'     => $this->l(static::DEBUG_DESCRIPTION),
					'name'     => 'cardstream_debug',
					'class'    => 'fixed-width-xs',
					'options'  => array(
						'query' =>  array(
							array(
								'value' => 'N',
								'label' => $this->l('Disabled'),
							),
							array(
								'value' => 'Y',
								'label' => $this->l('Enabled'),
							),
						),
						'id'    => 'value',
						'name'  => 'label',
					),
				),
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);


		$helper = new HelperFormCore();

		// Module, token and currentIndex
		$helper->module          = $this;
		$helper->name_controller = $this->name;
		$helper->token           = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;

		// Language
		$helper->default_form_language    = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title          = $this->displayName;
		$helper->show_toolbar   = true; // false -> remove toolbar
		$helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action  = 'submit' . $this->name;
		$helper->toolbar_btn    = array(
			'save' =>
				array(
					'desc' => $this->l('Save'),
					'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
							  '&token=' . Tools::getAdminTokenLite('AdminModules'),
				),
			'back' => array(
				'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			)
		);

		// Load current values
		$helper->fields_value['cardstream_merchant_id']      = Configuration::get('CARDSTREAM_MERCHANT_ID');
		$helper->fields_value['cardstream_integration_type'] = Configuration::get('CARDSTREAM_INTEGRATION_TYPE');
		$helper->fields_value['cardstream_currency_id']      = Configuration::get('CARDSTREAM_CURRENCY_ID');
		$helper->fields_value['cardstream_country_id']       = Configuration::get('CARDSTREAM_COUNTRY_ID');
		$helper->fields_value['cardstream_passphrase']       = Configuration::get('CARDSTREAM_MERCHANT_PASSPHRASE');
		$helper->fields_value['cardstream_frontend']         = Configuration::get('CARDSTREAM_FRONTEND');
		$helper->fields_value['cardstream_callback']         = Configuration::get('CARDSTREAM_CALLBACK');
		$helper->fields_value['cardstream_debug']            = Configuration::get('CARDSTREAM_DEBUG');
		$helper->fields_value['cardstream_form_responsive']  = Configuration::get('CARDSTREAM_FORM_RESPONSIVE');
		$helper->fields_value['cardstream_gateway_url']      = Configuration::get('CARDSTREAM_GATEWAY_URL');
		return $helper->generateForm($fields_form);
	}

    /**
     * Generate a generic form for the payment before
     * specialising for each integration type.
     *
     * @param $order
     * @return array
     */
	private function generatePaymentForm($order) {
		$this->log('Generating generic payment fields');
		$invoiceAddress = new Address((int)$order->cart->id_address_invoice);

		$currency = new Currency((int)($order->cart->id_currency));

		$parameters = array(
			'merchantID'        => Configuration::get('CARDSTREAM_MERCHANT_ID'),
			'currencyCode'      => (
				is_numeric(Configuration::get('CARDSTREAM_CURRENCY_ID')) ?
				Configuration::get('CARDSTREAM_CURRENCY_ID') :
				$currency->iso_code_num
			),
			'countryCode'       => Configuration::get('CARDSTREAM_COUNTRY_ID'),
			'action'            => "SALE",
			'type'              => 1,
			'transactionUnique' => (int)($this->context->cart->id) . '_' . date('YmdHis') . '_' . $order->cart->secure_key,
			'orderRef'          => $order->cart->id,
			'amount'            => number_format($order->cart->getOrderTotal(), 2, '', ''),
			'customerName'      => $invoiceAddress->firstname . ' ' . $invoiceAddress->lastname,
			'customerAddress'   => trim($invoiceAddress->address1) . "\n" . trim($invoiceAddress->address2) . "\n" . trim($invoiceAddress->city),
			'customerPostcode'  => $invoiceAddress->postcode,
			'merchantData'      => sprintf(
				'PrestaShop %s module v%s (%s integration)',
				$this->name,
				$this->version,
				Configuration::get('CARDSTREAM_INTEGRATION_TYPE')
			),
			'customerPhone'     => empty($invoiceAddress->phone) ? $invoiceAddress->phone_mobile : $invoiceAddress->phone
		);

		$includeDeviceData = in_array(Configuration::get('CARDSTREAM_INTEGRATION_TYPE'), ['hosted_v2', 'hosted_v3', 'iframe_v2', 'direct_v2'], true);
        if ($includeDeviceData) {
			include('hardwareinfo.html');
            $parameters = array_merge($parameters, [
				'threeDSRedirectURL' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
                'threeDSVersion'    => 2,
                'threeDSOptions[browserAcceptHeader]'      => (isset($_SERVER['HTTP_ACCEPT']) ? htmlentities($_SERVER['HTTP_ACCEPT']) : null),
                'threeDSOptions[browserIPAddress]'         => (isset($_SERVER['REMOTE_ADDR']) ? htmlentities($_SERVER['REMOTE_ADDR']) : null),
                'threeDSOptions[browserJavaEnabled]'    => $_COOKIE['java'],
                'threeDSOptions[browserLanguage]'		   => $_COOKIE['language'],
                'threeDSOptions[browserScreenColorDepth]'  => $_COOKIE['screen_depth'],
                'threeDSOptions[browserScreenHeight]'      => $_COOKIE['screen_height'],
                'threeDSOptions[browserScreenWidth]'       => $_COOKIE['screen_width'],
                'threeDSOptions[browserTimeZone]'          => $_COOKIE['timezone'],
                'threeDSOptions[browserUserAgent]'		   => (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : null),
			]);
			unset($_COOKIE);
        }

        /**
		 * ATTENTION!
		 * THIS MUST NOT BE SET!
		 *
		 * I had a nice 4-10 hour search attempting to find out why this was
		 * getting killed by Prestashop and returning a JSON encoded order
		 * out for the world to see...
		 *
		 * Turns out that in classes/pdf/HTMLTemplateInvoice.php it checks
		 * for a GET/POST 'debug' variable with Tools::getValue('debug')
		 * and then kills the script with a JSON encoded of the order.
		 *
		 * Do not delete this and the commented code as any poor soul
		 * who attempts this will have a long jouney pulling a lot of
		 * their hair out.
		 *
		 */

		// if (Configuration::get('CARDSTREAM_DEBUG') == 'Y') {
		// 	$form['debug'] = '1';
		// }

		return $parameters;
	}

	/**
	 * Generates the direct request information before sending for payment
	 */
	public function generateDirectPaymentForm($order, $post) {
		$this->log('Generating direct payment fields');
		$form = $this->generatePaymentForm($order);

		$port_matrix = array(
			'cardNumber',
			'cardCVV',
			'cardExpiryMonth',
			'cardExpiryYear',
			'threeDSOptions',
			'MD' => 'threeDSMD',
			'PaRes' => 'threeDSPaRes',
			'PaReq' => 'threeDSPaReq',
		);

		foreach ($port_matrix as $key => $value) {
			// If the key is an integer - treat as a direct cast
			$isCastedKey = !(((int)$key) === $key);
			$cast = ($isCastedKey ? $key : $value);
			$isset = isset($post[$cast]);
			$this->log(sprintf(
				'isCastedKey %d, original key=>value %s=>%s, key used %s, isset? %d',
				(int)$isCastedKey,
				$key,
				$value,
				$cast,
				$isset
			));
			if ($isset) {
				$form[$value] = $post[$cast];
			}
		}
		unset($port_matrix);

		// Only attempt an xref with a 3DS transaction to continue without
		// needing the card details any longer - we will most likely not
		// have these during the refresh too.
		if (isset($form['threeDSPaRes'])) {
			if (isset($post['xref'])) {
				$form['xref'] = $post['xref'];
				unset($form['cardNumber']);
				unset($form['cardCVV']);
				unset($form['cardExpiryMonth']);
				unset($form['cardExpiryYear']);
			}
		}

		if (isset($form['cardExpiryYear']) && strlen($form['cardExpiryYear']) == 4) {
			$form['cardExpiryYear'] = substr($form['cardExpiryYear'], -2, 2);
		}

		// Fix for prestashop CCC
		foreach ($form as &$value) {
			$value = trim($value);
		}

		if (Configuration::get('CARDSTREAM_MERCHANT_PASSPHRASE')) {
			$form['signature'] = $this->createSignature(
				$form,
				Configuration::get('CARDSTREAM_MERCHANT_PASSPHRASE')
			);
		}
		return $form;
	}

    /**
     * Generates the hosted request information before sending for payment
     * @param $order
     * @return array
     */
	public function getFormParameters($order) {
		$this->log('Generating hosted payment fields');

		$form = $this->generatePaymentForm($order);

		$link = $this->context->link->getModuleLink($this->name, 'validation', array(), true);
        
        if (Configuration::get('INTEGRATION_TYPE') == 'hosted_v2') {
            //$form['threeDSVersion'] = 2;
        }

		$form['redirectURL'] = $link;
		if (Configuration::get('CARDSTREAM_CALLBACK', 'Y') == 'Y') {
			$form['callbackURL'] = $link;
		}
		if (Configuration::get('CARDSTREAM_FORM_RESPONSIVE', 'Y') == 'Y') {
			$form['formResponsive'] = 'Y';
		}

		// Fix for prestashop CCC
		foreach ($form as &$value) {
			$value = trim($value);
		}

		if (Configuration::get('CARDSTREAM_MERCHANT_PASSPHRASE')) {
			$form['signature'] = $this->createSignature(
				$form,
				Configuration::get('CARDSTREAM_MERCHANT_PASSPHRASE')
			);
		}

        return $form;
	}

	/**
	 * Adds our css file to the header
	 */
	public function hookHeader() {
		if (Configuration::get('PS_CATALOG_MODE')) {
			return;
		}

		$this->context->controller->addCSS($this->_path . 'views/css/cardstream.css', 'all');

	}

	/**
	 * Checks whether the client is connecting to the server with HTTPS
	 */
	public function isSecure() {
		return (
			(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
			(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ||
			(isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
		);
	}

	/**
	 * Create a signature so that the server and client can verify genuine
	 * payments or whether an attempted fraud was made.
	 */
	public function createSignature(array $data, $key, $algo = null) {
		if ($algo === null) {
			$algo = 'SHA512';
		}
		$this->log("Creating ${algo} signature for data with '${key}'");

		ksort($data);

		// Create the URL encoded signature string
		$ret = http_build_query($data, '', '&');

		// Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
		$ret = preg_replace('/%0D%0A|%0A%0D|%0A|%0D/i', '%0A', $ret);

		// Hash the signature string and the key together
		$ret = hash($algo, $ret . $key);

		// Prefix the algorithm if not the default
		if ($algo !== 'SHA512') {
			$ret = '{' . $algo . '}' . $ret;
		}

		return $ret;
	}

	/**
	 * Makes a curl request with data provided
	 * @return		array		The response
	 */
	public function makeRequest($url, $req) {
		//die(http_build_query($req));
		$this->log('Making request to ' . $url);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($req));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		parse_str(curl_exec($ch), $res);
		curl_close($ch);
		if ($res !== null && $res != '') {
			$this->log('Received response to request');
		} else {
			$this->log('Either the request failured or an empty response was received');
		}
		return $res;
	}

	/**
	 * Log out a message to the logger and error log
	 * for debugging and error purposes
	 */
	public function log($msg) {
		static $canLog;
		if (is_null($canLog)) {
			$canLog = Configuration::get('CARDSTREAM_DEBUG') == 'Y';
		}
		if ($canLog) {
			// Severity level (3 for error, 1 for information)
			$msg = sprintf('[%s v%s] %s', $this->name, $this->version, $msg);
			PrestaShopLogger::addLog($msg, 1);
			error_log($msg);
		}
	}

	/**
	 * Redirect the user with or without the iframe integration
	 * This allows us to redirect the user out of the iframe back into
	 * the browser again.
	 */
    private function redirect($url) {
        $iframe = Configuration::get('CARDSTREAM_INTEGRATION_TYPE') === 'iframe';
        $this->log(sprintf('Redirecting user to URL %s %s iframe', $url, ($iframe ? 'w/' : 'w/o')));
        if ($iframe) {
            echo <<<SCRIPT
<script>window.top.location.href = "$url";</script>
SCRIPT;

        } else {
            Tools::redirect($url);
        }
    }

	public function validatePayment($context, $data) {
		session_start();
		$this->log('Validating payment');

		// if (!$this->active) {
		// 	return;
		// }
		$error = null;
		$cart = null;
		$base_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__;

		$this->log('RECV: ' . var_export($data, true));
		if (isset($data['orderRef'])) {
			$this->context->cart = new Cart($data['orderRef']);
			$this->context->customer = new Customer($this->context->cart->id_customer);
			$cart = $context->cart;
			$this->context = $context;
		}

		// if (!Validate::isLoadedObject($cart) || empty($data)){
		// 	$this->log(static::INVALID_REQUEST);
		// 	$this->redirect($base_url);
		// 	exit;
		// }

		if ($data['responseCode'] == 65802 && $data['threeDSVersion'] == 1) {
			$this->log('A 3DS response was returned. The form must be completed to continue the transaction.');
			// 3D Secure process must complete
			$pageUrl = ($this->isSecure() ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . $_SERVER["REQUEST_URI"];
			die("
				<script>document.onreadystatechange = function() { document.getElementById('3ds').submit(); }</script>
				<form id='3ds' action=\"" . htmlentities($data['threeDSACSURL']) . "\" method=\"POST\">
					<input type=\"hidden\" name=\"MD\" value=\"" . htmlentities($data['threeDSMD']) . "\">
					<input type=\"hidden\" name=\"PaReq\" value=\"" . htmlentities($data['threeDSPaReq']) . "\">
					<input type=\"hidden\" name=\"TermUrl\" value=\"" . htmlentities($pageUrl) . "\">
				</form>
			");
		}

		else if ($data['responseCode'] == 65802) {	
			$_SESSION['threeDSRef'] = $data['threeDSRef'];
			$_SESSION['threeDSMethodData'] = $data['threeDSMethodData'];
			var_dump($data);
			die(include('3dsv2-form.php'));
		}

		if(isset($data['threeDSMethodData']) || $data['cres']) {
			$req = array(
				'merchantID' =>	Configuration::get('CARDSTREAM_MERCHANT_ID'),
				'threeDSRef' 	=> $_SESSION['threeDSRef'],
				'action'	=>	'SALE',
				'threeDSResponse'	=> $data,
			);
			$req['signature'] = $this->createSignature(
				$req,
				Configuration::get('CARDSTREAM_MERCHANT_PASSPHRASE')
			);
			$this->validatePayment($this->context, $this->makeRequest($this->gateway_url,$req));
		}

		if ($data['responseCode'] == 0) {
			$sig = $data['signature'];
			unset($data['signature']);
			$sig_check = $sig == $this->createSignature(
				$data,
				Configuration::get('CARDSTREAM_MERCHANT_PASSPHRASE')
			);
			if (!$sig_check) {
				$error = 'The signature could not be verified from the server request';
			}
			$amount = Cart::getTotalCart($cart->id);
            // Only make into an order when a successful order was created!
            $order_id = Order::getIdByCartId($cart->id);
            if ($order_id === false) {
                try {
                    $status = $this->validateOrder(
                        (int)$cart->id,
                        Configuration::get('PS_OS_PAYMENT'),
                        $amount,
                        $this->displayName,
                        $this->l('Payment Accepted'),
                        array(),
                        $this->context->currency->id,
                        false,
                        $this->context->customer->secure_key
                    );
                } catch (PrestaShopException $e) {
                    $this->log('Exception! ' . var_export($e, true));
                    $error = 'Error during payment validation';
                }
                $order_id = Order::getIdByCartId($cart->id);
            } else {
                $this->log('Artificially skipping order validation');
            }
            $link = sprintf('%sindex.php?controller=order-confirmation&id_cart=%s&id_module=%s&id_order=%s&key=%s',
                $base_url,
                $cart->id,
                $this->id,
                $order_id,
                $cart->secure_key
            );
            return $this->redirect($link);

		} else {
			$error = "Your payment was unsuccessful ({$data['responseMessage']} - {$data['responseCode']})";
		}

		if (!$error) {
			$error = 'An unknown error occured - please contact support!';
		}

		// Instead of setting an order which will never get re-done just go
		// back to the checkout/order page
		// try {
		// 	$this->validateOrder(
		// 		(int)$cart->id,
		// 		Configuration::get('PS_OS_ERROR'),
		// 		Tools::getValue('transactionUnique'),
		// 		$this->displayName,
		// 		$error
		// 	);
		// } catch (PrestaShopException $e) {
		// 	$this->log('Exception! ' . var_export($e, true));
		// 	$error = 'Error during order failure';
		// }

		$notifications = json_encode(array(
			'error' => $error ? $error : 'An unknown error occured',
			'warning' => null,
			'success' => null,
			'info' => null,
		));

		if (session_status() == PHP_SESSION_ACTIVE) {
			$_SESSION['notifications'] = $notifications;
		} elseif (session_status() == PHP_SESSION_NONE) {
			session_start();
			$_SESSION['notifications'] = $notifications;
		} else {
			setcookie('notifications', $notifications);
		}

		$this->redirect(sprintf('%sindex.php?controller=%s', $base_url, $error ? 'cart' : ''));
		exit;
	}
}
