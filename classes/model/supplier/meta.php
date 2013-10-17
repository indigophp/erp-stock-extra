<?php

namespace Indigo\Erp\Stock;

class Model_Supplier_Meta extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'supplier_id',
		'key',
		'value',
	);

	protected static $_table_name = 'supplier_meta';
}
