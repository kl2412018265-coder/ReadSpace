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
$title = "";
$author = "";
$genre = "";
$summary = "";
$isMature = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $genre = trim($_POST["genre"] ?? "");
    $summary = trim($_POST["summary"] ?? "");
    $isMature = ($_POST["is_mature_content"] ?? "0") === "1" ? 1 : 0;

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Invalid form. Refresh the page and try again.";
    } elseif (
        $title === "" ||
        mb_strlen($title) > 255 ||
        $author === "" ||
        mb_strlen($author) > 150 ||
        $genre === "" ||
        mb_strlen($genre) > 50 ||
        mb_strlen($summary) > 5000
    ) {
        $error = "Check the title, author, genre, and summary.";
    } else {
        $coverPath = null;
        $savedFile = null;
        $hasCover = isset($_FILES["cover"])
            && $_FILES["cover"]["error"] !== UPLOAD_ERR_NO_FILE;

        if ($hasCover) {
            $file = $_FILES["cover"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $error = "The cover could not be uploaded.";
            } elseif ($file["size"] > 5 * 1024 * 1024) {
                $error = "The cover must be 5 MB or smaller.";
            } elseif (!is_uploaded_file($file["tmp_name"])) {
                $error = "Invalid cover upload.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file["tmp_name"]);

                $allowed = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (
                    !isset($allowed[$mime]) ||
                    !getimagesize($file["tmp_name"])
                ) {
                    $error = "Choose a JPG, PNG, or WebP cover.";
                } else {
                    $folder = __DIR__ . "/uploads/covers";

                    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
                        $error = "The covers folder could not be created.";
                    } else {
                        $filename = bin2hex(random_bytes(16))
                            . "." . $allowed[$mime];
                        $savedFile = $folder . "/" . $filename;

                        if (!move_uploaded_file(
                            $file["tmp_name"],
                            $savedFile
                        )) {
                            $error = "The cover could not be saved.";
                            $savedFile = null;
                        } else {
                            $coverPath = "uploads/covers/" . $filename;
                        }
                    }
                }
            }
        }

        if ($error === "") {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO books
                     (title, author, genre, summary,
                      cover_image_url, is_mature_content)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->bind_param(
                    "sssssi",
                    $title,
                    $author,
                    $genre,
                    $summary,
                    $coverPath,
                    $isMature
                );
                $stmt->execute();
                $bookId = $conn->insert_id;
                $stmt->close();

                header("Location: books.php?id=" . $bookId);
                exit;
            } catch (mysqli_sql_exception $exception) {
                if ($savedFile !== null && is_file($savedFile)) {
                    unlink($savedFile);
                }

                $error = "The book could not be saved. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add a Book | ReadSpace</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #fffdf9;
            color: #272536;
            font-family: Arial, sans-serif;
        }

        header {
            max-width: 850px;
            margin: auto;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        a {
            color: #c74770;
            text-decoration: none;
            font-weight: bold;
        }

        .logo {
            font-size: 23px;
            font-weight: 800;
        }

        main {
            max-width: 850px;
            margin: 20px auto 80px;
            padding: 0 25px;
        }

        .heading {
            padding: 32px;
            border-radius: 20px;
            background: #fbeade;
        }

        h1 {
            margin: 0 0 10px;
            font-family: Georgia, serif;
            font-size: 38px;
        }

        .heading p {
            margin: 0;
            color: #696572;
            line-height: 1.6;
        }

        .form-card {
            margin-top: 24px;
            padding: 30px;
            border: 1px solid #efebe6;
            border-radius: 18px;
            background: white;
        }

        label {
            display: block;
            margin: 20px 0 7px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd7d4;
            border-radius: 9px;
            background: white;
            font: inherit;
        }

        textarea {
            resize: vertical;
        }

        .hint {
            color: #77737d;
            font-size: 13px;
            line-height: 1.5;
        }

        .error {
            margin-top: 20px;
            padding: 14px 18px;
            border-radius: 10px;
            background: #ffe1eb;
            color: #a52947;
        }

        button {
            margin-top: 25px;
            padding: 13px 22px;
            border: 0;
            border-radius: 10px;
            background: #272639;
            color: white;
            font: bold 14px Arial, sans-serif;
            cursor: pointer;
        }

        @media (max-width: 600px) {
            header {
                flex-wrap: wrap;
            }

            .heading,
            .form-card {
                padding: 23px;
            }
        }
    </style>
</head>

<body>
    <header>
        <a class="logo" href="index.php">✦ ReadSpace</a>
        <a href="moderator.php">← Moderator Panel</a>
    </header>

    <main>
        <section class="heading">
            <h1>Add a Book</h1>
            <p>Add a book to the ReadSpace collection.</p>
        </section>

        <?php if ($error !== ""): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <form
            class="form-card"
            method="post"
            action="add_book.php"
            enctype="multipart/form-data"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION["csrf_token"]) ?>"
            >

            <label for="title">Book title</label>
            <input
                id="title"
                type="text"
                name="title"
                maxlength="255"
                value="<?= e($title) ?>"
                required
            >

            <label for="author">Author</label>
            <input
                id="author"
                type="text"
                name="author"
                maxlength="150"
                value="<?= e($author) ?>"
                required
            >

            <label for="genre">Genre</label>
            <input
                id="genre"
                type="text"
                name="genre"
                maxlength="50"
                value="<?= e($genre) ?>"
                required
            >

            <label for="summary">Summary</label>
            <textarea
                id="summary"
                name="summary"
                rows="6"
                maxlength="5000"
            ><?= e($summary) ?></textarea>

            <label for="cover">Book cover</label>
            <input
                id="cover"
                type="file"
                name="cover"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            >
            <p class="hint">Optional. JPG, PNG, or WebP, up to 5 MB.</p>

            <label for="is_mature_content">Age category</label>
            <select
                id="is_mature_content"
                name="is_mature_content"
                required
            >
                <option value="0" <?= $isMature === 0 ? "selected" : "" ?>>
                    Suitable for ages 13+
                </option>
                <option value="1" <?= $isMature === 1 ? "selected" : "" ?>>
                    Adults only (18+)
                </option>
            </select>

            <button type="submit">Add Book</button>
        </form>
    </main>
</body>
</html>