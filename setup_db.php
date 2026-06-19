<?php
require_once 'config/db.php';

echo "<h1>Database Setup</h1>";

$sqlFile = 'sendnaw_db.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL file ($sqlFile) not found.");
}

try {
    $sql = file_get_contents($sqlFile);
    
    // Temporarily disable the primary key requirement for this session
    // because phpMyAdmin dumps often create tables first and add primary keys later via ALTER TABLE.
    $pdo->exec("SET SESSION sql_require_primary_key = 0;");
    
    // Execute the SQL dump
    $pdo->exec($sql);
    
    echo "<p style='color: green;'>✅ Database successfully imported and setup!</p>";
    echo "<p>You can now delete this file for security purposes.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error importing database: " . $e->getMessage() . "</p>";
}
?>
