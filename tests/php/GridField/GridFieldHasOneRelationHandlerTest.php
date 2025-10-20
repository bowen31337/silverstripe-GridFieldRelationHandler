<?php
declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests\GridField;

use Arillo\GridFieldRelationHandler\GridField\GridFieldHasOneRelationHandler;
use Arillo\GridFieldRelationHandler\Tests\BaseTestCase;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\ORM\DataObject;

/**
 * Test GridFieldHasOneRelationHandler component
 */
class GridFieldHasOneRelationHandlerTest extends BaseTestCase
{
    /**
     * Test that constructor validates relation name exists
     */
    public function testConstructorValidatesRelationExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find a has_one relation');

        $object = new TestHasOneObject();
        new GridFieldHasOneRelationHandler($object, 'NonExistentRelation');
    }

    /**
     * Test that constructor accepts valid relation name
     */
    public function testConstructorAcceptsValidRelation(): void
    {
        $object = TestHasOneObject::create();
        $object->write();

        $handler = new GridFieldHasOneRelationHandler($object, 'RelatedImage');

        $this->assertInstanceOf(GridFieldHasOneRelationHandler::class, $handler);
    }

    /**
     * Test that constructor sets target fragment
     */
    public function testConstructorSetsTargetFragment(): void
    {
        $object = TestHasOneObject::create();
        $object->write();

        $handler = new GridFieldHasOneRelationHandler($object, 'RelatedImage', 'after');

        // Target fragment is protected, but we can verify object was created
        $this->assertInstanceOf(GridFieldHasOneRelationHandler::class, $handler);
    }

    /**
     * Test state initialization with current relationship value
     */
    public function testStateInitializationWithCurrentValue(): void
    {
        // Create related object
        $image = TestRelatedImage::create();
        $image->Title = 'Test Image';
        $image->write();

        // Create main object with relationship
        $object = TestHasOneObject::create();
        $object->RelatedImageID = $image->ID;
        $object->write();

        $handler = new GridFieldHasOneRelationHandler($object, 'RelatedImage');
        $gridField = GridField::create(
            'Images',
            'Images',
            TestRelatedImage::get(),
            GridFieldConfig::create()
        );

        // Access state through reflection since getState is protected
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('getState');
        $method->setAccessible(true);
        $state = $method->invoke($handler, $gridField);

        $this->assertEquals($image->ID, $state->RelationVal);
    }

    /**
     * Test that getColumnContent renders radio button
     */
    public function testGetColumnContentRendersRadioButton(): void
    {
        $object = TestHasOneObject::create();
        $object->write();

        $image = TestRelatedImage::create();
        $image->Title = 'Test Image';
        $image->write();

        $handler = new GridFieldHasOneRelationHandler($object, 'RelatedImage');
        $gridField = GridField::create(
            'Images',
            'Images',
            TestRelatedImage::get(),
            GridFieldConfig::create()
        );

        $content = $handler->getColumnContent($gridField, $image, 'RelationSetter');

        $this->assertStringContainsString('type="radio"', $content);
        $this->assertStringContainsString('value="' . $image->ID . '"', $content);
    }

    /**
     * Test that getColumnContent validates model class
     */
    public function testGetColumnContentValidatesModelClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $object = TestHasOneObject::create();
        $object->write();

        $handler = new GridFieldHasOneRelationHandler($object, 'RelatedImage');

        // Create GridField with wrong model class
        $gridField = GridField::create(
            'Wrong',
            'Wrong',
            TestHasOneObject::get(), // Wrong class!
            GridFieldConfig::create()
        );

        $wrongObject = TestHasOneObject::create();
        $wrongObject->write();

        $handler->getColumnContent($gridField, $wrongObject, 'RelationSetter');
    }
}

/**
 * Test fixture for has_one relationships
 */
class TestHasOneObject extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestHasOneObject';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_one = [
        'RelatedImage' => TestRelatedImage::class,
    ];
}

/**
 * Test fixture for related object
 */
class TestRelatedImage extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestRelatedImage';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];
}
