SearchConditions in action
==========================

This chapter explains how you can use search conditions in practice,
what kind of results you can expect with a search condition and
handy tips for getting the best result.

These examples shown below use the :doc:`input/filter_query`
syntax as input condition (condition for short).

Remember that almost all values of a field are OR'ed, meaning
that at least one value must match for the field to have a positive
match. ``field: value1, value2`` means from the current row
the value of column field1 must be e.g. value1 or value2;

.. note::

    Excluded values are not use OR'ed, so ``field: !value1, !value1;``
    will only match if field1 is not value1 and not value2;

Common mistakes and good to know
--------------------------------

Comparison
~~~~~~~~~~

Comparisons are applied as AND, meaning all of them must give a positive
match. So using multiple comparisons may not give the expected result.
``field: >10, <20`` is the same as using a range like ``field: ]10-20[``.

But ``field: >10, <20, >30`` will only give results when field is between
10 and 20, the higher then 30 part is ignored as the first part is more
restrictive.

You can solve this by using ranges like: ``field1: 10-20, >30;``
Or by using a subgroup like ``(field1: >10, <20); (field1: >30)``

PatternMatch
~~~~~~~~~~~~

PatternMatchers are either OR'ed or applied as AND. This depends on the
whether they are excluding or not.

Take the following matchers: ``field: ~*foo; ~!*bar;``
The first one is a "positive" matcher (field1 contains foo), the second
one is a negative/excluding matcher (field1 does *not* contain bar).

If both were OR'ed we would either get a result when field1 contains "foo"
or does not contain "bar", but if field one contains "foo" but also "bar"
we would get an unexpected result. So the matchers are applied separately.

.. caution::

    Don't use a regex unless there is an actual expression. ``field: "^(foo|bar)"``
    can be easily done with field: ``field1: ~>foo; field1: ~>bar"``

Last tip
~~~~~~~~

If something is not possible because of how the condition is evaluated
you can use subgroups to make it work.

Take the PatternMatcher, say we **want to** search a field that contains
e.g. "foo" *or* does not contain "bar". With ``field: ~*foo; ~!*bar;``
this will not work, but if we use two subgroups ``(field: ~*foo); (field: ~!*bar);``
it will work!

What to expect with a condition
-------------------------------

For all the examples assume we have the following records:

+----------+------------+--------------+-----------------+-----------+
| id       | gender     | reg_date     | is_admin        | enabled   |
+==========+============+==============+=================+===========+
| 10       | male       | 2011-01-04   | t               | t         |
+----------+------------+--------------+-----------------+-----------+
| 20       | female     | 2011-01-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 30       | male       | 2013-01-04   | f               | t         |
+----------+------------+--------------+-----------------+-----------+
| 100      | female     | 2013-05-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 500      | male       | 2015-03-04   | t               | f         |
+----------+------------+--------------+-----------------+-----------+

.. tip::

    You are not limited a single table, the actual searching in a database
    is done by a search processor which may support searching complex
    structures or separated documents.

    So no problem if you want to search for an invoice that has a customer
    relationship and you want to use the customer as leading condition.

Search for users with a specific gender
---------------------------------------

Say we want to find all users female users.

We use the following condition: ``gender: female``

We will give use the following result.

+----------+------------+--------------+-----------------+-----------+
| id       | gender     | reg_date     | is_admin        | enabled   |
+==========+============+==============+=================+===========+
| 20       | female     | 2011-01-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 100      | female     | 2013-05-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+

Or we can use a different approach by *excluding* male from the gender
list.

.. code-block:: php

    gender: !man;

Which will give the same result.

.. note::

    If we had another gender type like "N/A". Then we would have
    gotten all female users and users with gender "N/A".

Search for users with a specific gender and registration date
-------------------------------------------------------------

Say we want to find all users female users, that have registered
in or after the year 2011 but before 2015.
*Dates are in date notation year/month/day.*

The following conditions will all produce the same result, but use
different methods to get the result.

All conditions will give the following result.

+----------+------------+--------------+-----------------+-----------+
| id       | gender     | reg_date     | is_admin        | enabled   |
+==========+============+==============+=================+===========+
| 20       | female     | 2011-01-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 100      | female     | 2013-05-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+

.. note::

    Notice that the date is between ``"``, this is because any value part that is not
    a single word or number must be quoted in the FilterQuery syntax.

    Female is a single word so this doesn't require quoting.

Explicit range
~~~~~~~~~~~~~~

Find where gender is female and date is (inclusive) between "2011/01/01"
and "2014/12/31".

.. code-block:: php

    gender: female; date: "2011/01/01" - "2014/12/31";

Explicit range with exclusive bounds
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes the upper-value is not really predictable, for example you want to
search for a date that falls in a leap year. Instead of figuring out the last
day of the month you can use an exclusive upper-bound.

Find where gender is female and date is between (inclusive) "2011/01/01"
and (exclusive) "2014/12/31".

The lower bound is inclusive (by default) meaning it will only match a value
that is equal or higher than "2011/01/01".

The the upper-bound of the range is marked exclusive meaning it will only
match values that are lower than "2015/01/01".

.. code-block:: php

    gender: female; date: "2011/01/01" - ]"2015/01/01";

