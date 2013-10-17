<?php

namespace Indigo\Erp\Stock;

class Job_Supplier_Insert extends Job_Supplier
{
	public $delete = true;

	public function execute($job, $data)
	{
		return Model_Price::forge()->set($data)->save();
	}

	public function failure($job, $e)
	{
		throw $e;
	}
}
