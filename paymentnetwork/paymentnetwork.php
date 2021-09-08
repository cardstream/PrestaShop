<?php

use P3\SDK\Gateway;
use PrestaShop\PrestaShop\Core\Domain\Order\CancellationActionType;
use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorException;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

class PaymentNetwork extends PaymentModule {

    /**
     * @var Gateway
     */
    public $gateway;

    protected $brand_config;

    /**
     * Default construction of the Payment Module
     *
     * The information of the payment module is described in the
     * constructor and extracted by Prestashop automatically in
     * the modules admin area
     */
    public function __construct() {

        $this->version     = '2.0.2';
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->name        = 'paymentnetwork';
        $this->brand_config = include(_PS_MODULE_DIR_ . $this->name . '/config.php');

        $this->displayName = $this->brand_config['gateway_title'];
        $this->description = $this->brand_config['method_description'];
        $this->author      = $this->brand_config['author'];

        $this->bootstrap   = true;
        $this->tab         = 'payments_gateways';
        $this->controllers = ['validation', 'direct', 'hosted', 'error'];

        parent::__construct();

        if (isset($this->context->customer)) {
            $this->is_logged = $this->context->customer->isLogged();
        }

        $merchantID = Configuration::get('PAYMENT_NETWORK_MERCHANT_ID');
        $merchantSecret = Configuration::get('PAYMENT_NETWORK_MERCHANT_PASSPHRASE');
        $gatewayUrl = Configuration::get('PAYMENT_NETWORK_GATEWAY_URL');

        $this->gateway = new Gateway(
            $merchantID,
            $merchantSecret,
            $gatewayUrl
        );
    }

