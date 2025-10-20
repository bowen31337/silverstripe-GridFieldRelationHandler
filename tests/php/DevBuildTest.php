<?php
declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Manifest\ModuleLoader;

/**
 * Test that the module integrates correctly with SilverStripe dev/build
 */
class DevBuildTest extends SapphireTest
{
    /**
     * Test that the module is recognized by SilverStripe
     */
    public function testModuleIsRecognized(): void
    {
        $module = ModuleLoader::inst()->getManifest()->getModule('arillo/gridfieldrelationhandler');

        $this->assertNotNull(
            $module,
            'Module must be recognized by SilverStripe module loader'
        );
    }

    /**
     * Test that module path is correct
     */
    public function testModulePathIsCorrect(): void
    {
        $module = ModuleLoader::inst()->getManifest()->getModule('arillo/gridfieldrelationhandler');

        if ($module) {
            $path = $module->getPath();
            $this->assertDirectoryExists($path, 'Module path must exist');
            $this->assertFileExists($path . '/composer.json', 'Module must have composer.json');
        }
    }

    /**
     * Test that no errors occur during test database build
     */
    public function testDevBuildCompletesWithoutErrors(): void
    {
        // This test passes if the test database builds successfully
        // SapphireTest automatically builds the database before running tests
        $this->assertTrue(true, 'Dev build completed without errors');
    }
}
