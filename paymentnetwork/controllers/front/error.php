<?php

/**
 * @since 1.5.0
 * @uses  ModuleFrontControllerCore
 */
class PaymentNetworkErrorModuleFrontController extends ModuleFrontController {

	/**
	 * @see FrontController::initContent()
	 */
	public function initContent() {
        session_start();

        parent::initContent();

        $getvalue = Tools::getvalue('error_msg');
        $this->context->smarty->assign(array(
            'status' => 'failed',
            'error_msg' => $getvalue
        ));

        $this->setTemplate('module:paymentnetwork/views/templates/front/payment_error.tpl');
    }
}
