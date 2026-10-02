<?php
require "config.php";

$tables = ["users", "books", "reviews", "book_clubs"];

foreach ($tables as $table) {
    echo "<h2>" . htmlspecialchars($table) . "</h2>";

    $result = $conn->query("SHOW COLUMNS FROM `$table`");

    if (!$result) {
        echo "<p>Error: " . htmlspecialchars($conn->error) . "</p>";
        continue;
    }

    echo "<ul>";
    while ($column = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($column["Field"])
            . " (" . htmlspecialchars($column["Type"]) . ")</li>";
    }
    echo "</ul>";
}
