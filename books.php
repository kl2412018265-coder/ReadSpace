<?php
session_start();
require "config.php";

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$isAdult = isset($_SESSION["user_id"])
    && (int) ($_SESSION["is_minor"] ?? 1) === 0;

$search = trim($_GET["search"] ?? "");
$genre = trim($_GET["genre"] ?? "");
$bookId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
$isModerator = false;

if (isset($_SESSION["user_id"])) {
    $currentUserId = (int) $_SESSION["user_id"];

    $roleStmt = $conn->prepare(
        "SELECT role FROM users WHERE user_id = ? LIMIT 1"
    );
    $roleStmt->bind_param("i", $currentUserId);
    $roleStmt->execute();
    $account = $roleStmt->get_result()->fetch_assoc();
    $roleStmt->close();

    $isModerator = $account && $account["role"] === "moderator";
}

$genres = $conn->query(
    "SELECT DISTINCT genre
     FROM books
     WHERE genre IS NOT NULL AND genre <> ''
     ORDER BY genre"
);

if ($bookId) {
    if ($isAdult) {
        $stmt = $conn->prepare(
            "SELECT * FROM books WHERE book_id = ? LIMIT 1"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT * FROM books
             WHERE book_id = ? AND is_mature_content = 0
             LIMIT 1"
        );
    }

    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $selectedBook = $stmt->get_result()->fetch_assoc();
} else {
    $selectedBook = null;
}

$sql = "SELECT book_id, title, author, genre, summary,
               cover_image_url
        FROM books
        WHERE 1 = 1";

if (!$isAdult) {
    $sql .= " AND is_mature_content = 0";
}

if ($search !== "") {
    $sql .= " AND (title LIKE ? OR author LIKE ?)";
}

if ($genre !== "") {
    $sql .= " AND genre = ?";
}

$sql .= " ORDER BY title ASC";

$stmt = $conn->prepare($sql);
$like = "%" . $search . "%";

if ($search !== "" && $genre !== "") {
    $stmt->bind_param("sss", $like, $like, $genre);
} elseif ($search !== "") {
    $stmt->bind_param("ss", $like, $like);
} elseif ($genre !== "") {
    $stmt->bind_param("s", $genre);
}

$stmt->execute();
$books = $stmt->get_result();

