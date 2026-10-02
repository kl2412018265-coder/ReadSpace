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
$tab = $_GET["tab"] ?? "stats";

if (!in_array($tab, ["stats", "clubs", "reviews"], true)) {
    $tab = "stats";
}

$stmt = $conn->prepare(
    "SELECT username, email, birth_date, role
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}

$age = (new DateTimeImmutable($user["birth_date"]))
    ->diff(new DateTimeImmutable("today"))->y;

$stmt = $conn->prepare(
    "SELECT c.club_id, c.club_name, c.genre
     FROM club_memberships m
     JOIN book_clubs c ON c.club_id = m.club_id
     WHERE m.user_id = ?
       AND c.status = 'approved'
       AND c.min_age <= ?
     ORDER BY m.joined_at DESC"
);
$stmt->bind_param("ii", $userId, $age);
$stmt->execute();
$clubs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
    "SELECT r.rating, r.review_text, r.status,
            b.title, b.book_id, b.cover_image_url, b.genre
     FROM reviews r
     JOIN books b ON b.book_id = r.book_id
     WHERE r.user_id = ?
       AND (b.is_mature_content = 0 OR ? >= 18)
     ORDER BY r.created_at DESC, r.review_id DESC"
);
$stmt->bind_param("ii", $userId, $age);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$approvedBookIds = [];
$ratingTotal = 0;
$ratingCount = 0;

foreach ($reviews as $review) {
    if ($review["status"] === "approved") {
        $approvedBookIds[(int) $review["book_id"]] = true;
        $ratingTotal += (int) $review["rating"];
        $ratingCount++;
    }
}

$averageRating = $ratingCount > 0
    ? number_format($ratingTotal / $ratingCount, 1)
    : "—";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$year = (int) date("Y");

$stmt = $conn->prepare(
    "SELECT target_books
     FROM reading_goals
     WHERE user_id = ? AND goal_year = ?
     LIMIT 1"
);
$stmt->bind_param("ii", $userId, $year);
$stmt->execute();
$goal = $stmt->get_result()->fetch_assoc();
$stmt->close();

$targetBooks = $goal ? (int) $goal["target_books"] : 0;

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM finished_books
     WHERE user_id = ?
       AND YEAR(finished_at) = ?"
);
$stmt->bind_param("ii", $userId, $year);
$stmt->execute();
$booksRead = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$progress = $targetBooks > 0
    ? min(100, round($booksRead / $targetBooks * 100))
    : 0;

$finishedBookIds = [];

