Performing searches
===================

This chapter will explain how you can integrate RollerworksSearch into
your application.

You should have already :doc:`installed <installing>` the package and
have a good understanding about all the :doc:`components <introduction>`.
If not please do this before continuing.

Using the FactoryBuilder
------------------------

The FactoryBuilder helps with setting up the search system.
You only need to set a SearchFactory up once, and then it can be reused
multiple times.

.. note::

    The ``Searches`` class and SearchFactoryBuilder are only meant to be used when
    you using RollerworksSearch as a standalone. When making a framework plugin,
    you want to properly create the SearchFactory and FieldsRegistry
    manually using a Dependency Injection system.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        // Here you can optionally add new types or (type) extensions
        ->getSearchFactory();

Creating a FieldSet
-------------------

Now, before you can start performing searches, the system first needs a
``FieldSet`` which will hold the configuration of your search fields.

You can create as many FieldSets as you want, but each FieldSet needs a name
that should not clash with other FieldSets. So it's best to use descriptive
names like: 'customer_invoices' and 'customers'.

.. code-block:: php
    :linenos:

    $fieldset = $searchFactory->createFieldSetBuilder()
        ->add('id', 'integer')
        ->add('name', 'text')
        ->getFieldSet();

.. _do_manual_search:

Performing a manual search (SearchConditionBuilder)
---------------------------------------------------

In most cases you would ask the system to process an input and pass
it to a list of condition optimizers before applying it on the storage
layer. But it's also possible to create a SearchCondition manually.

The ``SearchConditionBuilder`` is just for this, if you already know how
an XML document is build then this should be pretty straightforward.

Each time you call ``group()`` it will create a new ``SearchConditionBuilder``
with a new depth. When you call ``end()`` it will return to the parent builder.

Calling ``field()`` will get you a new ``ValuesBagBuilder`` which
allows adding new values, and then calling ``end()`` to get back
to the ConditionBuilder.

.. note::

    Each value-type (except pattern-match) has a normalized value
    and a view value. Unless you pass a view value, the normalized value
    is used (as string).

    When a normalized value can not be casted to a string, this will
    throw an error.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\SearchConditionBuilder;
    use Rollerworks\Component\Search\Value\Compare;
    use Rollerworks\Component\Search\Value\PatternMatch;
    use Rollerworks\Component\Search\Value\Range;
    use Rollerworks\Component\Search\Value\SingleValue;

    $searchCondition = new SearchConditionBuilder::create($fieldset)
        ->field('id')
            ->addSingleValue(new SingleValue(12))
            ->addSingleValue(new SingleValue(30))
            ->addRange(new Range(50, 60))
        ->end()
        ->getSearchCondition();

This example will give you a SearchCondition with exactly one SearchGroup
and the following condition: id is 1 or 30 or (inclusive between 50 and 60).

Or if you need a more complex condition.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\SearchConditionBuilder;
    use Rollerworks\Component\Search\Value\ValuesGroup;
    use Rollerworks\Component\Search\Value\Compare;
    use Rollerworks\Component\Search\Value\PatternMatch;
    use Rollerworks\Component\Search\Value\Range;
    use Rollerworks\Component\Search\Value\SingleValue;

    $searchCondition = new SearchConditionBuilder::create($fieldset)
        ->field('id')
            ->addSingleValue(new SingleValue(12))
            ->addSingleValue(new SingleValue(30))
            ->addRange(new Range(50, 60))
        ->end()
        ->group(ValuesGroup::GROUP_LOGICAL_OR)
            ->field('id')
                ->addSingleValue(new SingleValue(12))
                ->addSingleValue(new SingleValue(30))
                ->addRange(new Range(50, 60))
            ->end()
            ->field('name')
                ->addSingleValue(new PatternMatch('rory', PatternMatch::PATTERN_STARTS_WITH))
                ->addSingleValue(new PatternMatch('amy', PatternMatch::PATTERN_STARTS_WITH))
                ->addSingleValue(new PatternMatch('williams', PatternMatch::PATTERN_ENDS_WITH))
            ->end()
        ->end()
        ->getSearchCondition();

.. tip::

    When you call ``field()`` with an existing field, the original field is returned.

    Set the second parameter to true to force a new one,
    note this will remove the old field!

