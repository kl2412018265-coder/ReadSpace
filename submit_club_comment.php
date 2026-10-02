<?php
session_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: clubs.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$postId = filter_input(INPUT_POST, "post_id", FILTER_VALIDATE_INT);
$commentText = trim($_POST["comment_text"] ?? "");
$sessionToken = $_SESSION["csrf_token"] ?? "";
$postedToken = $_POST["csrf_token"] ?? "";

if (
    !$postId ||
    $sessionToken === "" ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken)
) {
    http_response_code(400);
    exit("Invalid request.");
}

if (mb_strlen($commentText) < 2 || mb_strlen($commentText) > 500) {
    http_response_code(400);
    exit("Your comment must be between 2 and 500 characters long.");
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT p.club_id, c.min_age, u.birth_date
     FROM club_posts p
     JOIN book_clubs c ON c.club_id = p.club_id
     JOIN club_memberships m
       ON m.club_id = c.club_id AND m.user_id = ?
     JOIN users u ON u.user_id = m.user_id
     WHERE p.post_id = ?
       AND p.status = 'approved'
       AND c.status = 'approved'
     LIMIT 1"
);
$stmt->bind_param("ii", $userId, $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    http_response_code(403);
    exit("Join this club before writing a comment.");
}

$age = (new DateTimeImmutable($post["birth_date"]))
    ->diff(new DateTimeImmutable("today"))->y;

if ($age < (int) $post["min_age"]) {
    http_response_code(403);
    exit("You do not meet the minimum age for this club.");
}

$stmt = $conn->prepare(
    "INSERT INTO club_post_comments
     (post_id, user_id, comment_text, status)
     VALUES (?, ?, ?, 'pending')"
);
$stmt->bind_param("iis", $postId, $userId, $commentText);
$stmt->execute();
$stmt->close();

header("Location: club.php?id=" . (int) $post["club_id"] . "&commented=1");
exit;