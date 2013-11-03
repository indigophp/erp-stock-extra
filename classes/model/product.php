<?php

namespace Indigo\Erp\Stock;

class Model_Product extends \Orm\Model_Temporal
{
	protected static $alias = 't0';

	protected static $_belongs_to = array(
		'category',
		// 'manufacturer',
		//'type',
	);

	protected static $_eav = array(
		'meta' => array(
			'attribute' => 'key',
			'value'     => 'value',
		)
	);

	protected static $_has_many = array(
		'meta' => array(
			'model_to'       => 'Model_Product_Meta',
			'cascade_delete' => true,
		),
		'order_product' => array(
			'model_to' => '\\Indigo\\Webshop\\Model_Order_Product'
		),
	);

	protected static $_observers = array(
		'Orm\\Observer_Slug' => array(
			'events' => array('before_insert'),
			'source' => 'name',
		),
	);

	protected static $_has_one = array('price');

	protected static $_properties = array(
		'id',
		'temporal_start',
		'temporal_end',
		'category_id',
		'type_id',
		'tax_class_id',
		'name',
		'slug',
		'description',
		'enabled' => array(
			'default' => 1
		),
	);

	protected static $_table_name = 'products';

	public static function _init()
	{
		$subquery = \DB::select('price.id')
			->from(array(Model_Price::table(), 'price'))
			->where('price.product_id', '=', \DB::expr(\DB::quote_identifier('product.id')))
			->where('price.available', 1)
			->order_by('price.price')
			->limit(1);
		static::$_valid_relations['has_one'] = 'Indigo\\Erp\\Stock\\HasOne';
		static::$_has_one['price']['key_from'] = array('id', $subquery);
		static::$_has_one['price']['key_to'] = array('product_id', 'id');

		static::$_has_many['prices'] = array(
			'model_to'       => 'Model_Price',
			'cascade_delete' => true,
		);
	}
}
