Processing Searches Queries
===========================

In this chapter you will start by integrating RollerworksSearch
into your application, to process a search query provided a user.

You will learn how to handle search operations user errors.

Make sure you have the core package installed as described in the
`installation instructions <install>`.

Creating your SearchFactory
---------------------------

All components of RollerworksSearch expect a SearchFactory to create/build,
and perform search operations. So let's set it up:

.. code-block:: php

    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactoryBuilder()
        // Here you can optionally add new types and (type) extensions
        ->getSearchFactory();

The FactoryBuilder helps with setting up the search system quickly.

.. note::

    The ``Searches`` class and ``SearchFactoryBuilder`` are only meant to be used
    for stand-alone usage. The Framework integrations provide a more powerful
    system with lazy loading and automatic configuring.

You only need to set-up a SearchFactory once, and then it can be reused
multiple times trough the application.

Creating a FieldSet
-------------------

Before you can start performing searches the system needs to know which
fields you to want allow searching in. This configuration is kept in a :ref:`FieldSet <fieldset>`.

.. include:: fieldset.rst.inc

Processing input
----------------

Now, you are one step away from processing. For all clarity, everything
you have done so far is shown as a whole.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
    use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
    use Rollerworks\Component\Search\Extension\Core\Type\TextType;
    use Rollerworks\Component\Search\Input\ProcessorConfig;
    use Rollerworks\Component\Search\Input\StringQueryInput;
    use Rollerworks\Component\Search\Searches;

    $searchFactory = Searches::createSearchFactoryBuilder()
        ->getSearchFactory();

    $userFieldSet = $searchFactory->createFieldSetBuilder()
        ->add('id', IntegerType::class)
        ->add('username', TextType::class)
        ->add('firstName', TextType::class)
        ->add('lastName', TextType::class)
        ->add('regDate', DateTimeType::class)
        ->getFieldSet('users');

    $inputProcessor = new StringQueryInput();

    // Tip: Everything above this line is reusable, input processors
    // and fieldsets are idempotent from each other.

    try {
        // The ProcessorConfig allows to limit the amount of values, groups
        // and maximum nesting level.
        $processorConfig = new ProcessorConfig($userFieldSet);

        // The `process()` method parsers the input and produces
        // a valid SearchCondition (or throws an InvalidSearchConditionException
        // when something is wrong).

        $condition = $inputProcessor->process('firstName: sebastiaan, melany;');
    } catch (InvalidSearchConditionException $e) {
        // Each error message can be easily transformed to a localized version.
        // See 'Handling errors' below for more details.
        foreach ($e->getErrors() as $error) {
            echo $error.PHP_EOL;
        }
    }

That's it, this example shows the minimum amount of code needed process
a search query. But using a static string is not what we are looking for,
so lets improve upon this example, with a form.

.. note::

    This example is more advanced, and you properly want to abstract some
    of the details in your application. Framework integration already handle
    this nicely.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
    use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
    use Rollerworks\Component\Search\Extension\Core\Type\TextType;
    use Rollerworks\Component\Search\Input\ProcessorConfig;
    use Rollerworks\Component\Search\Input\StringQueryInput;
    use Rollerworks\Component\Search\Searches;

    $searchFactory = Searches::createSearchFactoryBuilder()
        ->getSearchFactory();

    $userFieldSet = $searchFactory->createFieldSetBuilder()
        ->add('id', IntegerType::class)
        ->add('username', TextType::class)
        ->add('firstName', TextType::class)
        ->add('lastName', TextType::class)
        ->add('regDate', DateTimeType::class)
        ->getFieldSet('users');

    $inputProcessor = new StringQueryInput();

    try {
        $processorConfig = new ProcessorConfig($userFieldSet);
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

        // When a POST is provided the processor will try to parse the input,
        // and redirect back to the current page with the query passed-on,
        // if the input is valid.

        $searchQuery = $isPost ? ($_POST['query'] ?? '') : ($_GET['search'] ?? '');

        // The processor always needs to parse the query, see below to apply caching
        // for better performance.
        $condition = $inputProcessor->process($processorConfig, $searchQuery);

        if ($isPost) {
            // Redirect to this page with the search-code provided.
            // Note: The $_POST['query'] value might be spoofed,
            // be sure to apply proper format detection.
            // Or use a proper HTTP request abstraction.

            header('Location: /search?search='.$searchQuery);
            exit();
        }
    } catch (InvalidSearchConditionException $e) {
        echo '<p>Your condition contains the following errors: <p>'.PHP_EOL;
        echo '<ul>'.PHP_EOL;

        foreach ($e->getErrors() as $error) {
           echo '<li>'.$error->path.': '.htmlspecialchars((string) $error).'</li>'.PHP_EOL;

           // Alternatively the error can displayed in a user's local format.
           // See 'Handling errors' below for more details.
           // echo '<li>'.$error->path.': '.htmlspecialchars($error->trans($translator)).'</li>'.PHP_EOL;
        }

        echo '</ul>'.PHP_EOL;
    }

    $searchQuery = htmlspecialchars($searchQuery);

    // Normally you would use a template system to take care of the presentation
    echo <<<HTML
        <form action="/search" method="post">

        <label for="search-condition">Condition: </label>
        <textarea id="search-condition" name="query" cols="10" rows="20">{$searchQuery}</textarea>

        <div>
            <button type="submit">Search</button> <button type="Reset">Reset</button>
        </div>
        </form>
    HTML;

