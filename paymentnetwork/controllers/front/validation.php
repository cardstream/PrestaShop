<?php

/**
 * @since 1.5.0
 */
class PaymentNetworkValidationModuleFrontController extends ModuleFrontController {

	public function postProcess() {
		parent::init();

		parent::initContent();

		$this->module->processResponse();

        exit;
	}
}
