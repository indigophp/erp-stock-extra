<?php

namespace Indigo\Erp\Stock;

class SupplierException extends \FuelException {}

class Supplier
{

	/**
	 * Default config
	 * @var array
	 */
	protected static $_defaults = array();

	/**
	 * Init
	 */
	public static function _init()
	{
		\Config::load('supplier', true);
		static::$_defaults = \Config::get('supplier.defaults', array());
	}

	/**
	 * Supplier driver forge.
	 *
	 * @param	string			$supplier		Supplier id or friendly name
	 * @param	array			$config				Config array
	 * @return  Supplier instance
	 */
	public static function forge($supplier, $config = array())
	{

		$model = Model_Supplier::query();
		if (is_int($supplier))
		{
			$model = $model->where('id', $supplier)->get_one();
		}
		elseif (is_string($supplier))
		{
			$model = $model->where('slug', $supplier)->get_one();
		}
		elseif ($supplier instanceof Model_Supplier)
		{
			$model = $supplier;
		}
		else
		{
			throw new SupplierException('Invalid Supplier!');
		}

		if ( ! $model instanceof Model_Supplier)
		{
			throw new SupplierException('Supplier ' . $supplier . ' not found');
		}

		if (\Arr::get($config, 'filter_disabled', false) === true and $model->enabled !== 1)
		{
			throw new SupplierException('This supplier (' . $model->name . ') has been disabled.');
		}

		if (isset($model->driver) and empty($model->driver))
		{
			throw new SupplierException('This supplier (' . $model->name . ') has no driver to be used.');
		}

		$driver = ucfirst(strtolower(isset($model->driver) ? $model->driver : $model->slug));

		$class = 'Indigo\\Erp\\Stock\\Supplier_' . $driver;

		if( ! class_exists($class, true))
		{
			throw new \FuelException('Could not find Supplier driver: ' . $driver);
		}

		$config = \Arr::merge(static::$_defaults, \Config::get('supplier.drivers.' . $driver, array()), $config);

		$driver = new $class($model, $config);

		return $driver;
	}
}