$stmt = $conn->prepare(
    "SELECT book_id
     FROM finished_books
     WHERE user_id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$finishedRows = $stmt->get_result();

while ($row = $finishedRows->fetch_assoc()) {
    $finishedBookIds[(int) $row["book_id"]] = true;
}

$stmt->close();

$favoriteGenre = "";

foreach ($reviews as $review) {
    if (
        $review["status"] === "approved"
        && (int) $review["rating"] >= 4
    ) {
        $favoriteGenre = $review["genre"];
        break;
    }
}

$stmt = $conn->prepare(
    "SELECT b.book_id, b.title, b.author, b.genre, b.cover_image_url
     FROM books b
     WHERE (b.is_mature_content = 0 OR ? >= 18)
       AND NOT EXISTS (
           SELECT 1
           FROM reviews r
           WHERE r.book_id = b.book_id AND r.user_id = ?
       )
     ORDER BY CASE WHEN b.genre = ? THEN 0 ELSE 1 END,
              b.book_id DESC
     LIMIT 4"
);
$stmt->bind_param("iis", $age, $userId, $favoriteGenre);

$stmt->execute();
$recommendations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$stmt = $conn->prepare(
    "SELECT b.book_id, b.title, b.author, b.cover_image_url
     FROM reading_list rl
     JOIN books b ON b.book_id = rl.book_id
     WHERE rl.user_id = ?
       AND rl.status = 'reading'
       AND (b.is_mature_content = 0 OR ? >= 18)
     ORDER BY rl.added_at DESC
     LIMIT 6"
);
$stmt->bind_param("ii", $userId, $age);
$stmt->execute();
$currentlyReading = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$stmt = $conn->prepare(
    "SELECT b.book_id, b.title, b.author, b.cover_image_url, f.finished_at
     FROM finished_books f
     JOIN books b ON b.book_id = f.book_id
     WHERE f.user_id = ?
     ORDER BY f.finished_at DESC
     LIMIT 4"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$finishedBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$stmt = $conn->prepare(
    "SELECT b.book_id, b.title, b.author, b.cover_image_url
     FROM reading_list rl
     JOIN books b ON b.book_id = rl.book_id
     WHERE rl.user_id = ?
       AND rl.status = 'want_to_read'
       AND (b.is_mature_content = 0 OR ? >= 18)
     ORDER BY rl.added_at DESC
     LIMIT 6"
);
$stmt->bind_param("ii", $userId, $age);
$stmt->execute();
$wantToRead = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile | ReadSpace</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #faf9f8;
            color: #282536;
            font-family: Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            max-width: 1050px;
            margin: auto;
            padding: 28px 24px 90px;
        }

        .back {
            color: #c34e76;
            font-size: 14px;
            font-weight: bold;
        }

        .profile-head {
            display: flex;
            align-items: center;
            gap: 18px;
            margin: 38px 0 24px;
        }

        .avatar {
            width: 76px;
            height: 76px;
            flex: none;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #ffdce9;
            color: #a53462;
            font-size: 32px;
            font-weight: bold;
        }

        .profile-head h1 {
            margin: 0 0 5px;
            font-family: Georgia, serif;
            font-size: 38px;
        }

        .email {
            margin: 0;
            color: #827a81;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            gap: 36px;
            overflow-x: auto;
            border-bottom: 1px solid #e9e5e5;
        }

        .tabs a {
            flex: none;
            padding: 17px 7px;
            color: #77717a;
            font-size: 16px;
            font-weight: bold;
        }

        .tabs a:hover { color: #bd4773; }

        .tabs a.active {
            color: #282536;
            border-bottom: 4px solid #bd4773;
        }

        .stats-card {
            max-width: 700px;
            margin: 45px auto 30px;
            padding: 32px;
            border-radius: 38px;
            background: linear-gradient(
                155deg,
                #f575aa 5%,
                #fb8298 52%,
                #ff742e 100%
            );
            color: white;
        }

        .stats-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 28px;
        }

        .pill {
            padding: 10px 17px;
            border: 1px solid #ffffffa8;
            border-radius: 30px;
            font-size: 14px;
            font-weight: bold;
        }

        .stats-inner {
            padding: 30px 20px 20px;
            border-radius: 32px;
            background: #9c35503b;
            text-align: center;
        }

        .stats-inner h2 {
            margin: 0 0 8px;
            font-size: 17px;
        }

        .stats-inner > p {
            max-width: 470px;
            margin: 0 auto 28px;
            font-size: 24px;
            font-weight: bold;
            line-height: 1.3;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .stat {
            min-height: 160px;
            padding: 18px 10px;
            border-radius: 22px;
            background: #ffffff24;
        }

        .stat-icon {
            display: grid;
            place-items: center;
            width: 47px;
            height: 47px;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: white;
            color: #dc6490;
            font-size: 22px;
        }

        .stat span {
            display: block;
            font-size: 13px;
            font-weight: bold;
        }

        .stat strong {
            display: block;
            margin-top: 5px;
            font-family: Georgia, serif;
            font-size: 34px;
        }

        .goal-card {
           max-width: 100%;
            margin: 0;
           padding: 12px;
           border: 1px solid #f3dfe7;
           border-radius: 28px;
           background: #fff0f5;
         }

        .goal-inner {
            padding: 34px 25px;
            border-radius: 30px;
            background: #f7f3ef;
            text-align: center;
        }

        .goal-inner h2 {
            margin: 5px 0;
            color: #d9578c;
            font-family: Georgia, serif;
            font-size: 40px;
        }

        .goal-inner p {
            color: #766d72;
            line-height: 1.6;
        }

        .goal-art {
            margin: 20px 0;
            font-size: 50px;
            letter-spacing: 8px;
        }

        .goal-track {
            height: 13px;
            overflow: hidden;
            border-radius: 20px;
            background: #e6dedb;
        }

        .goal-fill {
            height: 100%;
            border-radius: 20px;
            background: linear-gradient(90deg, #ef7da8, #ff763f);
        }

        .goal-form {
            max-width: 310px;
            margin: 25px auto 0;
            text-align: left;
        }

        .goal-form label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .goal-form input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ddd3d0;
            border-radius: 9px;
            font: inherit;
        }

        .goal-form button,
        .finished-button {
            margin-top: 12px;
            padding: 11px 18px;
            border: 0;
            border-radius: 9px;
            background: #292536;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .section {
            margin-top: 38px;
        }

        .section h2 {
            font-family: Georgia, serif;
            font-size: 30px;
        }

        .items {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .item {
            min-width: 0;
            padding: 20px;
            border: 1px solid #f0e5e8;
            border-radius: 18px;
            background: white;
        }

        .item a {
            color: #bb476f;
            font-weight: bold;
        }

        .item p {
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .tag {
            display: inline-block;
            margin: 8px 6px 0 0;
            padding: 7px 11px;
            border-radius: 20px;
            background: #ffe1eb;
            color: #aa4164;
            font-size: 12px;
            font-weight: bold;
        }

        .club-art {
            height: 195px;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
            overflow: hidden;
            border-radius: 15px;
            background: linear-gradient(135deg, #eed7bd, #e994a0);
        }

        .club-item:nth-child(even) .club-art {
            background: linear-gradient(135deg, #adc6a5, #5c8c83);
        }

        .club-poster {
            width: 135px;
            min-height: 155px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            transform: rotate(-5deg) translateY(15px);
            border: 2px solid white;
            border-radius: 5px 12px 12px 5px;
            background: #fff8ed;
            box-shadow: 9px 12px 16px #30253645;
        }

        .club-poster small {
            color: #bb6579;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .club-poster strong {
            font-family: Georgia, serif;
            overflow-wrap: anywhere;
        }

        .review-item {
            display: flex;
            align-items: flex-start;
            gap: 18px;
        }

        .review-cover {
            width: 100px;
            height: 145px;
            flex: none;
            object-fit: cover;
            border-radius: 7px;
            box-shadow: 5px 7px 15px #352b3540;
        }

        .cover-fallback {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 13px 8px;
            background: #ffe6e7;
            color: #8c4264;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .cover-fallback span { font-size: 10px; }

        .cover-fallback strong {
            color: #292536;
            font-family: Georgia, serif;
            font-size: 14px;
        }

        .review-info { min-width: 0; }

        @media (max-width: 600px) {
            .page { padding: 20px 16px 70px; }
            .tabs { gap: 22px; }

            .stats-card,
            .goal-card {
                padding: 18px;
                border-radius: 26px;
            }

            .stats-inner { padding: 24px 12px 12px; }
            .stats-inner > p { font-size: 19px; }
            .stat-grid { gap: 6px; }
            .stat { min-height: 140px; padding: 14px 5px; }
            .stat span { font-size: 11px; }
            .stat strong { font-size: 26px; }
            .items { grid-template-columns: 1fr; }
            .review-cover { width: 78px; height: 118px; }
        }

        .rec-intro {
    color: #77717c;
    line-height: 1.6;
}

.rec-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-top: 24px;
}

.rec-card {
    display: flex;
    align-items: center;
    gap: 20px;
    min-width: 0;
    padding: 18px;
    border: 1px solid #f3dfe7;
    border-radius: 20px;
    background: #fff0f5;
    color: #272536;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.rec-card:hover,
.rec-card:focus-visible {
    transform: translateY(-5px);
    box-shadow: 0 14px 30px #ad668026;
}

.rec-cover {
    width: 105px;
    height: 145px;
    flex: none;
    display: grid;
    place-items: center;
    overflow: hidden;
    border-radius: 10px;
    background: #f7dde7;
    box-shadow: 5px 7px 15px #70465724;
    font-size: 42px;
}

.rec-cover img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.rec-details {
    min-width: 0;
}

.rec-genre {
    color: #bf5277;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.rec-details h3 {
    margin: 9px 0;
    font-size: 19px;
}

.rec-details p {
    margin: 0;
    color: #77717c;
    font-size: 14px;
}

.rec-link {
    display: inline-block;
    margin-top: 18px;
    color: #b8436b;
    font-size: 13px;
    font-weight: bold;
}

@media (max-width: 700px) {
    .rec-grid {
        grid-template-columns: 1fr;
    }

    .rec-cover {
        width: 85px;
        height: 120px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .rec-card {
        transition: none;
    }
}
.reading-donut {
    width: 190px;
    aspect-ratio: 1;
    margin: 24px auto 12px;
    padding: 14px;
    border-radius: 50%;
    background: conic-gradient(
    #d9578c 0 var(--progress),
    #eadfe4 var(--progress) 100%
    );
}

.reading-donut-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #f7f3ef;
}

.reading-donut-center strong {
    color: #272536;
    font-size: 38px;
}

.reading-donut-center span {
    color: #766d72;
    font-size: 13px;
}
.reading-legend {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 18px;
    margin: 16px 0;
    color: #766d72;
    font-size: 13px;
}

.reading-legend span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.reading-legend i {
    width: 11px;
    height: 11px;
    border-radius: 50%;
}

.legend-finished { background: #d9578c; }
.legend-remaining { background: #eadfe4; }

@media (prefers-reduced-motion: no-preference) {
    .stats-reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.65s ease, transform 0.65s ease;
    }

    .stats-reveal.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
}
.reading-card {
    flex-wrap: wrap;
}

.reading-book {
    display: flex;
    flex: 1;
    align-items: center;
    gap: 20px;
    min-width: 220px;
    color: inherit;
    text-decoration: none;
}
.reading-card {
    display: block;
}

.reading-book {
    display: flex;
    align-items: center;
    gap: 20px;
    color: inherit;
    text-decoration: none;
}

.reading-card form {
    margin-top: 12px;
}

a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
    outline: 3px solid #c04478;
    outline-offset: 3px;
}
    </style>
</head>

<body>
    <main class="page">
        <a class="back" href="index.php">← Back to Home</a>

        <header class="profile-head">
            <div class="avatar">
                <?= e(mb_strtoupper(mb_substr($user["username"], 0, 1))) ?>
            </div>
            <div>
                <h1><?= e($user["username"]) ?></h1>
                <p class="email"><?= e($user["email"]) ?></p>
            </div>
        </header>

        <nav class="tabs" aria-label="Profile sections">
            <a
                href="profile.php?tab=stats"
                class="<?= $tab === "stats" ? "active" : "" ?>"
            >Stats</a>

            <a
                href="profile.php?tab=clubs"
                class="<?= $tab === "clubs" ? "active" : "" ?>"
            >Clubs</a>

            <a
                href="profile.php?tab=reviews"
                class="<?= $tab === "reviews" ? "active" : "" ?>"
            >Reviews</a>

            <?php if ($user["role"] === "moderator"): ?>
                <a href="moderator.php">Moderator Panel</a>
            <?php endif; ?>
        </nav>

        <?php if ($tab === "stats"): ?>
            <section class="stats-card">
                <div class="stats-top">
                    <span class="pill">✦ Reader Summary</span>
                    <span class="pill"><?= date("Y") ?></span>
                </div>

                <div class="stats-inner">
                    <h2>Your reading at a glance</h2>
                    <p>
                        Every book has a story. Here's your journey
                        on ReadSpace.
                    </p>

                    <div class="stat-grid">
                        <div class="stat">
                            <div class="stat-icon">📖</div>
                            <span>Books reviewed</span>
                            <strong><?= count($approvedBookIds) ?></strong>
                        </div>

                        <div class="stat">
                            <div class="stat-icon">✦</div>
                            <span>Clubs joined</span>
                            <strong><?= count($clubs) ?></strong>
                        </div>

                        <div class="stat">
                            <div class="stat-icon">★</div>
                            <span>Average rating given</span>
                            <strong><?= e($averageRating) ?></strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="goal-card">
                <div class="goal-inner">
                    <p>YOUR READING JOURNEY · <?= $year ?></p>
                    <h2>Reading goal</h2>
                    <div class="goal-art" aria-hidden="true">🌼 📚 🌷</div>

                    <?php if (isset($_GET["goal_saved"])): ?>
                        <p>Your reading goal was saved!</p>
                    <?php endif; ?>

                    <?php if ($targetBooks > 0): ?>
                        <p>
                            <strong>
                                <?= $booksRead ?> / <?= $targetBooks ?> books
                            </strong>
                            finished this year
                        </p>

                        <div class="reading-donut"
                           style="--progress: <?= (int) $progress ?>%;"
                           role="img"
                           aria-label="<?= $progress ?>% of reading goal completed">
                        <div class="reading-donut-center">
                            <strong><?= $booksRead ?></strong>
                            <span>of <?= $targetBooks ?> books</span>
                        </div>
                        </div>
                        <div class="reading-legend">
                             <span><i class="legend-finished"></i> Finished: <?= $booksRead ?></span>
                           <span><i class="legend-remaining"></i> Remaining: <?= max(0, $targetBooks - $booksRead) ?></span>
                        </div>
                        <p><?= $progress ?>% of your goal completed</p> 
                    <?php else: ?>
                        <p>Set the number of books you'd like to read this year.</p>
                    <?php endif; ?>

                    <form
                        method="post"
                        action="save_reading_goal.php"
                        class="goal-form"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION["csrf_token"]) ?>"
                        >

                        <label for="target_books">
                            Book goal for this year
                        </label>
                        <input
                            id="target_books"
                            name="target_books"
                            type="number"
                            min="1"
                            max="365"
                            value="<?= $targetBooks > 0 ? $targetBooks : 15 ?>"
                            required
                        >

                        <button type="submit">Save Goal</button>
                    </form>
                </div>
            </section>

            <section class="section">
    <h2>Currently Reading</h2>

    <?php if (empty($currentlyReading)): ?>
        <p>No books in progress yet.</p>
    <?php else: ?>
        <div class="rec-grid">
            <?php foreach ($currentlyReading as $book): ?>
                <article class="rec-card reading-card">
                  <a class="reading-book"
                  href="books.php?id=<?= (int) $book["book_id"] ?>">
                    <div class="rec-cover">
                        <?php
                        $cover = $book["cover_image_url"] ?? "";
                        $online = filter_var($cover, FILTER_VALIDATE_URL)
                            && str_starts_with($cover, "https://");
                        $uploaded = preg_match(
                            '~^uploads/covers/[a-f0-9]{32}\.(jpg|png|webp)$~',
                            $cover
                        ) && is_file(__DIR__ . "/" . $cover);
                        ?>
                        <?php if ($online || $uploaded): ?>
                            <img src="<?= e($cover) ?>"
                                 alt="Cover of <?= e($book["title"]) ?>">
                        <?php else: ?>
                            <span aria-hidden="true">📖</span>
                        <?php endif; ?>
                    </div>
                    <div class="rec-details">
                        <h3><?= e($book["title"]) ?></h3>
                        <p>by <?= e($book["author"]) ?></p>
                    </div>
                </a>
                </a>
                <form method="post" action="toggle_finished_book.php">
                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="book_id"
                           value="<?= (int) $book["book_id"] ?>">
                    <button class="finished-button" type="submit">
                        ✓ Mark as finished
                    </button>
                </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section">
    <h2>Finished Reading</h2>

    <?php if (empty($finishedBooks)): ?>
        <p>No finished books yet.</p>
    <?php else: ?>
        <div class="rec-grid">
            <?php foreach ($finishedBooks as $book): ?>
                <article class="rec-card reading-card">
                    <a class="reading-book" href="books.php?id=<?= (int) $book["book_id"] ?>">
                    <div class="rec-cover">
                        <?php
                        $cover = $book["cover_image_url"] ?? "";
                        $online = filter_var($cover, FILTER_VALIDATE_URL)
                            && str_starts_with($cover, "https://");
                        $uploaded = preg_match(
                            '~^uploads/covers/[a-f0-9]{32}\.(jpg|png|webp)$~',
                            $cover
                        ) && is_file(__DIR__ . "/" . $cover);
                        ?>
                        <?php if ($online || $uploaded): ?>
                            <img src="<?= e($cover) ?>"
                                 alt="Cover of <?= e($book["title"]) ?>">
                        <?php else: ?>
                            <span aria-hidden="true">📖</span>
                        <?php endif; ?>
                    </div>
                    <div class="rec-details">
                        <h3><?= e($book["title"]) ?></h3>
                        <p>by <?= e($book["author"]) ?></p>
                        <p>Finished on <?= e($book["finished_at"]) ?></p>
                    </div>
                </a>
                                 </a>
                <form method="post" action="toggle_finished_book.php">
                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION["csrf_token"]) ?>">
                    <input type="hidden" name="book_id"
                           value="<?= (int) $book["book_id"] ?>">
                    <button class="finished-button" type="submit">
                        Remove from Finished Reading
                    </button>
                </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
        <p>
        <a href="finished_books.php">View all finished books →</a>
    </p>
</section>

 <h2>Want to read</h2>
 

    <?php if (empty($wantToRead)): ?>
        <p>No books on your Want to Read list yet.</p>
    <?php else: ?>
        <div class="rec-grid">
            <?php foreach ($wantToRead as $book): ?>
                <a class="rec-card"
                   href="books.php?id=<?= (int) $book["book_id"] ?>">
                    <div class="rec-cover">
                        <?php
                        $cover = $book["cover_image_url"] ?? "";
                        $online = filter_var($cover, FILTER_VALIDATE_URL)
                            && str_starts_with($cover, "https://");
                        $uploaded = preg_match(
                            '~^uploads/covers/[a-f0-9]{32}\.(jpg|png|webp)$~',
                            $cover
                        ) && is_file(__DIR__ . "/" . $cover);
                        ?>
                        <?php if ($online || $uploaded): ?>
                            <img src="<?= e($cover) ?>"
                                 alt="Cover of <?= e($book["title"]) ?>">
                        <?php else: ?>
                            <span aria-hidden="true">📖</span>
                        <?php endif; ?>
                    </div>
                    <div class="rec-details">
                        <h3><?= e($book["title"]) ?></h3>
                        <p>by <?= e($book["author"]) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
        <section class="section">
    <h2>Recommended for you</h2>
    <p class="rec-intro">
        <?php if ($favoriteGenre !== ""): ?>
            Explore more stories picked for you.
        <?php else: ?>
            Discover a story you might love.
        <?php endif; ?>
    </p>

    <?php if (empty($recommendations)): ?>
        <p>More recommendations are coming soon.</p>
    <?php else: ?>
        <div class="rec-grid">
            <?php foreach ($recommendations as $book): ?>
                <a
                    class="rec-card"
                    href="books.php?id=<?= (int) $book["book_id"] ?>"
                >
                    <div class="rec-cover">
                        <?php
                        $cover = $book["cover_image_url"] ?? "";
                        $onlineCover = filter_var(
                            $cover,
                            FILTER_VALIDATE_URL
                        ) && str_starts_with($cover, "https://");

                        $uploadedCover = preg_match(
                            '~^uploads/covers/[a-f0-9]{32}\.(jpg|png|webp)$~',
                            $cover
                        ) && is_file(__DIR__ . "/" . $cover);
                        ?>

                        <?php if ($onlineCover || $uploadedCover): ?>
                            <img
                                src="<?= e($cover) ?>"
                                alt="Cover of <?= e($book["title"]) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <span aria-hidden="true">📖</span>
                        <?php endif; ?>
                    </div>

                    <div class="rec-details">
                        <span class="rec-genre"><?= e($book["genre"]) ?></span>
                        <h3><?= e($book["title"]) ?></h3>
                        <p>by <?= e($book["author"]) ?></p>
                        <span class="rec-link">Explore book →</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

        <?php if ($tab === "clubs"): ?>
            <section class="section">
                <h2>My Clubs (<?= count($clubs) ?>)</h2>

                <div class="items">
                    <?php if (count($clubs) === 0): ?>
                        <div class="item">
                            You haven't joined a club yet.
                            <a href="clubs.php">Find a club →</a>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($clubs as $club): ?>
                        <article class="item club-item">
                            <a href="club.php?id=<?= (int) $club["club_id"] ?>">
                                <div class="club-art" aria-hidden="true">
                                    <div class="club-poster">
                                        <small>READSPACE CLUB</small>
                                        <strong><?= e($club["club_name"]) ?></strong>
                                        <span>✦ 📖 ✦</span>
                                    </div>
                                </div>
                                <?= e($club["club_name"]) ?>
                            </a>
                            <br>
                            <span class="tag"><?= e($club["genre"]) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tab === "reviews"): ?>
            <section class="section">
                <h2>My Reviews (<?= count($reviews) ?>)</h2>

                <div class="items">
                    <?php if (count($reviews) === 0): ?>
                        <div class="item">
                            You haven't written a review yet.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($reviews as $review): ?>
                        <article class="item review-item">
                            <?php if (!empty($review["cover_image_url"])): ?>
                                <img
                                    class="review-cover"
                                    src="<?= e($review["cover_image_url"]) ?>"
                                    alt="Cover of <?= e($review["title"]) ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>
                                <div
                                    class="review-cover cover-fallback"
                                    aria-hidden="true"
                                >
                                    <span>READSPACE</span>
                                    <strong><?= e($review["title"]) ?></strong>
                                    <span>✦ 📖 ✦</span>
                                </div>
                            <?php endif; ?>

                            <div class="review-info">
                                <strong><?= e($review["title"]) ?></strong>
                                <br>

                                <span class="tag">
                                    <?= (int) $review["rating"] ?>/5
                                </span>
                                <span class="tag">
                                    <?= e(ucfirst($review["status"])) ?>
                                </span>

                                <p><?= e($review["review_text"]) ?></p>

                                <?php
                                $isFinished = isset(
                                    $finishedBookIds[(int) $review["book_id"]]
                                );
                                ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <script>
if (
    window.matchMedia("(prefers-reduced-motion: no-preference)").matches &&
    "IntersectionObserver" in window
) {
    const cards = document.querySelectorAll(
        ".stats-card, .goal-card, .section"
    );

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.08 });

    cards.forEach((card) => {
        card.classList.add("stats-reveal");
        observer.observe(card);
    });
}
</script>
</body>
</html>