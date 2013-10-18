<?php

namespace Indigo\Erp\Stock;

class Job_Supplier
{
	public $delete = true;

	public function execute($job, $data)
	{
		ini_set('default_socket_timeout', 1000);
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
			$log->error('Runtime error in file ' . $e->getFile() . ' on line ' . $e->getLine() . ' during execution of job: ' . $payload['job'] . ' (' .get_class($e) . ': ' . $e->getMessage() .  ')', array('payload' => $payload));
		}
		return false;
	}
}
