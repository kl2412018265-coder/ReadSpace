<?php
session_start();
require "config.php";

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT role FROM users WHERE user_id = ? LIMIT 1"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user["role"] !== "moderator") {
    http_response_code(403);
    exit("This page is for moderators only.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$bookId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$bookId || $bookId < 1) {
    http_response_code(400);
    exit("Invalid book ID.");
}

$stmt = $conn->prepare(
    "SELECT book_id, title, author, summary
     FROM books
     WHERE book_id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $bookId);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    http_response_code(404);
    exit("Book not found.");
}

$title = $book["title"];
$summary = $book["summary"] ?? "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $title = trim((string) ($_POST["title"] ?? ""));
    $summary = trim((string) ($_POST["summary"] ?? ""));

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Invalid form. Refresh the page and try again.";
    } elseif (
        $title === "" ||
        mb_strlen($title) > 255 ||
        mb_strlen($summary) > 5000
    ) {
        $error = "Enter a title under 255 characters and a summary under 5000 characters.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE books
             SET title = ?, summary = ?
             WHERE book_id = ?"
        );
        $stmt->bind_param("ssi", $title, $summary, $bookId);
        $stmt->execute();
        $stmt->close();

        header("Location: books.php?id=" . $bookId);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Book | ReadSpace</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #fffdf9;
            color: #272536;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 720px;
            margin: 50px auto;
            padding: 0 20px;
        }

        a {
            color: #ad426f;
            text-decoration: none;
            font-weight: bold;
        }

        .card {
            margin-top: 25px;
            padding: 30px;
            border: 1px solid #f1e2e7;
            border-radius: 18px;
            background: #fff;
        }

        h1 {
            margin-top: 0;
            font-family: Georgia, serif;
        }

        .muted { color: #77737d; }

        label {
            display: block;
            margin: 22px 0 8px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d7c8d1;
            border-radius: 9px;
            font: inherit;
        }

        textarea {
            min-height: 220px;
            resize: vertical;
        }

        button {
            margin-top: 22px;
            padding: 12px 20px;
            border: 0;
            border-radius: 9px;
            background: #bd5b89;
            color: #fff;
            font: bold 15px Arial, sans-serif;
            cursor: pointer;
        }

        .error {
            padding: 12px;
            border-radius: 9px;
            background: #fff0f2;
            color: #a52947;
        }
    </style>
</head>
<body>
<main>
    <a href="books.php?id=<?= $bookId ?>">← Back to Book</a>

    <div class="card">
        <h1>Edit Book</h1>
        <p class="muted">by <?= e($book["author"]) ?></p>

        <?php if ($error !== ""): ?>
            <p class="error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post"
              action="edit_book.php?id=<?= $bookId ?>">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <label for="title">Book title</label>
            <input id="title" name="title"
                   value="<?= e($title) ?>"
                   maxlength="255" required>

            <label for="summary">Description / Summary</label>
            <textarea id="summary" name="summary"
                      maxlength="5000"><?= e($summary) ?></textarea>

            <button type="submit">Save Changes</button>
        </form>
    </div>
</main>
</body>
</html>