.. index::
   single: input

Input Processors
================

Input Processors transform the input to a ``SearchCondition``.
Each processor supports exactly one specific format, which is described
in the reference section of this manual.

This chapter explains how you use the processors and how you configure
them to limit the conditions allowed complexity.

Loading processors
------------------

Instead of constructing the processors yourself it's best to lazily load
them using the ``InputProcessorLoader``, which allows to safely load
processors by format::

    use Rollerworks\Component\Search\Loader\InputProcessorLoader;

    $inputProcessorLoader = InputProcessorLoader::create();
    $inputProcessor = $inputProcessorLoader->get('xml');

    /* ... */

    // The create() method will register all build-in processors.
    // But you can also use a PSR-11 compatible Container.

    // \Psr\Container\ContainerInterface
    $container = ...;

    $formatToServiceId = [
        'array' => 'rollerworks_search.input.array',
        'json' => 'rollerworks_search.input.json',
        'xml' => 'rollerworks_search.input.xml',
        'string_query' => 'rollerworks_search.input.string_query',
    ];

    $inputProcessorLoader = new InputProcessorLoader($container, $formatToServiceId);

Values limit
------------

To prevent overloading your system the allowed complexity of the provided input
can be limited a by values (per group), maximum amount of groups and/or a group
maximum nesting level.

You can configure a processor using :class:`Rollerworks\\Component\\Search\\Input\\ProcessorConfig`.

By default the input is limited to a 100 values per field (per group), 10 groups (per group)
in total, with a maximum nesting level of 5 levels deep.

You can change these limits by calling ``setLimitValues``, ``setMaxGroups``
and ``setMaxNestingLevel`` respectively.

.. code-block:: php

        $inputProcessor = ...;

        $config = new ProcessorConfig($this->getFieldSet());
        $config->setMaxNestingLevel(2);
        $config->setLimitValues(50);
        $config->setMaxGroups(5);

        $input = ...;
        $condition$inputProcessor->process($config, $input);

.. caution::

    Unless you must support a large number of values its best to not
    set these values too high.

    Allowing users to pass a large number of values can result
    in a massive performance hit or even crashing of the application.

    Setting the nesting level to high may require you to increase
    the ``xdebug.max_nesting_level`` value.

Input format reference
----------------------

.. toctree::
    :maxdepth: 1

    reference/input/string_query
    reference/input/array
    reference/input/json
    reference/input/xml
