<?php

namespace Indigo\Erp\Stock;

abstract class Supplier_Soap extends Supplier_Request
{
	protected function request($url, array $options = array())
	{
		$options['driver'] = 'soap';
		$options['trace'] = true;
		$options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS;
		return parent::request($url, $options);
	}

	public function set_function($name, $params = array())
	{
		$this->request->set_function($name)->add_param($params);
		return $this;
	}
}