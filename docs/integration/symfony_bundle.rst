Symfony Framework Integration
=============================

Symfony is a **PHP framework** for web applications and a set of
reusable PHP components. Integration for RollerworksSearch in a
Symfony application, is provided using the RollerworksSearchBundle.

You need the Symfony FrameworkBundle and optionally Symfony Flex,
while not required the following examples assume your using at least
Symfony 3.4 and have Symfony Flex installed.

All RollerworksSearch services are can autowired.
There service-id's are shown for reference.

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
and you use the ServiceContainer instead of initializing classes yourself.

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

SearchProcessor
~~~~~~~~~~~~~~~

See also :doc:`/processing_searches` for more information and details.

.. note::

    The SearchProcessor requires additional Composer packages to function
    properly, install them using:

    .. code-block:: bash

        $ composer require --no-update "rollerworks/search-processor"
        $ composer require --no-update "symfony/psr-http-message-bridge"
        $ composer require --no-update "zendframework/zend-diactoros"
        $ composer update

Get the (default) SearchProcessor service using ``rollerworks_search.search_processor``::

    namespace Acme\Controller;

    use Rollerworks\Component\Search\Processor\ProcessorConfig;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Component\HttpFoundation\Request;
    use Acme\Search\FieldSet\UserFieldSet;

    class SearchController extends Controller
    {
        public function searchAction(Request $request)
        {
            $fieldSet = $this->get('rollerworks_search.factory')->createFieldSet(UserFieldSet::class);
            $config = new ProcessorConfig($fieldSet);

            // The $searchPayload contains READ-ONLY information of the processing result
            $searchPayload = $this->get('rollerworks_search.search_processor')->processRequest($request, $config);

            // When a POST is provided the processor will validate the input
            // and export it. Note that an empty result is also valid.
            //
            // The searchCode depends on the implementation of the SearchProcessor,
            // and in this case contains a JSON exported SearchCondition encoded for URI usage.
            if ($searchPayload->isChanged() && $searchPayload->isValid()) {
                return $this->redirectToRoute('user_search', ['search' => $searchPayload->searchCode]);
            }

            // ...

            if (null !== $searchPayload->searchCondition) {
                // Apply the SearchCondition for searching.
                // ...

                $data ...;
            }

            return $this->render(
                'user/search.html.twig',
                [
                    'data' => $data,
                    'search_payload' => $searchPayload, // contains errors (if any) and the exported condition
                ]
            );
        }
    }

That's it. You can now process search requests! See the reference section
below to learn more about application wide cache configuring.

.. note::

    The SearchProcessor accepts a Symfony HttpFoundation Request object or a
    PSR-7 ServerRequest instance. Format Adaption and caching is already done
    for you.

Registering types and type extensions
-------------------------------------

Registering types is only needed when they have injection dependencies
(constructor or setter). Type extensions always need to be registered.

To register a type, create a service as normal and tag it as ``rollerworks_search.type``.

.. configuration-block::

    .. code-block:: xml
        :linenos:

        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <service id="Acme\Search\Type\MyType" public="false">
                    <tag name="rollerworks_search.type" />
                </service>
            </services>
        </container>

    .. code-block:: yaml
        :linenos:

        services:
            'Acme\Search\Type\MyType':
                public: false
                tags:
                    - { name: rollerworks_search.type }

    .. code-block:: php
        :linenos:

        use Acme\Search\Type\MyType;
        use Symfony\Component\DependencyInjection\Definition;

        $definition = new Definition(MyType::class);
        $definition->setPublic(false);
        $definition->addTag('rollerworks_search.type');

        $container->setDefinition(MyType::class, $definition);

To register a type extension, create a service as normal and tag it as ``rollerworks_search.type_extension``
and a ``extended-type`` attribute with name of the type this extension applies to.

.. configuration-block::

    .. code-block:: xml
        :linenos:

        <?xml version="1.0" encoding="UTF-8" ?>
        <container xmlns="http://symfony.com/schema/dic/services"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

            <services>
                <service id="Acme\Search\Type\MyType">
                    <tag name="rollerworks_search.type" extended-type="Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType" />
                </service>
            </services>
        </container>

    .. code-block:: yaml
        :linenos:

        services:
            'Acme\Search\Type\MyType':
                tags:
                    - { name: rollerworks_search.type, extended-type: 'Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType' }

    .. code-block:: php
        :linenos:

        use Acme\Search\Type\MyType;
        use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
        use Symfony\Component\DependencyInjection\Definition;

        $definition = new Definition(MyType::class);
        $definition->addTag('rollerworks_search.type', ['extended-type' => SearchFieldType::class]);

        $container->setDefinition(MyType::class, $definition);

Condition processors
--------------------

Second to SearchFactory and SearchProcessor the bundle provides an integration
all build-in condition processors (Doctrine DBA/ORM) and Elasticsearch.

Doctrine integration
~~~~~~~~~~~~~~~~~~~~

Doctrine integration requires the `DoctrineBundle`_ is installed
and properly configured.

See :doc:`doctrine/index` for more information on usage details.

.. code-block:: bash

    $ composer require --no-update "doctrine/doctrine-bundle:^1.1"
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
of generated SQL/DQL conditions configure the ``rollerworks_search.doctrine.cache`` pool.

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
            processor:
                enabled: false
                disable_cache: false # Recommended to change this to '%kernel.debug%'

            doctrine:
                dbal:
                    enabled: false
                orm:
                    enabled: false
                    entity_managers: [default]

        # Configures the `rollerworks.search_processor.cache` pool, but only when the processor
        # is actually installed.
        framework:
            cache:
                rollerworks.search_processor.cache:
                    adapter: rollerworks_search.cache.adapter.array

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

        // Configures the `rollerworks.search_processor.cache` pool, but only when the processor
        // is actually installed.
        $container->prependExtensionConfig('framework', [
            'cache' => [
                'pools' => [
                    'rollerworks.search_processor.cache' => [
                        'adapter' => 'rollerworks_search.cache.adapter.array',
                    ],
                ],
            ],
        ]);

.. _`DoctrineBundle`: http://symfony.com/doc/current/doctrine.html

