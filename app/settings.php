<?php
	return [
			'settings' => [
				'displayErrorDetails' => true,
				 // Only set this if you need access to route within middleware
        'determineRouteBeforeAppMiddleware' => true,
				'view' => [
					'path' => __DIR__ . '/resources/views',
					'twig' => [
					'cache' => false
					]
				],
			]
	];