Processing input
----------------

The most common case is processing the input to a SearchCondition,
the system can process a wide range of supported formats.

This example uses the :doc:`input/filter_query` with the FieldSet configuration
shown above.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
    use Rollerworks\Component\Search\Exception\InputProcessorException;
    use Rollerworks\Component\Search\ConditionOptimizer\ChainOptimizer;
    use Rollerworks\Component\Search\ConditionOptimizer\DuplicateRemover;
    use Rollerworks\Component\Search\ConditionOptimizer\ValuesToRange;
    use Rollerworks\Component\Search\ConditionOptimizer\RangeOptimizer;
    use Rollerworks\Component\Search\Input\FilterQueryInput;
    use Rollerworks\Component\Search\Input\FilterQuery\QueryException;
    use Rollerworks\Component\Search\Input\ProcessorConfig;
    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->getSearchFactory();

    // Each input processor is reusable.
    // So its possible to use the FilterQueryInput instance multiple times.
    $inputProcessor = new FilterQueryInput();

    // The provided query can come from anything, like $_GET or $_POST
    $query = ... ;

    // The ProcessorConfig allows configuring value limits
    // group nesting and maximum group count.
    $config = new ProcessorConfig($fieldSet);

    // The input processor will transform all values to the normalized value
    // and validates range bounds are valid.

    try {
        $searchCondition = $inputProcessor->process($config, $query);
    } catch (InvalidSearchConditionException $e) {
        // The SearchCondition contains errors.
        // This is good moment to tell the user the condition
        // has errors which should be resolved.

        // The errors are stored on the SearchCondition.
        // See the section about handling processing errors
        // for more information on handling these.
    } catch (QueryException $e) {
        // This exception is specific for the FilterQueryInput
        // and is thrown when there is a syntax error in the input.
        // The message will point exactly what is wrong with the user input
        echo $e->getMessage();
    } catch (InputProcessorException $e) {
        // Generic processing error
        echo $e->getMessage();
    }

    // Note: processing errors is much more advanced
    // than you would expect. See the next section for more information.

    // Because the search condition may have duplicate or redundant
    // values we run them trough a list of optimizers.

    $optimizer = new ChainOptimizer();
    $optimizer->addOptimizer(new DuplicateRemover());
    $optimizer->addOptimizer(new ValuesToRange());
    $optimizer->addOptimizer(new RangeOptimizer());
    $optimizer->process($searchCondition);

    // Lock the condition to prevent future changes
    // This is not really required but its a good practice to this
    $searchCondition->getValuesGroup()->setDataLocked();

    // Now the $searchCondition is ready for applying on any supported storage engine

Handling processing errors
--------------------------

When processing input its possible the input is invalid e.g. a syntax/structure
error, passing an unsupported value-type to a field or missing a required field.

To not leave these situations unnoticed each processor will throw an exception
in case of an error. The exception itself provides more information on what is
wrong.

Please keep note of the following:

* The group and nesting level start at index 0 which is the root of the condition.

.. tip::

    All exceptions have a pre-formatted message for direct usage.

    So displaying an error message is as simple as ``echo $e->getMessage();``.

GroupsNestingException
~~~~~~~~~~~~~~~~~~~~~~

The ``Rollerworks\Component\Search\Exception\GroupsNestingException``
is thrown when the maximum nesting level is exceeded.

This exception provides the following properties:

* maxNesting: Maximum nesting level
* groupIdx: index of the nested-group exceeding the maximum nesting level
* nestingLevel: the nesting level at which the group is declared

ValuesOverflowException
~~~~~~~~~~~~~~~~~~~~~~~

The ``Rollerworks\Component\Search\Exception\ValuesOverflowException``
is thrown when the maximum number of values is exceeded.

This exception provides the following properties:

* fieldName: Name of the field which has to many values
* max: Maximum number of values within a field
* count: Number of values in the field
* groupIdx: index of the group at which the field was declared
* nestingLevel: the nesting level at which the field was declared

.. note::

    Not all processors will give the exact number of values.

    FilterQuery will stop further processing when the maximum amount
    of values is exceeded. But XML, JSON and Array will return the exact
    number of values.

GroupsOverflowException
~~~~~~~~~~~~~~~~~~~~~~~

