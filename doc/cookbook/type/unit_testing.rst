.. index::
   single: Type; Field Type testing

How to Unit Test your Field Types
=================================

A Field consists of 3 core object: a field type (implementing
:class:`Rollerworks\\Component\\Search\\FieldTypeInterface`) the
:class:`Rollerworks\\Component\\Search\\SearchField` and the
:class:`Rollerworks\\Component\\Search\\SearchFieldView`.

The only class that is usually manipulated by programmers is the field type class
which serves as a field blueprint. It is used to generate the ``SearchField`` and the
``SearchFieldView``. You could test it directly by mocking its interactions with the
factory but it would be complex. It is better to pass it to SearchFactory like it
is done in a real application. It is simple to bootstrap and you can trust
the Search components enough to use them as a testing base.

There is already a class that you can benefit from for simple FieldTypes
testing: :class:`Rollerworks\\Component\\Search\\Test\\SearchIntegrationTestCase`. It is used to
test the core types and you can use it to test your types too.

.. note::

    Depending on the way you installed RollerworksSearch the tests may
    not be downloaded. Use the ``--prefer-source`` option with
    Composer if this is the case.

The Basics
----------

The simplest ``SearchIntegrationTestCase`` implementation looks like the following::

    // src/Acme/Invoice/Tests/Search/Type/InvoiceNumberTypeTest.php
    namespace Acme\Invoice\Tests\Search\Type;

    use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
    use Acme\Invoice\Search\Type\InvoiceNumberType;
    use Acme\Invoice\Search\ValueComparison\InvoiceNumberComparison;
    use Acme\Invoice\InvoiceNumber;

    class InvoiceNumberTypeTest extends SearchIntegrationTestCase
    {
        public function testValidInvoiceNumber()
        {
            $field = $this->getFactory()->createField('invoice', 'invoice_number');

            $expectedOutput = new InvoiceNumber(2015, 20);
            $expectedView = '2015-0020';

            $this->assertTransformedEquals($field, $expectedOutput, '2015-0020', $expectedView);
            $this->assertTransformedEquals($field, $expectedOutput, '2015-020', $expectedView);
            $this->assertTransformedEquals($field, $expectedOutput, '2015-20', $expectedView);
        }

        public function testWrongInputFails()
        {
           $field = $this->getFactory()->createField('invoice', 'invoice_number');

            $this->assertTransformedFails($field, '201-0020');
            $this->assertTransformedFails($field, '2015-');
            $this->assertTransformedFails($field, '201500');
        }

        protected function getTypes()
        {
            return array(
                new InvoiceNumberType(
                    new InvoiceNumberComparison()
                )
            );
        }
    }

So, what does it test? Here comes a detailed explanation.

First you verify if the ``FieldType`` compiles. This includes basic class
inheritance, the ``buildField`` function and options resolution. This should
be the first test you write:

.. code-block:: php

    $type = new TestedType();
    $form = $this->getFactory()->create($type);

This test checks that none of your data transformers used by the field
failed. The ``assertTransformedEquals`` checks that the value-input is transformed
properly to the expected output and that the reverse transforming is what you
expect::

    $this->assertTransformedEquals($field, $expectedOutput, '2015-0020', $expectedView);
    $this->assertTransformedEquals($field, $expectedOutput, '2015-020', $expectedView);
    $this->assertTransformedEquals($field, $expectedOutput, '2015-20', $expectedView);

    $form->submit($formData);
    $this->assertTrue($form->isSynchronized());

.. note::

    The expected view result is not required, but its a good practice
    to ensure the field transformers work properly.

Next, verify that invalid values are not transformed::

    $this->assertTransformedFails($field, '201-0020');

.. caution::

    Make sure to only call ``getFactory`` method and not use the private
    ``factory`` property to get the factory.

    To access the factory builder (before calling the ``getFactory`` method)
    use the ``factoryBuilder`` property.

Adding a Type your Type Depends on
----------------------------------

Your field type may depend on other types that are not registered by
default. It might look like this::

    // src/Acme/Invoice/Search/Type/TestedType.php

    // ... the getParent method
    return 'my_custom_type';

To create your type correctly, you need to make the other type available
to the search factory in your test. The easiest way is to register it manually
before creating the child type using the ``getTypes`` method::

    // src/Acme/Test/Tests/Search/Type/TestedTypeTest.php
    namespace Acme\Test\Tests\Search\Type;

    use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
    use Acme\Test\Search\Type\ParentType;
    use Acme\Test\Search\Type\TestedType;
    use Acme\Test\ValueObject;

    class TestedTypeTest extends SearchIntegrationTestCase
    {
        public function testValidValueTransforms()
        {
            $field = $this->getFactory()->createField('field_name', 'tested_type');

            $expectedOutput = new ValueObject(10, 20, 50);
            $expectedView = '{10, 20, 50}';

            $this->assertTransformedEquals($field, $expectedOutput, '{10, 20,50}', $expectedView);
        }

        protected function getTypes()
        {
            return array(
                new ParentType(),
                new TestedType(),
            );
        }
    }

.. caution::

    Make sure the parent type you add is well tested. Otherwise you may
    be getting errors that are not related to the type you are currently
    testing but to its children.

Adding custom Extensions
------------------------

It often happens that you use some options that are added by
:doc:`type extensions </cookbook/type/create_field_type_extension>`. One of the
cases may be the Symfony ``ValidatorExtension`` with its ``constraints`` option.
The ``SearchIntegrationTestCase`` loads only the core form extension so an "Invalid option"
exception will be raised if you try to use it for testing a class that depends
on other extensions. You need add those extensions to the factory object::

    // src/Acme/Test/Tests/Search/Type/TestedTypeTest.php
    namespace Acme\Test\Tests\Search\Type;

    use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
    use Rollerworks\Component\Search\Extension\Symfony\ValidatorExtension;

    class TestedTypeTest extends SearchIntegrationTestCase
    {
        protected function getTypeExtensions()
        {
            return array(
                new ValidatorExtension(),
            );
        }

        // ... your tests
    }

.. note::

    The Symfony ``ValidatorExtension`` class is provided by a separate package.
    See :doc:`/installing` for more information to install this extension.

Testing against different Sets of Data
--------------------------------------

If you are not familiar yet with PHPUnit's `data providers`_, this might be
a good opportunity to use them::

    // src/Acme/Test/Tests/Search/Type/TestedTypeTest.php
    namespace Acme\Test\Tests\Search\Type;

    use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
    use Acme\Test\Search\Type\TestedType;
    use Acme\Test\ValueObject;

    class TestedTypeTest extends SearchIntegrationTestCase
    {
        protected function getTypes()
        {
            return array(
                new TestedType(),
            );
        }

        /**
         * @dataProvider getValidTestData
         */
        public function testValidDataTransforms($input, $expected, $viewExpected = null)
        {
            $field = $this->getFactory()->createField('field_name', 'tested_type');
            $this->assertTransformedEquals($field, $expectedOutput, $input, $expectedView);
        }

        public function getValidTestData()
        {
            return array(
                array('{10, 20,50}', new ValueObject(10, 20, 50), '{10, 20, 50}'),
                array('{10, 20, 50}', new ValueObject(10, 20, 50), '{10, 20, 50}'),
                array('{10,20,50}', new ValueObject(10, 20, 50), '{10, 20, 50}'),
            );
        }
    }

The code above will run your test three times with 3 different sets of
data. This allows for decoupling the test fixtures from the tests and
easily testing against multiple sets of data.

.. _`data providers`: http://www.phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
