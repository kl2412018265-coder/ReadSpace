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

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $itemId = filter_input(INPUT_POST, "item_id", FILTER_VALIDATE_INT);
    $itemType = $_POST["item_type"] ?? "";
    $decision = $_POST["decision"] ?? "";

    $queries = [
        "review" => "UPDATE reviews
                     SET status = ?
                     WHERE review_id = ? AND status = 'pending'",

        "club" => "UPDATE book_clubs
                   SET status = ?
                   WHERE club_id = ? AND status = 'pending'",

        "post" => "UPDATE club_posts
                   SET status = ?
                   WHERE post_id = ? AND status = 'pending'",

        "comment" => "UPDATE club_post_comments
                      SET status = ?
                      WHERE comment_id = ? AND status = 'pending'"
    ];

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Invalid request. Refresh the page and try again.";
    } elseif (
        !$itemId ||
        !is_string($itemType) ||
        !in_array(
            $itemType,
            ["review", "club", "post", "comment", "book_request"],
            true
        ) ||
        !in_array(
            $decision,
            $itemType === "book_request"
                ? ["added", "rejected"]
                : ["approved", "rejected"],
            true
        )
    ) {
        $error = "Invalid selection.";
    } else {
        if ($itemType === "book_request") {
            $stmt = $conn->prepare(
                "UPDATE book_requests
                 SET status = ?, reviewed_at = NOW(), reviewed_by = ?
                 WHERE request_id = ? AND status = 'pending'"
            );
            $stmt->bind_param("sii", $decision, $userId, $itemId);
        } else {
            $stmt = $conn->prepare($queries[$itemType]);
            $stmt->bind_param("si", $decision, $itemId);
        }

        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();

        if ($updated === 0) {
            $error = "This item has already been reviewed. Refresh the page to see the latest list.";
        } else {
            header("Location: moderator.php?done=1#" . $itemType);
            exit;
        }
    }
}

$reviews = $conn->query(
    "SELECT r.review_id AS item_id, r.rating, r.review_text,
            r.created_at, u.username, b.title
     FROM reviews r
     JOIN users u ON u.user_id = r.user_id
     JOIN books b ON b.book_id = r.book_id
     WHERE r.status = 'pending'
     ORDER BY r.created_at ASC, r.review_id ASC"
);

$clubs = $conn->query(
    "SELECT c.club_id AS item_id, c.club_name, c.genre,
            c.description, c.min_age, c.created_at, u.username
     FROM book_clubs c
     JOIN users u ON u.user_id = c.created_by
     WHERE c.status = 'pending'
     ORDER BY c.created_at ASC, c.club_id ASC"
);

$posts = $conn->query(
    "SELECT p.post_id AS item_id, p.post_text AS content,
            u.username, c.club_name
     FROM club_posts p
     JOIN users u ON u.user_id = p.user_id
     JOIN book_clubs c ON c.club_id = p.club_id
     WHERE p.status = 'pending'
     ORDER BY p.created_at ASC, p.post_id ASC"
);

$comments = $conn->query(
    "SELECT cm.comment_id AS item_id,
            cm.comment_text AS content,
            p.post_text AS original_post,
            u.username, c.club_name
     FROM club_post_comments cm
     JOIN users u ON u.user_id = cm.user_id
     JOIN club_posts p ON p.post_id = cm.post_id
     JOIN book_clubs c ON c.club_id = p.club_id
     WHERE cm.status = 'pending'
     ORDER BY cm.created_at ASC, cm.comment_id ASC"
);

