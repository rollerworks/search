Symfony Framework Integration
=============================

Symfony is a **PHP framework** for web applications and a set of
reusable PHP components. Integration for RollerworksSearch in a
Symfony application, is provided using the RollerworksSearchBundle.

You need the Symfony FrameworkBundle and optionally Symfony Flex,
while not required the following examples assume your using at least
Symfony 3.4 and have Symfony Flex installed.

All RollerworksSearch services can be autowired. There service-id's are
shown for reference.

.. note::

    If your new to Symfony, it's a really good idea to learn
    about the basics of Symfony first.

    These examples assume you know how to work with services,
    create Controllers and know how to change your app's
    configuration.

Installation
------------

Following the :doc:`installation instructions </installing>` install the
symfony Bundle by running:

.. code-block:: bash

    $ php composer.phar require rollerworks/search-bundle

.. tip::

    Install the RollerworksSearch :doc:`symfony_validator`
    extension to utilize validation constraints in your input.

    Make sure the ``framework.validator`` is enabled in your application's
    configuration, unless explicitly disabled this should be automatically enabled.

Basic usage
-----------

The purpose of the bundle is to integrate RollerworksSearch into your
Symfony application. Because of this most of RollerworksSearch's documentation
can be used as-is.

The main difference is that the integration is already done for you,
and you use the service Container instead of initializing classes yourself.

SearchFactory
~~~~~~~~~~~~~

Get the (default) SearchFactory service using ``rollerworks_search.factory``::

    ...

    $searchFactory = $container->get('rollerworks_search.factory');

    $fieldSetBuilder = $searchFactory->createFieldSetBuilder();
    $userFieldSet = $searchFactory->createFieldSet(UserFieldSet::class);

    $serializer = $searchFactory->getSerializer();
    $serializedCondition = $serializer->serialize($searchCondition);
    $searchCondition = $serializer->unserialize($serializedCondition);

Search Query
~~~~~~~~~~~~

.. code-block:: php
    :linenos:

    namespace App\Controller;

    use App\Search\FieldSet\UserFieldSet;
    use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
    use Rollerworks\Component\Search\Input\ProcessorConfig;
    use Rollerworks\Component\Search\Input\StringQueryInput;
    use Rollerworks\Component\Search\Loader\InputProcessorLoader;
    use Rollerworks\Component\Search\SearchFactory;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;

    class SearchController extends AbstractController
    {
        public function searchAction(Request $request, $search = '')
        {
            /** @var SearchFactory $searchFactory */
            $searchFactory = $this->get('search_factory');
            /** @var StringQueryInput $processor */
            $processor = $this->get('search.input_loader')->get('string_query');

            $fieldSet = $searchFactory->createFieldSet(UserFieldSet::class);
            $config = new ProcessorConfig($fieldSet);

            // Enable caching (optional).
            // https://www.php.net/manual/en/dateinterval.construct.php
            // $config->setCacheTTL(new \DateInterval('PT2M'));

            try {
                if ($request->isMethod('POST')) {
                    $search = $request->request->get('search');
                    $processor->process($config, $search);

                    return $this->redirectToRoute($request->attributes->get('_route'), ['search' => $request->request->get('search')]);
                }

                $condition = $processor->process($config, $search);
            } catch (InvalidSearchConditionException $e) {
                return $this->render(
                    'user/search.html.twig',
                    [
                        'data' => [],
                        'errors' => $e->getErrors(),
                        'search_query' => $search,
                    ]
                );
            }

            if (!$condition->isEmpty()) {
                // Apply the SearchCondition for searching.
                $data = ...;
            }

            return $this->render(
                'user/search.html.twig',
                [
                    'data' => $data,
                    'search_query' => $search,
                ]
            );
        }

        public static function getSubscribedServices(): array
        {
            return parent::getSubscribedServices() + [
                'search.factory' => SearchFactory::class,
                'search.input_loader' => InputProcessorLoader::class,
            ];
        }
    }


.. code-block:: twig
    :linenos:

    TBD.

That's it. You can now process search requests! See the reference section
below to learn more about application wide cache configuring.

Registering types and type extensions
-------------------------------------

Registering types is only needed when they have injection dependencies
(constructor or setter). Type extensions always need to be registered.

.. note::

    Tagging is only required if autoconfigure is not enabled, for the current file.

To register a type, create a service as normal and tag it as ``rollerworks_search.type``.

