<?php

namespace Indigo\Erp\Stock;

class Job_Supplier_Available extends Job_Supplier
{
	public $delete = true;

	public function execute($job, $data)
	{
		return Model_Price::query()
			->set(array(
				'available'  => \Arr::get($data, 'key', 1),
				'updated_at' => time()
			))
			->where('external_id', 'IN', \Arr::get($data, 'ids'))
			->where('supplier_id', \Arr::get($data, 'supplier_id'))
			->update();
	}
}
