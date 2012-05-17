<?php return array (
  'date' => 
  array (
    'validate' => '(?P<year>\\d{2,4})年(?P<month>(?:1[0-2]|[0]?[1-9]))月(?P<day>(?:3[01]?|2[0-9]|[01]?[0-9]))日',
    'match' => '\\p{N}{2,4}年\\p{N}月\\p{N}日',
  ),
  'dateTime' => 
  array (
    'validate' => '(?P<year>\\d{2,4})年(?P<month>(?:1[0-2]|[0]?[1-9]))月(?P<day>(?:3[01]?|2[0-9]|[01]?[0-9]))日\\h+(?P<ampm>(?:[aApP][mM]))(?P<hours>(?:[01]?\\d|2[0-3]))\\:(?P<minutes>[0-5]?\\d)',
    'match' => '\\p{N}{2,4}年\\p{N}月\\p{N}日\\h+(?:上午|下午)\\p{N}\\:\\p{N}{2}',
  ),
  'time' => 
  array (
    'validate' => '(?P<ampm>(?:[aApP][mM]))(?P<hours>(?:[01]?\\d|2[0-3]))\\:(?P<minutes>[0-5]?\\d)',
    'match' => '(?:上午|下午)\\p{N}\\:\\p{N}{2}',
  ),
  'am-pm' => true,
);
