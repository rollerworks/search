Performing searches
===================

Using the FactoryBuilder
------------------------

The FactoryBuilder helps with setting up the search system.
You only need to set it up a SearchFactory, and then it can be reuse multiple times.

.. note::

    The ``Searches`` class and SearchFactoryBuilder are only meant to be used when
    you using RollerworksSearch as a standalone. When making a framework plugin,
    you'd properly want create the SearchFactory and FieldsRegistry
    manually using a Dependency Injection system.

.. code-block:: php

    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        // Here can optionally add new types or (type) extensions
        ->getSearchFactory();

Creating a FieldSet
-------------------

Now, before you can start performing searches, the system first needs a ``FieldSet``
which will hold the configuration of your search fields.

You can create as many FieldSets as you want, but each FieldSet needs a name
that should not clash with other fieldsets. So use descriptive names like:
'customer_invoices' and 'customers'.

.. code-block:: php
    :linenos:

    $fieldset = $searchFactory->createFieldSetBuilder()
        ->add('id', 'integer')
        ->add('name', 'text')
        ->getFieldSet();

.. tip::

    You can also use the FieldSetBuilder to import the fields from models
    using the :doc:`metadata` component.

Performing a manual search (SearchConditionBuilder)
---------------------------------------------------

In most cases you'd ask the system to process an input and pass
it to a list of condition optimizers before applying it on the storage layer.
But its not uncommon to create a SearchCondition manually.

The ``SearchConditionBuilder`` is just for this, if you already know how
an XML document is build then this should be pretty straightforward.

Each time you call ``group()`` it will create a new ``SearchConditionBuilder``
with a new depth. When you call ``end()`` it will return to the parent builder.

Calling ``field()`` will give you a new ``ValuesBagBuilder`` which
allows adding new values, and then calling ``end()`` to get back
to the ConditionBuilder.

.. note::

    Each value-type (except pattern-match) has a normalized value
    and a view value. Unless you pass a view value, the normalized value
    is used (as string).

    When a normalized value can not be casted to a string, this will
    give an PHP error.

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
    use Rollerworks\Component\Search\ValuesGroup;
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

.. note::

    When you call ``field()`` with an existing field, the original field is returned.

    Set the second parameter to true to force a new one, this will remove the old field!

Processing input
----------------

The most common case is processing the input to a SearchCondition,
the system can process a wide range of supported formats.

This example uses the :doc:`input/filter_query` with the FieldSet shown above.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Input\FilterQueryInput;
    use Rollerworks\Component\Search\Input\ProcessorConfig;
    use Rollerworks\Component\Search\ConditionOptimizer\ChainOptimizer;
    use Rollerworks\Component\Search\ConditionOptimizer\DuplicateRemover;
    use Rollerworks\Component\Search\ConditionOptimizer\ValuesToRange;
    use Rollerworks\Component\Search\ConditionOptimizer\RangeOptimizer;
    use Rollerworks\Component\Search\Searches;

    $validator = Validation::createValidator();
    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->getSearchFactory();

    /* ... */

    // Each input processor is reusable.
    // So its possible to use use FilterQueryInput instance multiple times.
    $inputProcessor = new FilterQueryInput();

    // The query can come from anything, like $_GET or $_POST
    $query = ... ;

    // The ProcessorConfig allows configuring value limits
    // group nesting and maximum group count.
    $config = new ProcessorConfig($fieldSet);

    $searchCondition = $inputProcessor->process($config, $query);

    // Because the search condition may have duplicate or redundant
    // values we run them trough a list of optimizers.

    $formatter = new ChainOptimizer();
    $formatter->addFormatter(new TransformFormatter());
    $formatter->addFormatter(new ValidationFormatter($validator));
    $formatter->addFormatter(new DuplicateRemover());
    $formatter->addFormatter(new ValuesToRange());
    $formatter->addFormatter(new RangeOptimizer());
    $formatter->process($searchCondition);

    // Now the $searchCondition is already for applying on any supported storage engine

