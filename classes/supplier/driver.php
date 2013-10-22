<?php

namespace Indigo\Erp\Stock;

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
	* Driver constructor
	*
	* @param	Model_Supplier	$model		Model instance
	* @param	array			$config		Driver config
	*/
	public function __construct(Model_Supplier $model, array $config = array())
	{
		$this->config = $config;
		$this->model = $model;
	}

	/**
	* Get a driver config
	*
	* @param	mixed	$key		Config key
	* @param	mixed	$default	Default value
	* @return	mixed				Config value or whole config array
	*/
	public function get_config($key = null, $default = null)
	{
		return is_null($key) ? $this->config : \Arr::get($this->config, $key, $default);
	}

	/**
	* Set a driver config
	*
	* @param	mixed	$key	Config key or array to merge
	* @param	mixed	$value	Config value
	* @return	$this
	*/
	public function set_config($key, $value = null)
	{
		if (is_array($key))
		{
			$this->config = \Arr::merge($this->config, $key);
		}
		else
		{
			\Arr::set($this->config, $key, $value);
		}

		return $this;
	}

	/**
	 * Download files from suppliers
	 *
	 * @param	string		$file		Name of the file
	 * @param	boolean		$cached		Return already downloaded file
	 * @return	mixed					Result of download
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
	 * @param	boolean		$cached		Update from already downloaded files
	 * @return	boolean 				All products have been processed
	 */
	public function update($cached = false)
	{
		// Get data from supplier
		if( ! $products = $this->_update($cached))
		{
			return false;
		}

		$count = array(0,0,0,0,0);
		$available = array();

		// Get current prices from supplier
		$price = \DB::select('external_id', 'price', 'available')
				->from(Model_Price::table())
				->where('supplier_id', $this->model->id)
				->execute()
				->as_array('external_id');

		// Cast values to the appropriate data type
		array_walk($price, function(&$product, $id) use(&$price) {
			$product['price']     = \Num::currency($product['price']);
			$product['available'] = intval($product['available']);
			unset($price[$id]['external_id']);
		});

		foreach ($products as $id => $product)
		{
			// Default data casting and values
			$product['price'] = \Arr::get($product, 'price');
			if (is_null($product['price']))
			{
				continue;
			}
			else
			{
				$product['price'] = \Num::currency($product['price']);
			}

			$product['available'] = intval(\Arr::get($product, 'available', 1));

			// Check if product already exists: update it if yes and insert if not
			if (array_key_exists($id, $price))
			{
				// Check if product's price has been changed, or just became (un)available
				if ($product['price'] !== $price[$id]['price'])
				{
					// Method for updating meta fields as well
					$fields = $this->get_config('update.fields', array());
					$fields = \Arr::merge($fields, array('price', 'available'));

					// Foolproofness: set the update array manually
					$product = \Arr::filter_keys($product, $fields);

					\Arr::set($product, array(
						'external_id' => $id,
						'supplier_id' => $this->model->id
					));

					// Get job and queue
					$job = $this->get_config('update.job', 'Indigo\\Erp\\Stock\\Job_Supplier_Update');
					$queue = $this->get_config('update.queue', 'update');

					// Use Queue if available (greater performance)
					if (\Package::loaded('queue'))
					{
						$count[0] += \Queue::push($queue, $job, array($product, $price[$id])) ? 1 : 0;
					}
					else
					{
						try
						{
							$job = new $job();
							$count[0] += $job->execute(null, $product);
						}
						catch (\Exception $e)
						{

						}
					}
				}
				elseif($product['available'] !== $price[$id]['available'])
				{
					$available[$product['available']][] = $id;
				}
				else
				{
					$count[4]++;
				}
			}
			else
			{
				// Foolproofness: removing some fields from insert data
				$product = \Arr::filter_keys($product, array('id', 'product_id'), true);

				\Arr::set($product, array(
					'external_id' => $id,
					'supplier_id' => $this->model->id
				));

				// Get job and queue
				$job = $this->get_config('insert.job', 'Indigo\\Erp\\Stock\\Job_Supplier_Insert');
				$queue = $this->get_config('insert.queue', 'update');

				// Use Queue if available (greater performance)
				if (\Package::loaded('queue'))
				{
					$count[3] += \Queue::push($queue, $job, $product) ? 1 : 0;
				}
				else
				{
					try
					{
						$job = new $job();
						$count[3] += $job->execute(null, $product);
					}
					catch (\Exception $e)
					{

					}
				}
			}

			// Remove processed product from list
			unset($price[$id]);
		}

		// Already unavailable products should not be updated
		$price = array_filter($price, function($item) use(&$count) {
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
	 * Update price and availability
	 *
	 * @param	boolean		$cached		Update from already downloaded files
	 * @return	boolean 				All products have been processed
	 */
	public function change($cached = false)
	{
		// Get data from supplier
		if( ! $products = $this->_change($cached))
		{
			return true;
		}

		// Get current prices from supplier
		$price = \DB::select('external_id', 'price', 'available')
				->from(Model_Price::table())
				->where('supplier_id', $this->model->id)
				->execute()
				->as_array('external_id');

		// Cast values to the appropriate data type
		array_walk($price, function(&$product, $id) use(&$price) {
			$product['price']     = \Num::currency($product['price']);
			$product['available'] = intval($product['available']);
			unset($price[$id]['external_id']);
		});

		$count = array(0);
		$available = array();

		foreach ($products as $id => $product)
		{
			// Are we sure that it exists?
			if ( ! array_key_exists($id, $price))
			{
				continue;
			}

			// Default data casting and values
			$product['price'] = \Arr::get($product, 'price');
			is_null($product['price']) or $product['price'] = \Num::currency($product['price']);

			$a = \Arr::get($product, 'available');

			// Check if product's price has been changed, or just became (un)available
			if ( ! is_null($product['price']))
			{
				// Foolproofness: set the update array manually
				$product = array(
					'price'       => $product['price'],
					'external_id' => $id,
					'supplier_id' => $this->model->id
				);

				// Update availability if changed
				is_null($a) or $product['available'] = $a;

				// Get job and queue
				$job = $this->get_config('change.job', 'Indigo\\Erp\\Stock\\Job_Supplier_Change');
				$queue = $this->get_config('change.queue', 'update');

				// Use Queue if available (greater performance)
				if (\Package::loaded('queue'))
				{
					$count[0] += \Queue::push($queue, $job, array($product, $price[$id])) ? 1 : 0;
				}
				else
				{
					try
					{
						$job = new $job();
						$count[0] += $job->execute(null, $product);
					}
					catch (\Exception $e)
					{

					}
				}
			}
			elseif( ! is_null($a))
			{
				$available[$a][] = $id;
			}
		}

		// Update availability information
		$available = $this->_available($available);
		$count[1] = $available[0];
		$count[2] = $available[1];

		// Set the last updated time for supplier prices
		$this->model->set('last_update', time())->save();

		// Log success
		\Log::info($this->model->name . ' frissítve: ' . $count[0] . ' frissítés, ' . $count[1] . ' lett elérhetetlen, ' . $count[2] . ' lett elérhető.');

		// All product have been processed
		return array_sum($count) == count($products);
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
				$job = $this->get_config('available.job', 'Indigo\\Erp\\Stock\\Job_Supplier_Available');
				$queue = $this->get_config('available.queue', 'update');

				// Use Queue if available (greater performance)
				if (\Package::loaded('queue'))
				{
					\Queue::push($queue, $job, $value);
				}
				else
				{
					try
					{
						$job = new $job();
						$job->execute(null, $value);
					}
					catch (\Exception $e)
					{

					}
				}
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
