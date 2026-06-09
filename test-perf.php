<?php
require '/var/www/bootstrap/app.php';

$app = app();
$db = $app->make('db');

// Enable query logging
$db->enableQueryLog();

// Simulate login request
echo "Checking database performance...\n";

// Check user table query time
$start = microtime(true);
$users = DB::table('users')->limit(5)->get();
$time = (microtime(true) - $start) * 1000;

echo "User query: {$time}ms\n";

// Check queries
$queries = DB::getQueryLog();
foreach ($queries as $q) {
    echo "Query: {$q['query']}\n";
    echo "Time: {$q['time']}ms\n\n";
}
