---
name: phpunit-plugin-test
description: Writes PHPUnit 9 tests for myadmin-plugin packages following patterns in tests/PluginTest.php, tests/EventIntegrationTest.php, and tests/FileExistenceTest.php. Use when user says 'write test', 'add test case', 'test hook', or 'test plugin'. Covers static property inspection via ReflectionClass, GenericEvent handler verification with anonymous class mocks, and EventDispatcher registration/removal. Do NOT use for tests requiring live MyAdmin globals (get_module_db, myadmin_log, $GLOBALS['tf']) or SoftaculousNOC SOAP calls.
---
# phpunit-plugin-test

## Critical

- Never use Mockery — mock with anonymous classes only (no Mockery dependency in `composer.json`)
- All test files MUST start with `declare(strict_types=1);`
- Namespace: `Detain\MyAdminVpsSoftaculous\Tests` — all test classes live in `tests/`
- Use tabs for indentation (`.scrutinizer.yml` enforces this)
- Do NOT call `doEnable()` / `doDisable()` in tests — they require live globals (`$GLOBALS['tf']`, `myadmin_log`, SOAP)
- Run tests with: `composer test` or target a specific file with `vendor/bin/phpunit tests/PluginTest.php`

## Instructions

1. **Create the test file** at `tests/PluginTest.php` (or the appropriate test file for your class). Add opening boilerplate:
   ```php
   <?php
   declare(strict_types=1);
   namespace Detain\MyAdminVpsSoftaculous\Tests;
   use Detain\MyAdminVpsSoftaculous\Plugin;
   use PHPUnit\Framework\TestCase;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;
   ```
   Verify the file starts with `<?php` — `tests/FileExistenceTest.php` will check this.

2. **Extend `TestCase` and declare `setUp()`** with a `ReflectionClass` property for structural tests:
   ```php
   class PluginFooTest extends TestCase {
       private ReflectionClass $reflection;
       protected function setUp(): void {
           $this->reflection = new ReflectionClass(Plugin::class);
       }
   }
   ```
   Verify `Plugin::class` resolves — run `composer test -- --list-tests` before adding assertions.

3. **Test static properties** using direct access + `$this->reflection`:
   ```php
   public function testModulePropertyValue(): void {
       $this->assertSame('vps', Plugin::$module);
       $this->assertTrue($this->reflection->getProperty('module')->isStatic());
       $this->assertTrue($this->reflection->getProperty('module')->isPublic());
   }
   ```

4. **Test hook structure** via `Plugin::getHooks()`:
   ```php
   public function testGetHooksContainsExpectedKeys(): void {
       $hooks = Plugin::getHooks();
       $this->assertArrayHasKey('function.requirements', $hooks);
       $this->assertArrayHasKey('vps.load_addons', $hooks);
       $this->assertSame([Plugin::class, 'getAddon'], $hooks['vps.load_addons']);
   }
   ```
   Verify all callback values are `[Plugin::class, 'methodName']` arrays.

5. **Test method signatures** with `ReflectionClass::getMethod()`:
   ```php
   public function testGetRequirementsMethodSignature(): void {
       $method = $this->reflection->getMethod('getRequirements');
       $this->assertTrue($method->isStatic());
       $this->assertTrue($method->isPublic());
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   }
   ```

6. **Test event handler behavior** using anonymous class mocks + `GenericEvent`:
   ```php
   public function testGetRequirementsCallsAddPageRequirement(): void {
       $captured = [];
       $loader = new class($captured) {
           private array $ref;
           public function __construct(array &$ref) { $this->ref = &$ref; }
           public function add_page_requirement(string $name, string $path): void {
               $this->ref[] = [$name, $path];
           }
       };
       $event = new GenericEvent($loader);
       Plugin::getRequirements($event);
       $this->assertCount(1, $captured);
       $this->assertSame('vps_add_softaculous', $captured[0][0]);
       $this->assertStringContainsString('vps_add_softaculous.php', $captured[0][1]);
   }
   ```
   Verify the anonymous class implements the exact method the Plugin handler calls on `$event->getSubject()`.

7. **Test EventDispatcher registration/removal** (pattern from `tests/EventIntegrationTest.php`):
   ```php
   use Symfony\Component\EventDispatcher\EventDispatcher;
   public function testHooksCanBeRegisteredAndRemoved(): void {
       $dispatcher = new EventDispatcher();
       foreach (Plugin::getHooks() as $event => $cb) {
           $dispatcher->addListener($event, $cb);
       }
       foreach (Plugin::getHooks() as $event => $cb) {
           $this->assertTrue($dispatcher->hasListeners($event));
           $dispatcher->removeListener($event, $cb);
           $this->assertFalse($dispatcher->hasListeners($event));
       }
   }
   ```

8. **Add `@covers` / `@coversNothing` docblock** matching the class under test:
   - Structural/unit tests: `@covers \Detain\MyAdminVpsSoftaculous\Plugin`
   - File/filesystem tests: `@coversNothing`

## Examples

**User says:** "Write a test that verifies the settings hook maps to getSettings"

**Actions:**
1. Open `tests/PluginTest.php` (or create a new test file with boilerplate from Step 1)
2. Add method:
```php
/** @covers \Detain\MyAdminVpsSoftaculous\Plugin */
public function testSettingsHookMapping(): void {
    $hooks = Plugin::getHooks();
    $this->assertSame([Plugin::class, 'getSettings'], $hooks['vps.settings']);
}
```
3. Run: `composer test -- tests/PluginTest.php --filter testSettingsHookMapping`

**Result:** Green — method exists and maps correctly.

## Common Issues

- **"Class 'Detain\MyAdminVpsSoftaculous\Plugin' not found"**: Run `composer install` first — autoloader must be generated.
- **"Call to undefined function myadmin_log()"** when testing `doEnable`/`doDisable`: These methods require MyAdmin globals. Do not call them directly; use ReflectionClass to test signatures only.
- **"Too few arguments to function"** in anonymous class mock: The mock must exactly match the method signature that `Plugin` calls on `$event->getSubject()` — check `src/Plugin.php` for the exact method name and parameter types.
- **Mixed tabs/spaces causing PSR-2 failures in CI**: Use tabs only — copy indentation style from existing test files, do not let your editor convert to spaces.
- **`getType()->getName()` returns null or wrong FQCN**: If the type hint in `src/Plugin.php` uses a short class name (e.g., `ServiceHandler` not `\Full\Path\ServiceHandler`), `getName()` returns the short name — assert `'ServiceHandler'`, not the FQCN.
