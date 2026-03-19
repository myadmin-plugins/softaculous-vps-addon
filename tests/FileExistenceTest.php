<?php

declare(strict_types=1);

namespace Detain\MyAdminVpsSoftaculous\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for verifying the expected file structure of the package.
 *
 * @coversNothing
 */
class FileExistenceTest extends TestCase
{
    /**
     * @var string Base path of the package.
     */
    private string $basePath;

    /**
     * Set up the base path for each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->basePath = dirname(__DIR__);
    }

    /**
     * Test that the Plugin.php source file exists.
     *
     * @return void
     */
    public function testPluginPhpExists(): void
    {
        $this->assertFileExists($this->basePath . '/src/Plugin.php');
    }

    /**
     * Test that the vps_add_softaculous.php source file exists.
     *
     * @return void
     */
    public function testVpsAddSoftaculousPhpExists(): void
    {
        $this->assertFileExists($this->basePath . '/src/vps_add_softaculous.php');
    }

    /**
     * Test that composer.json exists.
     *
     * @return void
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists($this->basePath . '/composer.json');
    }

    /**
     * Test that the src directory exists.
     *
     * @return void
     */
    public function testSrcDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->basePath . '/src');
    }

    /**
     * Test that the tests directory exists.
     *
     * @return void
     */
    public function testTestsDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->basePath . '/tests');
    }

    /**
     * Test that README.md exists.
     *
     * @return void
     */
    public function testReadmeExists(): void
    {
        $this->assertFileExists($this->basePath . '/README.md');
    }

    /**
     * Test that Plugin.php contains the correct namespace declaration.
     *
     * @return void
     */
    public function testPluginPhpContainsNamespace(): void
    {
        $content = file_get_contents($this->basePath . '/src/Plugin.php');
        $this->assertStringContainsString('namespace Detain\\MyAdminVpsSoftaculous;', $content);
    }

    /**
     * Test that Plugin.php imports GenericEvent.
     *
     * @return void
     */
    public function testPluginPhpImportsGenericEvent(): void
    {
        $content = file_get_contents($this->basePath . '/src/Plugin.php');
        $this->assertStringContainsString('use Symfony\\Component\\EventDispatcher\\GenericEvent;', $content);
    }

    /**
     * Test that vps_add_softaculous.php defines the expected function.
     *
     * @return void
     */
    public function testVpsAddSoftaculousDefinesFunction(): void
    {
        $content = file_get_contents($this->basePath . '/src/vps_add_softaculous.php');
        $this->assertStringContainsString('function vps_add_softaculous()', $content);
    }

    /**
     * Test that composer.json contains valid JSON.
     *
     * @return void
     */
    public function testComposerJsonIsValidJson(): void
    {
        $content = file_get_contents($this->basePath . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded, 'composer.json should contain valid JSON');
    }

    /**
     * Test that composer.json has the correct package name.
     *
     * @return void
     */
    public function testComposerJsonPackageName(): void
    {
        $content = file_get_contents($this->basePath . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertSame('detain/myadmin-softaculous-vps-addon', $decoded['name']);
    }

    /**
     * Test that composer.json has the correct type.
     *
     * @return void
     */
    public function testComposerJsonType(): void
    {
        $content = file_get_contents($this->basePath . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertSame('myadmin-plugin', $decoded['type']);
    }

    /**
     * Test that composer.json has PSR-4 autoload configured.
     *
     * @return void
     */
    public function testComposerJsonAutoload(): void
    {
        $content = file_get_contents($this->basePath . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertArrayHasKey('autoload', $decoded);
        $this->assertArrayHasKey('psr-4', $decoded['autoload']);
        $this->assertArrayHasKey('Detain\\MyAdminVpsSoftaculous\\', $decoded['autoload']['psr-4']);
    }

    /**
     * Test that composer.json has the LGPL license.
     *
     * @return void
     */
    public function testComposerJsonLicense(): void
    {
        $content = file_get_contents($this->basePath . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertSame('LGPL-2.1-only', $decoded['license']);
    }

    /**
     * Test that source files use PHP opening tags.
     *
     * @return void
     */
    public function testSourceFilesHavePhpOpeningTag(): void
    {
        $files = [
            $this->basePath . '/src/Plugin.php',
            $this->basePath . '/src/vps_add_softaculous.php',
        ];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringStartsWith('<?php', $content, "File {$file} should start with <?php tag");
        }
    }
}