The ``Rollerworks\Component\Search\Exception\GroupsOverflowException``
is thrown when the maximum number of groups at a nesting level is exceeded.

This exception provides the following properties:

* max: Maximum number of subgroups within a (sub)group
* count: Number of groups in the (sub)group
* groupIdx: index of the group exceeding the maximum count
* nestingLevel: the nesting level at which the group was declared

.. note::

    Not all processors will give the exact number of groups.

    FilterQuery will stop further processing when the maximum amount
    of groups is exceeded. But XML, JSON and Array will return the exact
    number of values.

UnsupportedValueTypeException
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``Rollerworks\Component\Search\Exception\UnsupportedValueTypeException``
is thrown when you pass a value-type into a field which doesn't support
that value-type.

This exception provides the following properties:

* fieldName: Name of the field at which the value was declared
* valueType: Type of the value which was not accepted, e.g. range, comparison or pattern-match

InvalidSearchConditionException
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``Rollerworks\Component\Search\Exception\InvalidSearchConditionException``
is thrown when the SearchCondition has errors.

Most of these errors are eg. failed transformation or invalid range bounds.

This exception provides access to the invalid SearchCondition using ``getCondition()``.
The actual search value-errors are stored within the ValuesBag of each field.

The following example shows you can render these errors into a display for the user.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Value\ValuesGroup;
    use Rollerworks\Component\Search\Value\ValuesBag;

    // ..

    function displaySearchErrors(ValuesGroup $group, $nestingLevel = 0)
    {
        // By default hasErrors() only checks the fields in its own group.
        // But we want to check all nested groups too! so pass true to overwrite
        // this behaviour.
        if (!$group->hasErrors(true)) {
            return; // no errors so nothing do be done
        }

        $fields = $group->getFields();

        foreach ($fields as $fieldName => $values) {
            // $errors holds an array of ValuesError objects.
            //
            // A ValuesError object actually holds some very interesting information
            // including the "cause" which tells why the error occurred.
            // And a translatable message-template and parameters
            //
            // See ``Rollerworks\Component\Search\ValuesError`` for more information.

            $errors = $values->getErrors();

            if ($values->hasErrors()) {
                echo str_repeat(' ', $nestingLevel * 2).$fieldName.' has the following errors: ';

                foreach ($errors as $valueError) {
                    echo str_repeat(' ', $nestingLevel * 2).' - '.$valueError->getMessage();
                }
            }

            foreach ($group->getGroups() as $subGroup) {
                displaySearchErrors($group, ++$nestingLevel);
            }
        }
    }

    try {
        $searchCondition = $inputProcessor->process($config, $query);
    } catch (InvalidSearchConditionException $e) {
        $group = $e->getCondition()->getValuesGroup();

        displaySearchErrors($group);
    }

    // Caching of other exceptions has been deliberately omitted

You would properly want build something that is more advanced,
this is just a simple verbose example to show how you get the errors.

InputProcessorException
~~~~~~~~~~~~~~~~~~~~~~~

The ``Rollerworks\Component\Search\Exception\InputProcessorException`` is thrown
when a general error is hit. This is mostly used for malformed value structures.

The Exception message tells more about what is wrong, this exception
does not expose any special properties.

QueryException
~~~~~~~~~~~~~~

The ``Rollerworks\Component\Search\Input\FilterQuery\QueryException``
is only used by the FilterQuery input processor.

This exception is thrown when the provided input has a syntax error.

Example: ``[Syntax Error] line 0, col 46: Error: Expected '"(" or FieldIdentification', got ')'``

The error tells that at column 46 a group opening or
field-name was expected but something else was found instead.

This exception provides the following properties:

* line: Line-number at which the error occurred
* col: Column position at which the error occurred (starting from 0)
* expected: An array of tokens that were expected
* got: A Token-id, value or character that was found instead

For clarity the following token-ids are used:

