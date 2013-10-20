<?php

namespace Indigo\Erp\Stock;

class Job_Supplier
{
	public $delete = true;

	public function execute($job, $data)
	{
		$sup = Supplier::forge($data['id']);
		$sup->{\Arr::get($data, 'method', 'change')}(\Arr::get($data, 'cached', false));
	}

	public function failure($job, $e)
	{
		$log = $job->getLogger();
		$payload = $job->getPayload();

		if ($e instanceof SupplierException)
		{
			$log->critical('Supplier error during execution of job: ' . $payload['job'] . ' (' .get_class($e) . ': ' . $e->getMessage() .  ')', array('payload' => $payload));
		}
		else
		{
			$log->critical('Runtime error during execution of job: ' . $payload['job'] . ' (' .get_class($e) . ': ' . $e->getMessage() .  ')', array('payload' => $payload));
		}

		// Log trace
		$log->debug($e->getTraceAsString());

		return false;
	}
}
