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

$userId = (int) $_SESSION["user_id"];
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
    exit("Invalid request.");
}

$stmt = $conn->prepare(
    "SELECT role FROM users WHERE user_id = ? LIMIT 1"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user["role"] !== "moderator") {
    http_response_code(403);
    exit("Only moderators can change book covers.");
}

$stmt = $conn->prepare(
    "SELECT book_id FROM books WHERE book_id = ? LIMIT 1"
);
$stmt->bind_param("i", $bookId);
$stmt->execute();
$bookExists = (bool) $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bookExists) {
    http_response_code(404);
    exit("Book not found.");
}

if (
    !isset($_FILES["cover"]) ||
    $_FILES["cover"]["error"] !== UPLOAD_ERR_OK
) {
    http_response_code(400);
    exit("The image could not be uploaded.");
}

$file = $_FILES["cover"];

if ($file["size"] > 5 * 1024 * 1024) {
    http_response_code(400);
    exit("The image must be 5 MB or smaller.");
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file["tmp_name"]);

$allowed = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

if (!isset($allowed[$mime]) || !getimagesize($file["tmp_name"])) {
    http_response_code(400);
    exit("Please choose a JPG, PNG, or WebP image.");
}

$folder = __DIR__ . "/uploads/covers";

if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
    http_response_code(500);
    exit("The covers folder could not be created.");
}

$filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];
$destination = $folder . "/" . $filename;

if (!move_uploaded_file($file["tmp_name"], $destination)) {
    http_response_code(500);
    exit("The image could not be saved.");
}

$coverPath = "uploads/covers/" . $filename;

$stmt = $conn->prepare(
    "UPDATE books SET cover_image_url = ? WHERE book_id = ?"
);
$stmt->bind_param("si", $coverPath, $bookId);
$stmt->execute();
$stmt->close();

header("Location: books.php?id=" . $bookId . "&cover_saved=1");
exit;