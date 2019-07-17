Using the SearchProcessor
=========================

In this chapter you will finally start by integrating RollerworksSearch
into your application. You will learn how to process search operations
using the SearchProcessor and handle processing errors.

Following the `installation instructions <install>` first install the
search-processor by running:

.. code-block:: bash

    $ php composer.phar require rollerworks/search-processor

The SearchProcessor requires a `PSR-7`_ (Http Message) ServerRequest class which
is provided by many third party libraries.

Don't worry if your new to PSR-7, all implementations are expected to follow
the same interface. And therefor it doesn't matter which implementation you use!

.. note::

    A PSR-7 implementation may already be provided by the framework of your
    choice. Instead of focusing on all frameworks this chapter tries to be
    as generic as possible. See the :doc:`integration section <integration/index>`
    for more details.

    For the remainder of this chapter we will use the ``zendframework/zend-diactoros``
    library. Which other then the PSR-11 interface has no other dependencies.

    .. code-block:: bash

        $ php composer.phar require zendframework/zend-diactoros

Creating your SearchFactory
---------------------------

All components of RollerworksSearch expect a SearchFactory to create/build,
and perform search operations. So let's set it up:

.. code-block:: php

    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        // Here you can optionally add new types and (type) extensions
        ->getSearchFactory();

The FactoryBuilder helps with setting up the search system.
You only need to set a SearchFactory up once, and then it can be reused
multiple times.

.. note::

    The ``Searches`` class and ``SearchFactoryBuilder`` are only meant to be used
    when you'r using RollerworksSearch as a library. The Framework integrations
    provide a more powerful system with lazy loading and automatic configuring.

Creating a FieldSet
-------------------

Before you can start performing searches the system needs to know which
fields you to want allow searching in. This configuration is kept in a FieldSet.

.. include:: fieldset.rst.inc

Processing input
----------------

Now, you are one step away from processing. For all clarity, everything
you have done so far is shown as a whole.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Searches;
    use Rollerworks\Component\Search\Extension\Core\Type\TextType;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
    use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
    use Rollerworks\Component\Search\Loader;
    use Rollerworks\Component\Search\Processor\ProcessorConfig;
    use Rollerworks\Component\Search\Processor\Psr7SearchProcessor;
    use Zend\Diactoros\ServerRequestFactor;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        ->getSearchFactory();

    $inputProcessorLoader = Loader\InputProcessorLoader::create();
    $conditionExporterLoader = Loader\ConditionExporterLoader::create();
    $processor = new Psr7SearchProcessor($searchFactory, $inputProcessorLoader, $conditionExporterLoader);

    // Everything above this line is reusable.
    // Everything below is specific to this section of the application.

    $userFieldSet = $searchFactory->createFieldSetBuilder()
        ->add('id', IntegerType::class)
        ->add('username', TextType::class)
        ->add('firstName', TextType::class)
        ->add('lastName', TextType::class)
        ->add('regDate', DateTimeType::class)
        ->getFieldSet('users');

    // A PSR-7 ServerRequestInterface object instance
    $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();

    // The ProcessorConfig can be configured for advanced use-cases,
    // and limiting the allowed complexity of a SearchCondition.
    // See the class methods for all details.

    $processorConfig = new ProcessorConfig($userFieldSet);

    // The $searchPayload contains READ-ONLY information of the processing result
    $searchPayload = $processor->processRequest($request, $processorConfig);

    // When a POST is provided the processor will validate the input
    // and export it. Note that an empty result is also valid.
    //
    // The searchCode depends on the implementation of the SearchProcessor,
    // and in this case contains a JSON exported SearchCondition encoded for URI usage.
    if ($searchPayload->isChanged() && $searchPayload->isValid()) {
        // Redirect to this page with the search-code provided.
        header('Location: /search?search='.$searchPayload->searchCode);
        exit();
    }

    // Normally you would use a template system to take care of the presentation
    echo <<<HTML
    <form action="/search" method="post">

    <label for="search-condition">Condition: </label>
    <textarea id="search-condition" name="search" cols="10" rows="20">{htmlspecialchars($searchPayload->exportedCondition)}</textarea>

    <label for="search-format">Format ({$searchPayload->exportedFormat}): </label>
    <select id="search-format" name="format">
        <option name="json">JSON</option>
        <option name="xml">XML</option>
        <option name="string_query">StringQuery</option>
    </select>

    <div>
        <button type="submit">Search</button> <button type="Reset">Reset</button>
    </div>
    </form>

    HTML;

    // Always do this check because the uri-provided searchCode could be malformed resulting in
    // an invalid SearchCondition.
    if (!$payload->isValid()) {
        echo '<p>Sorry but your condition contains the following errors: <p>'.PHP_EOL;
        echo '<ul>'.PHP_EOL;

        // Each error message can be transformed to a localized version.
        // The message contains a messageTemplate and arguments for translation.
        foreach ($payload->messages as $error) {
           echo '<li>'.$error->path.': '.htmlspecialchars((string) $error).'</li>'.PHP_EOL;
        }

        echo '</ul>'.PHP_EOL;
    }

    // Notice: This is null when there are errors, when the condition is valid but has
    // no fields/values this is an empty SearchCondition object.
    $condition = $payload->searchCondition;

That's it, all input processing, optimizing and error handling is taken care of,
but there is more. The processor creates a ``searchCode`` which solves the form POST
redirect handling for you.

And this example can be used for both a Form, or a REST API end-point with one minor
change:

.. code-block:: php

    $processorConfig = new ProcessorConfig($userFieldSet, 'json');

This changes the (default) input/export format from ``string_query``
to ``json``. Or ``xml`` if you prefer this.

