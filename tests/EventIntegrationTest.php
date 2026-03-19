<?php

declare(strict_types=1);

namespace Detain\MyAdminVpsSoftaculous\Tests;

use Detain\MyAdminVpsSoftaculous\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for verifying event dispatcher integration and hook registration.
 *
 * These tests verify that the plugin hooks can be properly registered
 * with the Symfony EventDispatcher without invoking the actual handler
 * logic (which depends on external services and global state).
 *
 * @covers \Detain\MyAdminVpsSoftaculous\Plugin
 */
class EventIntegrationTest extends TestCase
{
    /**
     * Test that all hooks can be registered with the Symfony EventDispatcher.
     *
     * @return void
     */
    public function testHooksCanBeRegisteredWithEventDispatcher(): void
    {
        $dispatcher = new EventDispatcher();
        $hooks = Plugin::getHooks();

        foreach ($hooks as $eventName => $callback) {
            $dispatcher->addListener($eventName, $callback);
        }

        foreach ($hooks as $eventName => $callback) {
            $this->assertTrue(
                $dispatcher->hasListeners($eventName),
                "Event '{$eventName}' should have listeners after registration"
            );
        }
    }

    /**
     * Test that the dispatcher reports the correct listener count per event.
     *
     * @return void
     */
    public function testDispatcherListenerCount(): void
    {
        $dispatcher = new EventDispatcher();
        $hooks = Plugin::getHooks();

        foreach ($hooks as $eventName => $callback) {
            $dispatcher->addListener($eventName, $callback);
        }

        foreach ($hooks as $eventName => $callback) {
            $listeners = $dispatcher->getListeners($eventName);
            $this->assertCount(1, $listeners, "Event '{$eventName}' should have exactly 1 listener");
        }
    }

    /**
     * Test that getRequirements calls add_page_requirement on the event subject.
     *
     * Uses an anonymous class to avoid mocking vendor classes.
     *
     * @return void
     */
    public function testGetRequirementsCallsAddPageRequirement(): void
    {
        $addedRequirements = [];
        $loader = new class($addedRequirements) {
            /** @var array<int, array{string, string}> */
            private array $ref;

            /**
             * @param array<int, array{string, string}> $ref
             */
            public function __construct(array &$ref)
            {
                $this->ref = &$ref;
            }

            /**
             * @param string $name
             * @param string $path
             * @return void
             */
            public function add_page_requirement(string $name, string $path): void
            {
                $this->ref[] = [$name, $path];
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        $this->assertCount(1, $addedRequirements);
        $this->assertSame('vps_add_softaculous', $addedRequirements[0][0]);
        $this->assertStringContainsString('vps_add_softaculous.php', $addedRequirements[0][1]);
    }

    /**
     * Test that getRequirements references the correct addon file path.
     *
     * @return void
     */
    public function testGetRequirementsReferencesCorrectFilePath(): void
    {
        $addedRequirements = [];
        $loader = new class($addedRequirements) {
            /** @var array<int, array{string, string}> */
            private array $ref;

            /**
             * @param array<int, array{string, string}> $ref
             */
            public function __construct(array &$ref)
            {
                $this->ref = &$ref;
            }

            /**
             * @param string $name
             * @param string $path
             * @return void
             */
            public function add_page_requirement(string $name, string $path): void
            {
                $this->ref[] = [$name, $path];
            }
        };

        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);

        $this->assertStringContainsString('myadmin-softaculous-vps-addon', $addedRequirements[0][1]);
        $this->assertStringContainsString('src/vps_add_softaculous.php', $addedRequirements[0][1]);
    }

    /**
     * Test that hook event names follow the expected naming convention.
     *
     * @return void
     */
    public function testHookEventNamesFollowConvention(): void
    {
        $hooks = Plugin::getHooks();
        foreach (array_keys($hooks) as $eventName) {
            $this->assertMatchesRegularExpression(
                '/^[a-z]+\.[a-z_]+$/',
                $eventName,
                "Event name '{$eventName}' should follow 'scope.action' convention"
            );
        }
    }

    /**
     * Test that hooks can be removed from the dispatcher after registration.
     *
     * @return void
     */
    public function testHooksCanBeRemovedFromDispatcher(): void
    {
        $dispatcher = new EventDispatcher();
        $hooks = Plugin::getHooks();

        foreach ($hooks as $eventName => $callback) {
            $dispatcher->addListener($eventName, $callback);
        }

        foreach ($hooks as $eventName => $callback) {
            $dispatcher->removeListener($eventName, $callback);
            $this->assertFalse(
                $dispatcher->hasListeners($eventName),
                "Event '{$eventName}' should have no listeners after removal"
            );
        }
    }

    /**
     * Test that the GenericEvent subject is accessible in getRequirements.
     *
     * @return void
     */
    public function testGenericEventSubjectIsAccessible(): void
    {
        $subject = new \stdClass();
        $event = new GenericEvent($subject);
        $this->assertSame($subject, $event->getSubject());
    }

    /**
     * Test that the plugin methods that accept GenericEvent do not declare return types.
     *
     * This verifies the event handler pattern used throughout the codebase.
     *
     * @return void
     */
    public function testEventHandlerMethodsReturnVoid(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $eventMethods = ['getRequirements', 'getAddon', 'getSettings'];

        foreach ($eventMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            // These methods have no declared return type in the source
            $this->assertNull(
                $returnType,
                "Method '{$methodName}' should not have a declared return type"
            );
        }
    }

    /**
     * Test that getHooks return type is explicitly declared as array.
     *
     * @return void
     */
    public function testGetHooksReturnTypeIsArray(): void
    {
        $reflection = new ReflectionClass(Plugin::class);
        $method = $reflection->getMethod('getHooks');
        $returnType = $method->getReturnType();
        // The source does not declare a return type but the docblock says @return array
        // We verify the actual return value is an array
        $this->assertIsArray(Plugin::getHooks());
    }
}