And same thing can be done for the lower-bound.

.. code-block:: php

    gender: female; date: ["2012/12/31" - ]"2015/01/01";

The lower bound is now exclusive meaning it will only match a value that is higher
than "2011/01/01".

Implicit range with Comparisons
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Using ranges is just one method, it's also possible to use multiple comparisons,
which is better known as an "implicit range". It has the same effect as a range,
but is defined differently.

.. caution::

    Implicit ranges can't (currently) be optimized, so if you have a value
    which is overlapping in a range this will not be optimized.

    So avoid using implicit ranges whenever possible.

Find where gender is female and date is higher than "2011/01/01"
and lower than "2014/12/31".

.. code-block:: php

    gender: female; date: >"2011/01/01", <"2015/01/01";

Multiple single values
~~~~~~~~~~~~~~~~~~~~~~

So far we have only used ranges, but did you know it's also possible to use
multiple single values? OK, this may seem a bit crazy but it's not uncommon,
when you select a list of checkboxes all of these are technically single values.

For our date example this will result in 1460 single values (which for logical
reason are not all shown here, this example only shows 4 dates).

.. code-block:: php

    gender: female; date: "2011/01/02", "2011/01/03", "2011/01/04", "2011/01/05";

.. tip::

    The system already has an optimizer that can convert incremented values
    to ranges. So don't worry about the 1460 single values, in the end this
    this is simply converted into a single range.

    But you are properly are gonna hit the maximum values per field limit. So
    it's best to avoid this whenever possible.

Subgroup range
~~~~~~~~~~~~~~

Using subgroups in this case is just an example, normally you would use
one of the methods described above.

Find where gender is female and subgroup 0 is matching, subgroup 0 matches
when date is (inclusive) between "2011/01/01" and "2014/12/31".

.. code-block:: php

    gender: female; (date: "2011/01/01" - "2014/12/31";)

Search for users which either have admin access or are disabled
---------------------------------------------------------------

In the previous section we only used conditions where all the fields
must match. But what if we want to search with an OR condition?
We want to search for users which either have admin access **or**
are disabled.

This is where we can use an OR'ed group. In an OR'ed group at least one
field must match but the other fields are *optional*.

Using condition:

.. code-block:: php

    * is_admin: t; enabled: f;

Will give the following result.

+----------+------------+--------------+-----------------+-----------+
| id       | gender     | reg_date     | is_admin        | enabled   |
+==========+============+==============+=================+===========+
| 10       | male       | 2011-01-04   | t               | t         |
+----------+------------+--------------+-----------------+-----------+
| 20       | female     | 2011-01-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 100      | female     | 2013-05-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 500      | male       | 2015-03-04   | t               | f         |
+----------+------------+--------------+-----------------+-----------+

Lets analyze this result a bit further.

The first row matches because the user is an admin, the user is enabled
but we can ignore this because we already have a positive match.

The second row matches, the user is not an admin it's disabled
so the second field has a positive match.

.. note::

    The OR'ed symbol works only on groups, because the condition always
    starts with a group the OR'ed symbol is only valid at the start of
    a condition or subgroup. So the following is invalid: ``is_admin: t; * enabled: f;``

    But this is valid: ``is_admin: t; *(enabled: f)`` and marks subgroup 0
    as OR'ed.

Search for users which either "have admin access and are disabled" or female
----------------------------------------------------------------------------

Using OR'ed subgroups is great if want at least one field to match and
mark the rest as optional. But this will not work if want all the fields
to match but but just not together.

This is where subgroup (finally) come into play. Each subgroup can have
it's own condition which is applied secondary to the parent-group and
only fields within the subgroup will make it matching.

Using condition:

.. code-block:: php

    (is_admin: t; enabled: f); (gender: female);

.. note::

    Subgroups are always OR'ed to each other, but at **least one must
    match** for the group it's in! A group can be meant as the condition's root
    (the root group) or a nested subgroup.

Will give the following result.

+----------+------------+--------------+-----------------+-----------+
| id       | gender     | reg_date     | is_admin        | enabled   |
+==========+============+==============+=================+===========+
| 20       | female     | 2011-01-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 100      | female     | 2013-05-04   | f               | f         |
+----------+------------+--------------+-----------------+-----------+
| 500      | male       | 2015-03-04   | t               | f         |
+----------+------------+--------------+-----------------+-----------+

Lets analyze this result a bit further.

The first and second rows match because the user is a female, the second subgroup
does not match but as subgroups are OR'ed this is no problem.

The last row matches because first subgroup matches, the user is an admin and
is disabled, the second subgroup does not match and so is ignored.

.. caution::

    Note that we used two subgroups, if we the placed either of the fields
    in the root of the condition like ``gender: female; (is_admin: t; enabled: f);``
    We would have gotten a completely different result. The first subgroup must match
    as subgroups are only OR'ed to each other.

    So in practice using ``gender: female; (is_admin: t; enabled: f);``
    is the same as using ``gender: female; is_admin: t; enabled: f;``
