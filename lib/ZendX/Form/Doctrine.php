<?php
class ZendX_Form_Doctrine extends Zend_Form
{
    const INTEGER     = 'integer';
    const STRING      = 'string';
    const BOOLEAN     = 'boolean';
    const ONE         = 'one';
    const MANY        = 'many';

    protected $_model;
    protected $_validatorsBreakChaingOnFailure = true;
    protected $_validationMessages = array();

    public function __construct($model = null, $options = null)
    {
        parent::__construct($options);
        if ($model) {
            $this->setModel($model);
        }
    }

    /**
     * Returns the doctrine model associated to this form
     *
     * @return Doctrine_Record
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Returns the doctrine model associated with this form
     *
     * @param mixed $model
     * @return ZendX_Form_Doctrine
     */
    public function setModel($model)
    {
        if ($model instanceof Doctrine_Record) {
            $this->_model = $model;
        } else if ($model instanceof Doctrine_Table) {
            /* @var $model Doctrine_Table */
            $this->_model = $model->getRecord();
        } else if (is_string($model) && class_exists($model, true)) {
            $this->_model = new $model();
        } else {
            throw new ZendX_Form_Doctrine_Exception('Model should be a doctrine table or record instance. '
                . '"' . get_class($model) ? get_class($model) : gettype($model) . '" given'
            );
        }

        $this->_createFormFieldsFromModel();

        return $this;
    }

    protected function _createFormFieldsFromModel()
    {
        $this->clearElements();

        $relationColumns = $this->_processRelations();

        $this->_processColumns($relationColumns);
    }

    protected function _processRelations()
    {
        $table = $this->getModel()->getTable();
        $relationColumns = array();
        foreach ($table->getRelations() as $name => $relation) {
            /* @var $relation Doctrine_Relation*/

            $element = $this->_createElementForRelation($name, $relation);
            if (!$element) continue;

            // Collect relation columns so they are returned by this method
            // This is used when creating the elements for the regular columns
            $relationColumns[] = $relation->getLocalColumnName();



            /* @var $element Zend_Form_Element_Multi */
            if (!$element instanceof Zend_Form_Element_Multi) {
                throw new ZendX_Form_Doctrine_Exception(
                    'One to many column mappings should create multi elements. Please check your "mapColumnToFormControl" method',
                    1001
                );
            }

            $element->addMultiOptions($this->_getRelationMultiOptions($name, $relation));
            if ($this->getModel()->state() != Doctrine_Record::STATE_TCLEAN &&
                $this->getModel()->state() != Doctrine_Record::STATE_TDIRTY) // Not a new record
            {
                switch ($relation->getType()) {
                    case Doctrine_Relation::ONE;
                        $element->setValue(current($this->getModel()->get($name)->identifier()));
                        break;
                    case Doctrine_Relation::MANY:
                        $values = array();
                        foreach ($this->getModel()->get($name) as $toManyRelatedModel) {
                            /* @var $toManyRelatedModel Doctrine_Record */
                            $values[] = current($toManyRelatedModel->identifier());
                        }
                        if ($values) {
                            $element->setValue($values);
                        }
                        break;
                }
            }
            $this->addElement($element);
        }

        return $relationColumns;
    }

    protected function _getRelationMultiOptions($relationName, $relation)
    {
        $relatedModel = $relation->fetchRelatedFor($this->getModel());

        $optionPairs = array();

        foreach ($relatedModel->getTable()->findAll() as $selectionModel) {
            /* @var $selectionModel Doctrine_Record */
            $identifiers = $selectionModel->identifier();

            if (count($identifiers) > 1) {
                // Currently (25.11.2008) Doctring does not support mapping to models with composite
                // primary keys, so this exception will never be triggered.
                throw new ZendX_Form_Doctrine_Exception(
                    'Compisite primary keys are not supported. Model "' . get_class($relatedModel) . '"',
                    1000
                );
            }

            $id = reset($identifiers);

            $optionPairs[$id] = (string) $selectionModel;
        }

        return $optionPairs;
    }

