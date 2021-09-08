<?php

/**
 * @since 1.5.0
 * @uses  ModuleFrontControllerCore
 */
class PaymentNetworkDirectModuleFrontController extends ModuleFrontController {

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent() {
        session_start();

		parent::initContent();

        $redirectUrl = $this->context->link->getModuleLink($this->module->name, 'validation', array(), true);
        if ('Y' === Configuration::get('PAYMENT_NETWORK_DEBUG')) {
            $redirectUrl.= '?XDEBUG_SESSION_START=asdf';
        }

        $data = array_merge(
            $this->module->captureOrder($this->context),
            [
                'cardNumber' => $_POST['cardNumber'],
                'cardCVV' => $_POST['cardCVV'],
                'cardExpiryMonth' => $_POST['cardExpiryMonth'],
                'cardExpiryYear' => $_POST['cardExpiryYear'],
				'threeDSRedirectURL' => $redirectUrl,
            ],
            $_POST['browserInfo']
        );

        $res = $this->module->gateway->directRequest($data);

        setcookie('xref', $res['xref'], time()+315);

        $this->module->processResponse($res);

        exit;
	}
}
