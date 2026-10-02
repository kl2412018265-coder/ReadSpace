<?php
session_start();
require "config.php";

$isLoggedIn = isset($_SESSION["user_id"]);

if ($isLoggedIn && empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$isAdult = $isLoggedIn
    && (int) ($_SESSION["is_minor"] ?? 1) === 0;

if (!$isAdult) {
    $books = $conn->query(
        "SELECT book_id, title, author, genre, cover_image_url
         FROM books
         WHERE is_mature_content = 0
         ORDER BY book_id DESC
         LIMIT 8"
    );
} else {
    $books = $conn->query(
        "SELECT book_id, title, author, genre, cover_image_url
         FROM books
         ORDER BY book_id DESC
         LIMIT 8"
    );
}

function escape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ReadSpace | Every story starts here</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fffdf9;
            color: #252331;
            font-family: Arial, sans-serif;
        }

        a {
            text-decoration: none;
        }

        .nav {
            max-width: 1200px;
            margin: auto;
            padding: 20px 28px;
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
            gap: 24px;
        }

        .nav-links a {
            color: #393744;
            font-size: 14px;
            font-weight: 700;
        }

        .nav-links .join {
            padding: 11px 18px;
            border-radius: 24px;
            background: #272639;
            color: white;
        }

        .profile-menu {
            position: relative;
            z-index: 10;
        }

        .profile-menu summary {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #393744;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            list-style: none;
        }

        .profile-menu summary::-webkit-details-marker {
            display: none;
        }

        .profile-avatar {
            width: 35px;
            height: 35px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #ffdce9;
            color: #a53462;
            font-weight: 800;
        }

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 185px;
            padding: 9px;
            border: 1px solid #f2d7e1;
            border-radius: 14px;
            background: #fff0f5;
            box-shadow: 0 12px 28px #30253620;
        }

        .profile-dropdown a,
        .profile-dropdown button {
            display: block;
            width: 100%;
            padding: 11px 12px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #393744;
            font: 700 14px Arial, sans-serif;
            text-align: left;
            cursor: pointer;
        }

        .profile-dropdown a:hover,
        .profile-dropdown button:hover {
            background: #ffdce9;
        }

        .profile-dropdown form {
            margin: 0;
            padding-top: 5px;
            border-top: 1px solid #f2d7e1;
        }

        .profile-dropdown button {
            color: #b43c68;
        }

        .hero {
            max-width: 1200px;
            min-height: 450px;
            margin: 18px auto 0;
            padding: 65px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
            overflow: hidden;
            border-radius: 28px;
            background: #fbeade;
        }

        .hero-copy {
            max-width: 570px;
        }

        .eyebrow {
            color: #d65a75;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        h1 {
            margin: 18px 0;
            font-size: clamp(42px, 6vw, 76px);
            line-height: 1.08;
            letter-spacing: -3px;
        }

        h1 span {
            color: #e76780;
        }

        .hero-copy p {
            max-width: 470px;
            color: #625e69;
            font-size: 18px;
            line-height: 1.7;
        }

        .button {
            display: inline-block;
            margin-top: 18px;
            padding: 14px 23px;
            border-radius: 28px;
            background: #272639;
            color: white;
            font-weight: 700;
        }

        .hero-art {
            width: 280px;
            height: 290px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 42% 58% 55% 45%;
            background: linear-gradient(145deg, #ffb45e, #fa6b96);
            box-shadow: 20px 20px 0 #ffffff88;
            font-size: 125px;
            transform: rotate(-8deg);
        }

        .section {
            max-width: 1200px;
            margin: 65px auto;
            padding: 0 28px;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 20px;
            margin-bottom: 25px;
        }

        h2 {
            margin: 8px 0;
            font-size: 32px;
        }

        .section-head p {
            color: #77737d;
        }

        .book-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .book-card {
            padding: 16px;
            border: 1px solid #efebe6;
            border-radius: 16px;
            background: white;
            color: inherit;
        }

        .cover {
            width: 100%;
            height: 210px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 10px;
            background: #e8e2f1;
            color: #75638d;
            font-size: 67px;
        }

        .home-book-cover {
            width: 82%;
            height: 90%;
            padding: 5px;
            overflow: hidden;
            border: 2px solid #ffffffb8;
            border-radius: 5px 12px 12px 5px;
            background: #fff8ed;
            box-shadow: 8px 10px 18px #352b3530;
        }

        .home-book-cover img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .book-card:nth-child(3n+2) .cover {
            background: #f8e1d4;
            color: #d47f6a;
        }

        .book-card:nth-child(3n) .cover {
            background: #dcece2;
            color: #52866b;
        }

        .genre {
            display: block;
            margin-top: 15px;
            color: #df6681;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .book-card h3 {
            margin: 7px 0 5px;
            font-size: 17px;
        }

        .book-card p {
            margin: 0;
            color: #77737d;
            font-size: 14px;
        }

        .club-banner {
            max-width: 1200px;
            margin: 65px auto;
            padding: 45px;
            border-radius: 22px;
            background: #e9edfa;
            text-align: center;
        }

        .club-banner p {
            color: #625e69;
        }

        footer {
            padding: 28px;
            border-top: 1px solid #eee9e4;
            color: #77737d;
            text-align: center;
        }

        @media (max-width: 800px) {
            .hero-art {
                display: none;
            }

            .book-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 520px) {
            .nav {
                flex-wrap: wrap;
            }

            .nav-links {
                gap: 12px;
                flex-wrap: wrap;
            }

            .hero {
                margin: 10px 15px;
                padding: 42px 25px;
            }

            .book-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .cover {
                height: 160px;
            }

            .club-banner {
                margin: 40px 15px;
            }
        }

        @media (prefers-reduced-motion: no-preference) {
            .scroll-effect {
                opacity: 0;
                transform: translateY(35px);
                transition: opacity 0.7s ease, transform 0.7s ease;
            }

            .scroll-effect.visible {
                opacity: 1;
                transform: translateY(0);
            }

            .book-card.scroll-effect {
    transition-delay: var(--delay, 0ms);
}

.book-card.scroll-effect:hover,
.book-card.scroll-effect:focus-visible {
    transform: translateY(-7px) scale(1.02);
}
        }

        .book-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.book-card:hover,
.book-card:focus-visible {
    transform: translateY(-7px) scale(1.02);
    box-shadow: 0 18px 35px rgba(96, 57, 76, 0.16);
}

.home-book-cover img {
    transition: transform 0.4s ease;
}

.book-card:hover .home-book-cover img,
.book-card:focus-visible .home-book-cover img {
    transform: scale(1.06);
}

@media (prefers-reduced-motion: reduce) {
    .book-card,
    .home-book-cover img {
        transition: none;
    }
}

.view-all {
    flex-shrink: 0;
    color: #c74770;
    font-size: 14px;
    font-weight: 700;
}

.view-all:hover {
    text-decoration: underline;
}
    </style>
</head>

<body>
    <header class="nav">
        <a class="logo" href="index.php">✦ ReadSpace</a>

        <nav class="nav-links">
            <a href="books.php">Books</a>
            <a href="clubs.php">Clubs</a>
            <a href="request_book.php">Request a Book</a>

            <?php if ($isLoggedIn): ?>
                <details class="profile-menu">
                    <summary>
                        <span class="profile-avatar">
                            <?= escape(mb_strtoupper(
                                mb_substr($_SESSION["username"] ?? "", 0, 1)
                            )) ?>
                        </span>
                        <span>Hi, <?= escape($_SESSION["username"] ?? "") ?></span>
                    </summary>

                    <div class="profile-dropdown">
                        <a href="profile.php">My Profile</a>

                        <form method="post" action="logout.php">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= escape($_SESSION["csrf_token"]) ?>"
                            >
                            <button type="submit">Log Out</button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <a href="login.php">Log In</a>
                <a class="join" href="registration.php">Join ReadSpace</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-copy">
                <span class="eyebrow">A place for book lovers</span>
                <h1>Every book has <span>a story.</span></h1>
                <p>
                    Discover your next read, share your thoughts, and meet
                    readers who love the same stories you do.
                </p>
                <a class="button" href="books.php">Explore Books →</a>
            </div>

            <div class="hero-art" aria-hidden="true">📚</div>
        </section>

        <section id="books" class="section">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Explore the collection</span>
                    <h2>Books to discover</h2>
                    <p>Find a story that could become your new favorite.</p>
                </div>
                   <a class="view-all" href="books.php">View all books ▷</a>
            </div>

            <div class="book-grid">
                <?php if ($books && $books->num_rows > 0): ?>
                    <?php while ($book = $books->fetch_assoc()): ?>
                        <a
                            class="book-card"
                            href="books.php?id=<?= (int) $book["book_id"] ?>"
                        >
                            <div class="cover">
                                <?php
                                $cover = $book["cover_image_url"] ?? "";

                                $isOnlineCover =
                                    filter_var($cover, FILTER_VALIDATE_URL)
                                    && str_starts_with($cover, "https://");

                                $isUploadedCover = preg_match(
                                    '~^uploads/covers/[a-f0-9]{32}\.(jpg|png|webp)$~',
                                    $cover
                                ) && is_file(__DIR__ . "/" . $cover);
                                ?>

                                <?php if ($isOnlineCover || $isUploadedCover): ?>
                                    <div class="home-book-cover">
                                        <img
                                            src="<?= escape($cover) ?>"
                                            alt="Cover of <?= escape($book["title"]) ?>"
                                        >
                                    </div>
                                <?php else: ?>
                                    📖
                                <?php endif; ?>
                            </div>

                            <span class="genre">
                                <?= escape($book["genre"]) ?>
                            </span>
                            <h3><?= escape($book["title"]) ?></h3>
                            <p><?= escape($book["author"]) ?></p>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No books to display yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="clubs" class="club-banner">
            <span class="eyebrow">Read together</span>
            <h2>Stories are better when shared.</h2>
            <p>
                Join a ReadSpace book club and talk about your favorite
                stories with other readers.
            </p>
            <a class="button" href="clubs.php">Explore Clubs →</a>
        </section>
    </main>

    <script>
        const elements = document.querySelectorAll(
            ".section-head, .book-card, .club-banner"
        );

        if ("IntersectionObserver" in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("visible");
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12
            });

            elements.forEach((element, index) => {
                if (element.classList.contains("book-card")) {
                    element.style.setProperty(
                        "--delay",
                        `${(index % 4) * 90}ms`
                    );
                }

                element.classList.add("scroll-effect");
                observer.observe(element);
            });
        }
    </script>

    <footer>
        © <?= date("Y") ?> ReadSpace · A space for every reader
    </footer>
</body>
</html>