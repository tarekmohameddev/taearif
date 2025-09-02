<?php

return [
	'guard' => 'sanctum',
	'rbac_version' => 1,

	'permissions' => [
		'menu.dashboard',
		'menu.content',
		'menu.customers',
		'menu.crm',
		'menu.projects',
		'menu.properties',
		'menu.settings',
		'menu.apps',
		'menu.affiliate',
		'customers.view','customers.create','customers.update','customers.delete',
		'projects.view','projects.create','projects.update','projects.delete',
		'properties.view','properties.create','properties.update','properties.delete',
		'content.view','content.create','content.update','content.delete',
		'crm.view','crm.create','crm.update','crm.delete',
		'settings.view','settings.update',
	],

	'role_templates' => [
		'owner' => [
			'menu.dashboard','menu.content','menu.customers','menu.crm','menu.projects','menu.properties','menu.settings','menu.apps','menu.affiliate',
			'customers.view','customers.create','customers.update','customers.delete',
			'projects.view','projects.create','projects.update','projects.delete',
			'properties.view','properties.create','properties.update','properties.delete',
			'content.view','content.create','content.update','content.delete',
			'crm.view','crm.create','crm.update','crm.delete',
			'settings.view','settings.update',
		],
		'manager' => [
			'menu.dashboard','menu.content','menu.customers','menu.crm','menu.projects','menu.properties','menu.settings','menu.apps',
			'customers.view','customers.create','customers.update',
			'projects.view','projects.create','projects.update',
			'properties.view','properties.create','properties.update',
			'content.view','content.create','content.update',
			'crm.view','crm.create','crm.update',
			'settings.view','settings.update',
		],
		'agent' => [
			'menu.dashboard','menu.content','menu.customers','menu.crm','menu.projects','menu.properties','menu.settings','menu.apps',
			'customers.view','customers.create',
			'projects.view','projects.create',
			'properties.view','properties.create',
			'content.view','content.create',
			'crm.view','crm.create',
			'settings.view',
		],
	],
];
