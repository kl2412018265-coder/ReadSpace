<?php
session_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: books.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Invalid form. Refresh the page and try again.");
}

$bookId = filter_input(INPUT_POST, "book_id", FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, "rating", FILTER_VALIDATE_INT);
$reviewText = trim($_POST["review_text"] ?? "");

if (
    !$bookId ||
    !$rating ||
    $rating < 1 ||
    $rating > 5 ||
    mb_strlen($reviewText) < 10 ||
    mb_strlen($reviewText) > 2000
) {
    exit("Choose a rating from 1 to 5 and write a review between 10 and 2000 characters.");
}

// Make sure the book exists and is appropriate for the user's age.
$stmt = $conn->prepare(
    "SELECT is_mature_content FROM books WHERE book_id = ? LIMIT 1"
);
$stmt->bind_param("i", $bookId);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (
    !$book ||
    ((int) $_SESSION["is_minor"] === 1
        && (int) $book["is_mature_content"] === 1)
) {
    http_response_code(403);
    exit("This book is not available for your account.");
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "INSERT INTO reviews
     (book_id, user_id, rating, review_text, status)
     VALUES (?, ?, ?, ?, 'pending')"
);
$stmt->bind_param("iiis", $bookId, $userId, $rating, $reviewText);
$stmt->execute();

header("Location: books.php?id=" . $bookId . "&review=pending");
exit;