<?php

namespace Fuel\Tasks;

class Supplier
{
	/**
	 * Queue job
	 *
	 * @param	mixed		$id			Supplier id
	 * @param	string		$method		Method to run
	 * @param	integer		$delay
	 * @param	integer		$ttr		Time limit
	 * @return	null
	 */
	public function execute($id, $method, $delay = 0, $ttr = 300)
	{
		// Cast id
		is_numeric($id) and $id = (int)$id;

		// Job data
		$data = array(
			'id' => $id,
			'cached' => (bool)\Cli::option('cached', \Cli::option('c', false)),
			'method' => $method
		);

		// Execute job immediately
		$execute = (bool)\Cli::option('execute', \Cli::option('e', false));

		// Use Queue if available (greater performance)
		if (\Package::loaded('queue'))
		{
			// Create queue data
			if ($execute)
			{
				// Initialize logger
				$logger = clone \Log::instance();

				// Get original handler
				$handler = $logger->popHandler();
				$formatter = new \Monolog\Formatter\ContextLineFormatter("%level_name% - %datetime% --> %message% - %context%".PHP_EOL, "Y-m-d H:i:s");
				$handler->setFormatter($formatter);
				$logger->pushHandler($handler);

				// Console handler
				$handler = new \Monolog\Handler\ConsoleHandler(\Monolog\Logger::NOTICE);
				$formatter = new \Monolog\Formatter\LineFormatter("%message% - %context%".PHP_EOL, "Y-m-d H:i:s");
				$handler->setFormatter($formatter);
				$logger->pushHandler($handler);

				$queue = array('supplier', array('driver' => 'direct', 'logger' => $logger));
			}
			else
			{
				$queue = 'supplier';
			}

			$options = array(
				'delay' => $delay,
				'ttr'   => $ttr
			);

			// Push job and data to queue
			\Queue::push($queue, 'Indigo\\Erp\\Stock\\Job_Supplier', $data, $options);
		}
		else
		{
			try
			{
				$job = new Job_Supplier();
				return $job->execute(null, $data);
			}
			catch (\Exception $e)
			{
				// TODO: process exceptions
			}
		}
	}

	/**
	 * Queue update job
	 *
	 * @param	mixed		$id		Supplier id
	 * @param	integer		$delay
	 * @param	integer		$ttr	Time limit
	 * @return	null
	 */
	public function update($id, $delay = 0, $ttr = 300)
	{
		return $this->execute($id, 'update', $delay, $ttr);
	}

	/**
	 * Queue change job
	 *
	 * @param	mixed		$id		Supplier id
	 * @param	integer		$delay
	 * @param	integer		$ttr	Time limit
	 * @return	null
	 */
	public function change($id, $delay = 0, $ttr = 300)
	{
		return $this->execute($id, 'change', $delay, $ttr);
	}
}
