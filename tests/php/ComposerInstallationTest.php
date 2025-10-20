<?php
declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests;

use SilverStripe\Dev\SapphireTest;

/**
 * Test that the module installs correctly via Composer
 */
class ComposerInstallationTest extends SapphireTest
{
    /**
     * Test that composer.json exists and is valid
     */
    public function testComposerJsonExists(): void
    {
        $composerFile = BASE_PATH . '/composer.json';
        $this->assertFileExists($composerFile, 'composer.json file must exist');

        $composerData = json_decode(file_get_contents($composerFile), true);
        $this->assertNotNull($composerData, 'composer.json must be valid JSON');
    }

    /**
     * Test that required dependencies are specified
     */
    public function testRequiredDependencies(): void
    {
        $composerFile = BASE_PATH . '/composer.json';
        $composerData = json_decode(file_get_contents($composerFile), true);

        $this->assertArrayHasKey('require', $composerData);
        $this->assertArrayHasKey('php', $composerData['require']);
        $this->assertArrayHasKey('silverstripe/framework', $composerData['require']);

        // Verify PHP 8.1+ requirement
        $this->assertStringContainsString('8.1', $composerData['require']['php']);

        // Verify SilverStripe 5 requirement
        $this->assertStringContainsString('5', $composerData['require']['silverstripe/framework']);
    }

    /**
     * Test that PSR-4 autoloading is configured
     */
    public function testPsr4AutoloadingConfigured(): void
    {
        $composerFile = BASE_PATH . '/composer.json';
        $composerData = json_decode(file_get_contents($composerFile), true);

        $this->assertArrayHasKey('autoload', $composerData);
        $this->assertArrayHasKey('psr-4', $composerData['autoload']);
        $this->assertArrayHasKey('Arillo\\GridFieldRelationHandler\\', $composerData['autoload']['psr-4']);
    }
}
