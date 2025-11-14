<?php

return [
    'scheme'   => env('COUCHDB_SCHEME', 'http'),
    'host'     => env('COUCHDB_HOST', '127.0.0.1'),
    'port'     => env('COUCHDB_PORT', 5984),
    'username' => env('COUCHDB_USERNAME'),
    'password' => env('COUCHDB_PASSWORD'),
    'prefix'   => env('COUCHDB_DATABASE_PREFIX', ''),
    'timeout' => (int) env('COUCHDB_TIMEOUT', 10),
    'connect_timeout' => (int) env('COUCHDB_CONNECT_TIMEOUT', 5),
];
