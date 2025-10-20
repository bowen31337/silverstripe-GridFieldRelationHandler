<?php

declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\GridField;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_DataManipulator;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\ClassInfo;

/**
 * GridField component for managing has_many and many_many relationships
 *
 * Provides checkbox interface for selecting multiple related objects
 * in has_many or many_many relationships
 */
class GridFieldManyRelationHandler extends GridFieldRelationHandler implements GridField_DataManipulator
{
    /**
     * Helper object for accessing protected HasManyList properties
     *
     * @var GridFieldManyRelationHandler_HasManyList
     */
    protected GridFieldManyRelationHandler_HasManyList $cheatList;

    /**
     * Helper object for accessing protected ManyManyList properties
     *
     * @var GridFieldManyRelationHandler_ManyManyList
     */
    protected GridFieldManyRelationHandler_ManyManyList $cheatManyList;

    /**
     * Constructor
     *
     * @param string $targetFragment GridField fragment for rendering position
     */
    public function __construct(string $targetFragment = 'before')
    {
        parent::__construct(true, $targetFragment);
        $this->cheatList = new GridFieldManyRelationHandler_HasManyList();
        $this->cheatManyList = new GridFieldManyRelationHandler_ManyManyList();
    }

    /**
     * Render the checkbox for this record
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string
     * @throws \InvalidArgumentException If GridField list is not a RelationList
     */
    public function getColumnContent($gridField, $record, $columnName): string
    {
        $list = $gridField->getList();
        if (!$list instanceof RelationList) {
            throw new \InvalidArgumentException(sprintf(
                'GridFieldManyRelationHandler requires the GridField to have a RelationList. Got a %s instead.',
                get_class($list)
            ));
        }

        $state = $this->getState($gridField);
        $checked = in_array($record->ID, $state->RelationVal->toArray());

        $field = [
            'Checked' => $checked,
            'Value' => $record->ID,
            'Name' => $this->relationName($gridField)
        ];

        // For has_many relationships, disable checkbox if already assigned to another parent
        if ($list instanceof HasManyList) {
            $foreignKey = $this->cheatList->getForeignKey($list);
            if ($foreignKey) {
                $key = $record->{$foreignKey};
                if ($key && !$checked) {
                    $field['Disabled'] = true;
                }
            }
        }

        $fieldData = ArrayData::create($field);
        return $fieldData->renderWith('GridFieldManyRelationHandlerItem')->getValue();
    }

    /**
     * Manipulate the GridField data to show all available records, not just related ones
     *
     * @param GridField $gridField
     * @param SS_List $list
     * @return SS_List
     * @throws \InvalidArgumentException If list is not a RelationList
     */
    public function getManipulatedData(GridField $gridField, SS_List $list): SS_List
    {
        if (!$list instanceof RelationList) {
            throw new \InvalidArgumentException(sprintf(
                'GridFieldManyRelationHandler requires the GridField to have a RelationList. Got a %s instead.',
                get_class($list)
            ));
        }

        $state = $this->getState($gridField);

        // Initialize state with current relationship IDs
        if ($state->FirstTime) {
            $state->RelationVal = array_values($list->getIdList()) ?: [];
        }

        // If toggle is enabled and we're not showing the relation picker, return the list as-is
        if (!$state->ShowingRelation && $this->useToggle) {
            return $list;
        }

        // Clone the query and remove the foreign key filter to show all available records
        $query = clone $list->dataQuery();

        try {
            $foreignIDFilter = $this->cheatList->getForeignIDFilter($list);
            if ($foreignIDFilter) {
                $query->removeFilterOn($foreignIDFilter);
            }
        } catch (\InvalidArgumentException $e) {
            // No filter to remove
        }

        $orgList = $list;
        $newList = DataList::create($list->dataClass());
        $newList = $newList->setDataQuery($query);

        // For many_many relationships, add the join table to show selection state
        if ($orgList instanceof ManyManyList) {
            $joinTable = $this->cheatManyList->getJoinTable($orgList);
            if ($joinTable) {
                $baseClass = ClassInfo::baseDataClass($list->dataClass());
                $localKey = $this->cheatManyList->getLocalKey($orgList);

                if ($localKey && $baseClass) {
                    $query->leftJoin($joinTable, "\"{$joinTable}\".\"{$localKey}\" = \"{$baseClass}\".\"ID\"");
                    $newList = $newList->setDataQuery($query);
                }
            }
        }

        return $newList;
    }

