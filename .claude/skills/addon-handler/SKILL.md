---
name: addon-handler
description: Creates or modifies the AddonHandler registration in Plugin::getAddon(). Use when user says 'add addon', 'change addon cost', 'register addon', or modifies a VPS_*_COST constant. Covers set_text(), set_cost(), set_require_ip(), setEnable(), setDisable(), register() chain and the matching doEnable/doDisable handlers. Do NOT use for hook registration (getHooks), settings (getSettings), or page requirements (getRequirements).
---
# addon-handler

## Critical
- `function_requirements('class.AddonHandler')` MUST be called before `new \AddonHandler()` — it lazy-loads the class.
- The cost argument to `set_cost()` MUST be a defined constant (e.g. `VPS_SOFTACULOUS_COST`), never a hard-coded number.
- `$service->addAddon($addon)` MUST be called after `->register()` — omitting it silently drops the addon.
- All `doEnable`/`doDisable` handlers MUST use `$settings['PREFIX']` to key into `$serviceInfo` (e.g. `$serviceInfo[$settings['PREFIX'].'_ip']`), never hard-coded column names.
- Indentation is **tabs**, not spaces — enforced by `.scrutinizer.yml`.

## Instructions

1. **Load AddonHandler inside `getAddon()`** (`src/Plugin.php`):
   ```php
   public static function getAddon(GenericEvent $event)
   {
       /** @var \ServiceHandler $service */
       $service = $event->getSubject();
       function_requirements('class.AddonHandler');
       $addon = new \AddonHandler();
       $addon->setModule(self::$module)
           ->set_text('YourAddonLabel')
           ->set_cost(VPS_YOUR_ADDON_COST)
           ->set_require_ip(true)
           ->setEnable([__CLASS__, 'doEnable'])
           ->setDisable([__CLASS__, 'doDisable'])
           ->register();
       $service->addAddon($addon);
   }
   ```
   Verify: `self::$module` matches the module string (`'vps'`) set in `public static $module`.

2. **Implement `doEnable()`** — provision via NOC, log, record history:
   ```php
   public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   {
       $serviceInfo = $serviceOrder->getServiceInfo();
       $settings = get_module_settings(self::$module);
       require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
       myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
       // ... NOC call ...
       $GLOBALS['tf']->history->add($settings['TABLE'], 'add_youraddon', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
   }
   ```
   Verify: history action string is unique per addon (e.g. `'add_softaculous'`).

3. **Implement `doDisable()`** — cancel via NOC, log, history, send admin email:
   ```php
   public static function doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
   {
       $serviceInfo = $serviceOrder->getServiceInfo();
       $settings = get_module_settings(self::$module);
       require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
       myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
       // ... NOC cancel call ...
       $GLOBALS['tf']->history->add($settings['TABLE'], 'del_youraddon', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
       $email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
       $subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled '.self::$name;
       (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_youraddon_canceled.tpl');
   }
   ```
   Verify: admin email template path exists under `include/templates/email/admin/`.

4. **Update cost constant** — changing the cost means updating the constant name passed to `set_cost()` and the matching `getSettings()` entry (`VPS_YOUR_ADDON_COST` ↔ `vps_your_addon_cost` setting key). These must stay in sync.

5. **Run tests** to confirm registration:
   ```bash
   vendor/bin/phpunit tests/PluginTest.php
   vendor/bin/phpunit tests/EventIntegrationTest.php
   ```

## Examples

**User says:** "Change the Softaculous addon cost constant reference"

**Actions taken:**
- In `getAddon()`: change `->set_cost(VPS_SOFTACULOUS_COST)` to `->set_cost(VPS_SOFTACULOUS_LICENSE_COST)`
- In `getSettings()`: change key `'vps_softaculous_cost'` and `get_setting('VPS_SOFTACULOUS_COST')` to match new constant
- Run `vendor/bin/phpunit` to confirm no regressions

**Result:**
```php
$addon->set_cost(VPS_SOFTACULOUS_LICENSE_COST)
// getSettings:
$settings->add_text_setting(self::$module, _('Addon Costs'), 'vps_softaculous_license_cost',
    _('VPS Softaculous License'), _('Cost description.'),
    $settings->get_setting('VPS_SOFTACULOUS_LICENSE_COST'));
```

## Common Issues

- **`Class 'AddonHandler' not found`** — `function_requirements('class.AddonHandler')` is missing or called after `new \AddonHandler()`. Move it immediately before the instantiation.
- **Addon silently not appearing** — `$service->addAddon($addon)` was omitted after `->register()`. Both calls are required.
- **`Undefined index: PREFIX`** — `get_module_settings()` returned empty; confirm `self::$module` equals the registered module string (`'vps'`).
- **History/log column errors** — Hard-coded column names instead of `$settings['PREFIX'].'_ip'` etc. Always use the PREFIX pattern.
- **CS errors on commit** — Tabs replaced with spaces. Run `make php-cs-fixer` and verify `.scrutinizer.yml` tab rule is satisfied.