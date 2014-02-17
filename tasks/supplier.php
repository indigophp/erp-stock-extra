<?php

namespace Fuel\Tasks;

use Infigo\Queue\Connector\DirectConnector;
use \Indigo\Queue\Queue;

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
			'id'     => $id,
			'cached' => (bool)\Cli::option('cached', \Cli::option('c', false)),
			'force'  => (bool)\Cli::option('force', \Cli::option('f', false)),
			'method' => $method
		);

		// Execute job immediately
		$execute = (bool)\Cli::option('execute', \Cli::option('e', false));

		// Create queue data
		if ($execute)
		{
			// Initialize logger
			$logger = clone \Log::instance();

			// Get original handler
			$handler = $logger->popHandler();
			$handler->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());
			$logger->pushHandler($handler);

			// Console handler
			$handler = new \Monolog\Handler\ConsoleHandler(\Monolog\Logger::NOTICE);
			$formatter = new \Monolog\Formatter\LineFormatter("%message% - %context%".PHP_EOL, "Y-m-d H:i:s");
			$handler->setFormatter($formatter);
			$logger->pushHandler($handler);

			// Add other handlers to logger through Event trigger
			\Event::instance('queue')->trigger('logger', $logger);

			$connector = new DirectConnector();
		}
		else
		{
			$connector = \Config::get('queue.queues.supplier');
		}

		$options = array(
			'delay' => $delay,
			'ttr'   => $ttr
		);

		$queue = new Queue('supplier', $connector);

		// Push job and data to queue
		$queue->push('Indigo\\Erp\\Stock\\Job_Supplier', $data, $options);
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
