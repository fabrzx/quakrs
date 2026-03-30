#!/usr/bin/env php
<?php
declare(strict_types=1);

$appCfg = require __DIR__ . '/../../config/app.php';
$localCfgPath = __DIR__ . '/../../config/app.local.php';
if (is_file($localCfgPath)) {
    $localCfg = require $localCfgPath;
    if (is_array($localCfg)) {
        $appCfg = array_replace_recursive($appCfg, $localCfg);
    }
}

$dbCfg = is_array($appCfg['mysql_databases']['live'] ?? null) ? $appCfg['mysql_databases']['live'] : [];
$host = (string) ($dbCfg['host'] ?? '127.0.0.1');
$user = (string) ($dbCfg['user'] ?? '');
$pass = (string) ($dbCfg['password'] ?? '');
$dbName = (string) ($dbCfg['database'] ?? '');
$port = (int) ($dbCfg['port'] ?? 3306);

if ($user === '' || $dbName === '') {
    fwrite(STDERR, "DB config missing\n");
    exit(1);
}

$mysqli = new mysqli($host, $user, $pass, $dbName, $port);
if ($mysqli->connect_errno) {
    fwrite(STDERR, 'DB connect failed: ' . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset((string) ($dbCfg['charset'] ?? 'utf8mb4'));

$schemaPath = __DIR__ . '/../sql/schema.sql';
$sql = @file_get_contents($schemaPath);
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "schema.sql not found or empty\n");
    exit(1);
}

if (!$mysqli->multi_query($sql)) {
    fwrite(STDERR, 'Schema execution failed: ' . $mysqli->error . "\n");
    exit(1);
}

while ($mysqli->more_results() && $mysqli->next_result()) {
    $res = $mysqli->store_result();
    if ($res instanceof mysqli_result) {
        $res->free();
    }
}

$mysqli->close();
echo "schema_ok\n";
