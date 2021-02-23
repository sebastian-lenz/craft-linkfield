<?php

return [
	'server' => getenv('TEST_DB_HOST'),
  'port' => getenv('TEST_DB_PORT'),
	'user' => getenv('TEST_DB_USER'),
	'password' => getenv('TEST_DB_PASS'),
	'database' => getenv('TEST_DB_NAME'),
	'tablePrefix' => '',
];
