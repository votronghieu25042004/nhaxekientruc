<?php

// Script to migrate schema and copy all SQLite data to MySQL nhaxe_kientruc

$sqlitePdo = new PDO('sqlite:database/database.sqlite');
$sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mysqlPdo = new PDO('mysql:host=127.0.0.1;dbname=nhaxe_kientruc;charset=utf8mb4', 'root', '');
$mysqlPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Step 1: Importing tables schema from bushub_booking.sql...\n";
$sqlDump = file_get_contents('database/bushub_booking.sql');

// Disable foreign key checks
$mysqlPdo->exec("SET FOREIGN_KEY_CHECKS=0;");

// Split SQL dump into statements
// We only want the CREATE TABLE and ALTER statements from the dump, or we can run the whole dump then overwrite with SQLite data
$queries = explode(";\n", $sqlDump);
foreach ($queries as $q) {
    $trimmed = trim($q);
    if (!empty($trimmed) && !str_starts_with($trimmed, '--') && !str_starts_with($trimmed, '/*')) {
        try {
            $mysqlPdo->exec($trimmed);
        } catch (Exception $e) {
            // ignore insert duplicates or minor warnings
        }
    }
}

echo "Step 2: Checking tables created in MySQL...\n";
$tables = $mysqlPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables in MySQL: " . implode(', ', $tables) . "\n";

echo "Step 3: Copying all active data from SQLite to MySQL...\n";
// Clear tables and copy from SQLite
$sqliteTables = $sqlitePdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

foreach ($sqliteTables as $table) {
    if ($table === 'migrations') continue;
    // Check if table exists in MySQL
    if (!in_array($table, $tables)) {
        echo "Table $table does not exist in MySQL dump, skipping or checking migration\n";
        continue;
    }

    try {
        $rows = $sqlitePdo->query("SELECT * FROM \"$table\"")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            echo "- $table: 0 rows\n";
            continue;
        }

        // Truncate MySQL table
        $mysqlPdo->exec("TRUNCATE TABLE `$table`");

        // Prepare insert
        $columns = array_keys($rows[0]);
        $colNames = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $mysqlPdo->prepare("INSERT INTO `$table` (`$colNames`) VALUES ($placeholders)");

        $count = 0;
        foreach ($rows as $row) {
            $values = array_values($row);
            $stmt->execute($values);
            $count++;
        }
        echo "✓ $table: copied $count rows\n";
    } catch (Exception $e) {
        echo "Error on table $table: " . $e->getMessage() . "\n";
    }
}

$mysqlPdo->exec("SET FOREIGN_KEY_CHECKS=1;");
echo "Done migration to MySQL nhaxe_kientruc!\n";
