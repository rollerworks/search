SearchCondition in action
=========================

This chapter explains how you can use search conditions in practice,
what kind of results you can expect with a search condition and
handy tips for getting the best result.

These examples shown below use the :doc:`input/filter_query`
syntax as input condition (condition for short).

For all the examples we assume we have the following records:

+----------+------------+--------------+-----------------+-----------+
| id       | gender     | reg_date     | is_admin        | enabled   |
+==========+============+================================|===========+
| 10       | male       | 2015-20-04   | t               | t         |
+----------|------------+--------------------------------+-----------+
| 20       | female     | 2015-20-04   | f               | f         |
+----------|------------+--------------------------------+-----------+
| 30       | male       | 2015-20-04   | f               | t         |
+----------|------------+--------------------------------+-----------+
| 100      | female     | 2015-20-04   | f               | f         |
+----------|------------+--------------------------------------------+

.. tip::

    You are not limited a single table, the actual searching in a database
    is done by a search processor which may support searching complex
    structures or separated documents.

    So no problem if you want to search for an invoice that has a customer
    relationship and you want to use the customer as leading condition.

    ``invoice_row: ~*"my cool product"; customer_type: !consumer.``

    The Customer data is stored in the "customer" table while the "invoice"
    data is stored in it's own table. And did you know the invoice rows are
    actually in another table as well? That's three tables in a single
    condition!

Search for all users with a specific gender
-------------------------------------------

Say we want to find all users female users.

We can use the following condition: ``gender: female``

Which will give use the following result.

+----------+------------+--------------+-----------------+-----------+
| id       | gender     | reg_date     | is_admin        | enabled   |
+==========+============+================================|===========+
| 20       | female     | 2015-20-04   | f               | f         |
+----------|------------+--------------------------------+-----------+
| 100      | female     | 2015-20-04   | f               | f         |
+----------|------------+--------------------------------------------+

Or we can use a different approach by *excluding* male from the gender
list.

``gender: !man``

Which will give the same result.

But if we had another gender type like "N/A". Then would have gotten
all female users and users with gender "N/A".
