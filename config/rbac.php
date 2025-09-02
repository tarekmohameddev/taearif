<?php

return [
	'guard' => 'sanctum',
	'rbac_version' => 1,

	'permissions' => [
		'menu.dashboard',
		'menu.customers',
		'menu.projects',
		'menu.properties',
		'customers.view','customers.create','customers.update','customers.delete',
		'projects.view','projects.create','projects.update','projects.delete',
		'properties.view','properties.create','properties.update','properties.delete',
		'settings.update',
	],

	'role_templates' => [
		'owner' => [
			'menu.dashboard','menu.customers','menu.projects','menu.properties','settings.update',
			'customers.view','customers.create','customers.update','customers.delete',
			'projects.view','projects.create','projects.update','projects.delete',
			'properties.view','properties.create','properties.update','properties.delete',
		],
		'manager' => [
			'menu.dashboard','menu.customers','menu.projects','menu.properties',
			'customers.view','customers.create','customers.update',
			'projects.view','projects.create','projects.update',
			'properties.view','properties.create','properties.update',
		],
		'agent' => [
			'menu.dashboard','menu.customers','menu.projects','menu.properties',
			'customers.view','customers.create',
			'projects.view','projects.create',
			'properties.view','properties.create',
		],
	],
];
