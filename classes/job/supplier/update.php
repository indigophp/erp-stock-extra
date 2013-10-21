<?php

namespace Indigo\Erp\Stock;

class Job_Supplier_Update extends Job_Supplier
{
	public $delete = true;

	public function execute($job, $data)
	{
		// Trigger price update/change event and modify data
		$data = \Event::instance('supplier')->trigger('update', $data, 'array');
		$data = call_user_func_array('\\Arr::merge_assoc', $data);

		// This is all about update
		$data = $data[0];

		// Do not update some fields
		$set = \Arr::filter_keys($data, array('external_id', 'supplier_id'), true);

		$data['updated_at'] = time();

		return Model_Price::query()
			->set($set)
			->where('external_id', $data['external_id'])
			->where('supplier_id', $data['supplier_id'])
			->update();
	}
}