* String: a unquoted string like ``foo`` or ``12``
* QuotedString: a quoted string like ``"foo"``, ``"12"`` or ``"12.00"``
* Range: A range with lower and upper-bounds like ``12-15`` or ``]12-15[``
* ExcludedValue: An excluded range with lower and upper-bounds like ``!12-15``
  or ``!]12-15[``
* Comparison: Mathematical comparison like ``>12``, ``<15`` or ``>="foo-bar"``
* PatternMatch: A text based pattern matcher like ``~*foo``, ``~!*foo``

If the "got" or "expected" property is anything else then shown above,
its a literal character. For example ```>`` and ``(`` are literal characters.

.. note::

    QuotedString values don't actually contain the leading and trailing quotes
    when processing. *The processor already normalizes these.*

    This is just to indicate a QuotedString could be used at the position.

Improving performance
---------------------

Most search operations consist of a search condition that is being applied
on a storage engine like a database or search index.

But you properly don't want to display all 500 found records
on a single page. You paginate them to display a limited subset
per page. And each page uses the same search-condition.

However processing a user-input to a ``SearchCondition``
and optimizing it can be very slow (depending on the number of fields,
values and groups). And as the condition has not changed between page requests
there is no point in repeating these steps!

Fortunately SearchConditions are serializable, meaning you can export
(not to be confused with the exporter component) the condition to a
storage friendly format for faster loading.

The following part shows an example for storing a search-condition
using the PHP session system.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Exception\ExceptionInterface;
    use Rollerworks\Component\Search\ConditionOptimizer\ChainOptimizer;
    use Rollerworks\Component\Search\ConditionOptimizer\DuplicateRemover;
    use Rollerworks\Component\Search\ConditionOptimizer\ValuesToRange;
    use Rollerworks\Component\Search\ConditionOptimizer\RangeOptimizer;
    use Rollerworks\Component\Search\Input\FilterQueryInput;
    use Rollerworks\Component\Search\Input\FilterQuery\QueryException;
    use Rollerworks\Component\Search\Input\ProcessorConfig;
    use Rollerworks\Component\Search\FieldSetRegistry;
    use Rollerworks\Component\Search\Searches;

    // This example uses a PHP session, but you can actually use anything.
    // Just remember to NEVER store a PHP serialized object on the client-side
    // as this makes it possible to inject arbitrary code!
    session_start();

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->getSearchFactory();

    $fieldSetRegistry = new FieldSetRegistry();

    $fieldset = $searchFactory->createFieldSetBuilder()
        ->add('id', 'integer')
        ->add('name', 'text')
        ->getFieldSet();

    // It's important the FieldSet is registered in the FieldSetRegistry
    // before serializing. Else you will get an exception thrown.
    $fieldSetRegistry->add($fieldset);

    // The provided query can come from anything, like $_GET or $_POST
    $query = ... ;

    if (!is_string($query)) {
        exit('Expected a string.');
    }

    // Use an mad5 hash to generate a unique caching-key
    // md5 is the fastest hashing method and provides enough uniqueness for this situation
    // normally you would use something stronger like sha1 or even sha265
    $searchHash = 'search_'.md5($query);

    $searchConditionSerializer = new SearchConditionSerializer($fieldSetRegistry);

    if (isset($_SESSION[$searchHash])) {
        $searchCondition = $searchConditionSerializer->unserialize($_SESSION[$searchHash]);
    } else {
        $inputProcessor = new FilterQueryInput();
        $config = new ProcessorConfig($fieldSet);

        try {
            $searchCondition = $inputProcessor->process($config, $query);
        } catch (ExceptionInterface $e) {
            // Note: The Rollerworks\Component\Search\Exception\ExceptionInterface
            // is implemented by all the exceptions thrown by RollerworksSearch,
            // its possible (just not likely) that these messages expose sensitive
            // information about your application. See the section about error handling
            // for a better alternative.

            echo $e->getMessage();
        }

        $optimizer = new ChainOptimizer();
        $optimizer->addOptimizer(new DuplicateRemover());
        $optimizer->addOptimizer(new ValuesToRange());
        $optimizer->addOptimizer(new RangeOptimizer());
        $optimizer->process($searchCondition);

        $searchCondition->getValuesGroup()->setDataLocked();

        // Store the condition for feature usage
        $_SESSION[$searchHash] = $searchConditionSerializer->serialize($searchCondition);
    }

.. note::

    This example does not cover removing a search-condition when its no longer
    needed. Because we use a PHP Session the cached condition is automatically
    removed when the session expires.
