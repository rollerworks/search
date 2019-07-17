Composing SearchConditions
==========================

In this chapter you will learn how to compose your SearchConditions, without
using an input processor. Composing Conditions manually is perfect for testing
your integration, or using RollerworksSearch as a SDK for (REST) APIs.

Creating a FieldSet
-------------------

Before you can compose a SearchCondition you first need a FieldSet configuration,
even when using the Condition ony for exporting you need a FieldSet as the exporter
needs to know which data transformer(s) to use.

.. include:: fieldset.rst.inc

And now you can use the FieldSet for the SearchConditionBuilder, note that
values must be provided in the "model" format of the field-type.

For the DateTimeType this is a ``DateTime`` object, for the id this is an
``integer``, and username is simply a ``string``.

For this example we want to search for users:

Registered between the years 2015 and 2016 (but not within the month May)
*or* have a username which contains the word "admin".

.. code-block:: php

    use Rollerworks\Component\Search\ConditionOptimizer\DuplicateRemover;
    use Rollerworks\Component\Search\SearchConditionBuilder;
    use Rollerworks\Component\Search\Test\SearchConditionOptimizerTestCase;
    use Rollerworks\Component\Search\Value\Compare;
    use Rollerworks\Component\Search\Value\ExcludedRange;
    use Rollerworks\Component\Search\Value\PatternMatch;
    use Rollerworks\Component\Search\Value\Range;
    use Rollerworks\Component\Search\Value\ValuesBag;
    use Rollerworks\Component\Search\Value\ValuesGroup;

    $condition = SearchConditionBuilder::create($fieldSet, ValuesGroup::GROUP_LOGICAL_OR)
        ->field('regDate')
            ->add(new Range(new DateTime('2015-01-01 00:00:00 UTC'), new DateTime('2017-01-01 00:00:00 UTC'), true, false))
            ->add(new ExcludedRange(new DateTime('2015-03-01 00:00:00 UTC'), new DateTime('2015-04-01 00:00:00 UTC'), true, false))
        end()
        ->field('name')
            ->add(new PatternMatch('admin', PatternMatch::PATTERN_CONTAINS))
        ->end()
        ->getSearchCondition();

Lets break this down, ``field('name')`` tells the builder we want to work on
a SearchField named ``name``. Once we are done we call ``end`` and return back to
the ValuesGroup were we can add/remove other fields, and add new/remove (sub)groups.

.. code-block:: php

    new Range(new DateTime('2015-01-01 00:00:00 UTC'), new DateTime('2017-01-01 00:00:00 UTC'), true, false)

This code segment creates a new ``Range`` value value-holder with an inclusive
lower bound and exclusive upper bound (everything lower then the value itself).

.. note::

    Calling ``field('name')`` multiple times will return the same instance
    of the ``ValuesBagBuilder``. Use ``field('name', true)`` to overwrite
    the existing instance.

The ``SearchConditionBuilder`` is really powerful and developer friendly, but
instead of explaining everything, the following full example should give you an
idea about whats possible::

    use Rollerworks\Component\Search\ConditionOptimizer\DuplicateRemover;
    use Rollerworks\Component\Search\SearchConditionBuilder;
    use Rollerworks\Component\Search\Test\SearchConditionOptimizerTestCase;
    use Rollerworks\Component\Search\Value\Compare;
    use Rollerworks\Component\Search\Value\ExcludedRange;
    use Rollerworks\Component\Search\Value\PatternMatch;
    use Rollerworks\Component\Search\Value\Range;
    use Rollerworks\Component\Search\Value\ValuesBag;
    use Rollerworks\Component\Search\Value\ValuesGroup;

    $condition = SearchConditionBuilder::create($fieldSet, ValuesGroup::GROUP_LOGICAL_OR)
        ->field('regDate')
            ->add(new Range(new DateTime('2015-01-01 00:00:00 UTC'), new DateTime('2017-01-01 00:00:00 UTC'), true, false))
            ->add(new ExcludedRange(new DateTime('2015-03-01 00:00:00 UTC'), new DateTime('2015-04-01 00:00:00 UTC'), true, false))
        end()
        ->field('name')
            ->add(new PatternMatch('admin', PatternMatch::PATTERN_CONTAINS))
        ->end()
        ->group()
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
                ->add(new Compare(10, '>'))
            ->end()
            ->group(ValuesGroup::GROUP_LOGICAL_OR)
                ->field('first-name')
                    ->addSimpleValue('homer')
                    ->add(new PatternMatch('spider', PatternMatch::PATTERN_CONTAINS))
                    ->add(new PatternMatch('pig', PatternMatch::PATTERN_CONTAINS))
                    ->add(new PatternMatch('doctor', PatternMatch::PATTERN_STARTS_WITH, true)) // case-insensitive match
                    ->add(new PatternMatch('who', PatternMatch::PATTERN_CONTAINS, true)) // case-insensitive match
                ->end()
            ->end()
        ->end()
        ->getSearchCondition();

You can group as deep as needed, and always return to the previous level using ``end()``.
The builder support all of your complex and simple conditions.

See also :doc:`searching_in_practice`

Further reading
---------------

* :doc:`Visual condition builder <visual_condition_builder>` (coming soon)
* :doc:`reference/exporters`

