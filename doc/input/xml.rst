XmlInput
========

Processes input as an XML document.

See the XSD in 'src/Input/schema/dic/input/xml-input-1.0.xsd'
for more information about the schema.

Any node/leave inside the ``<field />``-node is optional.
But at least one must exists.

.. caution::

    Because of the way XSD validates the
    ``<fields>`` node must be provided before
    the ```<groups>`` node.

.. code-block:: xml
   :linenos:

    <?xml version="1.0" encoding="UTF-8"'.'?'.'>
    <search>
        <fields>
            <field name="field1">
                <single-values>
                    <value>value</value>
                    <value>value2</value>
                </single-values>

                <excluded-values>
                    <value>value</value>
                    <value>value2</value>
                </excluded-values>

                <ranges>
                    <range>
                        <lower>55</lower>
                        <upper inclusive="false">60</upper>
                    </range>
                    <range>
                        <lower inclusive="false">70</lower>
                        <upper>80</upper>
                    </range>
                </ranges>

                <excluded-ranges>
                    <range>
                        <lower>10</lower>
                        <upper>20</upper>
                    </range>
                </excluded-ranges>

                <comparisons>
                    <compare operator="&gt;">10</compare>
                    <compare operator="&lt;">50</compare>
                </comparisons>

                <pattern-matchers>
                    <pattern-matcher type="contains">foo</pattern-matcher>
                    <pattern-matcher type="ends_with" case-insensitive="true">bar</pattern-matcher>
                </pattern-matchers>
            </field>
        </fields>

        <groups>

            <group>
                <fields>
                    <field name="field1">
                        <single-values>
                            <value>value</value>
                            <value>value2</value>
                        </single-values>
                    </field>
                </fields>
            </group>

            <group>
                <fields>
                    <field name="field1">
                        <single-values>
                            <value>value3</value>
                            <value>value4</value>
                        </single-values>
                    </field>
                </fields>

                <!--<groups> ... </groups>-->

            </group>
        </groups>

    </search>
