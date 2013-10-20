<?php

return array(
	'queues' => array(
		'supplier' => array(
			'driver' => 'beanstalkd'
		),
		'update' => array(
			'driver' => 'beanstalkd'
		)
	)
);