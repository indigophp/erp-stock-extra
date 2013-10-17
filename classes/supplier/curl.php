<?php

namespace Indigo\Erp\Stock;

abstract class Supplier_Curl extends Supplier_Request
{
	protected function request($url, array $options = array())
	{
		$options['driver'] = 'curl';
		$return = parent::request($url, $options);

		$user_field = empty($this->model->user_field) ? 'user' : $this->model->user_field;
		$pass_field = empty($this->model->pass_field) ? 'pass' : $this->model->pass_field;

		$this->request->add_param(array(
			$user_field => $this->model->user,
			$pass_field => $this->model->pass
		));

		return $return;
	}

	protected function _download($file = 'price')
	{
		$this->request($this->model->update);
		return parent::_download($file);
	}
}