.. index::
   single: input; xml

XmlInput Format
===============

The provided input must be structured as described in the `XSD schema`_.

All nodes inside the ``<field />`` node are optional. But at least one
must exists. Value must be properly formatted for usage in XML.

.. caution::

    Because of the way XSD validates, the
    ``<fields>`` node must be provided before
    the ``<groups>`` node.

.. code-block:: xml
   :linenos:

    <?xml version="1.0" encoding="UTF-8"?>
    <search>
        <fields>
            <field name="field1">
                <simple-values>
                    <value>value</value>
                    <value>value2</value>
                </simple-values>

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
                        <simple-values>
                            <value>value</value>
                            <value>value2</value>
                        </simple-values>
                    </field>
                </fields>
            </group>

            <group>
                <fields>
                    <field name="field1">
                        <simple-values>
                            <value>value3</value>
                            <value>value4</value>
                        </simple-values>
                    </field>
                </fields>

                <!--<groups> ... </groups>-->

            </group>
        </groups>

    </search>

.. _`XSD schema`: https://github.com/rollerworks/search/blob/master/src/Input/schema/dic/input/xml-input-2.0.xsd
