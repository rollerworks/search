<?php return array (
  'date' => 
  array (
    'validate' => '(?P<day>(?:3[01]?|2[0-9]|[01]?[0-9]))[/.-](?P<month>(?:1[0-2]|[0]?[1-9]))[/.-](?P<year>\\d{2,4})',
    'match' => '\\p{N}[/.-]\\p{N}[/.-]\\p{N}{2,4}',
  ),
  'dateTime' => 
  array (
    'validate' => '(?P<day>(?:3[01]?|2[0-9]|[01]?[0-9]))[/.-](?P<month>(?:1[0-2]|[0]?[1-9]))[/.-](?P<year>\\d{2,4})\\h+(?P<hours>(?:[01]?\\d|2[0-3]))[/.-](?P<minutes>[0-5]?\\d)',
    'match' => '\\p{N}[/.-]\\p{N}[/.-]\\p{N}{2,4}\\h+\\p{N}{2}[/.-]\\p{N}{2}',
  ),
  'time' => 
  array (
    'validate' => '(?P<hours>(?:[01]?\\d|2[0-3]))[/.-](?P<minutes>[0-5]?\\d)',
    'match' => '\\p{N}{2}[/.-]\\p{N}{2}',
  ),
  'am-pm' => false,
);