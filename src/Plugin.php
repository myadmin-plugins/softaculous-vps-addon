<?php

namespace Detain\MyAdminVpsSoftaculous;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminVpsSoftaculous
 */
class Plugin
{
	public static $name = 'Softaculous VPS Addon';
	public static $description = 'Allows selling of Softaculous License Addons to a VPS.  Softaculous is the leading Auto Installer having 426 great scripts, 1115 PHP Classes and we are still adding more. Softaculous is widely used in the Web Hosting industry and it has helped millions of users install applications by the click of a button. Softaculous Auto Installer easily integrates into leading Control Panels like cPanel, Plesk, DirectAdmin, InterWorx, H-Sphere.  More info at https://www.softaculous.com/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'addon';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			'function.requirements' => [__CLASS__, 'getRequirements'],
			self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
			self::$module.'.settings' => [__CLASS__, 'getSettings']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Plugins\Loader $this->loader
		 */
		$loader = $event->getSubject();
		$loader->add_page_requirement('vps_add_softaculous', '/../vendor/detain/myadmin-softaculous-vps-addon/src/vps_add_softaculous.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getAddon(GenericEvent $event)
	{
		/**
		 * @var \ServiceHandler $service
		 */
		$service = $event->getSubject();
		function_requirements('class.AddonHandler');
		$addon = new \AddonHandler();
		$addon->setModule(self::$module)
			->set_text('Softaculous')
			->set_cost(VPS_SOFTACULOUS_COST)
			->set_require_ip(true)
			->setEnable([__CLASS__, 'doEnable'])
			->setDisable([__CLASS__, 'doDisable'])
			->register();
		$service->addAddon($addon);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 */
	public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
	{
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		$noc = new \Detain\MyAdminSoftaculous\SoftaculousNOC(SOFTACULOUS_USERNAME, SOFTACULOUS_PASSWORD);
		myadmin_log(self::$module, 'info', json_encode($noc->buy($serviceInfo[$settings['PREFIX'].'_ip'], '1M', 2, $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']), 1)), __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		$GLOBALS['tf']->history->add($settings['TABLE'], 'add_softaculous', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 */
	public static function doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
	{
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
		myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		$noc = new \Detain\MyAdminSoftaculous\SoftaculousNOC(SOFTACULOUS_USERNAME, SOFTACULOUS_PASSWORD);
		myadmin_log(self::$module, 'info', json_encode($noc->cancel('', $serviceInfo[$settings['PREFIX'].'_ip'])), __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		$GLOBALS['tf']->history->add($settings['TABLE'], 'del_softaculous', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
		$email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'.$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
		$subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled Softaculous';
		(new MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_softaculous_canceled.tpl');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, _('Addon Costs'), 'vps_softaculous_cost', _('VPS Softaculous License'), _('This is the cost for purchasing a softaculous license on top of a VPS.'), $settings->get_setting('VPS_SOFTACULOUS_COST'));
	}
}
