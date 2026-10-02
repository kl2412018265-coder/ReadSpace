<?php
session_start();
require "config.php";

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$clubId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$clubId) {
    http_response_code(404);
    exit("Club not found.");
}

$userId = (int) ($_SESSION["user_id"] ?? 0);

$stmt = $conn->prepare(
    "SELECT c.club_id, c.club_name, c.genre, c.description,
            c.min_age, c.status, c.created_by, u.username AS creator
     FROM book_clubs c
     JOIN users u ON u.user_id = c.created_by
     WHERE c.club_id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $clubId);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$club || $club["status"] !== "approved") {
    http_response_code(404);
    exit("Club not found.");
}

$age = 13;

if ($userId > 0) {
    $stmt = $conn->prepare(
        "SELECT birth_date, role FROM users WHERE user_id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(403);
        exit("Account not found.");
    }

    $age = (new DateTimeImmutable($user["birth_date"]))
        ->diff(new DateTimeImmutable("today"))->y;
}

if ($age < (int) $club["min_age"]) {
    http_response_code(403);
    exit("You do not meet the minimum age to view this club.");
}

$stmt = $conn->prepare(
    "SELECT u.username
     FROM club_memberships m
     JOIN users u ON u.user_id = m.user_id
     WHERE m.club_id = ?
     ORDER BY m.joined_at ASC"
);
$stmt->bind_param("i", $clubId);
$stmt->execute();
$members = $stmt->get_result();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$isMember = false;

if ($userId > 0) {
    $memberStmt = $conn->prepare(
        "SELECT 1
         FROM club_memberships
         WHERE club_id = ? AND user_id = ?
         LIMIT 1"
    );
    $memberStmt->bind_param("ii", $clubId, $userId);
    $memberStmt->execute();
    $isMember = (bool) $memberStmt->get_result()->fetch_assoc();
    $memberStmt->close();
}

$postStmt = $conn->prepare(
    "SELECT p.post_id, p.post_text, p.status, p.created_at,
            u.username,
            (SELECT COUNT(*)
             FROM club_post_likes l
             WHERE l.post_id = p.post_id) AS like_count
     FROM club_posts p
     JOIN users u ON u.user_id = p.user_id
     WHERE p.club_id = ?
       AND (
           p.status = 'approved'
           OR (
               p.user_id = ?
               AND p.status IN ('pending', 'rejected')
           )
       )
     ORDER BY p.created_at DESC, p.post_id DESC"
);
$postStmt->bind_param("ii", $clubId, $userId);
$postStmt->execute();
$posts = $postStmt->get_result();

$likedPostIds = [];

if ($userId > 0) {
    $likeStmt = $conn->prepare(
        "SELECT l.post_id
         FROM club_post_likes l
         JOIN club_posts p ON p.post_id = l.post_id
         WHERE l.user_id = ? AND p.club_id = ?"
    );
    $likeStmt->bind_param("ii", $userId, $clubId);
    $likeStmt->execute();
    $likes = $likeStmt->get_result();

    while ($like = $likes->fetch_assoc()) {
        $likedPostIds[(int) $like["post_id"]] = true;
    }

    $likeStmt->close();
}

$commentsByPost = [];

$commentStmt = $conn->prepare(
    "SELECT cm.post_id, cm.comment_text, cm.status,
            cm.created_at, u.username
     FROM club_post_comments cm
     JOIN club_posts p ON p.post_id = cm.post_id
     JOIN users u ON u.user_id = cm.user_id
     WHERE p.club_id = ?
       AND p.status = 'approved'
       AND (
           cm.status = 'approved'
           OR (
               cm.user_id = ?
               AND cm.status IN ('pending', 'rejected')
           )
       )
     ORDER BY cm.created_at ASC, cm.comment_id ASC"
);
$commentStmt->bind_param("ii", $clubId, $userId);
$commentStmt->execute();
$commentRows = $commentStmt->get_result();

while ($comment = $commentRows->fetch_assoc()) {
    $commentsByPost[(int) $comment["post_id"]][] = $comment;
}

$commentStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($club["club_name"]) ?> | ReadSpace</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #fffdf9;
            color: #272536;
            font-family: Arial, sans-serif;
        }

        a {
            color: #c74770;
            text-decoration: none;
            font-weight: bold;
        }

        .nav {
            max-width: 1050px;
            margin: auto;
            padding: 24px;
        }

        main {
            max-width: 1050px;
            margin: auto;
            padding: 16px 24px 80px;
        }

        .hero {
            padding: 48px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fbeade, #f6b4c8);
        }

        h1 {
            margin: 12px 0;
            font-family: Georgia, serif;
            font-size: clamp(36px, 6vw, 58px);
        }

        .eyebrow {
            color: #aa4164;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-top: 30px;
        }

        .card {
            padding: 30px;
            border: 1px solid #f1e5e6;
            border-radius: 18px;
            background: white;
        }

        .description {
            line-height: 1.8;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .tag {
            display: inline-block;
            margin: 6px 6px 0 0;
            padding: 8px 12px;
            border-radius: 20px;
            background: #ffe1eb;
            color: #aa4164;
            font-size: 13px;
            font-weight: bold;
        }

        .member {
            padding: 12px 0;
            border-bottom: 1px solid #f3e9e9;
            overflow-wrap: anywhere;
        }

        .member:last-child { border-bottom: 0; }

        .discussion {
            margin-top: 25px;
        }

        .discussion label {
            display: block;
            margin: 20px 0 10px;
            font-weight: bold;
        }

        .discussion textarea {
            width: 100%;
            padding: 14px;
            border: 1px solid #e5d9dc;
            border-radius: 12px;
            font: inherit;
            resize: vertical;
        }

        .discussion button {
            margin: 14px 0 25px;
            padding: 12px 20px;
            border: 0;
            border-radius: 10px;
            background: #272536;
            color: white;
            font: bold 14px Arial, sans-serif;
            cursor: pointer;
        }

        .post {
            margin-top: 15px;
            padding: 20px;
            border-radius: 12px;
            background: #fff0f4;
        }

        .post p {
            margin-bottom: 0;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .post-notice {
            padding: 14px;
            border-radius: 10px;
            background: #e5f4e9;
            color: #256d40;
        }

        .membership-button {
            margin-top: 22px;
            padding: 12px 22px;
            border: 0;
            border-radius: 10px;
            background: #272536;
            color: white;
            font: bold 14px Arial, sans-serif;
            cursor: pointer;
        }

        .post-date {
            display: block;
            margin-top: 5px;
            color: #77737d;
            font-size: 12px;
        }

        .like-button {
            margin: 12px 0 0;
            padding: 8px 13px;
            border: 1px solid #edb8c8;
            border-radius: 20px;
            background: white;
            color: #bb4268;
            font: bold 13px Arial, sans-serif;
            cursor: pointer;
        }

        .comments {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid #edcfda;
        }

        .comment {
            margin: 12px 0;
            padding: 12px 15px;
            border-radius: 10px;
            background: white;
        }

        .comment small { color: #aa4164; }

        .comment p {
            margin: 7px 0 0;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .comments textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5d9dc;
            border-radius: 10px;
            font: inherit;
        }

        .comments button {
            margin: 10px 0 0;
        }

        @media (max-width: 700px) {
            .hero { padding: 32px; }
            .details { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <nav class="nav">
        <a href="clubs.php">← Back to Clubs</a>
    </nav>

    <main>
        <section class="hero">
            <span class="eyebrow">ReadSpace Club</span>
            <h1><?= e($club["club_name"]) ?></h1>
            <p>Created by <?= e($club["creator"]) ?></p>
            <span class="tag"><?= e($club["genre"]) ?></span>
            <span class="tag">Ages <?= (int) $club["min_age"] ?>+</span>

            <?php if ($userId > 0): ?>
                <form method="post" action="club_membership.php">
                    <input
                        type="hidden"
                        name="club_id"
                        value="<?= (int) $clubId ?>"
                    >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["csrf_token"]) ?>"
                    >
                    <input type="hidden" name="return_to" value="club">
                    <input
                        type="hidden"
                        name="action"
                        value="<?= $isMember ? "leave" : "join" ?>"
                    >

                    <button class="membership-button" type="submit">
                        <?= $isMember ? "Leave Club" : "Join Club" ?>
                    </button>
                </form>
            <?php else: ?>
                <p>
                    <a href="login.php">Log in to join this club</a>
                </p>
            <?php endif; ?>
        </section>

        <div class="details">
            <section class="card">
                <h2>About this club</h2>
                <p class="description"><?= e($club["description"]) ?></p>
            </section>

            <section class="card">
                <h2>Club members (<?= $members->num_rows ?>)</h2>

                <?php if ($members->num_rows === 0): ?>
                    <p>No members yet.</p>
                <?php else: ?>
                    <?php while ($member = $members->fetch_assoc()): ?>
                        <div class="member">
                            ✦ <?= e($member["username"]) ?>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </section>
        </div>

        <section class="card discussion">
            <h2>Club discussion</h2>

            <?php if (isset($_GET["posted"])): ?>
                <p class="post-notice">
                    Your post was submitted and is awaiting approval.
                </p>
            <?php endif; ?>

            <?php if ($isMember): ?>
                <form method="post" action="submit_club_post.php">
                    <input
                        type="hidden"
                        name="club_id"
                        value="<?= (int) $clubId ?>"
                    >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["csrf_token"]) ?>"
                    >

                    <label for="post_text">
                        Share something with your club
                    </label>
                    <textarea
                        id="post_text"
                        name="post_text"
                        rows="4"
                        minlength="10"
                        maxlength="1000"
                        required
                        placeholder="What are you reading right now?"
                    ></textarea>

                    <button type="submit">Submit Post</button>
                </form>
            <?php else: ?>
                <p>Join this club to write a post.</p>
            <?php endif; ?>

            <h3>Member posts</h3>

            <?php if ($posts->num_rows === 0): ?>
                <p>No posts yet. Start the first discussion!</p>
            <?php else: ?>
                <?php while ($post = $posts->fetch_assoc()): ?>
                    <article class="post">
                        <strong><?= e($post["username"]) ?></strong>

                        <small class="post-date">
                            <?= e(date("d M Y, h:i A", strtotime($post["created_at"]))) ?>
                        </small>

                        <?php if ($post["status"] === "pending"): ?>
                            <span class="tag">Pending approval</span>
                        <?php elseif ($post["status"] === "rejected"): ?>
                            <span class="tag">Rejected</span>
                        <?php endif; ?>

                        <p><?= e($post["post_text"]) ?></p>

                        <?php if ($post["status"] === "approved"): ?>
                            <?php if ($isMember): ?>
                                <form method="post" action="like_club_post.php">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["csrf_token"]) ?>"
                                    >
                                    <input
                                        type="hidden"
                                        name="post_id"
                                        value="<?= (int) $post["post_id"] ?>"
                                    >

                                    <button class="like-button" type="submit">
                                        <?= isset($likedPostIds[(int) $post["post_id"]])
                                            ? "♥ Liked"
                                            : "♡ Like" ?>
                                        · <?= (int) $post["like_count"] ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <small>
                                    <?= (int) $post["like_count"] ?> likes
                                </small>
                            <?php endif; ?>

                            <div class="comments">
                                <h4>Comments</h4>

                                <?php foreach (
                                    $commentsByPost[(int) $post["post_id"]] ?? []
                                    as $comment
                                ): ?>
                                    <div class="comment">
                                        <strong><?= e($comment["username"]) ?></strong>

                                        <?php if ($comment["status"] === "pending"): ?>
                                            <small>· Pending approval</small>
                                        <?php elseif ($comment["status"] === "rejected"): ?>
                                            <small>· Rejected</small>
                                        <?php endif; ?>

                                        <p><?= e($comment["comment_text"]) ?></p>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($isMember): ?>
                                    <form
                                        method="post"
                                        action="submit_club_comment.php"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION["csrf_token"]) ?>"
                                        >
                                        <input
                                            type="hidden"
                                            name="post_id"
                                            value="<?= (int) $post["post_id"] ?>"
                                        >

                                        <label
                                            for="comment-<?= (int) $post["post_id"] ?>"
                                        >
                                            Write a comment
                                        </label>
                                        <textarea
                                            id="comment-<?= (int) $post["post_id"] ?>"
                                            name="comment_text"
                                            rows="2"
                                            minlength="2"
                                            maxlength="500"
                                            required
                                        ></textarea>

                                        <button type="submit">
                                            Submit Comment
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>
    </main>

    <script>
        document.querySelectorAll('form[action="like_club_post.php"]').forEach(form => {
            form.addEventListener("submit", async event => {
                event.preventDefault();

                const button = form.querySelector("button");
                const wasLiked = button.textContent.includes("Liked");
                const count = Number(button.textContent.match(/\d+\s*$/)?.[0] ?? 0);

                button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: "POST",
                        body: new FormData(form),
                        credentials: "same-origin"
                    });

                    if (!response.ok) {
                        throw new Error("Your like could not be saved.");
                    }

                    const newCount = wasLiked ? count - 1 : count + 1;
                    button.textContent = wasLiked
                        ? `♡ Like · ${newCount}`
                        : `♥ Liked · ${newCount}`;
                } catch (error) {
                    alert(error.message);
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
</body>
</html>