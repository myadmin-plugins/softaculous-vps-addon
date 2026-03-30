---
name: softaculous-noc-api
description: Implements SoftaculousNOC API calls (buy/cancel) in Plugin lifecycle methods. Use when user says 'provision license', 'cancel license', 'call NOC API', or adds new lifecycle actions for a VPS addon. Covers require_once for license functions, myadmin_log() calls, NOC instantiation, and history recording. Do NOT use for non-Softaculous addons or for adding new hooks/settings — those follow a separate plugin registration pattern.
---
# softaculous-noc-api

## Critical
- **Never** interpolate `$_GET`/`$_POST` into log messages or NOC calls — always pull data from `$serviceInfo` keyed via `$settings['PREFIX']`.
- Always call `require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php'` before instantiating `SoftaculousNOC` — the SOAP credentials constants (`SOFTACULOUS_USERNAME`, `SOFTACULOUS_PASSWORD`) are defined there.
- Log the NOC response via `myadmin_log()` as `json_encode($noc->buy(...))` / `json_encode($noc->cancel(...))` — never swallow the response silently.
- `doDisable()` must send an admin email after the cancel call — do not omit it.

## Instructions

1. **Resolve service context.** At the top of each lifecycle method:
   ```php
   $serviceInfo = $serviceOrder->getServiceInfo();
   $settings = get_module_settings(self::$module);
   ```
   All subsequent field access uses `$serviceInfo[$settings['PREFIX'].'_fieldname']`.
   Verify `$settings['PREFIX']`, `$settings['TABLE']`, and `$settings['TBLNAME']` are populated before continuing.

2. **Load license functions.** Immediately after resolving context:
   ```php
   require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
   ```
   This path is relative to `src/Plugin.php`. Verify the file exists at that path before running.

3. **Log the action start.**
   ```php
   myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
   ```
   For disable: replace `'Activation'` with `'Deactivation'`.

4. **Instantiate NOC and call the API.**
   - **Enable (buy):**
     ```php
     $noc = new \Detain\MyAdminSoftaculous\SoftaculousNOC(SOFTACULOUS_USERNAME, SOFTACULOUS_PASSWORD);
     myadmin_log(self::$module, 'info',
         json_encode($noc->buy(
             $serviceInfo[$settings['PREFIX'].'_ip'],
             '1M', 2,
             $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']),
             1
         )),
         __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']
     );
     ```
   - **Disable (cancel):**
     ```php
     $noc = new \Detain\MyAdminSoftaculous\SoftaculousNOC(SOFTACULOUS_USERNAME, SOFTACULOUS_PASSWORD);
     myadmin_log(self::$module, 'info',
         json_encode($noc->cancel('', $serviceInfo[$settings['PREFIX'].'_ip'])),
         __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']
     );
     ```
   Note: `cancel()` takes an empty string as first arg, IP as second.

5. **Record history.**
   ```php
   // Enable:
   $GLOBALS['tf']->history->add($settings['TABLE'], 'add_softaculous',
       $serviceInfo[$settings['PREFIX'].'_id'],
       $serviceInfo[$settings['PREFIX'].'_ip'],
       $serviceInfo[$settings['PREFIX'].'_custid']
   );
   // Disable:
   $GLOBALS['tf']->history->add($settings['TABLE'], 'del_softaculous',
       $serviceInfo[$settings['PREFIX'].'_id'],
       $serviceInfo[$settings['PREFIX'].'_ip'],
       $serviceInfo[$settings['PREFIX'].'_custid']
   );
   ```

6. **Send admin cancellation email (disable only).** After history:
   ```php
   $email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'
       .$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>'
       .'Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
   $subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled Softaculous';
   (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_softaculous_canceled.tpl');
   ```

## Examples

**User says:** "Add a doRenew action that re-buys the Softaculous license."

**Actions taken:**
1. Copy the `doEnable` signature: `public static function doRenew(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)`
2. Follow steps 1–5 above; use action string `'renew_softaculous'` in the history call.
3. Register the handler in `getAddon()` via `->setRenew([__CLASS__, 'doRenew'])`.

**Result:**
```php
public static function doRenew(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
{
    $serviceInfo = $serviceOrder->getServiceInfo();
    $settings = get_module_settings(self::$module);
    require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
    myadmin_log(self::$module, 'info', self::$name.' Renewal', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
    $noc = new \Detain\MyAdminSoftaculous\SoftaculousNOC(SOFTACULOUS_USERNAME, SOFTACULOUS_PASSWORD);
    myadmin_log(self::$module, 'info',
        json_encode($noc->buy($serviceInfo[$settings['PREFIX'].'_ip'], '1M', 2, $GLOBALS['tf']->accounts->cross_reference($serviceInfo[$settings['PREFIX'].'_custid']), 1)),
        __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']
    );
    $GLOBALS['tf']->history->add($settings['TABLE'], 'renew_softaculous', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
}
```

## Common Issues

- **`Fatal error: Undefined constant SOFTACULOUS_USERNAME`** — `require_once` for `license.functions.inc.php` is missing or the path is wrong. Verify the relative path `__DIR__.'/../../../../include/licenses/license.functions.inc.php'` resolves correctly from `src/Plugin.php`.
- **`Class 'Detain\MyAdminSoftaculous\SoftaculousNOC' not found`** — run `composer install`; check `composer.json` requires `detain/myadmin-softaculous-licensing`.
- **`$noc->buy()` returns falsy / empty array** — SOAP extension may be absent. Verify `php -m | grep soap`; ensure `ext-soap` is enabled in `php.ini`.
- **History not recorded** — `$GLOBALS['tf']` is not initialized in the test/CLI context. In tests, mock `$GLOBALS['tf']->history` with an anonymous class implementing `add()`.
- **Admin email not sent on cancel** — confirm `admin/vps_softaculous_canceled.tpl` exists under the Smarty templates path; missing template causes a silent failure in `\MyAdmin\Mail::adminMail()`.