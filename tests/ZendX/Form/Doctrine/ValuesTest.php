<?php
require_once 'stubs.php';

class ZendX_Form_Doctrine_ValuesTest extends ZendX_ModelTestCase
{
    public function assertFormHasOnlyEmptyValues($form)
    {
        foreach ($form->getElements() as $element) {
            /* @var $element Zend_Form_Element */
            $valueIsEmpty = $element->getValue() == false;
            // Note that we are testing for NON-strict equality.
            // Some feilds have initial values to 0 instead of null.
            $this->assertTrue($valueIsEmpty);
        }
    }

    public function testFormHasNoValuesForNewModelsWithoutRelations()
    {
        $this->assertFormHasOnlyEmptyValues(new ZendX_Form_Doctrine(new Stub()));
    }

    public function testFormHasNoValuesForNewModelsWithRelations()
    {
        $this->assertFormHasOnlyEmptyValues(new ZendX_Form_Doctrine(new Book()));
    }

    public function testFormShouldBePrepopulatedWithValuesFromNonEmptyModel()
    {
        $book = new Book();
        $book->title = 'The C Programming language';
        $book->save();

        $form = new ZendX_Form_Doctrine($book);
        $this->assertEquals($book->title, $form->getValue('title'));
    }

    public function testFormShouldBePopulatedWithValuesFromRelations()
    {
        $johnResig = new Author();
        $johnResig->firstName = 'John';
        $johnResig->lastName = 'Resig';
        $johnResig->save();

        $proJs = new Book();
        $proJs->title = 'Pro Javascript Techniques';
        $proJs->Author = $johnResig;
        $proJs->save();

        $proJsForm = new ZendX_Form_Doctrine($proJs);
        $this->assertEquals($proJs->title, $proJsForm->getValue('title'));
        $this->assertEquals($johnResig->id, $proJsForm->getValue('Author'));

        $unBook = new Book();
        $unBook->title = 'Unpublished';
        $unBook->Author = $johnResig;
        $unBook->save();

        $johnForm = new ZendX_Form_Doctrine($johnResig);

        $this->assertType('array', $johnForm->getValue('Books'));
        $this->assertContains($proJs->id, $johnForm->getValue('Books'));
        $this->assertContains($unBook->id, $johnForm->getValue('Books'));
        $this->assertEquals(count($johnResig->Books), count($johnForm->getValue('Books')));
    }

    public function testWhenAFormIsVerifiedWithDataTheChangesAreReflectedToTheModel()
    {
        $newBook = new Book();

        $data = array ('title' => 'My New Book');

        $newBookForm = new ZendX_Form_Doctrine($newBook);
        $this->assertTrue($newBookForm->isValid($data));
        $this->assertEquals($data['title'], $newBook->title);
    }

    public function testWhenAFormIsPopulatedTheDataIsReflectedToTheModel()
    {
        $newBook = new Book();
        $newBookForm = new ZendX_Form_Doctrine($newBook);
        $data = array ('title' => 'My New Book');
        $newBookForm->populate($data);

        $this->assertEquals($data['title'], $newBook->title);
    }
}