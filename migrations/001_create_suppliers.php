<?php

namespace Fuel\Migrations;

class Create_suppliers
{
	public function up()
	{
		\DBUtil::create_table('suppliers', array(
			'id'         => array('constraint' => 11, 'type' => 'int', 'auto_increment' => true, 'unsigned' => true),
			'name'       => array('constraint' => 128, 'type' => 'varchar'),
			'slug'       => array('constraint' => 128, 'type' => 'varchar'),
			'url'        => array('constraint' => 2083, 'type' => 'varchar', 'null' => true),
			'email'      => array('constraint' => 320, 'type' => 'varchar', 'null' => true),
			'enabled'    => array('constraint' => 1, 'type' => 'tinyint', 'default' => 1),
			'created_at' => array('constraint' => 11, 'type' => 'int', 'null' => true),
			'updated_at' => array('constraint' => 11, 'type' => 'int', 'null' => true),

		), array('id'), false, 'InnoDB');
	}

	public function down()
	{
		\DBUtil::drop_table('suppliers');
	}
}
