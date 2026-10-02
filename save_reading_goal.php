<?php
session_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$sessionToken = $_SESSION["csrf_token"] ?? "";
$postedToken = $_POST["csrf_token"] ?? "";
$target = filter_input(
    INPUT_POST,
    "target_books",
    FILTER_VALIDATE_INT
);

if (
    $sessionToken === "" ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken) ||
    !$target ||
    $target < 1 ||
    $target > 365
) {
    http_response_code(400);
    exit("Invalid goal. Choose between 1 and 365 books.");
}

$userId = (int) $_SESSION["user_id"];
$year = (int) date("Y");

$stmt = $conn->prepare(
    "INSERT INTO reading_goals (user_id, goal_year, target_books)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE target_books = VALUES(target_books)"
);
$stmt->bind_param("iii", $userId, $year, $target);
$stmt->execute();
$stmt->close();

header("Location: profile.php?tab=stats&goal_saved=1");
exit;