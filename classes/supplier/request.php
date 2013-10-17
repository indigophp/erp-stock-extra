<?php

namespace Indigo\Erp\Stock;

abstract class Supplier_Request extends Supplier_Driver
{
	/**
	 * Request object
	 * @var Request
	 */
	protected $request;

	/**
	 * Response object
	 * @var Response
	 */
	protected $response;

	/**
	 * Create Request object
	 * @param  string $url     Resource URL
	 * @param  array  $options Array of options (must include driver)
	 * @return $this           Returns this for method chaining
	 */
	protected function request($url, array $options = array())
	{
		empty($options['driver']) && $options['driver'] = 'curl';
		$this->request = \Request::forge($url, $options);
		$this->get_config('auto_format', true) === false and $this->request->set_auto_format(false);
		return $this;
	}


	protected function execute()
	{
		try
		{
			$this->response = $this->request->execute()->response();
			return $this->response->status == 200 ? true : false;
		}
		catch (\RequestException $e)
		{
			throw new SupplierException($e->getMessage(), $e->getCode(), $e);
		}
	}

	protected function _download($file = 'price')
	{
		if ($this->execute() === true)
		{
			return $this->response->body;
		}
		return false;
	}
}