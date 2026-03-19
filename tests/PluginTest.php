<?php

declare(strict_types=1);

namespace Detain\MyAdminVpsSoftaculous\Tests;

use Detain\MyAdminVpsSoftaculous\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Unit tests for the Plugin class.
 *
 * Tests cover class structure, static properties, hook registration,
 * event handler signatures, and settings integration.
 *
 * @covers \Detain\MyAdminVpsSoftaculous\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass<Plugin>
     */
    private ReflectionClass $reflection;

    /**
     * Set up reflection for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that the Plugin class can be instantiated.
     *
     * @return void
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the Plugin class exists in the expected namespace.
     *
     * @return void
     */
    public function testClassExistsInCorrectNamespace(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
        $this->assertSame('Detain\\MyAdminVpsSoftaculous', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the $name static property is set correctly.
     *
     * @return void
     */
    public function testNamePropertyValue(): void
    {
        $this->assertSame('Softaculous VPS Addon', Plugin::$name);
    }

    /**
     * Test that the $description static property is a non-empty string.
     *
     * @return void
     */
    public function testDescriptionPropertyIsNonEmpty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
        $this->assertStringContainsString('Softaculous', Plugin::$description);
    }

    /**
     * Test that the $help static property exists and is a string.
     *
     * @return void
     */
    public function testHelpPropertyIsString(): void
    {
        $this->assertIsString(Plugin::$help);
    }

    /**
     * Test that the $module static property is set to 'vps'.
     *
     * @return void
     */
    public function testModulePropertyValue(): void
    {
        $this->assertSame('vps', Plugin::$module);
    }

    /**
     * Test that the $type static property is set to 'addon'.
     *
     * @return void
     */
    public function testTypePropertyValue(): void
    {
        $this->assertSame('addon', Plugin::$type);
    }

    /**
     * Test that all expected static properties exist on the class.
     *
     * @return void
     */
    public function testAllStaticPropertiesExist(): void
    {
        $expectedProperties = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expectedProperties as $property) {
            $this->assertTrue(
                $this->reflection->hasProperty($property),
                "Expected static property \${$property} to exist on Plugin class"
            );
            $this->assertTrue(
                $this->reflection->getProperty($property)->isStatic(),
                "Expected \${$property} to be static"
            );
            $this->assertTrue(
                $this->reflection->getProperty($property)->isPublic(),
                "Expected \${$property} to be public"
            );
        }
    }

    /**
     * Test that getHooks returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks returns the expected hook keys.
     *
     * @return void
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertArrayHasKey('vps.load_addons', $hooks);
        $this->assertArrayHasKey('vps.settings', $hooks);
    }

    /**
     * Test that getHooks returns exactly three hooks.
     *
     * @return void
     */
    public function testGetHooksReturnsExactlyThreeHooks(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(3, $hooks);
    }

    /**
     * Test that each hook callback references a valid static method.
     *
     * @return void
     */
    public function testHookCallbacksReferenceValidMethods(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertIsArray($callback, "Callback for '{$eventName}' should be an array");
            $this->assertCount(2, $callback, "Callback for '{$eventName}' should have exactly 2 elements");
            $this->assertSame(Plugin::class, $callback[0], "Callback class for '{$eventName}' should be Plugin");
            $this->assertTrue(
                $this->reflection->hasMethod($callback[1]),
                "Method '{$callback[1]}' referenced by hook '{$eventName}' should exist on Plugin class"
            );
        }
    }

    /**
     * Test that hook callbacks point to static methods.
     *
     * @return void
     */
    public function testHookCallbacksAreStaticMethods(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $method = $this->reflection->getMethod($callback[1]);
            $this->assertTrue(
                $method->isStatic(),
                "Method '{$callback[1]}' for hook '{$eventName}' should be static"
            );
            $this->assertTrue(
                $method->isPublic(),
                "Method '{$callback[1]}' for hook '{$eventName}' should be public"
            );
        }
    }

    /**
     * Test that getHooks uses the module property dynamically for hook names.
     *
     * @return void
     */
    public function testGetHooksUsesModulePropertyInKeys(): void
    {
        $hooks = Plugin::getHooks();
        $module = Plugin::$module;
        $this->assertArrayHasKey("{$module}.load_addons", $hooks);
        $this->assertArrayHasKey("{$module}.settings", $hooks);
    }

    /**
     * Test that the getRequirements method has the correct signature.
     *
     * @return void
     */
    public function testGetRequirementsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType, 'Parameter should have a type hint');
        $this->assertSame(GenericEvent::class, $paramType->getName());
    }

    /**
     * Test that the getAddon method has the correct signature.
     *
     * @return void
     */
    public function testGetAddonMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getAddon');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType);
        $this->assertSame(GenericEvent::class, $paramType->getName());
    }

    /**
     * Test that the getSettings method has the correct signature.
     *
     * @return void
     */
    public function testGetSettingsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType);
        $this->assertSame(GenericEvent::class, $paramType->getName());
    }

    /**
     * Test that the doEnable method has the correct signature.
     *
     * @return void
     */
    public function testDoEnableMethodSignature(): void
    {
        $method = $this->reflection->getMethod('doEnable');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * Test that the doDisable method has the correct signature.
     *
     * @return void
     */
    public function testDoDisableMethodSignature(): void
    {
        $method = $this->reflection->getMethod('doDisable');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
        $this->assertSame('regexMatch', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * Test that doEnable and doDisable have matching signatures.
     *
     * @return void
     */
    public function testDoEnableAndDoDisableHaveMatchingSignatures(): void
    {
        $enableParams = $this->reflection->getMethod('doEnable')->getParameters();
        $disableParams = $this->reflection->getMethod('doDisable')->getParameters();

        $this->assertCount(count($enableParams), $disableParams);

        foreach ($enableParams as $index => $enableParam) {
            $disableParam = $disableParams[$index];
            $this->assertSame(
                $enableParam->getName(),
                $disableParam->getName(),
                "Parameter {$index} name should match between doEnable and doDisable"
            );
            if ($enableParam->hasType() && $disableParam->hasType()) {
                $this->assertSame(
                    $enableParam->getType()->getName(),
                    $disableParam->getType()->getName(),
                    "Parameter {$index} type should match between doEnable and doDisable"
                );
            }
        }
    }

    /**
     * Test that the constructor takes no arguments.
     *
     * @return void
     */
    public function testConstructorTakesNoArguments(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Test that all expected methods exist on the class.
     *
     * @return void
     */
    public function testAllExpectedMethodsExist(): void
    {
        $expectedMethods = [
            'getHooks',
            'getRequirements',
            'getAddon',
            'getSettings',
            'doEnable',
            'doDisable',
        ];
        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Expected method '{$method}' to exist on Plugin class"
            );
        }
    }

    /**
     * Test that the class is not abstract.
     *
     * @return void
     */
    public function testClassIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the class is not final.
     *
     * @return void
     */
    public function testClassIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Test that getHooks is a pure function (returns same result on multiple calls).
     *
     * @return void
     */
    public function testGetHooksIsPure(): void
    {
        $result1 = Plugin::getHooks();
        $result2 = Plugin::getHooks();
        $this->assertSame($result1, $result2);
    }

    /**
     * Test that the function.requirements hook maps to getRequirements.
     *
     * @return void
     */
    public function testRequirementsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
    }

    /**
     * Test that the load_addons hook maps to getAddon.
     *
     * @return void
     */
    public function testLoadAddonsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getAddon'], $hooks['vps.load_addons']);
    }

    /**
     * Test that the settings hook maps to getSettings.
     *
     * @return void
     */
    public function testSettingsHookMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame([Plugin::class, 'getSettings'], $hooks['vps.settings']);
    }

    /**
     * Test that all hook callbacks are callable arrays.
     *
     * @return void
     */
    public function testHookCallbacksAreCallable(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $callback) {
            $this->assertIsCallable(
                $callback,
                "Hook callback for '{$eventName}' should be callable"
            );
        }
    }

    /**
     * Test that the static property values are strings.
     *
     * @return void
     */
    public function testStaticPropertyTypes(): void
    {
        $this->assertIsString(Plugin::$name);
        $this->assertIsString(Plugin::$description);
        $this->assertIsString(Plugin::$help);
        $this->assertIsString(Plugin::$module);
        $this->assertIsString(Plugin::$type);
    }

    /**
     * Test that the description mentions Softaculous features.
     *
     * @return void
     */
    public function testDescriptionContainsSoftaculousFeatures(): void
    {
        $this->assertStringContainsString('Auto Installer', Plugin::$description);
        $this->assertStringContainsString('scripts', Plugin::$description);
        $this->assertStringContainsString('cPanel', Plugin::$description);
    }

    /**
     * Test that doEnable first parameter type-hints ServiceHandler.
     *
     * @return void
     */
    public function testDoEnableServiceHandlerTypeHint(): void
    {
        $method = $this->reflection->getMethod('doEnable');
        $params = $method->getParameters();
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('ServiceHandler', $type->getName());
    }

    /**
     * Test that doDisable first parameter type-hints ServiceHandler.
     *
     * @return void
     */
    public function testDoDisableServiceHandlerTypeHint(): void
    {
        $method = $this->reflection->getMethod('doDisable');
        $params = $method->getParameters();
        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame('ServiceHandler', $type->getName());
    }

    /**
     * Test that the class has no parent class (extends nothing).
     *
     * @return void
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Test that the class implements no interfaces.
     *
     * @return void
     */
    public function testClassImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaces());
    }

    /**
     * Test that the class uses no traits.
     *
     * @return void
     */
    public function testClassUsesNoTraits(): void
    {
        $this->assertEmpty($this->reflection->getTraits());
    }
}
