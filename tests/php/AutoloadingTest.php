<?php
declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests;

use Arillo\GridFieldRelationHandler\GridField\GridFieldRelationHandler;
use SilverStripe\Dev\SapphireTest;

/**
 * Test that all module classes are autoloadable
 */
class AutoloadingTest extends SapphireTest
{
    /**
     * Test that the base GridFieldRelationHandler class exists and is autoloadable
     */
    public function testBaseClassAutoloads(): void
    {
        $this->assertTrue(
            class_exists(GridFieldRelationHandler::class),
            'GridFieldRelationHandler base class must be autoloadable'
        );
    }

    /**
     * Test that the base class is abstract
     */
    public function testBaseClassIsAbstract(): void
    {
        $reflection = new \ReflectionClass(GridFieldRelationHandler::class);
        $this->assertTrue(
            $reflection->isAbstract(),
            'GridFieldRelationHandler must be an abstract class'
        );
    }

    /**
     * Test that base class implements required interfaces
     */
    public function testBaseClassImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionClass(GridFieldRelationHandler::class);
        $interfaces = $reflection->getInterfaceNames();

        $this->assertContains(
            'SilverStripe\Forms\GridField\GridField_ColumnProvider',
            $interfaces,
            'Must implement GridField_ColumnProvider'
        );

        $this->assertContains(
            'SilverStripe\Forms\GridField\GridField_HTMLProvider',
            $interfaces,
            'Must implement GridField_HTMLProvider'
        );

        $this->assertContains(
            'SilverStripe\Forms\GridField\GridField_ActionProvider',
            $interfaces,
            'Must implement GridField_ActionProvider'
        );
    }

    /**
     * Test that namespace structure follows PSR-4 conventions
     */
    public function testNamespaceStructure(): void
    {
        $baseClass = GridFieldRelationHandler::class;
        $this->assertStringStartsWith(
            'Arillo\\GridFieldRelationHandler\\',
            $baseClass,
            'Namespace must start with Arillo\\GridFieldRelationHandler\\'
        );
    }
}
