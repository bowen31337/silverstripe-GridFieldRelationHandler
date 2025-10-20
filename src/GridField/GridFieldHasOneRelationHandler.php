<?php

declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\GridField;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ArrayData;

/**
 * GridField component for managing has_one relationships
 *
 * Provides radio button interface for selecting a single related object
 * in a has_one relationship
 */
class GridFieldHasOneRelationHandler extends GridFieldRelationHandler
{
    /**
     * The object that has the relationship
     *
     * @var DataObject
     */
    protected DataObject $onObject;

    /**
     * Name of the has_one relation
     *
     * @var string
     */
    protected string $relationName;

    /**
     * Class name of the target object
     *
     * @var string
     */
    protected string $targetObject;

    /**
     * Constructor
     *
     * @param DataObject $onObject The object that has the has_one relationship
     * @param string $relationName Name of the has_one relation (e.g., 'MainImage')
     * @param string $targetFragment GridField fragment for rendering position
     * @throws \InvalidArgumentException If relation name doesn't exist
     */
    public function __construct(DataObject $onObject, string $relationName, string $targetFragment = 'before')
    {
        $this->onObject = $onObject;
        $this->relationName = $relationName;

        // Validate that the relation exists
        $hasOne = $onObject->hasOne($relationName);
        if (!$hasOne) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to find a has_one relation named "%s" on %s',
                $relationName,
                $onObject->ClassName
            ));
        }

        $this->targetObject = $hasOne;

        parent::__construct(false, $targetFragment);
    }

    /**
     * Initialize state with current has_one relationship value
     *
     * @param mixed $state GridField state object
     * @param mixed $extra Additional parameters (unused)
     * @return void
     */
    protected function setupState($state, $extra = null): void
    {
        parent::setupState($state, $extra);

        if ($state->FirstTime) {
            // Get current relationship ID
            $relationMethod = $this->relationName;
            $relatedObject = $this->onObject->$relationMethod();

            if ($relatedObject && $relatedObject->exists()) {
                $state->RelationVal = $relatedObject->ID;
            } else {
                $state->RelationVal = 0;
            }
        }
    }

    /**
     * Render the radio button for this record
     *
     * @param GridField $gridField
     * @param DataObject $record
     * @param string $columnName
     * @return string
     * @throws \InvalidArgumentException If GridField model class doesn't match relation target
     */
    public function getColumnContent($gridField, $record, $columnName): string
    {
        $class = $gridField->getModelClass();

        // Validate that the GridField's model class matches the relation target
        if (!($class === $this->targetObject || is_subclass_of($class, $this->targetObject))) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a subclass of %s. Perhaps you wanted to use %s::get() as the list for this GridField?',
                $class,
                $this->targetObject,
                $this->targetObject
            ));
        }

        $state = $this->getState($gridField);

        // Check if this record is currently selected
        $checked = ($state->RelationVal == $record->ID);

        $field = ArrayData::create([
            'Checked' => $checked,
            'Value' => $record->ID,
            'Name' => $this->relationName . 'ID'
        ]);

        return $field->renderWith('GridFieldHasOneRelationHandlerItem')->getValue();
    }

    /**
     * Save the selected has_one relationship
     *
     * @param GridField $gridField
     * @param mixed $arguments
     * @param array $data
     * @return void
     */
    protected function saveGridRelation(GridField $gridField, $arguments, $data): void
    {
        $field = $this->relationName . 'ID';
        $state = $this->getState($gridField);

        // Get selected ID from state
        $id = (int) $state->RelationVal;

        // Update the relationship
        $this->onObject->$field = $id;
        $this->onObject->write();

        parent::saveGridRelation($gridField, $arguments, $data);
    }
}
