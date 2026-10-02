<?php
session_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php?tab=stats");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$bookId = filter_input(INPUT_POST, "book_id", FILTER_VALIDATE_INT);
$sessionToken = $_SESSION["csrf_token"] ?? "";
$postedToken = $_POST["csrf_token"] ?? "";

if (
    !$bookId ||
    $sessionToken === "" ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken)
) {
    http_response_code(400);
    exit("Permintaan tidak sah.");
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT b.is_mature_content, u.birth_date
     FROM books b
     JOIN users u ON u.user_id = ?
     WHERE b.book_id = ?
     LIMIT 1"
);
$stmt->bind_param("ii", $userId, $bookId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    http_response_code(404);
    exit("Buku tidak dijumpai.");
}

$age = (new DateTimeImmutable($data["birth_date"]))
    ->diff(new DateTimeImmutable("today"))->y;

if ((int) $data["is_mature_content"] === 1 && $age < 18) {
    http_response_code(403);
    exit("Buku ini untuk pengguna berumur 18 tahun ke atas.");
}

$stmt = $conn->prepare(
    "SELECT 1 FROM finished_books
     WHERE user_id = ? AND book_id = ?
     LIMIT 1"
);
$stmt->bind_param("ii", $userId, $bookId);
$stmt->execute();
$finished = (bool) $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($finished) {
    $stmt = $conn->prepare(
        "DELETE FROM finished_books
         WHERE user_id = ? AND book_id = ?"
    );
    $stmt->bind_param("ii", $userId, $bookId);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO finished_books (user_id, book_id, finished_at)
         VALUES (?, ?, ?)"
    );
    $date = (new DateTimeImmutable(
        "now",
        new DateTimeZone("Asia/Kuala_Lumpur")
    ))->format("Y-m-d");
    $stmt->bind_param("iis", $userId, $bookId, $date);
}

$stmt->execute();
$stmt->close();
if (!$finished) {
    $stmt = $conn->prepare(
        "DELETE FROM reading_list
         WHERE user_id = ? AND book_id = ?"
    );
    $stmt->bind_param("ii", $userId, $bookId);
    $stmt->execute();
    $stmt->close();
}
header("Location: profile.php?tab=stats");
exit;