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

$clubId = filter_input(INPUT_POST, "club_id", FILTER_VALIDATE_INT);
$postText = trim($_POST["post_text"] ?? "");
$sessionToken = $_SESSION["csrf_token"] ?? "";
$postedToken = $_POST["csrf_token"] ?? "";

if (
    !$clubId ||
    $sessionToken === "" ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken)
) {
    http_response_code(400);
    exit("Invalid request. Refresh the page and try again.");
}

if (mb_strlen($postText) < 10 || mb_strlen($postText) > 1000) {
    http_response_code(400);
    exit("Your post must be between 10 and 1000 characters long.");
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT c.min_age, u.birth_date
     FROM book_clubs c
     JOIN club_memberships m ON m.club_id = c.club_id
     JOIN users u ON u.user_id = m.user_id
     WHERE c.club_id = ?
       AND m.user_id = ?
       AND c.status = 'approved'
     LIMIT 1"
);
$stmt->bind_param("ii", $clubId, $userId);
$stmt->execute();
$access = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$access) {
    http_response_code(403);
    exit("Join this club before writing a post.");
}

$birthDate = new DateTimeImmutable($access["birth_date"]);
$age = $birthDate->diff(new DateTimeImmutable("today"))->y;

if ($age < (int) $access["min_age"]) {
    http_response_code(403);
    exit("You do not meet the minimum age for this club.");
}

$stmt = $conn->prepare(
    "INSERT INTO club_posts (club_id, user_id, post_text, status)
     VALUES (?, ?, ?, 'pending')"
);
$stmt->bind_param("iis", $clubId, $userId, $postText);
$stmt->execute();
$stmt->close();

header("Location: club.php?id=" . $clubId . "&posted=1");
exit;