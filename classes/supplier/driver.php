<?php
/**
 * Part of Fuel Core Extension.
 *
 * @package 	Fuel
 * @subpackage	Core
 * @version 	1.0
 * @author		Indigo Development Team
 * @license 	MIT License
 * @copyright	2013 - 2014 Indigo Development Team
 * @link		https://indigophp.com
 */

namespace Indigo\Erp\Stock;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

/**
 * Supplier driver
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
abstract class Supplier_Driver
{
	/**
	* Driver config
	*
	* @var array
	*/
	protected $config = array();

	/**
	 * Model_Supplier instance
	 *
	 * @var Model_Supplier
	 */
	protected $model;

	/**
	 * Fractal manager instance
	 *
	 * @var Model_Supplier
	 */
	protected $manager;

	/**
	* Driver constructor
	*
	* @param Model_Supplier $model  Model instance
	* @param array          $config Driver config
	*/
	public function __construct(Model_Supplier $model, array $config = array())
	{
		$this->config = $config;
		$this->model = $model;
		$this->manager = new Manager;
	}

	/**
	* Get a driver config
	*
	* @param  mixed $key     Config key
	* @param  mixed $default Default value
	* @return mixed Config value or whole config array
	*/
	public function get_config($key = null, $default = null)
	{
		return \Arr::get($this->config, $key, $default);
	}

	/**
	* Set a driver config
	*
	* @param  mixed $key   Config key or array to merge
	* @param  mixed $value Config value
	* @return Supplier_Driver
	*/
	public function set_config($key, $value = null)
	{
		\Arr::set($this->config, $key, $value);

		return $this;
	}

	/**
	 * Download files from suppliers
	 *
	 * @param  string  $file   Name of the file
	 * @param  boolean $cached Return already downloaded file
	 * @return mixed Result of download
	 */
	public function download($file = 'price', $cached = false)
	{
		// Return data from cache
		if ($cached === true && $this->get_config('cache.enabled', true) === true)
		{
			try
			{
				// Log: Returning file from cache
				return \Cache::get($this->get_config('cache.prefix', 'supplier') . '.' . $this->model->slug . '.' . $file);
			}
			catch (\CacheNotFoundException $e)
			{
				// Log: cached mode is selected, but the cache doesn't exist
			}
		}

		// Execute driver-specific download method
		$result = $this->_download($file);

		if ($result)
		{
			//Log: download successful
			if ($this->get_config('cache.enabled', true) === true)
			{
				$cache = \Cache::forge($this->get_config('cache.prefix', 'supplier') . '.' . $this->model->slug . '.' . $file, $this->get_config('cache'));
				$cache->set($result, $this->get_config('cache.expiration'));
			}

			return $result;
		}
		else
		{
			//Log: download failed
			throw new SupplierException("Download failed");
		}
	}

	/**
	 * Update existing products, insert new ones
	 *
	 * @param  boolean $cached Update from already downloaded files
	 * @param  boolean $force  Force update of all values
	 * @return boolean All products have been processed
	 */
	public function update($cached = false, $force = false)
	{
		// Get data from supplier
		if( ! $products = $this->_update($cached))
		{
			return false;
		}

		$manager = new Manager;
		$products = $manager->createData($products)->toArray();
		$products = reset($products);

		$count = array(0,0,0,0,0);
		$available = array();

		$price = $this->get_current_prices();

		// Get update job and queue
		$update_job = $this->get_job('update');
		$update = $this->get_queue('update');

		// Get insert job and queue
		$insert_job = $this->get_job('insert');
		$insert = $this->get_queue('insert');

		foreach ($products as $product)
		{
			// Default data casting and values
			if ( ! isset($product['price']))
			{
				continue;
			}

			$product['supplier_id'] = $this->model->id;

			// Foolproofness: removing sensitive fields from data
			$product = \Arr::filter_keys($product, array('id', 'product_id'), true);

			// Check if product already exists: update it if yes and insert if not
			if ($_price = \Arr::get($price, $product['external_id'], false))
			{
				// Check if product's price has been changed, or just became (un)available
				if ($product['price'] !== $_price['price'] or $force === true)
				{
					$count[0] += $update->push($update_job, array($product, $_price)) ? 1 : 0;
				}
				elseif($product['available'] !== $_price['available'])
				{
					$available[$product['available']][] = $product['external_id'];
				}
				else
				{
					$count[4]++;
				}
			}
			else
			{
				$count[3] += $insert->push($insert_job, $product) ? 1 : 0;
			}

			// Remove processed product from list
			\Arr::delete($price, $product['external_id']);
		}

		// Already unavailable products should not be updated
		$price = array_filter($price, function($item) use (&$count) {
			if ($item['available'] !== 0)
			{
				return true;
			}
			else
			{
				// This product is not updated, so increase this counter
				$count[4]++;
				return false;
			}
		});

		// Unprocessed products are treated as unavailable as we processed the whole stock
		$available[0] = \Arr::merge(\Arr::get($available, 0, array()), array_keys($price));

		// Update availability information
		$available = $this->_available($available);
		$count[1] = $available[0];
		$count[2] = $available[1];

		// Set the last updated time for supplier prices
		$this->model->set('last_update', time())->save();

		// Log success
		\Log::info($this->model->name . ' frissítve: ' . $count[0] . ' frissítés, ' . $count[1] . ' lett elérhetetlen, ' . $count[2] . ' lett elérhető, ' . $count[3] . ' felvéve, ' . $count[4] . ' változatlan.');

		// All products have been processed
		return array_sum($count) == count($products);
	}

	/**
	 * Update price and availability changes
	 *
	 * @param  boolean $cached Update from already downloaded files
	 * @return boolean All products have been processed
	 */
	public function change($cached = false)
	{
		if( ! $products = $this->_change($cached))
		{
			return true;
		}

		$manager = new Manager;
		$products = $manager->createData($products)->toArray();
		$products = reset($products);

		// Get current prices from supplier
		$price = $this->get_current_prices();

		$count = 0;

		// Get job and queue
		$job = $this->get_job('change');
		$queue = $this->get_queue('change');

		foreach ($products as $id => $product)
		{
			// Are we sure that it exists?
			if ( ! array_key_exists($id, $price))
			{
				continue;
			}

			$product['supplier_id'] = $this->model->id;
			$count += $queue->push($job, array($product, $price[$id])) ? 1 : 0;
		}

		// Set the last updated time for supplier prices
		$this->model->set('last_update', time())->save();

		// Log success
		\Log::info($this->model->name . ' frissítve: ' . $count . ' termék változott.');

		// All product have been processed
		return $count == count($products);
	}

	protected function _available(array $available)
	{
		$count = array(0,0);
		foreach ($available as $key => $value)
		{
			if ( ! empty($value) && is_array($value))
			{
				$count[($key > 0 ? 1 : $key)] += count($value);

				$value = array(
					'key'         => $key,
					'ids'         => $value,
					'supplier_id' => $this->model->id
				);

				// Get job and queue
				$job = $this->get_job('available');
				$queue = $this->get_queue('available');

				$queue->push($job, $value);
			}
		}

		return $count;
	}


	public function order(Model_Price $price, $qty)
	{
		if ($price->supplier_id !== $this->model->id)
		{
			throw new SupplierException('The given Model_Price does not belong to this Supplier');
		}

		return $this->_order($price, $qty);
	}

	protected function get_queue($name)
	{
		$queue = $this->get_config($name . '.queue', $name);

		return \Queue::forge($queue);
	}

	protected function get_job($name)
	{
		return $this->get_config($name . '.job', 'Indigo\\Erp\\Stock\\Job_Supplier_' . ucfirst(strtolower($name)));
	}

	protected function get_current_prices()
	{
		$price = \DB::select('external_id', 'price', 'available')
				->from(Model_Price::table())
				->where('supplier_id', $this->model->id)
				// ->as_object('Model_Price')
				->execute()
				->as_array('external_id');

		return array_map(function ($item) {
			return array(
				'price' => \Num::currency($item['price']),
				'available' => (int) $item['available'],
			);
		}, $price);
		// return $this->model->prices;
	}

	/**
	 * Download files from suppliers
	 *
	 * @param	string		$file		Name of the file
	 * @param	boolean		$cached		Return already downloaded file
	 * @return	mixed					Result of download
	 */
	abstract protected function _download($file);

	/**
	 * Process downloaded file for update
	 *
	 * @param	boolean		$cached		Update from already downloaded files
	 * @return	array					The whole stock of supplier
	 */
	abstract protected function _update($cached = false);

	/**
	 * Process downloaded file for update changes
	 *
	 * @param	boolean		$cached		Update from already downloaded files
	 * @return	array					The changes of supplier
	 */
	abstract protected function _change($cached = false);

	abstract protected function _order();

	abstract protected function _invoice();
}
