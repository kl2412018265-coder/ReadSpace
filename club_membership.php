<?php
session_start();
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: clubs.php");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$sessionToken = $_SESSION["csrf_token"] ?? "";
$postedToken = $_POST["csrf_token"] ?? "";

if (
    $sessionToken === "" ||
    !is_string($postedToken) ||
    !hash_equals($sessionToken, $postedToken)
) {
    http_response_code(403);
    exit("Invalid request. Refresh the page and try again.");
}

$clubId = filter_input(INPUT_POST, "club_id", FILTER_VALIDATE_INT);
$action = $_POST["action"] ?? "";

if (!$clubId || !in_array($action, ["join", "leave"], true)) {
    http_response_code(400);
    exit("Invalid club information.");
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT c.min_age, c.status, u.birth_date
     FROM book_clubs c
     JOIN users u ON u.user_id = ?
     WHERE c.club_id = ?
     LIMIT 1"
);
$stmt->bind_param("ii", $userId, $clubId);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$club || $club["status"] !== "approved") {
    http_response_code(404);
    exit("Club not found.");
}

$birthDate = DateTimeImmutable::createFromFormat("!Y-m-d", $club["birth_date"]);

if (!$birthDate) {
    http_response_code(400);
    exit("Your account's date of birth is invalid.");
}

$age = $birthDate->diff(new DateTimeImmutable("today"))->y;

if ($age < (int) $club["min_age"]) {
    http_response_code(403);
    exit("You do not meet the minimum age for this club.");
}

if ($action === "join") {
    $stmt = $conn->prepare(
        "INSERT IGNORE INTO club_memberships (club_id, user_id)
         VALUES (?, ?)"
    );
} else {
    $stmt = $conn->prepare(
        "DELETE FROM club_memberships
         WHERE club_id = ? AND user_id = ?"
    );
}

$stmt->bind_param("ii", $clubId, $userId);
$stmt->execute();
$stmt->close();

$returnTo = $_POST["return_to"] ?? "";

if ($returnTo === "club") {
    header(
        "Location: club.php?id=" . $clubId .
        "&membership=" . $action
    );
} else {
    header("Location: clubs.php?membership=" . $action);
}
exit;