.. configuration-block::

    .. code-block:: xml
        :linenos:

        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <defaults autowire="true" autoconfigure="true" public="false" />

                <service id="App\Search\Type\MyType" />
            </services>
        </container>

    .. code-block:: yaml
        :linenos:

        services:
            _defaults:
                autowire: true
                autoconfigure: true
                public: false

            'App\Search\Type\MyType': ~

    .. code-block:: php
        :linenos:

        use App\Search\Type\MyType;
        use Symfony\Component\DependencyInjection\Definition;

        $definition = new Definition(MyType::class);
        $definition->setPublic(false);
        $definition->addTag('rollerworks_search.type');

        $container->setDefinition(MyType::class, $definition);

To register a type extension, create a service as normal and tag it as ``rollerworks_search.type_extension``.

.. configuration-block::

    .. code-block:: xml
        :linenos:

        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <service id="App\Search\Type\MyTypeExtension" />
            </services>
        </container>

    .. code-block:: yaml
        :linenos:

        services:
            'App\Search\Type\MyTypeExtension': ~

    .. code-block:: php
        :linenos:

        use App\Search\Type\MyTypeExtension;
        use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
        use Symfony\Component\DependencyInjection\Definition;

        $definition = new Definition(MyType::class);
        $definition->addTag('rollerworks_search.type_extension');

        $container->setDefinition(MyType::class, $definition);

Condition processors
--------------------

Second to SearchFactory and SearchProcessor the bundle provides an integration
all build-in condition processors (Doctrine DBAL) and Elasticsearch.

Doctrine integration
~~~~~~~~~~~~~~~~~~~~

Doctrine integration requires the `DoctrineBundle`_ is installed
and properly configured.

See :doc:`doctrine/index` for more information on usage details.

.. code-block:: bash

    $ composer require --no-update "doctrine/doctrine-bundle"
    $ composer require --no-update "rollerworks/search-doctrine-dbal"
    $ composer update

To install the ORM extension run:

.. code-block:: bash

    $ composer require "rollerworks/search-doctrine-orm:^2.0"

That's it, the extensions are automatically detected and enabled.
To disable/enable the extension use the following:

.. configuration-block::

    .. code-block:: yaml
        :linenos:

        rollerworks_search:
            doctrine:
                dbal:
                    enabled: true
                orm: false # same as ` { enabled: false } `

    .. code-block:: php
        :linenos:

        /** @var $container \Symfony\Component\DependencyInjection\ContainerBuilder */
        $container->loadFromExtension('rollerworks_search', [
            'doctrine' => [
                'orm' => ['enabled' => true],
                'orm' => true,
            ],
        ]);

.. note::

    The DBAL extension cannot be disabled when ORM extension is enabled.

Basic Usage
***********

Usage of the Doctrine extensions is as you expect, both the DBAL and ORM
factories are automatically registered:

.. code-block:: php
    :linenos:

    // \Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory
    $doctrineDbalFactory = $container->get('rollerworks_search.doctrine_dbal.factory');

    // \Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory
    $doctrineOrmFactory = $container->get('rollerworks_search.doctrine_orm.factory');

Enable Caching
**************

By default the Doctrine integration doesn't have caching enabled, to enable caching
of generated query conditions configure the ``rollerworks_search.doctrine.cache`` pool.

.. configuration-block::

    .. code-block:: yaml
        :linenos:

        framework:
            cache:
                rollerworks_search.doctrine.cache:
                    adapter: ...

    .. code-block:: php
        :linenos:

        /** @var $container \Symfony\Component\DependencyInjection\ContainerBuilder */
        $container->prependExtensionConfig('framework', [
            'cache' => [
                'pools' => [
                    'rollerworks_search.doctrine.cache' => [
                        'adapter' => ...,
                    ],
                ],
            ],
        ]);

.. caution::

    Don't use the ``cache.system`` (Filesystem) adapter but instead use a
    Redis/Memcache adapter for best performance.

Bundle configuration reference
------------------------------

This subsection shows the complete the bundle's configuration, for reference.
Note that extensions are disabled by default, and will be automatically enabled
when there related dependency is installed.

.. configuration-block::

    .. code-block:: yaml
        :linenos:

        rollerworks_search:
            doctrine:
                dbal:
                    enabled: false
                orm:
                    enabled: false
                    entity_managers: [default]

    .. code-block:: php
        :linenos:

        /** @var $container \Symfony\Component\DependencyInjection\ContainerBuilder */
        $container->loadFromExtension('rollerworks_search', [
            'doctrine' => [
                'dbal' => ['enabled' => false],
                'orm' => [
                    'enabled' => false,
                    'entity_managers' => ['default']
                ],
            ],
        ]);

.. _`DoctrineBundle`: http://symfony.com/doc/current/doctrine.html

