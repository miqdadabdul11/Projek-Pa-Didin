<?php
echo "Testing Redis connection...\n";
try {
    $redis = new Redis();
    $result = $redis->connect('redis', 6379);
    echo "Redis connected: " . ($result ? "YES" : "NO") . "\n";
    echo "Ping: " . $redis->ping() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
