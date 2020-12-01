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

/**
 * @since 1.5.0
 * @uses  ModuleFrontControllerCore
 */
class CardstreamPaymentModuleFrontController extends ModuleFrontController {
	public function init() {
		parent::init();
	}

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent() {

		parent::initContent();

		if (in_array(Configuration::get('CARDSTREAM_INTEGRATION_TYPE'), ['direct', 'direct_v2'], true)) {

			// Must send post data here otherwise we'd use Tools::getAllValues()
			// which includes GET and POST data :(
			$req = $this->module->generateDirectPaymentForm($this->context, $_POST);

			$res = $this->module->makeRequest($this->module->gateway_url, $req);

			$this->module->validatePayment($this->context, $res);
		} else {
			$this->context->smarty->assign(array(
				'frontend'             => Configuration::get('CARDSTREAM_FRONTEND'),
				'url'                  => $this->module->gateway_url,
				'this_path'            => $this->module->getPathUri(),
				'this_path_ssl'        => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
				'form'                 => $this->module->getFormParameters($this->context),
			));


            if (in_array(Configuration::get('CARDSTREAM_INTEGRATION_TYPE'), ['iframe'], true)) {
                $this->setTemplate('module:cardstream/views/templates/front/iframe_payment.tpl');
            } else {
                $this->setTemplate('module:cardstream/views/templates/front/hosted_payment.tpl');
            }
		}
	}
}
