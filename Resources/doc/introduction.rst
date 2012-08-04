Introduction
============

The RecordFilterBundle is Symfony 2 Bundle for filter-based record searching.

Filter-based in that is uses a filtering system to search,
you don't search by terms but by conditions.

The system was designed to be used for any kind of storage, input and locale.

Organization of this Book
-------------------------

This book has been written so that those who need information quickly are able
to find what they need, and those who wish to learn more advanced topics can
read deeper into each chapter.

The book begins with an overview of the RecordFilterBundle (RecordFilter),
discussing what's included in the package and preparing you for the remainder of the book.

It is possible to read this user guide just like any other book (from
beginning to end). Each chapter begins with a discussion of the contents it
contains, followed by a short code sample designed to give you a head start.
As you get further into a chapter you will learn more about RecordFilter's
capabilities, but often you will be able to head directly to the topic you
wish to learn about.

Throughout this book you will be presented with code samples, which most
people should find ample to implement RecordFilter appropriately in their own
projects.


Code Samples
------------

Code samples presented in this book will be displayed on a different colored
background in a mono-spaced font. Samples are not to be taken as copy & paste
code snippets.

Code examples are used through the book to clarify what is written in text.
They will sometimes be usable as-is, but they should always be taken as
outline/pseudo code only.

A code sample will look like this:

.. code-block:: php

    class AClass
    {
      ...
    }

    //A Comment
    $obj = new AClass($arg1, $arg2, ... );

    /* A note about another way of doing something
    $obj = AClass::newInstance($arg1, $arg2, ... );

    */

The presence of 3 dots ``...`` in a code sample indicates that we have left
out a chunk of the code for brevity, they are not actually part of the code.

We will often place multi-line comments ``/* ... */`` in the code so that we
can show alternative ways of achieving the same result.

You should read the code examples given and try to understand them. They are
kept concise so that you are not overwhelmed with information.
