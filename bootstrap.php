<?php

Autoloader::add_classes(array(
	'Indigo\\Erp\\Stock\\Model_Price'            => __DIR__ . '/classes/model/price.php',
	'Indigo\\Erp\\Stock\\Model_Supplier'         => __DIR__ . '/classes/model/supplier.php',
	'Indigo\\Erp\\Stock\\Model_Supplier_Meta'    => __DIR__ . '/classes/model/supplier/meta.php',
	'Indigo\\Erp\\Stock\\Supplier'               => __DIR__ . '/classes/supplier.php',
	'Indigo\\Erp\\Stock\\Supplier_Driver'        => __DIR__ . '/classes/supplier/driver.php',
	'Indigo\\Erp\\Stock\\Supplier_Request'       => __DIR__ . '/classes/supplier/request.php',
	'Indigo\\Erp\\Stock\\Supplier_Curl'          => __DIR__ . '/classes/supplier/curl.php',
	'Indigo\\Erp\\Stock\\Supplier_Soap'          => __DIR__ . '/classes/supplier/soap.php',
	'Indigo\\Erp\\Stock\\Job_Supplier'           => __DIR__ . '/classes/job/supplier.php',
	'Indigo\\Erp\\Stock\\Job_Supplier_Available' => __DIR__ . '/classes/job/supplier/available.php',
	'Indigo\\Erp\\Stock\\Job_Supplier_Update'    => __DIR__ . '/classes/job/supplier/update.php',
	'Indigo\\Erp\\Stock\\Job_Supplier_Change'    => __DIR__ . '/classes/job/supplier/change.php',
	'Indigo\\Erp\\Stock\\Job_Supplier_Insert'    => __DIR__ . '/classes/job/supplier/insert.php',
));
