<?php

/**
 * @since 1.5.0
 */
class CardstreamValidationModuleFrontController extends ModuleFrontController
{

	public function postProcess()
	{
		parent::init();

		parent::initContent();

		$this->module->processResponse();

		exit;
	}
}
