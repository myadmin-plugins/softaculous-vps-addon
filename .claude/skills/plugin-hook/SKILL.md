---
name: plugin-hook
description: Adds a new Symfony EventDispatcher hook to src/Plugin.php. Use when user says 'add hook', 'register event', 'listen for event', or needs to respond to a new MyAdmin event. Generates the hook entry in Plugin::getHooks() and the corresponding static handler method accepting GenericEvent $event. Do NOT use for modifying src/vps_add_softaculous.php directly or for doEnable/doDisable addon lifecycle methods.
---
# plugin-hook

## Critical

- ALL handler methods must be `public static` with NO declared return type (not even `void`) — the existing `getRequirements`, `getAddon`, `getSettings` have no return type, tests assert `assertNull($returnType)`.
- Event names MUST follow `scope.action` lowercase pattern (letters and underscores only, e.g. `vps.load_addons`) — `EventIntegrationTest` asserts event names match this format.
- Use `self::$module.'.event_name'` for module-scoped events; use a plain string for global events (e.g. `'function.requirements'`).
- Indentation is **tabs**, not spaces — enforced by `.scrutinizer.yml` and `.codeclimate.yml`.
- Do NOT add `use` statements for classes that are referenced only via global namespace (e.g. `\AddonHandler`, `\MyAdmin\Mail`) — only `GenericEvent` is imported at the top of `src/Plugin.php`.

## Instructions

1. **Identify the event name and handler method name.**
   - Event name: `self::$module.'.your_event'` for module events, or `'scope.action'` for cross-module events.
   - Method name: camelCase, e.g. `getStatus` for `vps.status`.
   - Verify no existing key in `getHooks()` conflicts before proceeding.

2. **Add the hook entry to `getHooks()` in `src/Plugin.php`.**
   Append to the return array (file: `src/Plugin.php`, lines 32–36):
   ```php
   self::$module.'.your_event' => [__CLASS__, 'yourHandler'],
   ```
   Full array shape after edit:
   ```php
   return [
   	'function.requirements' => [__CLASS__, 'getRequirements'],
   	self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
   	self::$module.'.settings'   => [__CLASS__, 'getSettings'],
   	self::$module.'.your_event' => [__CLASS__, 'yourHandler'],
   ];
   ```

3. **Add the static handler method to `src/Plugin.php`.**
   Place after the last existing handler. Exact pattern — tabs, PHPDoc, `$event->getSubject()`, no return type:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function yourHandler(GenericEvent $event)
   {
   	/**
   	 * @var \ExpectedSubjectType $subject
   	 */
   	$subject = $event->getSubject();
   	$settings = get_module_settings(self::$module);
   	myadmin_log(self::$module, 'info', 'Your message', __LINE__, __FILE__, self::$module, 0);
   	// handler logic here
   }
   ```
   Verify the method appears before the closing `}` of the class.

4. **Update `tests/PluginTest.php`.**
   - In `testGetHooksReturnsExactlyThreeHooks`: update `assertCount(3, $hooks)` → `assertCount(4, $hooks)` (increment by 1 per new hook).
   - In `testGetHooksContainsExpectedKeys`: add `$this->assertArrayHasKey('vps.your_event', $hooks);`.
   - Add a method signature test:
   ```php
   public function testYourHandlerMethodSignature(): void
   {
   	$method = $this->reflection->getMethod('yourHandler');
   	$this->assertTrue($method->isStatic());
   	$this->assertTrue($method->isPublic());
   	$params = $method->getParameters();
   	$this->assertCount(1, $params);
   	$this->assertSame('event', $params[0]->getName());
   	$this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   }
   ```

5. **Run tests to verify.**
   ```bash
   vendor/bin/phpunit tests/PluginTest.php tests/EventIntegrationTest.php
   ```
   All tests must pass before committing.

## Examples

**User says:** "Add a hook for `vps.refresh_status` that logs the VPS IP."

**Actions taken:**
1. Add to `getHooks()` in `src/Plugin.php`: `self::$module.'.refresh_status' => [__CLASS__, 'getRefreshStatus'],`
2. Add method:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getRefreshStatus(GenericEvent $event)
   {
   	/**
   	 * @var \ServiceHandler $service
   	 */
   	$service = $event->getSubject();
   	$settings = get_module_settings(self::$module);
   	$serviceInfo = $service->getServiceInfo();
   	myadmin_log(self::$module, 'info', 'Refreshing status for IP '.$serviceInfo[$settings['PREFIX'].'_ip'], __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
   }
   ```
3. Update `testGetHooksReturnsExactlyThreeHooks` → `assertCount(4, ...)`.
4. Run `vendor/bin/phpunit` — all green.

**Result:** New hook registered, handler fires on `vps.refresh_status` events dispatched by MyAdmin core.

## Common Issues

- **`assertCount(3, $hooks)` fails after adding hook:** You forgot to update `testGetHooksReturnsExactlyThreeHooks` in `tests/PluginTest.php`. Change the count to match the new total.
- **`testEventHandlerMethodsReturnVoid` fails:** You declared `void` return type on the new handler. Remove it — existing handlers have no declared return type and the test asserts `assertNull($returnType)`.
- **`testHookEventNamesFollowConvention` fails:** Event name contains uppercase or doesn't match lowercase `scope.action` format (e.g. used `vps.refreshStatus`). Rename to `vps.refresh_status`.
- **Indentation CI error in `.scrutinizer.yml`:** File was saved with spaces. Re-save `src/Plugin.php` with tab indentation only.
- **`is_callable` returns false for new hook callback:** The handler method name in `getHooks()` doesn't match the actual method name. Check spelling — PHP method names are case-insensitive but the string in the array must match `$this->reflection->hasMethod($callback[1])`.