$bookRequests = $conn->query(
    "SELECT br.request_id AS item_id, br.title, br.author, br.reason,
            br.created_at, u.username
     FROM book_requests br
     JOIN users u ON u.user_id = br.user_id
     WHERE br.status = 'pending'
     ORDER BY br.created_at ASC, br.request_id ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Moderator Panel | ReadSpace</title>
    <style>
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            background: #fffdf9;
            color: #272536;
            font-family: Arial, sans-serif;
        }

        a { text-decoration: none; }

        header {
            max-width: 1050px;
            margin: auto;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            color: #eb597a;
            font-size: 23px;
            font-weight: 800;
        }

        .back {
            color: #272536;
            font-size: 14px;
            font-weight: bold;
        }

        main {
            max-width: 1050px;
            margin: 15px auto 80px;
            padding: 0 24px;
        }

        .heading {
            padding: 35px;
            border-radius: 20px;
            background: #fbeade;
        }

        h1 {
            margin: 0 0 10px;
            font-family: Georgia, serif;
            font-size: clamp(32px, 5vw, 43px);
        }

        .heading p {
            margin: 0;
            color: #696572;
            line-height: 1.6;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 24px 0 35px;
        }

        .tabs a {
            padding: 11px 16px;
            border-radius: 25px;
            background: #fff0f4;
            color: #a83f62;
            font-size: 14px;
            font-weight: bold;
        }

        .tabs a:hover { background: #ffe1eb; }

        .section {
            margin-top: 42px;
            scroll-margin-top: 20px;
        }

        .section h2 {
            margin: 0 0 7px;
            font-family: Georgia, serif;
            font-size: 28px;
        }

        .muted {
            color: #77737d;
            font-size: 14px;
        }

        .card {
            margin-top: 17px;
            padding: 24px;
            border: 1px solid #f1e2e7;
            border-radius: 16px;
            background: #fff;
        }

        .card h3 {
            margin: 0 0 9px;
            font-size: 21px;
            overflow-wrap: anywhere;
        }

        .card p {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .content { white-space: pre-wrap; }

        .original {
            padding: 12px 15px;
            border-radius: 10px;
            background: #fff0f4;
            color: #716d79;
            white-space: pre-wrap;
        }

        .tag {
            display: inline-block;
            margin: 5px 8px 5px 0;
            padding: 6px 11px;
            border-radius: 20px;
            background: #ffe1eb;
            color: #a83f62;
            font-size: 12px;
            font-weight: bold;
        }

        .stars {
            color: #e78548;
            font-size: 21px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            padding: 11px 18px;
            border: 0;
            border-radius: 9px;
            color: white;
            font: bold 14px Arial, sans-serif;
            cursor: pointer;
        }

        .approve { background: #2d7653; }
        .reject { background: #b84d60; }

        .notice, .error {
            margin-top: 20px;
            padding: 14px 18px;
            border-radius: 10px;
        }

        .notice {
            background: #e5f4e9;
            color: #256d40;
        }

        .error {
            background: #ffe1eb;
            color: #a52947;
        }

        .empty {
            padding: 25px;
            border: 1px dashed #eadde1;
            border-radius: 14px;
            color: #77737d;
            text-align: center;
        }

        @media (max-width: 600px) {
            header, main {
                padding-left: 18px;
                padding-right: 18px;
            }

            .heading { padding: 25px; }
            .card { padding: 19px; }
        }
    </style>
</head>
<body>
    <header>
        <a class="logo" href="index.php">✦ ReadSpace</a>
        <a class="back" href="index.php">← Back to Home</a>
    </header>

    <main>
        <section class="heading">
            <h1>Moderator Panel</h1>
            <p>Review book requests, reviews, clubs, posts, and comments in one place.</p>
        </section>

        <?php if (isset($_GET["done"])): ?>
            <p class="notice">Your decision was saved successfully.</p>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <nav class="tabs" aria-label="Moderation sections">
            <a href="#review">Reviews (<?= $reviews->num_rows ?>)</a>
            <a href="#club">Clubs (<?= $clubs->num_rows ?>)</a>
            <a href="#post">Posts (<?= $posts->num_rows ?>)</a>
            <a href="#comment">Comments (<?= $comments->num_rows ?>)</a>
            <a href="#book_request">
                Book Requests (<?= $bookRequests->num_rows ?>)
            </a>
        </nav>

        <section class="section" id="review">
            <h2>Book reviews</h2>
            <p class="muted">Reviews awaiting approval.</p>

            <?php if ($reviews->num_rows === 0): ?>
                <p class="empty">No reviews awaiting approval.</p>
            <?php endif; ?>

            <?php while ($item = $reviews->fetch_assoc()): ?>
                <article class="card">
                    <h3><?= e($item["title"]) ?></h3>
                    <p class="muted">
                        By <?= e($item["username"]) ?>
                        · <?= e($item["created_at"]) ?>
                    </p>

                    <p class="stars">
                        <?= str_repeat(
                            "★",
                            max(0, min(5, (int) $item["rating"]))
                        ) ?>
                    </p>

                    <p><?= nl2br(e($item["review_text"])) ?></p>

                    <form class="actions" method="post" action="moderator.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="item_type" value="review">
                        <input type="hidden" name="item_id"
                               value="<?= (int) $item["item_id"] ?>">

                        <button class="approve" type="submit"
                                name="decision" value="approved">
                            Approve
                        </button>
                        <button class="reject" type="submit"
                                name="decision" value="rejected">
                            Reject
                        </button>
                    </form>
                </article>
            <?php endwhile; ?>
        </section>

        <section class="section" id="club">
            <h2>Book clubs</h2>
            <p class="muted">New clubs awaiting approval.</p>

            <?php if ($clubs->num_rows === 0): ?>
                <p class="empty">No clubs awaiting approval.</p>
            <?php endif; ?>

            <?php while ($item = $clubs->fetch_assoc()): ?>
                <article class="card">
                    <h3><?= e($item["club_name"]) ?></h3>
                    <span class="tag"><?= e($item["genre"]) ?></span>
                    <span class="tag">
                        Ages <?= (int) $item["min_age"] ?>+
                    </span>

                    <p class="muted">
                        Created by <?= e($item["username"]) ?>
                        · <?= e($item["created_at"]) ?>
                    </p>

                    <p><?= nl2br(e($item["description"])) ?></p>

                    <form class="actions" method="post" action="moderator.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="item_type" value="club">
                        <input type="hidden" name="item_id"
                               value="<?= (int) $item["item_id"] ?>">

                        <button class="approve" type="submit"
                                name="decision" value="approved">
                            Approve
                        </button>
                        <button class="reject" type="submit"
                                name="decision" value="rejected">
                            Reject
                        </button>
                    </form>
                </article>
            <?php endwhile; ?>
        </section>

        <section class="section" id="post">
            <h2>Club posts</h2>
            <p class="muted">Member posts awaiting approval.</p>

            <?php if ($posts->num_rows === 0): ?>
                <p class="empty">No posts awaiting approval.</p>
            <?php endif; ?>

            <?php while ($item = $posts->fetch_assoc()): ?>
                <article class="card">
                    <h3><?= e($item["club_name"]) ?></h3>
                    <p class="muted">By <?= e($item["username"]) ?></p>
                    <p class="content"><?= e($item["content"]) ?></p>

                    <form class="actions" method="post" action="moderator.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="item_type" value="post">
                        <input type="hidden" name="item_id"
                               value="<?= (int) $item["item_id"] ?>">

                        <button class="approve" type="submit"
                                name="decision" value="approved">
                            Approve
                        </button>
                        <button class="reject" type="submit"
                                name="decision" value="rejected">
                            Reject
                        </button>
                    </form>
                </article>
            <?php endwhile; ?>
        </section>

        <section class="section" id="comment">
            <h2>Club comments</h2>
            <p class="muted">Member comments awaiting approval.</p>

            <?php if ($comments->num_rows === 0): ?>
                <p class="empty">No comments awaiting approval.</p>
            <?php endif; ?>

            <?php while ($item = $comments->fetch_assoc()): ?>
                <article class="card">
                    <h3><?= e($item["club_name"]) ?></h3>
                    <p class="muted">By <?= e($item["username"]) ?></p>

                    <p class="original">
                        Original post: <?= e($item["original_post"]) ?>
                    </p>

                    <p class="content"><?= e($item["content"]) ?></p>

                    <form class="actions" method="post" action="moderator.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="item_type" value="comment">
                        <input type="hidden" name="item_id"
                               value="<?= (int) $item["item_id"] ?>">

                        <button class="approve" type="submit"
                                name="decision" value="approved">
                            Approve
                        </button>
                        <button class="reject" type="submit"
                                name="decision" value="rejected">
                            Reject
                        </button>
                    </form>
                </article>
            <?php endwhile; ?>
        </section>

        <section class="section" id="book_request">
            <h2>Book requests</h2>
            <p class="muted">
                Check the title, author and age suitability before adding
                a requested book. Mark it as Added only after it appears
                in the catalogue.
            </p>

            <?php if ($bookRequests->num_rows === 0): ?>
                <p class="empty">No book requests awaiting review.</p>
            <?php endif; ?>

            <?php while ($item = $bookRequests->fetch_assoc()): ?>
                <article class="card">
                    <h3><?= e($item["title"]) ?></h3>
                    <p>by <?= e($item["author"]) ?></p>

                    <?php if (!empty($item["reason"])): ?>
                        <p class="content">
                            <?= nl2br(e($item["reason"])) ?>
                        </p>
                    <?php endif; ?>

                    <p class="muted">
                        Requested by <?= e($item["username"]) ?>
                        on <?= e(substr($item["created_at"], 0, 10)) ?>
                    </p>

                    <form class="actions" method="post" action="moderator.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="item_type"
                               value="book_request">
                        <input type="hidden" name="item_id"
                               value="<?= (int) $item["item_id"] ?>">

                        <button class="approve" type="submit"
                                name="decision" value="added">
                            Mark as Added
                        </button>
                        <button class="reject" type="submit"
                                name="decision" value="rejected">
                            Reject
                        </button>
                    </form>
                </article>
            <?php endwhile; ?>
        </section>
    </main>
</body>
</html>