That's it, all input processing, and error handling is taken care of, however now
the query will be parsed for every request, if you only allow small conditions
this is performance hit is barely noticeable, but if you need to handle bigger
queries it's advised to cache the produced search condition for additional requests.

Improving performance
---------------------

To Cache the parsed result wrap the input processor with a
:class:`Rollerworks\\Component\\Search\\Input\\CachingInputProcessor`
as shown below. Note that you need a FieldSetRegistry set-up for the
serializer to work properly.

The CachingInputProcessor uses `PSR-16`_ for caching.

.. code-block:: php

    use Rollerworks\\Component\\Search\\Input\\CachingInputProcessor;

    ...

    // A \Psr\SimpleCache\CacheInterface instance
    $cache = ...;

    $inputProcessor = new StringQueryInput();
    $inputProcessor = new CachingInputProcessor($cache, $searchFactory->getSerializer(), $inputProcessor, $ttl = 60);

.. warning::

    It's strongly advised to use a memory-based cache system like Redis
    or Memcache. The cache should have a short time to life (TTL) like 5 minutes.

Done, caching is now enabled!

But wait, did you know you can also change the TTL per processor?
*This will only affect new items, not items already in the cache.*

.. code-block:: php

    $processorConfig = new ProcessorConfig($userFieldSet);
    $processorConfig->setCacheTTL(60*5); // Time in seconds (5 minutes)

Handling errors
---------------

The examples above show processor errors in the English language and in some
cases the information can be a little verbose (eg. unsupported value types).

Fortunately each error is more then a simple string, in fact it's a
:class:`Rollerworks\\Component\\Search\\ConditionErrorMessage` object
with a bunch of useful information:

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
     * @var mixed
     */
    public $cause;

    /**
     * A list of parameter names who's values must be translated separately.
     *
     * Either token: ["unexpected"]
     *
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
    $translator->setLocale('en');

    ...

    } catch (InvalidSearchConditionException $e) {
        echo '<p>Your condition contains the following errors: <p>'.PHP_EOL;
        echo '<ul>'.PHP_EOL;

        foreach ($e->getErrors() as $error) {
           echo '<li>'.$error->path.': '.htmlspecialchars($error->trans($translator)).'</li>'.PHP_EOL;
        }

        echo '</ul>'.PHP_EOL;
    }

Debugging information
---------------------

But wait, what is ``$cause`` about? This value holds some useful information about
what caused this error. It can be an Exception object, ``ConstraintViolation`` or anything.
**It's only meant to be used for debugging, and may contain sensitive information!**

Further reading
---------------

* :doc:`Using Elasticsearch <integration/elasticsearch/index>`
* :doc:`Doctrine DBAL/ORM integration <integration/doctrine/index>`
* :doc:`Visual condition builder <visual_condition_builder>` (coming soon)

.. _`PSR-16`: https://www.php-fig.org/psr/psr-16/
.. _`Translator component`: https://symfony.com/doc/current/components/translation.html
