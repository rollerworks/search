.. index::
   single: Input; Custom input processor

How to Create a Custom Input processor
======================================

RollerworksSearch already provides input processors for a wide range of
formats, including XML, JSON, PHP Array's and the user-friendly FilterQuery.

But sometimes you need support something else, whether you want to make
a simple string processor, use a binary file or fixed-length string syntax
or anything you can think of, everything is possible.

Each processor follows a very simple principle, accept a user-input and
transform this to SearchCondition object. That's it. You don't have to enforce
groups field-names or even support ranges!

This example shows a simple processor which will accept any
value and will place it in a list of configured fields, no support for ranges,
comparisons or pattern-matchers.

.. tip::

    In the future we hope to add support for an input processor that will
    work similar to what we are building here. Find out more at:
    `SmartQuery - GitHub issue tracker`_

For example we provide the following input: ``foobar, 2012-12-05, "bar"``.
The values will be placed as single-values on the configured fields.

Say we have two fields that will be used for condition: field1 and field2.
The created SearchCondition ``ValuesGroup`` will look like::

    $valuesGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);

    $valuesBag = ValuesBag();
    $valuesBag->addSingleValue(new SingleValue('foobar'));
    $valuesBag->addSingleValue(new SingleValue('2012-12-05'));
    $valuesBag->addSingleValue(new SingleValue('bar'));

    $valuesGroup->addField('field1', $valuesBag);
    $valuesGroup->addField('field2', $valuesBag);

First lets create a custom ``ProcessorConfig`` class for configuring the
mapping fields that need to be used.

.. code-block:: php
    :linenos:

    namespace Acme\Search\Input;

    use Rollerworks\Component\Search\Input\ProcessorConfig;

    class SingleValuesInputConfig extends InputProcessorInterface
    {
        private $processingFields;

        public function setProcessingFields(array $processingFields)
        {
            $this->processingFields = $processingFields;
        }

        public function getProcessingFields()
        {
            return $this->processingFields;
        }
    }

.. code-block:: php
    :linenos:

    namespace Acme\Search\Input;

    use Rollerworks\Component\Search\InputProcessorInterface;
    use Rollerworks\Component\Search\Value\SingleValue;
    use Rollerworks\Component\Search\ValuesBag;
    use Rollerworks\Component\Search\ValuesGroup;

    class SingleValuesInput implements InputProcessorInterface
    {
        public function process(ProcessorConfig $config, $input)
        {
            if (!$config instanceof SingleValuesInputConfig) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected 1 argument of type "%s", "%s" given',
                        'Acme\\Search\\Input\\SingleValuesInputConfig'
                         get_class($config)
                    )
                );
            }

            if (!is_string($input)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected 2 argument of type "string", "%s" given',
                         gettype($input)
                    )
                );
            }

            // Instead of using a complex regex or something we can simply use str_getcsv()
            // and run array_map() over the returned array to remove leading and trailing whitespace
            $values = array_map('trim', str_getcsv($input, ',', '"', '"'));

            $valuesGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
            $valuesBag = ValuesBag();

            foreach ($values as $value) {
                $valuesBag->addSingleValue(new SingleValue($value));
            }

            $processingFields = $config->getProcessingFields();

            // Each field gets all the values exactly once.
            foreach ($processingFields as $fieldName) {
                if (!$config->getFieldSet()->has($fieldName)) {
                    throw new \RuntimeException(
                        sprintf('Unable to processing unregistered field "%s"', $fieldName)
                    );
                }

                $valuesGroup->addField($fieldName, $valuesBag);
            }

            return $condition = new SearchCondition(
                $config->getFieldSet(),
                $valuesGroup
            );
        }
    }

That's it, a very simple straightforward input processor, you can extent
this functionality by also detecting ranges and other operands.

Need more inspiration? Take a look at one of the already provided `input processors`_.

.. tip::

    For this example we are using the :class:`Rollerworks\\Component\\Search\\InputProcessorInterface`
    but it's also possible to leverage the :class:`Rollerworks\\Component\\Search\\Input\\AbstractInput`
    which provides some helper methods for field alias resolving and type
    support validating.

Now that we have an input processor, it may be a good idea to create an
exporter that can deal with search conditions within the input format.

See more at: :doc:`exporter`

.. _`SmartQuery - GitHub issue tracker`: https://github.com/rollerworks/RollerworksSearch/issues/23
.. _`input processors`: https://github.com/rollerworks/RollerworksSearch/tree/master/src/Input
