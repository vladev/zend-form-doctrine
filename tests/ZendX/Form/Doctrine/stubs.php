<?php
class Stub extends Doctrine_Record
{
    const FIELDS_COUNT = 6;

    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 32, array ('notnull' => true));
        $this->hasColumn('longText', 'string', 1024, array ('notnull' => true));
        $this->hasColumn('intField', 'integer', array ('notnull' => true));
        $this->hasColumn('boolField', 'boolean', array ('notnull' => true));
        $this->hasColumn('shortText', 'string', 10, array ('notnull' => true));
        $this->hasColumn('skippedColumn', 'string', 10, array ('notnull' => true));
    }
}

class Car extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('make', 'string', 32, array ('notnull' => true));
    }

    public function setUp()
    {
        $this->hasMany('Driver as Drivers', array (
            'local' => 'carId',
            'foreign' => 'driverId',
            'refClass' => 'CarDriver'
        ));
    }

    public function __toString()
    {
        return $this->make;
    }
}

class Driver extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 32);
    }

    public function setUp()
    {
        $this->hasMany('Car as CarsToDrive', array (
            'local' => 'driverId',
            'foreign' => 'carId',
            'refClass' => 'CarDriver'
        ));
    }

    public function __toString()
    {
        return $this->name;
    }
}

class CarDriver extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('carId', 'integer');
        $this->hasColumn('driverId', 'integer');
    }
}


class Book extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('title', 'string', 128, array ('notnull' => true));
        $this->hasColumn('authorId', 'integer', array ('notnull' => false));
    }

    public function setUp()
    {
        $this->hasOne('Author', array (
            'local'   => 'authorId',
            'foreign' => 'id'
        ));
    }

    public function __toString()
    {
        return $this->title . ($this->authorId ? ' ' . (string) $this->Author : '');
    }
}

class Author extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('firstName', 'string', 32, array ('notnull' => true));
        $this->hasColumn('lastName', 'string', 32, array ('notnull' => true));
    }

    public function setUp()
    {
        $this->hasMany('Book as Books', array (
            'local' => 'id',
            'foreign' => 'authorId'
        ));
    }

    public function __toString()
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}

class WrongForm extends ZendX_Form_Doctrine
{
    protected function _createElementForRelation($relationName, Doctrine_Relation $relation)
    {
        return new Zend_Form_Element_Text($relationName);
    }
}

class ExcludingElementForm extends ZendX_Form_Doctrine
{
    protected function _createElementForColumn($filedName, array $column)
    {
        if ($filedName == 'skippedColumn') {
            return false;
        }
        return parent::_createElementForColumn($filedName, $column);
    }
}

class ExcludingRelationForm extends ZendX_Form_Doctrine
{
    protected function _createElementForRelation($relationName, Doctrine_Relation $relation)
    {
        if ($relationName == 'Author') {
            return false;
        }
        return parent::_createElementForRelation($relationName, $relation);
    }
}

class ValidationStub extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('stringValidation', 'string', 10, array ('notnull' => true));
    }
}

class ValidationStubRegExp extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('regexValidation', 'string', 32, array ('regexp' => '/\d+/'));
    }
}


