A FieldSet is build using either the FieldSetBuilder or a FieldSetConfigurator,
using a FieldSetConfigurator makes it possible for the FieldSet to be serialized
and makes your configuration easily sharable for other processors.

For now we will use the FieldSetBuilder, building a FieldSet configuration
for a user registration system.

Say your user registration system has the following columns (with the storage type):

* ``user_id``: integer
* ``username``: text
* ``password``: text
* ``first_name``: text
* ``last_name``: text
* ``reg_date``: datetime

You want to allow searching in all columns except password, because RollerworksSearch
is agnostic to your data system you need tell the system which fields there are
and then later map these fields to a (table) column.

.. note::

    It may feel redundant to map these fields twice, but this is with a reason.

    A FieldSet can be used for any data system or storage, if the FieldSet was
    aware of your data system it would be only possible for one storage.
    And switching to ElasticSearch from Doctrine ORM would be more difficult.

.. code-block:: php
    :linenos:

    use Rollerworks\Component\Search\Extension\Core\Type\TextType;
    use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
    use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;

    $userFieldSet = $searchFactory->createFieldSetBuilder()
        ->add('id', IntegerType::class)
        ->add('username', TextType::class)
        ->add('firstName', TextType::class)
        ->add('lastName', TextType::class)
        ->add('regDate', DateTimeType::class)
        ->getFieldSet('users');

That's it. The FieldSet is now ready for usage, the ``getFieldSet`` method
has an optional argument where you can provide the name of the set (eg. ``users``).

Naming a set makes it easier to identify the set's configuration when passed
to a Condition processor. But it is not required for normal usage.

.. tip::

    The ``getFieldSet`` method produces a new FieldSet configuration every time,
    this allows to create multiple FieldSet configurations who share a common
    structure and configuration.
