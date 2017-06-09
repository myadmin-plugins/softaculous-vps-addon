<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_softaculous define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Softaculous Licensing VPS Addon',
	'description' => 'Allows selling of Softaculous Server and VPS License Types.  More info at https://www.netenberg.com/softaculous.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a softaculous license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-softaculous-vps-addon',
	'repo' => 'https://github.com/detain/myadmin-softaculous-vps-addon',
	'version' => '1.0.0',
	'type' => 'addon',
	'hooks' => [
		'vps.load_addons' => ['Detain\MyAdminVpsSoftaculous\Plugin', 'Load'],
		'vps.settings' => ['Detain\MyAdminVpsSoftaculous\Plugin', 'Settings'],
		/* 'function.requirements' => ['Detain\MyAdminVpsSoftaculous\Plugin', 'Requirements'],
		'licenses.activate' => ['Detain\MyAdminVpsSoftaculous\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminVpsSoftaculous\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVpsSoftaculous\Plugin', 'Menu'] */
	],
];
