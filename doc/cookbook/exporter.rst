.. index::
   single: Input; Custom condition exporter

How to Create a custom condition exporter
=========================================

The main reason you need an exporter is to export a search condition to
a format that can later be processed by an input processor.

Same as the input processor an exporter follows a simple principle,
it exports a search condition to a processable output.

.. note::

    It's possible that an exporter is us unable to export all search
    conditions it receives. Sometimes the condition is just too complex
    to be exported. *So don't force yourself to support all conditions.*


This example assumes you have created a custom input processor (as described
in :doc:`input_processor`).

.. code-block:: php
    :linenos:

    namespace Acme\Search\Exporter;

    use Rollerworks\Component\Search\ExporterInterface
    use Rollerworks\Component\Search\SearchCondition;

    class SingleValuesExporter implements ExporterInterface
    {
        public function exportCondition(SearchCondition $condition)
        {
            $fields = $condition->getValuesGroup()->getFields();
            $values = array();

            foreach ($fields as $valuesBag) {
                $values = array_merge(array_map(array($this, 'valueExtractor'), $valuesBag->getSingleValues()));
            }

            return implode(', ', array_unique($values));
        }

        /**
         * @internal
         */
        public function valueExtractor(SingleValue $value)
        {
            return $this->exportValue($value->getViewValue());
        }

        /**
         * @internal
         */
        public function exportValue($value)
        {
            if ('' === $value) {
                throw new \InvalidArgumentException(
                    'Unable to export empty view-value. Please make sure there is a view-value set.'
                );
            }

            if (!preg_match('/^([\p{L}\p{N}]+)$/siu', $value)) {
                return '"'.str_replace('"', '""', $value).'"';
            }

            return $value;
        }
    }

That's it, a very simple straightforward condition exporter.

Need more inspiration? Take a look at one of the already provided `exporters`_.

.. tip::

    For this example we are using the :class:`Rollerworks\\Component\\Search\\ExporterInterface`
    but it's also possible to leverage the :class:`Rollerworks\\Component\\Search\\Exporter\\AbstractExporter`
    which provides some helper methods for PatternMatchType exporting.

    Note that the ``AbstractExporter`` requires you to implement the ``exportGroup``
    method rather than ``exportCondition``.

.. _`exporters`: https://github.com/rollerworks/RollerworksSearch/tree/master/src/Exporter
