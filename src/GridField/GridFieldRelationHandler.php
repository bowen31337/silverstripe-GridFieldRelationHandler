<?php

declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\GridField;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * Base class for GridField relationship handler components
 *
 * Provides shared functionality for managing relationships through GridField
 * with radio buttons (has_one) or checkboxes (has_many/many_many)
 */
abstract class GridFieldRelationHandler implements
    GridField_ColumnProvider,
    GridField_HTMLProvider,
    GridField_ActionProvider
{
    /**
     * Target fragment for button rendering
     *
     * @var string
     */
    protected string $targetFragment;

    /**
     * Whether to use toggle mode for relation editing
     *
     * @var bool
     */
    protected bool $useToggle;

    /**
     * Column title for the relation setter column
     *
     * @var string
     */
    protected string $columnTitle = 'Relation Status';

    /**
     * Button titles for different actions
     *
     * @var array<string, string>
     */
    protected array $buttonTitles = [
        'SAVE_RELATION' => 'Save changes',
        'CANCELSAVE_RELATION' => 'Cancel changes',
        'TOGGLE_RELATION' => 'Change relation status',
    ];

    /**
     * Constructor
     *
     * @param bool $useToggle Whether to use toggle mode for showing relation controls
     * @param string $targetFragment GridField fragment name for button placement
     */
    public function __construct(bool $useToggle = true, string $targetFragment = 'before')
    {
        $this->targetFragment = $targetFragment;
        $this->useToggle = $useToggle;
    }

    /**
     * Set whether to use toggle mode
     *
     * @param bool $useToggle
     * @return $this
     */
    public function setUseToggle(bool $useToggle): static
    {
        $this->useToggle = $useToggle;
        return $this;
    }

    /**
     * Set the column title
     *
     * @param string $columnTitle
     * @return $this
     */
    public function setColumnTitle(string $columnTitle): static
    {
        $this->columnTitle = $columnTitle;
        return $this;
    }

    /**
     * Get the column title
     *
     * @return string
     */
    public function getColumnTitle(): string
    {
        return $this->columnTitle;
    }

    /**
     * Set a button title
     *
     * @param string $name Button identifier
     * @param string $title Button label
     * @return $this
     */
    public function setButtonTitle(string $name, string $title): static
    {
        $this->buttonTitles[$name] = $title;
        return $this;
    }

    /**
     * Get a button title (with i18n support)
     *
     * @param string $name Button identifier
     * @return string
     */
    public function getButtonTitle(string $name): string
    {
        if (isset($this->buttonTitles[$name])) {
            $value = $this->buttonTitles[$name];
            $key = sprintf('GridFieldRelationHandler.%s-%s', $name, str_replace(' ', '', $value));
            return _t($key, $value);
        }

        return _t('GridFieldRelationHandler.' . $name, $name);
    }

    /**
     * Get GridField state for this component
     *
     * @param GridField $gridField
     * @return \SilverStripe\ORM\FieldType\DBField
     */
    protected function getState(GridField $gridField)
    {
        static $state = null;
        if (!$state) {
            $state = $gridField->State->GridFieldRelationHandler;
            $this->setupState($state);
        }
        return $state;
    }

    /**
     * Initialize state with default values
     *
     * @param mixed $state GridField state object
     * @return void
     */
    protected function setupState($state): void
    {
        if (!isset($state->RelationVal)) {
            $state->RelationVal = 0;
            $state->FirstTime = 1;
        } else {
            $state->FirstTime = 0;
        }

        if (!isset($state->ShowingRelation)) {
            $state->ShowingRelation = 0;
        }
    }

    /**
     * Modify the list of columns displayed in the table
     *
     * @param GridField $gridField
     * @param array $columns List reference of all column names
     * @return void
     */
    public function augmentColumns($gridField, &$columns): void
    {
        $state = $this->getState($gridField);
        if ($state->ShowingRelation || !$this->useToggle) {
            if (!in_array('RelationSetter', $columns)) {
                array_unshift($columns, 'RelationSetter');
            }
            if ($this->useToggle && ($key = array_search('Actions', $columns)) !== false) {
                unset($columns[$key]);
            }
        }
    }

    /**
     * Return a list of the columns handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getColumnsHandled($gridField): array
    {
        return ['RelationSetter'];
    }

    /**
     * Return metadata about the column
     *
     * @param GridField $gridField
     * @param string $columnName
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName): array
    {
        if ($columnName === 'RelationSetter') {
            return [
                'title' => $this->columnTitle
            ];
        }
        return [];
    }

    /**
     * Return attributes for the column's TD tag
     *
     * @param GridField $gridField
     * @param \SilverStripe\ORM\DataObject $record
     * @param string $columnName
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName): array
    {
        return ['class' => 'col-noedit'];
    }

    /**
     * Get form action buttons for relation handling
     *
     * @param GridField $gridField
     * @return ArrayList
     */
    protected function getFields(GridField $gridField): ArrayList
    {
        $state = $this->getState($gridField);

        if (!$this->useToggle) {
            $fields = [
                GridField_FormAction::create(
                    $gridField,
                    'relationhandler-saverel',
                    $this->getButtonTitle('SAVE_RELATION'),
                    'saveGridRelation',
                    null
                )
            ];
        } elseif ($state->ShowingRelation) {
            $fields = [
                GridField_FormAction::create(
                    $gridField,
                    'relationhandler-cancelrel',
                    $this->getButtonTitle('CANCELSAVE_RELATION'),
                    'cancelGridRelation',
                    null
                ),
                GridField_FormAction::create(
                    $gridField,
                    'relationhandler-saverel',
                    $this->getButtonTitle('SAVE_RELATION'),
                    'saveGridRelation',
                    null
                )
            ];
        } else {
            $fields = [
                GridField_FormAction::create(
                    $gridField,
                    'relationhandler-togglerel',
                    $this->getButtonTitle('TOGGLE_RELATION'),
                    'toggleGridRelation',
                    null
                )
            ];
        }

        return ArrayList::create($fields);
    }

    /**
     * Return HTML fragments to include in the GridField
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField): array
    {
        Requirements::javascript('arillo/gridfieldrelationhandler:client/dist/GridFieldRelationHandler.js');

        $data = ArrayData::create([
            'Fields' => $this->getFields($gridField)
        ]);

        return [
            $this->targetFragment => $data->renderWith('GridFieldRelationHandlerButtons')
        ];
    }

    /**
     * Return a list of the actions handled by this component
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField): array
    {
        return ['saveGridRelation', 'cancelGridRelation', 'toggleGridRelation'];
    }

    /**
     * Handle an action on the GridField
     *
     * @param GridField $gridField
     * @param string $actionName
     * @param mixed $arguments
     * @param array $data
     * @return void
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data): void
    {
        $actions = array_map('strtolower', $this->getActions($gridField));
        if (in_array($actionName, $actions)) {
            $this->$actionName($gridField, $arguments, $data);
        }
    }

    /**
     * Toggle relation editing mode
     *
     * @param GridField $gridField
     * @param mixed $arguments
     * @param array $data
     * @return void
     */
    protected function toggleGridRelation(GridField $gridField, $arguments, $data): void
    {
        $state = $this->getState($gridField);
        $state->ShowingRelation = true;
    }

    /**
     * Cancel relation editing
     *
     * @param GridField $gridField
     * @param mixed $arguments
     * @param array $data
     * @return void
     */
    protected function cancelGridRelation(GridField $gridField, $arguments, $data): void
    {
        $state = $this->getState($gridField);
        $state->ShowingRelation = false;
        $state->FirstTime = true;
    }

    /**
     * Save grid relation (to be implemented by subclasses)
     *
     * @param GridField $gridField
     * @param mixed $arguments
     * @param array $data
     * @return void
     */
    protected function saveGridRelation(GridField $gridField, $arguments, $data): void
    {
        $state = $this->getState($gridField);
        $state->ShowingRelation = false;
    }
}