    protected function _processColumns($relationColumns)
    {
        $table = $this->getModel()->getTable();
        foreach ($table->getColumns() as $name => $column)
        {
            $fieldName = $table->getFieldName($name);
            if (isset($column['autoincrement'])) continue;

            if (in_array($name, $relationColumns)) continue;

            $element = $this->_createElementForColumn($fieldName, $column);
            if ($element) {

                if ($this->getModel()->state() != Doctrine_Record::STATE_TCLEAN &&
                    $this->getModel()->state() != Doctrine_Record::STATE_TDIRTY) // Not a new record
                {
                    $element->setValue($this->getModel()->get($fieldName));
                }

                $this->addElement($element);
            }
        }
    }

    /**
     * Creates an element based on whay column is the element for.
     * Be default
     * strings shorted than 64 chars are repesended by Text field,
     * strings longer than 64 chars => Textareas,
     * integers => Text fields,
     * boolean => Checkbox,
     *
     * Extend the form and override this method to provide custom functionality,
     * but be sure to return a new Zend_Form_Element instance
     *
     * @param string $fieldName
     * @param array $column
     * @return Zend_Form_Element
     */
    protected function _createElementForColumn($fieldName, array $column)
    {
        switch ($column['type']) {
            case self::STRING:
                return $column['length'] > 64
                    ? new Zend_Form_Element_Textarea($fieldName)
                    : new Zend_Form_Element_Text($fieldName);
            case self::INTEGER:
                return new Zend_Form_Element_Text($fieldName);
            case self::BOOLEAN:
                return new Zend_Form_Element_Checkbox($fieldName);
            default:
                return false;
        }
    }

    protected function _createElementForRelation($relationName, Doctrine_Relation $relation)
    {
        switch ($relation->getType()) {
            case Doctrine_Relation::ONE:
                return new Zend_Form_Element_Select($relationName);
            case Doctrine_Relation::MANY:
                return new Zend_Form_Element_Multiselect($relationName);
            default:
                throw new ZendX_Form_Doctrine_Exception(
                    'Relation type not supported ("' . $relation->getType() . '"). ' .
                    'Consider extending ' . __CLASS__ . ' and overriding ' . __METHOD__ . '() to support it.'
                );
        }
    }



    public function setValidatorsBreakChainOfFailure($value)
    {
        $this->_validatorsBreakChaingOnFailure = (bool) $value;
        return $this;
    }

    public function getValidatorsBreakChainOfFailure()
    {
        return $this->_validatorsBreakChaingOnFailure;
    }

    public function setValidationMessage($field, $type, $message)
    {
        $this->_validationMessages[$field][$type] = $message;
    }

    public function clearValidationMessages()
    {
        $this->_validationMessages = array();
    }

    public function setValidationMessages($messages)
    {
        $this->_validationMessages = (array)$messages;
    }

    public function getValidationMessages($field = null)
    {
        if ($field) {
            return $this->_validationMessages[$field];
        } else {
            return $this->_validationMessages;
        }
    }

    public function getValidationMessage($field, $type)
    {
        if (array_key_exists($field, $this->_validationMessages)) {
            if (array_key_exists($type, $this->_validationMessages[$field])) {
                return $this->_validationMessages[$field][$type];
            }
        }

        return null;
    }



    public function isValid($data)
    {
        $valid = parent::isValid($data);

        $model = $this->getModel();
        $model->fromArray($this->getValues());

        if (!$model->isValid()) {
            $valid = false;
            foreach ($model->getErrorStack() as $field => $errors) {
                $element = $this->getElement($field);
                foreach ($errors as $error) {
                    $message = $this->getValidationMessage($field, $error);
                    if ($message) {
                        $element->addError($message);
                    }
                }
            }

        }

        return $valid;
    }

    public function populate($values)
    {
        parent::populate($values);
        $this->getModel()->fromArray($this->getValues());
    }
}
