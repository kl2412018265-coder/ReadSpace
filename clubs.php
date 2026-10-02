<?php
session_start();
require "config.php";

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$isLoggedIn = isset($_SESSION["user_id"]);
$userId = $isLoggedIn ? (int) $_SESSION["user_id"] : 0;
$age = 13;
$error = "";

if ($isLoggedIn) {
    $stmt = $conn->prepare(
        "SELECT birth_date FROM users WHERE user_id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();

    if (!$account) {
        $_SESSION = [];
        session_destroy();
        header("Location: login.php");
        exit;
    }

    $age = (new DateTimeImmutable($account["birth_date"]))
        ->diff(new DateTimeImmutable("today"))->y;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$isLoggedIn) {
        header("Location: login.php");
        exit;
    }

    $token = $_POST["csrf_token"] ?? "";
    $name = trim($_POST["club_name"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $minAge = filter_input(
        INPUT_POST,
        "min_age",
        FILTER_VALIDATE_INT
    );

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Invalid form. Refresh the page and try again.";
    } elseif (
        mb_strlen($name) < 3 ||
        mb_strlen($name) > 100 ||
        mb_strlen($genre) < 2 ||
        mb_strlen($genre) > 50 ||
        mb_strlen($description) < 10 ||
        mb_strlen($description) > 1000
    ) {
        $error = "Check the club name, genre, and description.";
    } elseif (
        !in_array($minAge, [13, 16, 18], true) ||
        $minAge > $age
    ) {
        $error = "Choose an age limit that is appropriate for your age.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO book_clubs
             (club_name, genre, description, created_by, min_age, status)
             VALUES (?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->bind_param(
            "sssii",
            $name,
            $genre,
            $description,
            $userId,
            $minAge
        );
        $stmt->execute();

        header("Location: clubs.php?created=1");
        exit;
    }
}

if ($isLoggedIn) {
    $stmt = $conn->prepare(
        "SELECT c.club_id, c.club_name, c.genre, c.description,
                c.min_age, c.status, u.username,
                (SELECT COUNT(*)
                 FROM club_memberships m
                 WHERE m.club_id = c.club_id) AS member_count
         FROM book_clubs c
         JOIN users u ON u.user_id = c.created_by
         WHERE c.min_age <= ?
           AND (
               c.status = 'approved'
               OR (c.created_by = ? AND c.status = 'pending')
           )
         ORDER BY c.created_at DESC, c.club_id DESC"
    );
    $stmt->bind_param("ii", $age, $userId);
} else {
    $stmt = $conn->prepare(
        "SELECT c.club_id, c.club_name, c.genre, c.description,
                c.min_age, c.status, u.username,
                (SELECT COUNT(*)
                 FROM club_memberships m
                 WHERE m.club_id = c.club_id) AS member_count
         FROM book_clubs c
         JOIN users u ON u.user_id = c.created_by
         WHERE c.min_age <= ?
           AND c.status = 'approved'
         ORDER BY c.created_at DESC, c.club_id DESC"
    );
    $stmt->bind_param("i", $age);
}

$stmt->execute();
$clubs = $stmt->get_result();

$joinedClubIds = [];

if ($isLoggedIn) {
    $memberStmt = $conn->prepare(
        "SELECT club_id FROM club_memberships WHERE user_id = ?"
    );
    $memberStmt->bind_param("i", $userId);
    $memberStmt->execute();
    $memberships = $memberStmt->get_result();

    while ($membership = $memberships->fetch_assoc()) {
        $joinedClubIds[(int) $membership["club_id"]] = true;
    }

    $memberStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Clubs | ReadSpace</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #fffdf9;
            color: #272536;
            font-family: Arial, sans-serif;
        }

        a { text-decoration: none; }

        .nav {
            max-width: 1180px;
            margin: auto;
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            color: #eb597a;
            font-size: 24px;
            font-weight: 800;
        }

        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .links a {
            color: #343244;
            font-size: 14px;
            font-weight: bold;
        }

        main {
            max-width: 1180px;
            margin: auto;
            padding: 25px 28px 80px;
        }

        .hero {
            padding: 45px;
            border-radius: 24px;
            background: #fbeade;
        }

        .eyebrow {
            color: #d65a75;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            margin: 12px 0;
            font-size: clamp(36px, 5vw, 55px);
        }

        h2 { font-size: 27px; }

        .muted {
            color: #716d79;
            line-height: 1.6;
        }

        .layout {
            display: block;
            margin-top: 40px;
        }

        .layout > section:first-child {
            margin-bottom: 55px;
        }

        .club-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
        }

        .club-card {
            min-width: 0;
            padding: 0;
            border: 0;
            background: transparent;
        }

        .club-art {
            height: 255px;
            padding: 28px;
            display: grid;
            place-items: end center;
            overflow: hidden;
            border-radius: 25px;
            background: linear-gradient(140deg, #eed7bd, #e994a0);
        }

        .club-card:nth-child(4n+2) .club-art {
            background: linear-gradient(140deg, #abbc87, #507d75);
        }

        .club-card:nth-child(4n+3) .club-art {
            background: linear-gradient(140deg, #ffaaa0, #f36a83);
        }

        .club-card:nth-child(4n) .club-art {
            background: linear-gradient(140deg, #c2acd9, #7870ae);
        }

        .book-poster {
            width: 62%;
            height: 205px;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            transform: rotate(-4deg) translateY(12px);
            border: 2px solid #ffffffa8;
            border-radius: 5px 12px 12px 5px;
            background: #fff8ed;
            box-shadow: 12px 14px 22px #30253645;
        }

        .club-card:nth-child(even) .book-poster {
            transform: rotate(4deg) translateY(12px);
            background: #f8eaf0;
        }

        .poster-small {
            color: #b76374;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .poster-title {
            max-width: 100%;
            color: #302536;
            font-family: Georgia, serif;
            font-size: 19px;
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .poster-icon { font-size: 17px; }

        .club-info {
            position: relative;
            padding: 24px 5px 0;
        }

        .creator-avatar {
            position: absolute;
            top: -19px;
            left: 5px;
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 3px solid #fffdf9;
            border-radius: 50%;
            background: #f7b0c4;
            color: #593044;
            font-weight: bold;
        }

        .club-info .creator {
            margin: 8px 0 9px;
            color: #77737d;
            font-size: 12px;
        }

        .club-info h3 {
            margin: 0 0 9px;
            font-family: Georgia, serif;
            font-size: 24px;
            font-weight: normal;
            line-height: 1.2;
        }

        .club-info h3 a { color: inherit; }
        .club-info h3 a:hover { color: #c74770; }

        .club-info .description {
            min-height: 48px;
            margin: 0 0 16px;
            font-size: 14px;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .club-tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .tag {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            background: #ffe1eb;
            color: #aa4164;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fff2d8;
            color: #956014;
        }

        .create-card {
            max-width: 720px;
            margin: 0 auto;
            padding: 26px;
            border: 1px solid #efebe6;
            border-radius: 17px;
            background: white;
        }

        .create-card h2 { margin-top: 0; }

        label {
            display: block;
            margin: 17px 0 7px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd7d4;
            border-radius: 9px;
            background: white;
            font: inherit;
        }

        textarea { resize: vertical; }

        button,
        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            border: 0;
            border-radius: 9px;
            background: #272639;
            color: white;
            font: bold 14px Arial, sans-serif;
            cursor: pointer;
        }

        .notice {
            padding: 14px 18px;
            border-radius: 10px;
            background: #e5f4e9;
            color: #256d40;
        }

        .error {
            padding: 14px 18px;
            border-radius: 10px;
            background: #ffe6ec;
            color: #a52947;
        }

        @media (max-width: 1000px) {
            .club-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .nav { flex-wrap: wrap; }
            .hero { padding: 32px; }
        }

        @media (max-width: 580px) {
            .club-list { grid-template-columns: 1fr; }
            .club-art { height: 290px; }
        }
    </style>
</head>

<body>
    <header class="nav">
        <a class="logo" href="index.php">✦ ReadSpace</a>

        <nav class="links">
            <a href="index.php">Home</a>
            <a href="books.php">Books</a>
            <a href="clubs.php">Clubs</a>
            <?php if (!$isLoggedIn): ?>
                <a href="login.php">Log In</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="hero">
            <span class="eyebrow">Read together</span>
            <h1>Find your book club.</h1>
            <p class="muted">
                Meet readers who share your love for your favorite genres.
            </p>
        </section>

        <div class="layout">
            <section>
                <h2>Reader clubs</h2>

                <?php if (isset($_GET["created"])): ?>
                    <p class="notice">
                        Your club was submitted and is awaiting approval.
                    </p>
                <?php endif; ?>

                <div class="club-list">
                    <?php if ($clubs->num_rows === 0): ?>
                        <p class="muted">
                            No clubs are available to display yet.
                        </p>
                    <?php endif; ?>

                    <?php while ($club = $clubs->fetch_assoc()): ?>
                        <article class="club-card">
                            <div class="club-art" aria-hidden="true">
                                <div class="book-poster">
                                    <span class="poster-small">READSPACE CLUB</span>
                                    <span class="poster-title">
                                        <?= e($club["club_name"]) ?>
                                    </span>
                                    <span class="poster-icon">✦ 📖 ✦</span>
                                </div>
                            </div>

                            <div class="club-info">
                                <div class="creator-avatar" aria-hidden="true">
                                    <?= e(mb_strtoupper(
                                        mb_substr($club["username"], 0, 1)
                                    )) ?>
                                </div>

                                <p class="creator">
                                    Created by <?= e($club["username"]) ?>
                                </p>

                                <h3>
                                    <?php if ($club["status"] === "approved"): ?>
                                        <a href="club.php?id=<?= (int) $club["club_id"] ?>">
                                            <?= e($club["club_name"]) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= e($club["club_name"]) ?>
                                    <?php endif; ?>
                                </h3>

                                <p class="description">
                                    <?= e($club["description"]) ?>
                                </p>

                                <div class="club-tags">
                                    <span class="tag"><?= e($club["genre"]) ?></span>
                                    <span class="tag">
                                        Ages <?= (int) $club["min_age"] ?>+
                                    </span>

                                    <?php if ($club["status"] === "approved"): ?>
                                        <span class="tag">
                                            <?= (int) $club["member_count"] ?>
                                            members
                                        </span>
                                    <?php else: ?>
                                        <span class="tag pending">
                                            Pending approval
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($club["status"] === "approved"): ?>
                                    <?php if ($isLoggedIn): ?>
                                        <?php
                                        $isMember = isset(
                                            $joinedClubIds[(int) $club["club_id"]]
                                        );
                                        ?>

                                        <form method="post" action="club_membership.php">
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION["csrf_token"]) ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="club_id"
                                                value="<?= (int) $club["club_id"] ?>"
                                            >
                                            <input
                                                type="hidden"
                                                name="action"
                                                value="<?= $isMember ? "leave" : "join" ?>"
                                            >

                                            <button type="submit">
                                                <?= $isMember ? "Leave Club" : "Join Club" ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a class="button" href="login.php">
                                            Log In to Join
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            </section>

            <section class="create-card">
                <h2>Create a Club</h2>

                <?php if ($isLoggedIn): ?>
                    <p class="muted">
                        Create a space for your favorite genre.
                        Your club will be reviewed before it appears publicly.
                    </p>

                    <?php if ($error !== ""): ?>
                        <p class="error"><?= e($error) ?></p>
                    <?php endif; ?>

                    <form method="post" action="clubs.php">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <label for="club_name">Club name</label>
                        <input
                            id="club_name"
                            name="club_name"
                            maxlength="100"
                            minlength="3"
                            required
                        >

                        <label for="genre">Genre</label>
                        <input
                            id="genre"
                            name="genre"
                            maxlength="50"
                            required
                        >

                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            minlength="10"
                            maxlength="1000"
                            required
                        ></textarea>

                        <label for="min_age">Minimum age</label>
                        <select id="min_age" name="min_age" required>
                            <?php foreach ([13, 16, 18] as $limit): ?>
                                <?php if ($age >= $limit): ?>
                                    <option value="<?= $limit ?>">
                                        <?= $limit ?>+
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit">Submit Club</button>
                    </form>
                <?php else: ?>
                    <p class="muted">
                        Log in to create your own book club.
                    </p>
                    <a class="button" href="login.php">
                        Log In
                    </a>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>