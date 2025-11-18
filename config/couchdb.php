<?php

return [
    'scheme'   => env('COUCHDB_SCHEME', 'https'),
    'host'     => env('COUCHDB_HOST', 'couchdb-public.onrender.com'),
    'port'     => env('COUCHDB_PORT', 443),
    'username' => env('COUCHDB_USERNAME'),
    'password' => env('COUCHDB_PASSWORD'),
    'prefix'   => env('COUCHDB_DATABASE_PREFIX', ''),
    'timeout' => (int) env('COUCHDB_TIMEOUT', 60),
    'connect_timeout' => (int) env('COUCHDB_CONNECT_TIMEOUT', 30),
    'verify_ssl' => filter_var(env('COUCHDB_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
];
