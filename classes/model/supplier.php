<?php

namespace Indigo\Erp\Stock;

class Model_Supplier extends \Orm\Model
{
	protected static $_eav = array(
		'meta' => array(
			'attribute' => 'key',
			'value'     => 'value',
		),
	);

	protected static $_has_many = array(
		'meta' => array(
			'model_to'       => 'Model_Supplier_Meta',
			'cascade_delete' => true,
		),
		'prices'
	);

	protected static $_observers = array(
		'Orm\Observer_CreatedAt' => array(
			'events' => array('before_insert'),
			'mysql_timestamp' => false,
		),
		'Orm\Observer_UpdatedAt' => array(
			'events' => array('before_update'),
			'mysql_timestamp' => false,
		),
		'Orm\\Observer_Slug' => array(
			'events' => array('before_insert'),
			'source' => 'name',
		),
		'Orm\\Observer_Typing' => array(
			'events' => array('before_save', 'after_save', 'after_load')
		),
	);

	protected static $_properties = array(
		'id' => array(
			'data_type' => 'int'
		),
		'name',
		'slug',
		'url',
		'email',
		'enabled' => array(
			'default'   => 1,
			'data_type' => 'int'
		),
		'created_at' => array(
			'data_type' => 'int'
		),
		'updated_at' => array(
			'data_type' => 'int'
		),
	);

	protected static $_table_name = 'suppliers';
}
