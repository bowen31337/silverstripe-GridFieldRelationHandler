<?php
declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests\Model;

use Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler_HasManyList;
use Arillo\GridFieldRelationHandler\GridField\GridFieldManyRelationHandler_ManyManyList;
use Arillo\GridFieldRelationHandler\Tests\BaseTestCase;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Test GridFieldRelationHandlerRelationList helper classes
 *
 * These helper classes provide access to protected properties of HasManyList
 * and ManyManyList for the GridFieldManyRelationHandler
 */
class GridFieldRelationHandlerRelationListTest extends BaseTestCase
{
    /**
     * Test that HasManyList helper can be instantiated
     */
    public function testHasManyListHelperInstantiation(): void
    {
        $helper = new GridFieldManyRelationHandler_HasManyList();
        $this->assertInstanceOf(GridFieldManyRelationHandler_HasManyList::class, $helper);
    }

    /**
     * Test that ManyManyList helper can be instantiated
     */
    public function testManyManyListHelperInstantiation(): void
    {
        $helper = new GridFieldManyRelationHandler_ManyManyList();
        $this->assertInstanceOf(GridFieldManyRelationHandler_ManyManyList::class, $helper);
    }

    /**
     * Test that getForeignKey returns null when no list provided
     */
    public function testGetForeignKeyReturnsNullWithoutList(): void
    {
        $helper = new GridFieldManyRelationHandler_HasManyList();
        $result = $helper->getForeignKey(null);
        $this->assertNull($result);
    }

    /**
     * Test that getForeignKey can access a real HasManyList foreign key
     */
    public function testGetForeignKeyAccessesRealList(): void
    {
        $parent = TestRelationParent::create();
        $parent->write();

        $list = $parent->Children();
        $this->assertInstanceOf(\SilverStripe\ORM\HasManyList::class, $list);

        $helper = new GridFieldManyRelationHandler_HasManyList();
        $foreignKey = $helper->getForeignKey($list);

        $this->assertIsString($foreignKey);
        $this->assertStringContainsString('ID', $foreignKey);
    }

    /**
     * Test that ManyManyList helper methods return null when no list provided
     */
    public function testManyManyHelperReturnsNullWithoutList(): void
    {
        $helper = new GridFieldManyRelationHandler_ManyManyList();

        $this->assertNull($helper->getJoinTable(null));
        $this->assertNull($helper->getLocalKey(null));
        $this->assertNull($helper->getForeignKey(null));
    }

    /**
     * Test that ManyManyList helper can access real ManyManyList properties
     */
    public function testManyManyHelperAccessesRealList(): void
    {
        $parent = TestRelationParent::create();
        $parent->write();

        $list = $parent->Tags();
        $this->assertInstanceOf(\SilverStripe\ORM\ManyManyList::class, $list);

        $helper = new GridFieldManyRelationHandler_ManyManyList();

        $joinTable = $helper->getJoinTable($list);
        $this->assertIsString($joinTable);

        $localKey = $helper->getLocalKey($list);
        $this->assertIsString($localKey);

        $foreignKey = $helper->getForeignKey($list);
        $this->assertIsString($foreignKey);
    }
}

/**
 * Test fixture for relationship testing
 */
class TestRelationParent extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestRelationParent';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_many = [
        'Children' => TestRelationChild::class,
    ];

    private static $many_many = [
        'Tags' => TestRelationTag::class,
    ];
}

/**
 * Test fixture for has_many relationship
 */
class TestRelationChild extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestRelationChild';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Parent' => TestRelationParent::class,
    ];
}

/**
 * Test fixture for many_many relationship
 */
class TestRelationTag extends DataObject implements TestOnly
{
    private static $table_name = 'GridFieldRelationHandler_TestRelationTag';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $belongs_many_many = [
        'Parents' => TestRelationParent::class,
    ];
}
