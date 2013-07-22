XmlInput
========

Accepts filtering preference in as an XML document.

Any node/leave inside the ``<field />``-node is optional.
But at least one must exists.

.. code-block:: xml
   :linenos:

    <?xml version="1.0" encoding="UTF-8"?>
    <filters>
        <groups>

            <group>
                <field name="user">
                    <single-values>
                        <value>2</value>
                    </single-values>

                    <excluded-values>
                        <value>15</value>
                    </excluded-values>

                    <ranges>
                        <range>
                            <lower>10</lower>
                            <higher>20</higher>
                        </range>
                    </ranges>

                    <excluded-ranges>
                        <range>
                            <lower>10</lower>
                            <higher>20</higher>
                        </range>
                    </excluded-ranges>

                    <compares>
                        <compare opr="&gt;">25</compare>
                    </compares>
                </field>
            </group>

            <group>
                <field name="user">
                    <single-values>
                        <value>100</value>
                    </single-values>
                    <!-- ... -->
                </field>

            </group>
        </groups>
    </filters>
