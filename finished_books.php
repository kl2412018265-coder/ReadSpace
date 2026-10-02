<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$userId = (int) $_SESSION["user_id"];
$page = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;
$perPage = 12;

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM finished_books
     WHERE user_id = ?"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();

$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $conn->prepare(
    "SELECT b.book_id, b.title, b.author, b.cover_image_url, f.finished_at
     FROM finished_books f
     JOIN books b ON b.book_id = f.book_id
     WHERE f.user_id = ?
     ORDER BY f.finished_at DESC, f.book_id DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param("iii", $userId, $perPage, $offset);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Finished Reading | ReadSpace</title>
    <style>
        body {
            margin: 0;
            background: #faf8f8;
            color: #1f1b32;
            font-family: Arial, sans-serif;
        }
        main {
            max-width: 1100px;
            margin: 50px auto;
            padding: 0 20px;
        }
        h1 { font-size: 36px; }
        .back, .pages a {
            color: #ad426f;
            text-decoration: none;
            font-weight: bold;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .book {
            display: flex;
            align-items: center;
            gap: 18px;
            min-height: 160px;
            padding: 20px;
            border: 1px solid #f2dce6;
            border-radius: 24px;
            background: #fff0f5;
            color: inherit;
            text-decoration: none;
        }
        .book img, .fallback {
            width: 100px;
            height: 145px;
            flex: none;
            border-radius: 12px;
            object-fit: cover;
        }
        .fallback {
            display: grid;
            place-items: center;
            background: #f7dce8;
            font-size: 42px;
        }
        .book h2 { margin: 0 0 8px; font-size: 21px; }
        .book p { margin: 4px 0; color: #777080; }
        .pages { display: flex; gap: 20px; margin: 30px 0; }
    </style>
</head>
<body>
<main>
    <a class="back" href="profile.php?tab=stats">← Back to Stats</a>
    <h1>Finished Reading</h1>
    <p><?= $total ?> book<?= $total === 1 ? "" : "s" ?> finished</p>

    <?php if (!$books): ?>
        <p>No finished books yet.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($books as $book): ?>
                <a class="book" href="books.php?id=<?= (int) $book["book_id"] ?>">
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
                        <span class="fallback" aria-hidden="true">📖</span>
                    <?php endif; ?>
                    <div>
                        <h2><?= e($book["title"]) ?></h2>
                        <p>by <?= e($book["author"]) ?></p>
                        <p>Finished on <?= e($book["finished_at"]) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <nav class="pages" aria-label="Pages">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">← Previous</a>
            <?php endif; ?>
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>">Next →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>
</body>
</html>