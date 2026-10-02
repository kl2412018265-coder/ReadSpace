<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
    || !isset($_POST["csrf_token"], $_SESSION["csrf_token"])
    || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Invalid request.");
}

$userId = (int) $_SESSION["user_id"];
$bookId = filter_input(INPUT_POST, "book_id", FILTER_VALIDATE_INT);
$status = $_POST["status"] ?? "";

if (!$bookId || !in_array($status, ["reading", "want_to_read"], true)) {
    http_response_code(400);
    exit("Invalid book or status.");
}

$isAdult = (int) ($_SESSION["is_minor"] ?? 1) === 0;
$stmt = $conn->prepare(
    "SELECT book_id FROM books
     WHERE book_id = ? AND (is_mature_content = 0 OR ? = 1)
     LIMIT 1"
);
$stmt->bind_param("ii", $bookId, $isAdult);
$stmt->execute();
$bookExists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bookExists) {
    http_response_code(404);
    exit("Book not found.");
}

$stmt = $conn->prepare(
    "INSERT INTO reading_list (user_id, book_id, status)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status)"
);
$stmt->bind_param("iis", $userId, $bookId, $status);
$stmt->execute();
$stmt->close();

header("Location: books.php?id=" . $bookId . "&reading_saved=1");
exit;