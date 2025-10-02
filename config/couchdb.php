<?php

return [
    'scheme'   => env('COUCHDB_SCHEME', 'http'),
    'host'     => env('COUCHDB_HOST', '127.0.0.1'),
    'port'     => env('COUCHDB_PORT', 5984),
    'username' => env('COUCHDB_USERNAME'),
    'password' => env('COUCHDB_PASSWORD'),
    'prefix'   => env('COUCHDB_DATABASE_PREFIX', ''),
];