    /**
     * Hook from prestashop to allow module installation
     *
     * @return boolean Whether the install was successful
     * @throws PrestaShopException
     */
    public function install() {
        // Log will likely never occur due to missing settings.
        $this->log('Running install hook.');
        // If prestashop multi store is active, change context to global
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (
            !parent::install()
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('actionProductCancel')
            || !$this->registerHook('actionFrontControllerSetMedia')
            || !$this->registerHook('paymentReturn')
            || !$this->createTable()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function createTable()
    {
        return Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'paymentnetwork_wallets` (
            `merchant_id` varchar(255) NOT NULL,
            `customer_email` varchar(255) NOT NULL,
            `wallet_id` varchar(255) NOT NULL
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8;
');
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
            Configuration::deleteByName('PAYMENT_NETWORK_MERCHANT_ID') &&
            Configuration::deleteByName('PAYMENT_NETWORK_FRONTEND') &&
            Configuration::deleteByName('PAYMENT_NETWORK_MERCHANT_PASSPHRASE') &&
            Configuration::deleteByName('PAYMENT_NETWORK_DEBUG') &&
            Configuration::deleteByName('PAYMENT_NETWORK_INTEGRATION_TYPE') &&
            Configuration::deleteByName('PAYMENT_NETWORK_FORM_RESPONSIVE') &&
            Configuration::deleteByName('PAYMENT_NETWORK_GATEWAY_URL') &&
            Configuration::deleteByName('PAYMENT_NETWORK_CUSTOMER_WALLETS') &&
            $this->deleteTables() &&
            parent::uninstall()
        );
    }

    protected function deleteTables()
    {
        return Db::getInstance()->execute('
        DROP TABLE IF EXISTS `'._DB_PREFIX_.'paymentnetwork_wallets`;
    ');
    }

    /**
     * Hook from Prestashop to allow the module to display payment options
     *
     * @return PaymentOption[]	A list of payment options
     */
    public function hookPaymentOptions($params) {
        $type = Configuration::get('PAYMENT_NETWORK_INTEGRATION_TYPE');
        $this->log('Running payment options hook');
        if (!$this->active) {
            return false;
        }

        $paymentOption = new PaymentOption();
        $paymentOption->setCallToActionText($this->l(Configuration::get('PAYMENT_NETWORK_FRONTEND')))
            ->setAction($this->context->link->getModuleLink($this->name, 'hosted', array(), true));

        switch ($type) {
            case 'direct':
                $paymentOption
                    ->setForm($this->generateDirectForm())
                    ->setAdditionalInformation($this->brand_config['formfill_notice']);
                break;
            case 'iframe':
            case 'hosted':
            case 'modal':
            default:
                $this->context->smarty->assign(
                    array(
                        'frontend'      => Configuration::get('PAYMENT_NETWORK_FRONTEND'),
                        'this_path'     => $this->_path,
                        'this_path_bw'  => $this->_path,
                        'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
                        'tpl_dir'       => _THEME_DIR_,
                    )
                );
                $paymentOption->setAdditionalInformation(
                    $this->l($this->brand_config['redirect_notice'])
                );
        };

        // If we were to return a bunch of payment options
        // they would all show up so instead just generate
        // the right form and give it as the only option.
        return array($paymentOption);
    }

    public function hookActionProductCancel($params)
    {
        /** @var Order $order */
        $order = $params['order'];

        $isEligibleForRefund = isset($params['action'])
            ? in_array($params['action'], [CancellationActionType::STANDARD_REFUND, CancellationActionType::PARTIAL_REFUND])
            : $order->hasBeenPaid();

        $refundStatus = (int)Configuration::get('PS_OS_REFUND');
        $cancelStatus = (int)Configuration::get('PS_OS_CANCELED');

        if ($isEligibleForRefund && $order->current_state !== $refundStatus) {
            try {
                /** @var OrderPayment $payment */
                $payment = current($order->getOrderPayments());

                $refundData = \Tools::getValue('cancel_product');

                $key = current(preg_grep('/amount_.+/D', array_keys( $refundData )));
                $currency = new Currency((int)($payment->id_currency));

                $preparedAmount = \P3\SDK\AmountHelper::calculateAmountByCurrency($refundData[$key], $currency->iso_code);

                $res = $this->gateway->refundRequest($payment->transaction_id, $preparedAmount);

                if ($res['response']['state'] === 'canceled') {
                    $order->setCurrentState($cancelStatus);
                } else {
                    $order->setCurrentState($refundStatus);
                }

                $order->save();
                return true;
            } catch (Exception $exception) {
                throw new ModuleErrorException($exception->getMessage());
            }
        }
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
        if ($params['order']->getCurrentState() === _PS_OS_PAYMENT_) {
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

        return $this->context->smarty->fetch('module:paymentnetwork/views/templates/front/payment_confirmation.tpl');
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

        if ($params['order']->getCurrentState() === _PS_OS_PAYMENT_) {
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

        return $this->context->smarty->fetch('module:paymentnetwork/views/templates/front/payment_confirmation.tpl');
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        // Only on product page
        if ('order' === $this->context->controller->php_self) {
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-payform',
                'modules/'.$this->name.'/views/js/payform.js',
                [
                    'priority' => 200,
                    'attribute' => 'async',
                ]
            );
            $this->context->controller->registerJavascript(
                'module-'.$this->name.'-direct-integration-validations',
                'modules/'.$this->name.'/views/js/direct-integration.js',
                [
                    'priority' => 200,
                    'attribute' => 'async',
                ]
            );
        }
    }

    /**
     * Update configuration for any changes made in the module admin section
     */
    public function getContent() {
        $this->log('Updating module configuration');
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('PAYMENT_NETWORK_MERCHANT_ID', Tools::getvalue('payment_network_merchant_id'));
            Configuration::updateValue('PAYMENT_NETWORK_INTEGRATION_TYPE', Tools::getvalue('payment_network_integration_type'));
            Configuration::updateValue('PAYMENT_NETWORK_FRONTEND', Tools::getvalue('payment_network_frontend'));
            Configuration::updateValue('PAYMENT_NETWORK_MERCHANT_PASSPHRASE', Tools::getvalue('payment_network_passphrase'));
            Configuration::updateValue('PAYMENT_NETWORK_DEBUG', Tools::getvalue('payment_network_debug'));
            Configuration::updateValue('PAYMENT_NETWORK_FORM_RESPONSIVE', Tools::getvalue('payment_network_form_responsive'));
            Configuration::updateValue('PAYMENT_NETWORK_GATEWAY_URL', Tools::getvalue('payment_network_gateway_url'));
            Configuration::updateValue('PAYMENT_NETWORK_CUSTOMER_WALLETS', Tools::getvalue('payment_network_customer_wallets'));
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
                'title' => $this->l($this->brand_config['admin_module_settings_title']),
            ),
            'input'  => array(
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Merchant ID'),
                    'name'     => 'payment_network_merchant_id',
                    'class'    => 'fixed-width-md',
                    'required' => true,
                ),
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Integration Type'),
                    'name'     => 'payment_network_integration_type',
                    'class'    => 'fixed-width-xl',
                    'required' => true,
                    'options'  => array(
                        'query' =>  array(
                            array(
                                'value' => 'hosted',
                                'label' => $this->l('Hosted'),
                            ),
                            array(
                                'value' => 'modal',
                                'label' => $this->l('Hosted (Modal)'),
                            ),
                            array(
                                'value' => 'iframe',
                                'label' => $this->l('Hosted (Embedded)'),
                            ),
                            array(
                                'value' => 'direct',
                                'label' => $this->l('Direct 3D-Secure'),
                            ),
                        ),
                        'id'    => 'value',
                        'name'  => 'label',
                    ),
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Passphrase / Shared Secret'),
                    'name'     => 'payment_network_passphrase',
                    'class'    => 'fixed-width-xl',
                    'required' => true,
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Frontend'),
                    'name'     => 'payment_network_frontend',
                    'class'    => 'fixed-width-xl',
                    'required' => true,
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('Gateway URL'),
                    'desc'     => $this->l('Please enter your gateway URL.'),
                    'name'     => 'payment_network_gateway_url',
                    'class'    => 'fixed-width-xl',
                    'required' => true,
                ),
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Form Responsive'),
                    'desc'     => $this->l('Allow the hosted form to automatically size depending on the device '),
                    'name'     => 'payment_network_form_responsive',
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
                    'label'    => $this->l('Customer Wallets'),
                    'name'     => 'payment_network_customer_wallets',
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
                array(
                    'type'     => 'select',
                    'label'    => $this->l('Debug'),
                    'desc'     => $this->l('This mode is insecure and will slow performance. Intended for debugging ONLY.'),
                    'name'     => 'payment_network_debug',
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
        $helper->fields_value['payment_network_merchant_id']      = Configuration::get('PAYMENT_NETWORK_MERCHANT_ID');
        $helper->fields_value['payment_network_integration_type'] = Configuration::get('PAYMENT_NETWORK_INTEGRATION_TYPE');
        $helper->fields_value['payment_network_passphrase']       = Configuration::get('PAYMENT_NETWORK_MERCHANT_PASSPHRASE');
        $helper->fields_value['payment_network_frontend']         = Configuration::get('PAYMENT_NETWORK_FRONTEND');
        $helper->fields_value['payment_network_debug']            = Configuration::get('PAYMENT_NETWORK_DEBUG');
        $helper->fields_value['payment_network_form_responsive']  = Configuration::get('PAYMENT_NETWORK_FORM_RESPONSIVE');
        $helper->fields_value['payment_network_gateway_url']      = Configuration::get('PAYMENT_NETWORK_GATEWAY_URL');
        $helper->fields_value['payment_network_customer_wallets'] = Configuration::get('PAYMENT_NETWORK_CUSTOMER_WALLETS');
        return $helper->generateForm($fields_form);
    }

    /**
     * Generate the direct form with card fields
     */
    private function generateDirectForm() {
        $this->log('Generating the direct form');
        $this->context->smarty->assign(
            array(
                'url' => $this->context->link->getModuleLink($this->name, 'direct', array(), true),
                'device_data'  => [
                    'browserInfo[deviceChannel]'				=> 'browser',
                    'browserInfo[deviceIdentity]'			=> (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : null),
                    'browserInfo[deviceTimeZone]'			=> '0',
                    'browserInfo[deviceCapabilities]'		=> '',
                    'browserInfo[deviceScreenResolution]'	=> '1x1x1',
                    'browserInfo[deviceAcceptContent]'		=> (isset($_SERVER['HTTP_ACCEPT']) ? htmlentities($_SERVER['HTTP_ACCEPT']) : null),
                    'browserInfo[deviceAcceptEncoding]'		=> (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? htmlentities($_SERVER['HTTP_ACCEPT_ENCODING']) : null),
                    'browserInfo[deviceAcceptLanguage]'		=> (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null),
                    'browserInfo[deviceAcceptCharset]'		=> (isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? htmlentities($_SERVER['HTTP_ACCEPT_CHARSET']) : null),
                ],
            )
        );

        return $this->context->smarty->fetch('module:paymentnetwork/views/templates/front/direct_payment.tpl');
    }

    /**
     * Log out a message to the logger and error log
     * for debugging and error purposes
     */
    public function log($msg) {
        static $canLog;
        if (is_null($canLog)) {
            $canLog = Configuration::get('PAYMENT_NETWORK_DEBUG') == 'Y';
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
    protected function redirect($url) {
        $iframe = Configuration::get('PAYMENT_NETWORK_INTEGRATION_TYPE') === 'iframe';
        $this->log(sprintf('Redirecting user to URL %s %s iframe', $url, ($iframe ? 'w/' : 'w/o')));
        if ($iframe) {
            echo <<<SCRIPT
<script>window.top.location.href = "$url";</script>
SCRIPT;

        } else {
            Tools::redirect($url);
        }
    }

    /**
     * Return an array of parameters required for gateway request
     *
     * @param $order
     * @return array
     */
    public function captureOrder($order) {
        $this->log('Generating generic payment fields');
        $invoiceAddress = new Address((int)$order->cart->id_address_invoice);

        $currency = new Currency((int)($order->cart->id_currency));
        $country = new Country((int)($invoiceAddress->id_country));

        $merchantId = Configuration::get('PAYMENT_NETWORK_MERCHANT_ID');
        $email = $this->context->customer->email;

        $billingAddress = trim($invoiceAddress->address1);
        if (!empty($invoiceAddress->address2)) {
            $billingAddress.= "\n" . trim($invoiceAddress->address2);
        }

        if (!empty($invoiceAddress->city)) {
            $billingAddress.= "\n" . trim($invoiceAddress->city);
        }

        if (!empty($invoiceAddress->country)) {
            $billingAddress.= "\n" . trim($invoiceAddress->country);
        }

        // Only make into an order when a successful order was created!
        $this->validateOrder(
            (int)$this->context->cart->id,
            Configuration::get('PS_OS_PREPARATION'),
            $this->context->cart->getOrderTotal(),
            $this->displayName,
            $this->l('Processing in progress'),
            [],
            $this->context->currency->id,
            false,
            $this->context->customer->secure_key
        );

        $order_id = Order::getIdByCartId($this->context->cart->id);

        $parameters = array(
            'merchantID'        => $merchantId,
            'currencyCode'      => $currency->iso_code,
            'countryCode'       => $country->iso_code,
            'action'            => "SALE",
            'type'              => 1,
            'transactionUnique' => (int)($this->context->cart->id) . '_' . date('YmdHis') . '_' . $order->cart->secure_key,
            'orderRef'          => $order_id,
            'amount'            => \P3\SDK\AmountHelper::calculateAmountByCurrency(
                $order->cart->getOrderTotal(),
                $currency->iso_code
            ),
            'customerName'      => $invoiceAddress->firstname . ' ' . $invoiceAddress->lastname,
            'customerAddress'   => $billingAddress,
            'customerPostcode'  => $invoiceAddress->postcode,
            'customerEmail'     => $email,
            'merchantData'      => sprintf(
                'PrestaShop %s module v%s (%s integration)',
                $this->name,
                $this->version,
                Configuration::get('PAYMENT_NETWORK_INTEGRATION_TYPE')
            ),
            'customerPhone'     => empty($invoiceAddress->phone) ? $invoiceAddress->phone_mobile : $invoiceAddress->phone
        );

        if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parameters['remoteAddress'] = $_SERVER['REMOTE_ADDR'];
        }

        /**
         * Wallets
         */
        if (Configuration::get('PAYMENT_NETWORK_CUSTOMER_WALLETS') === 'Y'
            && Configuration::get('PAYMENT_NETWORK_INTEGRATION_TYPE') == 'modal'
            && $this->is_logged
        ) {
            $wallet_table_name = _DB_PREFIX_ . 'paymentnetwork_wallets';

            $wallets = DB::getInstance()->executeS("SELECT wallet_id FROM $wallet_table_name WHERE merchant_id = '$merchantId' AND customer_email = '$email' LIMIT 1");

            //If the customer wallet record exists.
            if (count($wallets) > 0)
            {
                //Add walletID to request.
                $parameters['walletID'] = $wallets[0]['wallet_id'];
            } else {
                //Create a new wallet.
                $parameters['walletStore'] = 'Y';
            }
            $parameters['walletEnabled'] = 'Y';
            $parameters['walletRequired'] = 'Y';
        }

        return $parameters;
    }

    /**
     * Wallet creation.
     *
     * A wallet will always be created if a walletID is returned. Even if payment fails.
     */
    protected function createWallet($response) {
        //when the wallets is enabled, the user is logged in and there is a wallet ID in the response.

        if (
            Configuration::get('PAYMENT_NETWORK_CUSTOMER_WALLETS') === 'Y'
            && $this->context->customer->isLogged()
            && isset($response['walletID'], $response['merchantID'], $response['customerEmail'])
        ) {

            $wallet_table_name = _DB_PREFIX_ . 'paymentnetwork_wallets';

            $merchantId = $response['merchantID'];
            $email = $response['customerEmail'];

            $wallets = DB::getInstance()->executeS("SELECT wallet_id FROM $wallet_table_name WHERE merchant_id = '$merchantId' AND customer_email = '$email' LIMIT 1");

            //If the customer wallet record does not exists.
            if (count($wallets) == 0) {
                //Add walletID to request.
                DB::getInstance()->insert('paymentnetwork_wallets',
                    [
                        'merchant_id' => $merchantId,
                        'customer_email' => $response['customerEmail'],
                        'wallet_id' => $response['walletID'],
                    ]
                );
            }
        }
    }

    /**
     * @param null $response
     * @return string
     */
    public function processResponse($response = null) {
        try {
            // ThreeDSVersion 1
            if (isset($_REQUEST['MD'], $_REQUEST['PaRes'])) {
                $req = array(
                    'action' => 'SALE',
                    'merchantID' => Configuration::get('PAYMENT_NETWORK_MERCHANT_ID'),
                    'xref' => $_COOKIE['xref'],
                    'threeDSMD' => $_REQUEST['MD'],
                    'threeDSPaRes' => $_REQUEST['PaRes'],
                    'threeDSPaReq' => (isset($_REQUEST['PaReq']) ? $_REQUEST['PaReq'] : null),
                );

                $response = $this->gateway->directRequest($req);
            }

            // ThreeDSVersion 2 with challenges
            if (isset($_POST['threeDSMethodData']) || isset($_POST['cres'])) {
                $req = array(
                    'merchantID' => Configuration::get('PAYMENT_NETWORK_MERCHANT_ID'),
                    'action' => 'SALE',
                    // The following field must be passed to continue the 3DS request
                    'threeDSRef' => $_COOKIE['threeDSRef'],
                    'threeDSResponse' => $_POST,
                );

                $response = $this->gateway->directRequest($req);
            }

            if (!isset($response)) {
                $response = $_POST;
            }

            $this->createWallet($response);

            $this->gateway->verifyResponse(
                $response,
                [$this, 'onThreeDSRequired'],
                [$this, 'onSuccess']
            );
        } catch (\Exception $exception) {
            if (isset($response['orderRef'], $response['xref'])) {
                $order = new Order($response['orderRef']);
                $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
            }

            $link = $this->context->link->getModuleLink($this->name, 'error', array('error_msg' => $exception->getMessage()), true);

            $this->redirect($link);
        }
    }

    /**
     * @param $response
     *
     * @return void
     * @throws Exception
     */
    public function onSuccess($response) {
        $this->context->customer = new Customer($this->context->cart->id_customer);

        // Only make into an order when a successful order was created!
        $order = new Order($response['orderRef']);

        $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
        /** @var OrderPayment $payment */
        $payment = current($order->getOrderPayments());
        $payment->transaction_id = $response['xref'];
        $payment->save();

        $base_url = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__;

        $link = sprintf('%sindex.php?controller=order-confirmation&id_cart=%s&id_module=%s&id_order=%s&key=%s',
            $base_url,
            $order->id_cart,
            $this->id,
            $response['orderRef'],
            $order->secure_key
        );

        $this->redirect($link);
    }

    public function onThreeDSRequired($threeDSVersion, $response) {
        // check for version
        if ($threeDSVersion >= 200) {
            // Silently POST the 3DS request to the ACS in the IFRAME
            echo Gateway::silentPost($response['threeDSURL'], $response['threeDSRequest']);

            // Remember the threeDSRef as need it when the ACS responds
            setcookie('threeDSRef', $response['threeDSRef'], time()+315);
        } else {
            echo Gateway::silentPost($response['threeDSURL'], $response['threeDSRequest']);
        }
    }
}
