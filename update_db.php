<?php
include 'config.php';

$sql = file_get_contents('database.sql');

try {
    $pdo->exec($sql);
    echo "Database updated successfully.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