.. note::

    The input/export format can be changed using the ``format``
    ``ServerRequestInterface`` body-parameter. The loader ensures
    only valid formats are accepted.

Working with multiple processors
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When you need to support multiple processors per page (either multiple datagrids).
You need to set the request-prefix with ``setRequestPrefix('user')``::

    $processorConfig = new ProcessorConfig($userFieldSet);
    $processorConfig->setRequestPrefix('user');

    ...

    if ($searchPayload->isChanged() && $searchPayload->isValid()) {
        // Redirect to this page with the search-code provided.
        header('Location: /search?search['.$processorConfig->getRequestPrefix().']='.$searchPayload->searchCode);
        exit();
    }

Improving performance
---------------------

The example shown above is really powerful and works really wel,
but when you are dealing with a high traffic application and/or complex
conditions performance *will* become a problem.

Instead of reprocessing a valid condition for every request, you can cache
the SearchPayload and use it for another request.

Reusing the processor example let's replace the processor with a CachedProcessor.
The CachedProcessor uses `PSR-16`_ (SimpleCache) and works similar to the ``Psr7SearchProcessor``,
except that it uses a cached SearchPayload when possible.

.. warning::

    It's strongly advised to use a memory-based cache system like Redis
    or Memcache. The cache should have a short time to life (TTL) like 5 minutes.

.. code-block:: php
    :linenos:

    ...

    // \Psr\SimpleCache\CacheInterface instance
    $cache = ...;

    $processor = new Psr7SearchProcessor($searchFactory, $inputProcessorLoader, $conditionExporterLoader);
    $processor = new CachedSearchProcessor($cache, $processor, $searchFactory);

Done, caching is now enabled!

But wait, did you know you can also change the TTL per processor?
*This will only affect new items, not items already in the cache.*

.. code-block:: php

    $processorConfig = new ProcessorConfig($userFieldSet, 'json');
    $processorConfig->setCacheTTL(60*5); // Time in seconds (5 minutes)

Handling errors
---------------

The examples above show processor errors in the English language and in some
cases the information can be a little verbose (eg. unsupported value types).

Fortunately each error is more then a simple string, in fact it's a
:class:`Rollerworks\\Component\\Search\\ConditionErrorMessage` object
with a ton of useful information:

.. code-block:: php

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $message;

    /**
     * The template for the error message.
     *
     * @var string
     */
    public $messageTemplate;

    /**
     * The parameters that should be substituted in the message template.
     *
     * @var array
     */
    public $messageParameters;

    /**
     * The value for error message pluralization.
     *
     * @var int|null
     */
    public $messagePluralization;

    /**
     * @var mixed
     */
    public $cause;

    /**
     * @var string[]
     */
    public $translatedParameters;

The ``$messageTemplate`` and ``$messageParameters`` are the most interesting
when you want to display the error message in a localized format. Plus RollerworksSearch,
comes pre-bundled the translations in various locales.

.. tip::

    Is your language not supported yet or found a typo? Open a pull request for
    https://github.com/rollerworks/search/tree/master/lib/Core/Resources/translations

    **Note:** All translations must be provided in the XLIFF format.
    See the contribution guidelines for more details.

Before we can continue we first need to install a compatible Translator,
for this example we'll use the Symfony `Translator component`_.

This example shows how you can use the Translator to translate error messages,
but for more flexibility it's best to perform the rendering logic in a template.

.. code-block:: php

    use Symfony\Component\Translation\Translator;
    use Symfony\Component\Translation\MessageSelector;
    use Symfony\Component\Translation\Loader\XliffFileLoader;
    use Rollerworks\Component\Search\ConditionErrorMessage;

    // Location of the translations.
    $resourcesDirectory = dirname((new \ReflectionClass(FieldSet::class))->getFileName()).'/Resources/translations';

    $translator = new Translator('fr_FR', new MessageSelector());
    $translator->setFallbackLocales(array('en'));
    $translator->addLoader('xlf', new XliffFileLoader());
    $translator->addResource('xlf', $resourcesDirectory.'/messages.en.xlf', 'en');
    $translator->addResource('xlf', $resourcesDirectory.'/messages.nl.xlf', 'nl');

    // Change with your own locale.
    $translator->setLocale('nl');

    function translateConditionErrorMessage(ConditionErrorMessage $message)
    {
        if (null !== $message->messagePluralization) {
            return $translator->transChoice(
                $message->messageTemplate,
                $message->messagePluralization,
                $message->translatedParameters,
                'messages'
            );
        }

        return $translator->trans($message->messageTemplate, $message->translatedParameters, 'messages');
    }

    ...

    if (!$payload->isValid()) {
        echo '<p>Sorry but your condition contains the following errors: <p>'.PHP_EOL;
        echo '<ul>'.PHP_EOL;

        foreach ($payload->messages as $error) {
           echo '<li>'.$error->path.': '.htmlspecialchars(translateConditionErrorMessage($error)).'</li>'.PHP_EOL;
        }

        echo '</ul>'.PHP_EOL;
    }

.. tip::

    Framework integrations already provide a way to translate error messages.

But wait, what is ``$cause`` about? This value holds some useful information about
what caused this error. It can be an Exception object, ``ConstraintViolation`` or anything.
**It's only meant to be used for debugging, and may contain sensitive information!**

Further reading
---------------

* :doc:`Using Elasticsearch <integration/elasticsearch/index>`
* :doc:`Doctrine DBAL/ORM integration <integration/doctrine/index>`
* :doc:`Visual condition builder <visual_condition_builder>` (coming soon)

.. _`PSR-7`: http://www.php-fig.org/psr/psr-7/
.. _`Translator component`: http://symfony.com/doc/current/components/translation.html
