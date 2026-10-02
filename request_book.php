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

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$title = "";
$author = "";
$reason = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(400);
        exit("Invalid request.");
    }

    $title = trim((string) ($_POST["title"] ?? ""));
    $author = trim((string) ($_POST["author"] ?? ""));
    $reason = trim((string) ($_POST["reason"] ?? ""));

    if (
        mb_strlen($title) < 2 || mb_strlen($title) > 200 ||
        mb_strlen($author) < 2 || mb_strlen($author) > 150 ||
        mb_strlen($reason) > 500
    ) {
        $error = "Enter a title and author of at least 2 characters. Keep the reason under 500 characters.";
    } else {
        $stmt = $conn->prepare(
            "SELECT book_id FROM books
             WHERE LOWER(title) = LOWER(?)
               AND LOWER(author) = LOWER(?)
             LIMIT 1"
        );
        $stmt->bind_param("ss", $title, $author);
        $stmt->execute();
        $alreadyInCatalogue = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT request_id FROM book_requests
             WHERE user_id = ?
               AND LOWER(title) = LOWER(?)
               AND LOWER(author) = LOWER(?)
               AND status = 'pending'
             LIMIT 1"
        );
        $stmt->bind_param("iss", $userId, $title, $author);
        $stmt->execute();
        $alreadyPending = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($alreadyInCatalogue) {
            $error = "This book is already in the ReadSpace catalogue.";
        } elseif ($alreadyPending) {
            $error = "You already have a pending request for this book.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO book_requests (user_id, title, author, reason)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("isss", $userId, $title, $author, $reason);
            $stmt->execute();
            $stmt->close();

            header("Location: request_book.php?sent=1");
            exit;
        }
    }
}

$message = isset($_GET["sent"])
    ? "Your book request has been sent to the moderator."
    : "";

$stmt = $conn->prepare(
    "SELECT title, author, status, created_at
     FROM book_requests
     WHERE user_id = ?
     ORDER BY created_at DESC, request_id DESC
     LIMIT 30"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request a Book | ReadSpace</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #352b32;
            background: #fff;
        }

        main {
            width: min(900px, calc(100% - 36px));
            margin: 40px auto 80px;
        }

        a { color: #a33b70; }

        .back {
            display: inline-block;
            margin-bottom: 26px;
            text-decoration: none;
        }

        h1 { margin-bottom: 8px; font-size: 34px; }

        .intro {
            color: #766874;
            line-height: 1.5;
            margin-bottom: 28px;
        }

        .card {
            border: 1px solid #f0dce5;
            border-radius: 18px;
            padding: 26px;
            margin: 22px 0;
            background: #fff;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 18px 0 7px;
        }

        input, textarea {
            display: block;
            width: 100%;
            border: 1px solid #d7c8d1;
            border-radius: 9px;
            padding: 12px;
            font: inherit;
        }

        textarea { min-height: 105px; resize: vertical; }

        button {
            background: #bd5b89;
            color: #fff;
            border: 0;
            border-radius: 9px;
            padding: 12px 20px;
            margin-top: 20px;
            cursor: pointer;
            font: inherit;
        }

        button:hover { background: #9c3e6a; }

        .notice {
            border-radius: 9px;
            padding: 12px 15px;
            margin: 15px 0;
            background: #eff9f0;
        }

        .error { background: #fff0f2; }

        .request {
            border-top: 1px solid #f0dce5;
            padding: 16px 0;
        }

        .muted { color: #766874; font-size: 14px; }

        .status {
            display: inline-block;
            border-radius: 99px;
            padding: 5px 10px;
            background: #f9e9f0;
            color: #803151;
            font-size: 13px;
        }
    </style>
</head>
<body>
<main>
    <a class="back" href="profile.php?tab=stats">← Back to Profile</a>

    <h1>Request a Book</h1>
    <p class="intro">
        Can't find a book in ReadSpace? Tell us its title and author.
        A moderator will review your request.
    </p>

    <?php if ($message !== ""): ?>
        <p class="notice" role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p class="notice error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <section class="card">
        <h2>Suggest a book</h2>

        <form method="post" action="request_book.php">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <label for="title">Book title</label>
            <input id="title" name="title" value="<?= e($title) ?>"
                   required minlength="2" maxlength="200">

            <label for="author">Author</label>
            <input id="author" name="author" value="<?= e($author) ?>"
                   required minlength="2" maxlength="150">

            <label for="reason">
                Why would you like to see it here? (optional)
            </label>
            <textarea id="reason" name="reason"
                      maxlength="500"><?= e($reason) ?></textarea>

            <button type="submit">Send Request</button>
        </form>
    </section>

    <section class="card">
        <h2>My Requests</h2>

        <?php if (!$requests): ?>
            <p class="muted">You haven't requested a book yet.</p>
        <?php endif; ?>

        <?php foreach ($requests as $request): ?>
            <div class="request">
                <strong><?= e($request["title"]) ?></strong>
                by <?= e($request["author"]) ?>

                <p>
                    <span class="status">
                        <?= e(ucfirst($request["status"])) ?>
                    </span>
                    <span class="muted">
                        Submitted
                        <?= e(substr($request["created_at"], 0, 10)) ?>
                    </span>
                </p>
            </div>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html> 