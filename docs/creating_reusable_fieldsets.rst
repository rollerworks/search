Creating Reusable FieldSets
===========================

While creating FieldSet configurations on the fly is great for testing
and exporting. It's generally better to use a FieldSetConfigurator
to hold your FieldSet configuration.

Using a FieldSetConfigurator makes it possible for the FieldSet to
be serialized and makes your configuration sharable for other
processors.

And, creating a FieldSetConfigurator is no more different then using
the builder.

Say your user registration system has the following columns (with there respective
storage type):

* ``user_id``: integer
* ``username``: text
* ``password``: text
* ``first_name``: text
* ``last_name``: text
* ``reg_date``: datetime

You want to allow searching in all columns except password, because RollerworksSearch
is agnostic to your data system you need tell the system which fields there are
and then later map these fields to a column.

.. note::

    It may feel redundant to map these fields twice, but this is with a reason.

    A FieldSet can be used for any data system or storage, if the FieldSet was
    aware of your data system it would be only possible for one storage.
    And switching from Doctrine ORM to ElasticSearch would be more difficult.

Ok, now lets create a FieldSetConfigurator::

    namespace Acme\Search\FieldSet;

    use Rollerworks\Component\Search\Extension\Core\Type\TextType;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
    use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType
    use Rollerworks\Component\Search\FieldSetBuilder;
    use Rollerworks\Component\Search\FieldSetConfigurator;

    class UsersFieldSet implements FieldSetConfigurator
    {
        public function buildFieldSet(FieldSetBuilder $builder)
        {
            $builder
                ->add('id', IntegerType::class)
                ->add('username', TextType::class)
                ->add('firstName', TextType::class)
                ->add('lastName', TextType::class)
                ->add('regDate', DateTimeType::class);
        }
    }

That's it. The ``UsersFieldSet`` is now ready for usage::

    use Acme\Search\FieldSet;
    use Rollerworks\Component\Search\Searches;

    $searchFactory = new Searches::createSearchFactory();

    $fieldSet = $searchFactory->createFieldSet(FieldSet\UsersFieldSet::class);

Alternatively when your FieldSetConfigurator has dependencies you can use
a factory-constructor with the ``LazyFieldSetRegistry``::

    use Acme\Search\FieldSet;
    use Rollerworks\Component\Search\LazyFieldSetRegistry;

    $fieldSetRegistry = LazyFieldSetRegistry::create([FieldSet\UsersFieldSet::class => function () {
        return new FieldSet\UsersFieldSet();
    ]);

*Framework integrations provide a similar mechanise, see the integrations
section for details.*

.. note::

    Depending the implementation of the ``FieldSetRegistry``
    the configurator is only constructed once.

    The ``LazyFieldSetRegistry`` supports a PSR-11 compatible Container
