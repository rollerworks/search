<?php return array (
  'date' => 
  array (
    'validate' => '(?P<year>\\d{2,4})[/.-](?P<month>(?:1[0-2]|[0]?[1-9]))[/.-](?P<day>(?:3[01]?|2[0-9]|[01]?[0-9]))',
    'match' => '\\p{N}{2,4}[/.-]\\p{N}[/.-]\\p{N}',
  ),
  'dateTime' => 
  array (
    'validate' => '(?P<year>\\d{2,4})[/.-](?P<month>(?:1[0-2]|[0]?[1-9]))[/.-](?P<day>(?:3[01]?|2[0-9]|[01]?[0-9]))\\h+(?P<hours>(?:[01]?\\d|2[0-3]))\\:(?P<minutes>[0-5]?\\d)\\h+(?P<ampm>(?:[aApP][mM]))',
    'match' => '\\p{N}{2,4}[/.-]\\p{N}[/.-]\\p{N}\\h+\\p{N}\\:\\p{N}{2}\\h+(?:ص|م)',
  ),
  'time' => 
  array (
    'validate' => '(?P<hours>(?:[01]?\\d|2[0-3]))\\:(?P<minutes>[0-5]?\\d)\\h+(?P<ampm>(?:[aApP][mM]))',
    'match' => '\\p{N}\\:\\p{N}{2}\\h+(?:ص|م)',
  ),
  'am-pm' => true,
);
