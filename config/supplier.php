<?php

return array(
	'defaults' => array(
		'filter_disabled' => false,
		'auto_format' => true,
		'cache' => array(
			'enabled'    => true,
			'prefix'     => 'supplier',
			'expiration' => 86400,
			'driver'     => 'file',
		),
		'update' => array(
			'queue'    => 'update',
			'job'      => 'Indigo\\Erp\\Stock\\Job_Supplier_Update',
			'interval' => '-1 day'
		),
		'change' => array(
			'queue'    => 'update',
			'job'      => 'Indigo\\Erp\\Stock\\Job_Supplier_Change',
			'interval' => '-1 hour'
		),
		'insert' => array(
			'queue' => 'update',
			'job'   => 'Indigo\\Erp\\Stock\\Job_Supplier_Insert'
		),
		'available' => array(
			'queue' => 'update',
			'job'   => 'Indigo\\Erp\\Stock\\Job_Supplier_Available'
		)
	),
	'drivers' => array(),
);
