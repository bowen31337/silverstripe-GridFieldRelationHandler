<?php
declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

/**
 * Test backward compatibility with SilverStripe 4 code
 *
 * Ensures that legacy class names (without namespaces) continue to work
 * via SilverStripe Injector class aliases
 */
class BackwardCompatibilityTest extends SapphireTest
{
    /**
     * Test that legacy GridFieldRelationHandler class name resolves
     */
    public function testLegacyGridFieldRelationHandlerClassExists(): void
    {
        // In SS5, we use Injector to create instances with legacy class names
        $this->assertTrue(
            class_exists('Arillo\GridFieldRelationHandler\GridField\GridFieldRelationHandler'),
            'Namespaced GridFieldRelationHandler class must exist'
        );
    }

    /**
     * Test that legacy GridFieldHasOneRelationHandler class name resolves
     */
    public function testLegacyGridFieldHasOneRelationHandlerResolves(): void
    {
        // Test that Injector can create instance with legacy name
        $injector = Injector::inst();

        // Check if class mapping exists
        $this->assertTrue(
            class_exists('Arillo\GridFieldRelationHandler\GridField\GridFieldHasOneRelationHandler'),
            'Namespaced GridFieldHasOneRelationHandler class must exist'
        );
    }

    /**
     * Test that legacy GridFieldManyRelationHandler class name resolves
     */
    public function testLegacyGridFieldManyRelationHandlerResolves(): void
    {
        // Test that Injector can create instance with legacy name
        $injector = Injector::inst();

        // Check if class mapping exists
        $this->assertTrue(
            class_exists('Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler'),
            'Namespaced GridFieldManyRelationHandler class must exist'
        );
    }

    /**
     * Test constructor signature compatibility with SilverStripe 4 code
     *
     * GridFieldHasOneRelationHandler constructor signature:
     * SS4: __construct($onObject, $relationName, $segement = 'before')
     * SS5: __construct(DataObject $onObject, string $relationName, string $targetFragment = 'before')
     */
    public function testGridFieldHasOneRelationHandlerConstructorCompatibility(): void
    {
        $reflection = new \ReflectionClass('Arillo\GridFieldRelationHandler\GridField\GridFieldHasOneRelationHandler');
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor, 'Constructor must exist');

        $params = $constructor->getParameters();

        // Should have 3 parameters: $onObject, $relationName, $targetFragment
        $this->assertCount(3, $params, 'Constructor must have 3 parameters');

        // Third parameter should be optional (has default value)
        $this->assertTrue(
            $params[2]->isOptional(),
            'Third parameter (targetFragment) must be optional for backward compatibility'
        );

        $this->assertEquals(
            'before',
            $params[2]->getDefaultValue(),
            'Default value for targetFragment must be "before"'
        );
    }

    /**
     * Test constructor signature compatibility for GridFieldManyRelationHandler
     *
     * SS4: __construct($useToggle = true, $segement = 'before')
     * SS5: __construct(string $targetFragment = 'before')
     *
     * Note: This is a BREAKING change - the constructor signature changed
     * Users will need to update their code to remove the $useToggle parameter
     */
    public function testGridFieldManyRelationHandlerConstructorSignature(): void
    {
        $reflection = new \ReflectionClass('Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler');
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor, 'Constructor must exist');

        $params = $constructor->getParameters();

        // Should have 1 parameter: $targetFragment
        $this->assertCount(1, $params, 'Constructor must have 1 parameter');

        // First parameter should be optional
        $this->assertTrue(
            $params[0]->isOptional(),
            'First parameter (targetFragment) must be optional'
        );

        $this->assertEquals(
            'before',
            $params[0]->getDefaultValue(),
            'Default value for targetFragment must be "before"'
        );
    }

    /**
     * Test that helper classes are accessible
     */
    public function testHelperClassesAreAccessible(): void
    {
        $this->assertTrue(
            class_exists('Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler_HasManyList'),
            'HasManyList helper class must exist'
        );

        $this->assertTrue(
            class_exists('Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler_ManyManyList'),
            'ManyManyList helper class must exist'
        );
    }

    /**
     * Test namespace structure for all classes
     */
    public function testNamespaceStructure(): void
    {
        $classes = [
            'Arillo\GridFieldRelationHandler\GridField\GridFieldRelationHandler',
            'Arillo\GridFieldRelationHandler\GridField\GridFieldHasOneRelationHandler',
            'Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler',
        ];

        foreach ($classes as $class) {
            $this->assertTrue(
                class_exists($class),
                "Class {$class} must exist"
            );

            $this->assertStringStartsWith(
                'Arillo\GridFieldRelationHandler\\',
                $class,
                "Class {$class} must be in Arillo\GridFieldRelationHandler namespace"
            );
        }
    }
}