function cover($book) {
    $url = $book["cover_image_url"] ?? "";

    $isOnlineCover =
        filter_var($url, FILTER_VALIDATE_URL) &&
        str_starts_with($url, "https://");

    $isUploadedCover =
        preg_match(
            '~^uploads/covers/[a-f0-9]{32}\.(jpg|png|webp)$~',
            $url
        ) &&
        is_file(__DIR__ . "/" . $url);

    if ($isOnlineCover || $isUploadedCover) {
        echo '<div class="uploaded-cover">'
          . '<img src="' . e($url) . '" alt="Cover of '
          . e($book["title"]) . '">'
          . '</div>';
    } else {
        echo '<div class="cover-placeholder" aria-label="Temporary book cover">'
            . '<small>READSPACE LIBRARY</small>'
            . '<strong>' . e($book["title"]) . '</strong>'
            . '<span>' . e($book["author"]) . '</span>'
            . '<small>✦ 📖 ✦</small>'
            . '</div>';
    }
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Explore Books | ReadSpace</title>

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
            max-width: 1200px;
            margin: auto;
            padding: 22px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo {
            color: #eb597a;
            font-size: 24px;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-links a {
            color: #343244;
            font-size: 14px;
            font-weight: 700;
        }

        .nav-links .pill {
            padding: 11px 18px;
            border-radius: 24px;
            background: #272639;
            color: white;
        }

        main {
            max-width: 1200px;
            margin: auto;
            padding: 25px 28px 80px;
        }

        .heading {
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

        .heading p,
        .muted {
            color: #696572;
            line-height: 1.6;
        }

        .filters {
            display: flex;
            gap: 12px;
            margin: 30px 0;
        }

        .filters input,
        .filters select {
            min-height: 45px;
            padding: 10px 14px;
            border: 1px solid #ddd7d4;
            border-radius: 10px;
            background: white;
            font: inherit;
        }

        .filters input { flex: 1; }
        .filters select { min-width: 165px; }

        button,
        .button {
            display: inline-block;
            padding: 12px 22px;
            border: 0;
            border-radius: 10px;
            background: #272639;
            color: white;
            font: 700 14px Arial, sans-serif;
            cursor: pointer;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card {
            display: block;
            padding: 16px;
            border: 1px solid #efebe6;
            border-radius: 16px;
            background: white;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px #30253614;
        }

        .cover {
            width: 100%;
            height: 230px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 10px;
            background: #e8e2f1;
            font-size: 65px;
        }

        .cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .genre {
            display: block;
            margin-top: 15px;
            color: #df6681;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .card h2 {
            margin: 7px 0;
            font-size: 18px;
        }

        .card p {
            margin: 0;
            color: #77737d;
            font-size: 14px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #d65a75;
            font-weight: 700;
        }

        .detail {
            display: grid;
            grid-template-columns: 270px minmax(0, 1fr);
            align-items: start;
            gap: 55px;
            padding: 38px;
            border: 0;
            border-radius: 24px;
            background: #fbeade;
        }

        .detail .cover {
            width: 270px;
            height: 380px;
            border-radius: 8px;
            background: #f5d5df;
            box-shadow: 12px 16px 25px #352b3530;
        }

        .detail .cover img {
            object-fit: contain;
        }

        .book-info {
            padding-top: 18px;
        }

        .book-info h1 {
            max-width: 750px;
            margin: 14px 0 9px;
            font-family: Georgia, serif;
            font-size: clamp(38px, 5vw, 65px);
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .book-author {
            margin: 0 0 28px;
            color: #595161;
            font-size: 22px;
        }

        .book-badge {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 20px;
            background: #ffe1eb;
            color: #aa4164;
            font-size: 13px;
            font-weight: bold;
        }

        .book-summary {
            margin-top: 30px;
            padding: 32px 38px;
            border: 1px solid #f0e5e8;
            border-radius: 20px;
            background: white;
        }

        .book-summary h2 {
            margin: 10px 0;
            font-family: Georgia, serif;
            font-size: 30px;
        }

        .book-summary p {
            max-width: 850px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .empty {
            padding: 35px;
            border-radius: 12px;
            background: #f7f1ec;
        }

        .review-section {
            max-width: 100%;
            margin-top: 40px;
        }

        .review-section h2 {
            font-size: 28px;
        }

        .review-notice {
            padding: 14px 18px;
            border-radius: 10px;
            background: #e5f4e9;
            color: #256d40;
        }

        .review-form {
            padding: 27px;
            border: 1px solid #efebe6;
            border-radius: 16px;
            background: white;
        }

        .review-form h3 {
            margin-top: 0;
            font-size: 21px;
        }

        .review-form label {
            display: block;
            margin: 18px 0 7px;
            font-weight: 700;
        }

        .review-form select,
        .review-form textarea {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd7d4;
            border-radius: 9px;
            background: white;
            font: inherit;
        }

        .review-form button {
            margin-top: 18px;
        }

        .published-review {
            margin: 15px 0;
            padding: 20px;
            border: 1px solid #fff3f6;
            border-radius: 14px;
            background: #ffe1eb;
        }

        .review-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .review-stars {
            color: #e78548;
        }

        .published-review p {
            line-height: 1.7;
            overflow-wrap: anywhere;
        }

        .published-review small {
            color: #77737d;
        }

        .cover-placeholder {
            width: 82%;
            height: 90%;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            border: 2px solid #ffffffb8;
            border-radius: 5px 12px 12px 5px;
            background: #fff8ed;
            box-shadow: 8px 10px 18px #352b3530;
            overflow-wrap: anywhere;
        }

        .cover-placeholder small {
            color: #be6380;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .cover-placeholder strong {
            color: #302536;
            font-family: Georgia, serif;
            font-size: clamp(15px, 2vw, 24px);
            line-height: 1.2;
        }

        .cover-placeholder span {
            color: #716271;
            font-size: 13px;
        }

        @media (max-width: 800px) {
            .grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 700px) {
            .detail {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 25px;
            }

            .detail .cover {
                width: 210px;
                height: 300px;
            }

            .book-info { padding-top: 0; }
            .book-summary { padding: 25px; }
        }

        @media (max-width: 550px) {
            .nav { flex-wrap: wrap; }
            .nav-links { flex-wrap: wrap; }
            .filters { flex-wrap: wrap; }
            .filters input { flex-basis: 100%; }
            .grid { gap: 10px; }
            .cover { height: 170px; }
        }

        .uploaded-cover {
    width: 82%;
    height: 90%;
    padding: 5px;
    border: 2px solid #ffffffb8;
    border-radius: 5px 12px 12px 5px;
    background: #fff8ed;
    box-shadow: 8px 10px 18px #352b3530;
    overflow: hidden;
}

.uploaded-cover img,
.detail .uploaded-cover img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.edit-book-button {
    display: inline-block;
    margin: 18px 0;
    padding: 12px 20px;
    border-radius: 12px;
    background: #bd5b89;
    color: #fff;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.2s ease;
}

.edit-book-button:hover {
    background: #9c3e6a;
}

.edit-book-button:focus-visible {
    outline: 3px solid #7f2754;
    outline-offset: 3px;
}
    </style>
</head>

<body>
    <header class="nav">
        <a class="logo" href="index.php">✦ ReadSpace</a>

        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="books.php">Books</a>
            
            <?php if ($isModerator): ?>
              <a class="pill" href="add_book.php">+ Add Book</a>
            <?php endif; ?>

            <?php if (isset($_SESSION["user_id"])): ?>
                <span>Hi, <?= e($_SESSION["username"]) ?></span>
            <?php else: ?>
                <a href="login.php">Log In</a>
                <a class="pill" href="registration.php">Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?php if ($bookId): ?>
            <a class="back" href="books.php">← Back to all books</a>

            <?php if ($selectedBook): ?>
                <article class="detail">
                    <div class="cover">
                        <?php cover($selectedBook); ?>
                    </div>

                    <div class="book-info">
                        <span class="eyebrow">
                            <?= e($selectedBook["genre"]) ?>
                        </span>

                        <h1><?= e($selectedBook["title"]) ?></h1>

                        <p class="book-author">
                            by <?= e($selectedBook["author"]) ?>
                        </p>

                        <span class="book-badge">
                            ✦ A ReadSpace reader pick
                        </span>
                    </div>
                </article>

                <?php if (isset($_SESSION["user_id"])): ?>
                    <?php
                     if (empty($_SESSION["csrf_token"])) {
                         $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                   }
                    ?>

                <?php if (isset($_GET["reading_saved"])): ?>
                   <p class="review-notice">Your reading list has been updated.</p>
                 <?php endif; ?>

                     <form method="post" action="set_reading_status.php"
                        style="display:flex; gap:12px; flex-wrap:wrap; margin:20px 0;">
                    <input type="hidden" name="csrf_token"
                         value="<?= e($_SESSION["csrf_token"]) ?>">
                        <input type="hidden" name="book_id"
                           value="<?= (int) $selectedBook["book_id"] ?>">

                        <button type="submit" name="status" value="reading">
                        📖 Currently Reading
                        </button>
                        <button type="submit" name="status" value="want_to_read">
                        ♡ Want to Read
                        </button>
                   </form>
                <?php endif; ?>

                <?php if ($isModerator): ?>

                    <a href="edit_book.php?id=<?= (int) $selectedBook["book_id"] ?>"
                            class="edit-book-button"
                            style="display:inline-block; margin:16px 0;">
                           ✎ Edit Book Details
                          </a>
    <?php
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    ?>

    <?php if (isset($_GET["cover_saved"])): ?>
        <p class="review-notice">Book cover saved successfully.</p>
    <?php endif; ?>

    <form
        method="post"
        action="upload_book_cover.php"
        enctype="multipart/form-data"
        style="margin: 22px 0; padding: 22px; background: white;
               border: 1px solid #f0e5e8; border-radius: 16px;"
    >
        <h2 style="margin-top: 0;">Change Book Cover</h2>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION["csrf_token"]) ?>"
        >
        <input
            type="hidden"
            name="book_id"
            value="<?= (int) $selectedBook["book_id"] ?>"
        >

        <label for="cover">Choose a cover from your computer</label>
        <input
            id="cover"
            type="file"
            name="cover"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            required
            style="display: block; margin: 12px 0;"
        >

        <button type="submit">Upload Cover</button>
        <p class="muted">JPG, PNG, or WebP. Maximum 5 MB.</p>
                  </form>
                <?php endif; ?>

                <section class="book-summary">
                    <span class="eyebrow">Discover the story</span>
                    <h2>About this book</h2>
                    <p><?= nl2br(e($selectedBook["summary"])) ?></p>
                </section>

                <section class="review-section">
                    <h2>Reader reviews</h2>

                    <?php
                    $reviewQuery = $conn->prepare(
                        "SELECT r.rating, r.review_text, r.created_at,
                                u.username
                         FROM reviews r
                         JOIN users u ON u.user_id = r.user_id
                         WHERE r.book_id = ? AND r.status = 'approved'
                         ORDER BY r.created_at DESC, r.review_id DESC"
                    );

                    $currentBookId = (int) $selectedBook["book_id"];
                    $reviewQuery->bind_param("i", $currentBookId);
                    $reviewQuery->execute();
                    $approvedReviews = $reviewQuery->get_result();
                    ?>

                    <?php if ($approvedReviews->num_rows === 0): ?>
                        <p class="muted">
                            No published reviews for this book yet.
                        </p>
                    <?php endif; ?>

                    <?php while ($item = $approvedReviews->fetch_assoc()): ?>
                        <article class="published-review">
                            <div class="review-top">
                                <strong><?= e($item["username"]) ?></strong>
                                <span class="review-stars">
                                    <?= str_repeat(
                                        "★",
                                        (int) $item["rating"]
                                    ) ?>
                                </span>
                            </div>

                            <p><?= nl2br(e($item["review_text"])) ?></p>
                            <small><?= e(substr($item["created_at"], 0, 10)) ?></small>
                        </article>
                    <?php endwhile; ?>

                    <?php if (($_GET["review"] ?? "") === "pending"): ?>
                        <p class="review-notice">
                            Your review was submitted and is awaiting approval.
                        </p>
                    <?php endif; ?>

                    <?php if (isset($_SESSION["user_id"])): ?>
                        <?php
                        if (empty($_SESSION["csrf_token"])) {
                            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                        }
                        ?>

                        <form
                            class="review-form"
                            method="post"
                            action="submit_review.php"
                        >
                            <h3>Write your review</h3>
                            <p class="muted">
                                Your review will appear after it is approved.
                            </p>

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e($_SESSION["csrf_token"]) ?>"
                            >

                            <input
                                type="hidden"
                                name="book_id"
                                value="<?= (int) $selectedBook["book_id"] ?>"
                            >

                            <label for="rating">Rating</label>
                            <select id="rating" name="rating" required>
                                <option value="">Choose a rating</option>
                                <option value="1">★ 1 star</option>
                                <option value="2">★★ 2 stars</option>
                                <option value="3">★★★ 3 stars</option>
                                <option value="4">★★★★ 4 stars</option>
                                <option value="5">★★★★★ 5 stars</option>
                            </select>

                            <label for="review_text">Review</label>
                            <textarea
                                id="review_text"
                                name="review_text"
                                rows="5"
                                minlength="10"
                                maxlength="2000"
                                placeholder="What did you think of this book?"
                                required
                            ></textarea>

                            <button type="submit">Submit Review</button>
                        </form>
                    <?php else: ?>
                        <p class="muted">
                            <a href="login.php">Log in</a>
                            to write a review.
                        </p>
                    <?php endif; ?>
                </section>

                

            <?php else: ?>
                <div class="empty">
                    This book was not found or is unavailable for your age group.
                </div>
            <?php endif; ?>

        <?php else: ?>
            <section class="heading">
                <span class="eyebrow">The ReadSpace collection</span>
                <h1>Find your next story.</h1>
                <p>Explore books by title, author, or genre.</p>
            </section>

            <form class="filters" method="get" action="books.php">
                <input
                    type="search"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Search titles or authors..."
                    aria-label="Search titles or authors"
                >

                <select name="genre" aria-label="Choose a genre">
                    <option value="">All genres</option>
                    <?php while ($item = $genres->fetch_assoc()): ?>
                        <option
                            value="<?= e($item["genre"]) ?>"
                            <?= $genre === $item["genre"] ? "selected" : "" ?>
                        >
                            <?= e($item["genre"]) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit">Search Books</button>
            </form>

            <div class="grid">
                <?php if ($books->num_rows === 0): ?>
                    <p class="empty">
                        No books found. Try another search.
                    </p>
                <?php endif; ?>

                <?php while ($book = $books->fetch_assoc()): ?>
                    <a
                        class="card"
                        href="books.php?id=<?= (int) $book["book_id"] ?>"
                    >
                        <div class="cover"><?php cover($book); ?></div>
                        <span class="genre"><?= e($book["genre"]) ?></span>
                        <h2><?= e($book["title"]) ?></h2>
                        <p><?= e($book["author"]) ?></p>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>