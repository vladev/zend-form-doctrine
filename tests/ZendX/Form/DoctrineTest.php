<?php
require_once 'Doctrine/stubs.php';

class ZendX_Form_DoctrineTest extends ZendX_ModelTestCase
{
    /**
     * @return ZendX_Form_Doctrine
     */
    public function createFormForStub()
    {
        return new ZendX_Form_Doctrine('Stub');
    }

    public function testFormCanBeCreatedWithoutAModel()
    {
        new ZendX_Form_Doctrine();
    }

    public function testFormCanBeCreatedWithARecordInstance()
    {
        $model = new Stub();
        $f = new ZendX_Form_Doctrine($model);
        $this->assertSame($model, $f->getModel());
    }

    public function testFormCanBeCreatedWithATableInstance()
    {
        $f = new ZendX_Form_Doctrine(Doctrine::getTable('Stub'));
        $this->assertType('Stub', $f->getModel());
    }

    public function testFormThrowsExceptionWhenTriedToBeCreatedWithoutAValidModel()
    {
        $this->setExpectedException('ZendX_Form_Doctrine_Exception');

        new ZendX_Form_Doctrine($this);
    }

    public function testFormCanBeCreatedWithAString()
    {
        $f = new ZendX_Form_Doctrine('Stub');
        $this->assertType('Stub', $f->getModel());
    }

    public function testFormHasFieldsForAllCorrespondingModelFields()
    {
        $f = $this->createFormForStub();
        $this->assertEquals(Stub::FIELDS_COUNT, count($f->getElements()));
    }

    public function testFormValidationFailsIfModelValidationFailsAndPassesWhenModelIsValid()
    {
        $record = new ValidationStub();
        $this->assertFalse($record->isValid());

        $form = new ZendX_Form_Doctrine($record);
        $this->assertFalse($form->isValid(array()));

        Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_VALIDATE, Doctrine::VALIDATE_LENGTHS);
        $this->assertFalse($form->isValid(array('stringValidation' => 'more than 10 characters long')));

        $this->assertTrue($form->isValid(array('stringValidation' => 'valid')));

        $record2 = new ValidationStubRegExp();
        $form2 = new ZendX_Form_Doctrine($record2);

        $this->assertFalse($record2->isValid());
        $this->assertFalse($form2->isValid(array ('regexValidation' => 'some text')));

        $this->assertTrue($form2->isValid(array ('regexValidation' => '123')));
    }

    public function testInvalidFormsContainMessagesForTheInvalidElements()
    {
        $record = new ValidationStub();
        $form = new ZendX_Form_Doctrine($record);

        $message = 'This field should not be empty';

        $form->setValidationMessage('stringValidation', 'notnull', $message);

        $this->assertFalse($form->isValid(array()));

        $this->assertContains($message, $form->getElement('stringValidation')->getErrorMessages());
    }

}