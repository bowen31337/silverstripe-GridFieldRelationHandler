<?php
declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests\GridField;

use Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler;
use Arillo\GridFieldRelationHandler\Tests\BaseTestCase;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\ORM\DataObject;

/**
 * Test GridFieldManyRelationHandler component
 */
class GridFieldManyRelationHandlerTest extends BaseTestCase
{
    /**
     * Test that constructor accepts optional targetFragment parameter
     */
    public function testConstructorAcceptsTargetFragment(): void
    {
        $handler = new GridFieldManyRelationHandler('before');
        $this->assertInstanceOf(GridFieldManyRelationHandler::class, $handler);

        $handler2 = new GridFieldManyRelationHandler('after');
        $this->assertInstanceOf(GridFieldManyRelationHandler::class, $handler2);
    }

    /**
     * Test that constructor defaults to useToggle = true
     */
    public function testConstructorDefaultsToToggle(): void
    {
        $handler = new GridFieldManyRelationHandler();
        $this->assertInstanceOf(GridFieldManyRelationHandler::class, $handler);
    }

    /**
     * Test that getColumnContent renders checkbox button
     */
    public function testGetColumnContentRendersCheckbox(): void
    {
        $parent = TestManyParent::create();
        $parent->write();

        $child = TestManyChild::create();
        $child->Title = 'Test Child';
        $child->write();

        $handler = new GridFieldManyRelationHandler();
        $gridField = GridField::create(
            'Children',
            'Children',
            $parent->Children(),
            GridFieldConfig::create()
        );

        $content = $handler->getColumnContent($gridField, $child, 'RelationSetter');

        $this->assertStringContainsString('type="checkbox"', $content);
        $this->assertStringContainsString('value="' . $child->ID . '"', $content);
    }

    /**
     * Test that getColumnContent validates RelationList
     */
    public function testGetColumnContentValidatesRelationList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('requires the GridField to have a RelationList');

        $handler = new GridFieldManyRelationHandler();

        // Create GridField with non-RelationList
        $gridField = GridField::create(
            'Wrong',
            'Wrong',
            TestManyChild::get(), // DataList, not RelationList!
            GridFieldConfig::create()
        );

        $child = TestManyChild::create();
        $child->write();

        $handler->getColumnContent($gridField, $child, 'RelationSetter');
    }

    /**
     * Test state initialization with current relationship values
     */
    public function testStateInitializationWithCurrentValues(): void
    {
        // Create parent with multiple children
        $parent = TestManyParent::create();
        $parent->write();

        $child1 = TestManyChild::create();
        $child1->Title = 'Child 1';
        $child1->ParentID = $parent->ID;
        $child1->write();

        $child2 = TestManyChild::create();
        $child2->Title = 'Child 2';
        $child2->ParentID = $parent->ID;
        $child2->write();

        $handler = new GridFieldManyRelationHandler();
        $gridField = GridField::create(
            'Children',
            'Children',
            $parent->Children(),
            GridFieldConfig::create()
        );

        // Access state through reflection
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('getState');
        $method->setAccessible(true);
        $state = $method->invoke($handler, $gridField);

        // Should contain both child IDs
        $this->assertIsArray($state->RelationVal);
        $this->assertContains($child1->ID, $state->RelationVal);
        $this->assertContains($child2->ID, $state->RelationVal);
    }

    /**
     * Test that checkboxes are checked for related items
     */
    public function testCheckboxesCheckedForRelatedItems(): void
    {
        $parent = TestManyParent::create();
        $parent->write();

        $relatedChild = TestManyChild::create();
        $relatedChild->Title = 'Related Child';
        $relatedChild->ParentID = $parent->ID;
        $relatedChild->write();

        $unrelatedChild = TestManyChild::create();
        $unrelatedChild->Title = 'Unrelated Child';
        $unrelatedChild->write();

        $handler = new GridFieldManyRelationHandler();
        $gridField = GridField::create(
            'Children',
            'Children',
            $parent->Children(),
            GridFieldConfig::create()
        );

        // Related child should be checked
        $relatedContent = $handler->getColumnContent($gridField, $relatedChild, 'RelationSetter');
        $this->assertStringContainsString('checked', $relatedContent);

        // Unrelated child should not be checked
        $unrelatedContent = $handler->getColumnContent($gridField, $unrelatedChild, 'RelationSetter');
        $this->assertStringNotContainsString('checked', $unrelatedContent);
    }

    /**
     * Test that has_many relationships prevent modification when already assigned
     */
    public function testHasManyDisabledWhenAlreadyAssigned(): void
    {
        $parent1 = TestManyParent::create();
        $parent1->write();

        $parent2 = TestManyParent::create();
        $parent2->write();

        // Child belongs to parent1
        $child = TestManyChild::create();
        $child->Title = 'Child';
        $child->ParentID = $parent1->ID;
        $child->write();

        // Try to show it in parent2's grid
        $handler = new GridFieldManyRelationHandler();
        $gridField = GridField::create(
            'Children',
            'Children',
            $parent2->Children(),
            GridFieldConfig::create()
        );

        $content = $handler->getColumnContent($gridField, $child, 'RelationSetter');

        // Should be disabled because it belongs to another parent
        $this->assertStringContainsString('disabled', $content);
    }

    /**
     * Test pagination state persistence
     */
    public function testPaginationStatePersistence(): void
    {
        $parent = TestManyParent::create();
        $parent->write();

        $handler = new GridFieldManyRelationHandler();
        $gridField = GridField::create(
            'Children',
            'Children',
            $parent->Children(),
            GridFieldConfig::create()
        );

        // Get initial state
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('getState');
        $method->setAccessible(true);
        $state1 = $method->invoke($handler, $gridField);

        // Should have FirstTime flag set
        $this->assertTrue($state1->FirstTime);

        // Get state again - should have same session data
        $state2 = $method->invoke($handler, $gridField);

        // States should reference the same object
        $this->assertSame($state1, $state2);
    }
}

/**
 * Test fixture for has_many parent
 */
class TestManyParent extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestManyParent';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_many = [
        'Children' => TestManyChild::class,
    ];

    private static $many_many = [
        'Tags' => TestManyTag::class,
    ];
}

/**
 * Test fixture for has_many child
 */
class TestManyChild extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestManyChild';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Parent' => TestManyParent::class,
    ];
}

/**
 * Test fixture for many_many relationship
 */
class TestManyTag extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestManyTag';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $belongs_many_many = [
        'Parents' => TestManyParent::class,
    ];
}
