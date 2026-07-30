<?php
header('Content-Type: text/plain');
echo "=== RAILWAY DB DIAGNOSTIC ===\n";
echo "MYSQLHOST: " . var_export(getenv('MYSQLHOST'), true) . "\n";
echo "MYSQLPORT: " . var_export(getenv('MYSQLPORT'), true) . "\n";
echo "MYSQLUSER: " . var_export(getenv('MYSQLUSER'), true) . "\n";
echo "MYSQLDATABASE: " . var_export(getenv('MYSQLDATABASE'), true) . "\n";
echo "DB_HOST: " . var_export(getenv('DB_HOST'), true) . "\n";
echo "RAILWAY_ENVIRONMENT: " . var_export(getenv('RAILWAY_ENVIRONMENT'), true) . "\n";

$tests = [
    ['mysql.railway.internal', 3306],
    ['sakura.proxy.rlwy.net', 48834],
    ['127.0.0.1', 3306],
];

foreach ($tests as $t) {
    $t0 = microtime(true);
    $fp = @fsockopen($t[0], $t[1], $errno, $errstr, 2);
    $dt = round((microtime(true) - $t0) * 1000, 2);
    if ($fp) {
        fclose($fp);
        echo "✓ Socket {$t[0]}:{$t[1]} -> CONNECTED in {$dt}ms\n";
    } else {
        echo "✗ Socket {$t[0]}:{$t[1]} -> FAILED ({$errstr}) in {$dt}ms\n";
    }
}