    /**
     * Generate a unique name for the relation field
     *
     * @param GridField $gridField
     * @return string
     */
    protected function relationName(GridField $gridField): string
    {
        return $gridField->getName() . get_class($gridField->getList());
    }

    /**
     * Reset state to current relationship values when canceling
     *
     * @param GridField $gridField
     * @param mixed $arguments
     * @param array $data
     * @return void
     */
    protected function cancelGridRelation(GridField $gridField, $arguments, $data): void
    {
        parent::cancelGridRelation($gridField, $arguments, $data);

        $state = $this->getState($gridField);
        $state->RelationVal = array_values($gridField->getList()->getIdList()) ?: [];
    }

    /**
     * Save the selected many relationships
     *
     * @param GridField $gridField
     * @param mixed $arguments
     * @param array $data
     * @return void
     */
    protected function saveGridRelation(GridField $gridField, $arguments, $data): void
    {
        $state = $this->getState($gridField);
        $list = $gridField->getList();

        if ($list instanceof RelationList) {
            $list->setByIdList($state->RelationVal->toArray());
        }

        parent::saveGridRelation($gridField, $arguments, $data);
    }
}

/**
 * Helper class to access protected HasManyList properties
 */
class GridFieldManyRelationHandler_HasManyList extends HasManyList
{
    /**
     * Constructor - creates empty list
     */
    public function __construct()
    {
        // Empty constructor - this is just a helper to access protected properties
    }

    /**
     * Get the foreign key from a HasManyList
     *
     * @param HasManyList|null $on
     * @return string|null
     */
    public function getForeignKey(?HasManyList $on = null): ?string
    {
        if (!$on) {
            return null;
        }
        return $on->foreignKey ?? null;
    }

    /**
     * Get the foreign ID filter from a RelationList
     *
     * @param RelationList|null $on
     * @return string|null
     */
    public function getForeignIDFilter(?RelationList $on = null): ?string
    {
        if (!$on) {
            return null;
        }
        return $on->foreignIDFilter();
    }
}

/**
 * Helper class to access protected ManyManyList properties
 */
class GridFieldManyRelationHandler_ManyManyList extends ManyManyList
{
    /**
     * Constructor - creates empty list
     */
    public function __construct()
    {
        // Empty constructor - this is just a helper to access protected properties
    }

    /**
     * Get the join table from a ManyManyList
     *
     * @param ManyManyList|null $on
     * @return string|null
     */
    public function getJoinTable(?ManyManyList $on = null): ?string
    {
        if (!$on) {
            return null;
        }
        return $on->joinTable ?? null;
    }

    /**
     * Get the local key from a ManyManyList
     *
     * @param ManyManyList|null $on
     * @return string|null
     */
    public function getLocalKey(?ManyManyList $on = null): ?string
    {
        if (!$on) {
            return null;
        }
        return $on->localKey ?? null;
    }

    /**
     * Get the foreign key from a ManyManyList
     *
     * @param ManyManyList|null $on
     * @return string|null
     */
    public function getForeignKey(?ManyManyList $on = null): ?string
    {
        if (!$on) {
            return null;
        }
        return $on->foreignKey ?? null;
    }

    /**
     * Get the foreign ID filter from a RelationList
     *
     * @param RelationList|null $on
     * @return string|null
     */
    public function getForeignIDFilter(?RelationList $on = null): ?string
    {
        if (!$on) {
            return null;
        }
        return $on->foreignIDFilter();
    }
}
