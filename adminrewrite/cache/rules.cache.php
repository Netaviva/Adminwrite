<?php $aCacheData = array (
  'base' => '/techprojects/lrewritev2/',
  'rules' => 
  array (
    1 => 
    array (
      'regex' => '^cash\\/?$',
      'expression' => 'cash',
      'location' => 'money.php',
      'flag_raw' => 'L',
      'flag' => 
      array (
        'L' => true,
      ),
    ),
    2 => 
    array (
      'regex' => '^money\\.php\\/?$',
      'expression' => 'money.php',
      'location' => 'cool.php',
      'flag_raw' => 'R',
      'flag' => 
      array (
        'R' => true,
      ),
    ),
    3 => 
    array (
      'regex' => '^u\\/([0-9a-zA-Z_-]+?)\\/?$',
      'expression' => 'u/:alphanum:/',
      'location' => 'profile.php?user=$1',
      'flag_raw' => 'L,NC',
      'flag' => 
      array (
        'L' => true,
        'NC' => true,
      ),
    ),
    4 => 
    array (
      'regex' => '^go\\/([a-zA-Z0-9.-_])\\/?$',
      'expression' => 'go/:regex:([a-zA-Z0-9.-_])/',
      'location' => 'redirect.php',
      'flag_raw' => 'L,NC',
      'flag' => 
      array (
        'L' => true,
        'NC' => true,
      ),
    ),
    5 => 
    array (
      'regex' => '^blog\\/([0-9]+)\\/?$',
      'expression' => 'blog/:int:/',
      'location' => 'blog.php?page=$1',
      'flag_raw' => 'L',
      'flag' => 
      array (
        'L' => true,
      ),
    ),
  ),
); ?>