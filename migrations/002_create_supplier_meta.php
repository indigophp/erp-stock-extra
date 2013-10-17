<?php

namespace Fuel\Migrations;

class Create_supplier_meta
{
	public function up()
	{
		\DBUtil::create_table('supplier_meta', array(
			'id'          => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
			'supplier_id' => array('constraint' => 11, 'type' => 'int', 'unsigned' => true),
			'key'         => array('constraint' => 255, 'type' => 'varchar'),
			'value'       => array('type' => 'text', 'null' => true),
		), array('id'));

		\DBUtil::add_foreign_key('supplier_meta', array(
			'constraint' => 'supplier_meta_id_suppliers_id',
			'key' => 'supplier_id',
			'reference' => array(
				'table' => 'suppliers',
				'column' => 'id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => 'CASCADE'
		));
	}

	public function down()
	{
		\DBUtil::drop_table('supplier_meta');
	}
}
