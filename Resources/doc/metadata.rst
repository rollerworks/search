Metadata
========

Class metadata is used by the FieldSet-factory for populating FieldSets
based on the information provided by the classes.

The information can be stored directly with the class using Annotations.
Or as a separate file using YAML or XML.

.. note::

    Metadata files are named relative to the bundle namespace, followed by
    sub-namespace and filename in Resources/config/record_filter/.

    So if your class file is 'src/Acme/StoreBundle/Model/Product/Product.php'
    the metadata is stored in
    'src/Acme/StoreBundle/Resources/config/record_filter/Model.Product.Product.ext'

.. configuration-block::

    .. code-block:: php-annotations

        // src/Acme/StoreBundle/Model/Product.php
        namespace Acme\StoreBundle\Model;

        use Rollerworks\Bundle\RecordFilterBundle\Annotation as RecordFilter;

        class Product
        {
            /**
             * @RecordFilter\Field("product_id", required=false, type="number", AcceptRanges=true, AcceptCompares=true)
             */
            protected $id;

            /**
             * @RecordFilter\Field("product_name", type="text")
             */
            protected $name;

            /**
             * @RecordFilter\Field("product_price", type=@RecordFilter\Type("decimal", min=0.01), AcceptRanges=true, AcceptCompares=true)
             */
            protected $price;

            // ...
        }

    .. code-block:: yaml

        # src/Acme/StoreBundle/Resources/config/record_filter/Model.Product.yml
        id:
            # Name is the filter field-name
            name: product_id
            type: number
            required: false
            accept-ranges: true
            accept-compares: true

        name:
            name: product_name
            type: text

        price:
            name: product_price
            accept-ranges: true
            accept-compares: true
            type:
                name: decimal
                params:
                    min: 0.01

    .. code-block:: xml

        <!-- src/Acme/StoreBundle/Resources/config/record_filter/Model.Product.xml -->
        <properties>
            <property id="id" name="product_id" required="false">
                <type name="number" />
            </property>
            <property id="name" name="product_name">
                <type name="text" />
            </property>
            <property id="name" name="product_name">
                <type name="text" />
            </property>
            <property id="price" name="product_price" accept-ranges="true" accept-compares="true">
                <type name="text">
                    <param key="min" type="float">0.01</param>
                    <!-- An array is build as follow. Key and type are optional for <value> -->
                    <!--
                    <param key="key">
                        <value type="string">value</value>
                        <value type="string">
                            <value key="foo">value</value>
                        </value>
                    </param>
                    -->
                </type>
            </property>
        </properties>

.. note::

    A class can accept only one metadata definition format.
    For example, it's not possible to mix YAML metadata definitions with
    annotated PHP class definitions.

Overwriting
------------

Overwriting the metadata works the same as overwriting Resources in Symfony.

If you don't know how to do this please read `How to use Bundle Inheritance to Override parts of a Bundle. <http://symfony.com/doc/current/cookbook/bundles/inheritance.html>`_

.. caution::

    Any class metadata (except annotations) that was set in the 'parent'
    bundle is ignored, you must copy it in order to have everything.

    This is will hopefully be fixed in the next version of the RecordFilterBundle.
