<?php

include_once 'stubs.php';

class ZendX_Form_Doctrine_MappingsTest extends ZendX_ModelTestCase
{
    /**
     * A Doctrine model
     *
     * @var Doctrine_Record
     */
    protected $model;

    /**
     * Form, associated with a Doctirne model
     *
     * @var ZendX_Form_Doctrine
     */
    protected $form;

    public function setUp()
    {
        parent::setUp();

        $this->model = new Stub();
        $this->form = new ZendX_Form_Doctrine($this->model);
    }

    public function assertColumnMappingToElement($name, $formFieldClass)
    {
        $this->assertType($formFieldClass, $this->form->getElement($name));
    }

    public function testShortStringsAreMappedToTextFieldsByDefault()
    {
        $this->assertColumnMappingToElement('name', 'Zend_Form_Element_Text');
    }

    public function testLongStringsAreMappedToTextareaFieldsByDefault()
    {
        $this->assertColumnMappingToElement('longText', 'Zend_Form_Element_Textarea');
    }

    public function testIntegerFieldsAreMappedToTextFields()
    {
        $this->assertColumnMappingToElement('intField', 'Zend_Form_Element_Text');
    }

    public function testBooleanFieldsAreMappedToCheckboxFields()
    {
        $this->assertColumnMappingToElement('boolField', 'Zend_Form_Element_Checkbox');
    }

    public function testSomeColumnsMayNotBeIncludedInTheFormIfTheMappingMethodReturnsFalse()
    {
        $this->assertColumnMappingToElement('skippedColumn', 'Zend_Form_Element');
        $exForm = new ExcludingElementForm($this->model);
        $this->assertNull($exForm->getElement('skippedColumn'));
    }

    public function testSomeRelationsMayNotBeIncludedInTheFormIfTheMappingMethodReturnsFalse()
    {
        $book = new Book();
        $book->title = 'Good to Great';
        $book->Author = new Author();
        $book->Author->firstName = 'Jim';
        $book->Author->lastName = 'Collins';

        $form = new ExcludingRelationForm($book);
        $this->assertNull($form->getElement('Author'));
    }
}

