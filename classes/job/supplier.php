<?php

namespace Indigo\Erp\Stock;

class Job_Supplier
{
	public $delete = true;

	public function __construct()
	{
		// Reset connection
		$instance = \Config::get('db.active', 'default');
		unset(\Database_Connection::$instances[$instance]);
	}

	public function execute($job, $data)
	{
		$sup = Supplier::forge($data['id']);

		switch (\Arr::get($data, 'method', 'change'))
		{
			case 'update':
				$sup->update(\Arr::get($data, 'cached', false), \Arr::get($data, 'force', false));
				break;

			case 'change':
			default:
				$sup->change(\Arr::get($data, 'cached', false));
				break;
		}
	}

	public function failure($job, $e)
	{
		$log = $job->getLogger();
		$payload = $job->getPayload();

		if ($e instanceof SupplierException)
		{
			$log->critical('Supplier error during execution of job: {job} (' . $e->getMessage() .  ') Data: {data}', $payload);
		}
		else
		{
			$log->critical('Runtime error during execution of job: {job} (' .get_class($e) . ': ' . $e->getMessage() .  ') Data: {data}', $payload);

			// Log trace
			$log->debug($e->getTraceAsString());
		}

		return false;
	}
}
