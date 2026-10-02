<?php
session_start();
require "config.php";

$error = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Your form session has expired. Refresh the page and try again.";
    } else {
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
            $error = "Enter a valid email and password.";
        } else {
            $stmt = $conn->prepare(
                "SELECT user_id, username, email, password_hash, is_minor
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($password, $user["password_hash"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = (int) $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["is_minor"] = (int) $user["is_minor"];

                header("Location: login.php?success=1");
                exit;
            }

            $error = "Incorrect email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log In | ReadSpace</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: #30303b;
            background: #292b35;
        }

        .auth-card {
            width: min(100%, 900px);
            min-height: 530px;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            overflow: hidden;
            border-radius: 18px;
            background: white;
            box-shadow: 0 20px 50px #f94c8740;
        }

        .form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 55px;
        }

        .brand {
            margin: 0 0 42px;
            font-size: 20px;
            font-weight: 800;
            color: #f06073;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 29px;
        }

        .subtitle {
            margin: 0 0 27px;
            color: #858590;
            font-size: 14px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin: 0 0 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .field {
            margin-bottom: 18px;
        }

        .field input {
            width: 100%;
            height: 44px;
            padding: 0 13px;
            border: 1px solid #e3dfe3;
            border-radius: 9px;
            outline: none;
            font: inherit;
            font-size: 14px;
        }

        .field input:focus {
            border-color: #fa7b83;
            box-shadow: 0 0 0 3px #fa7b8326;
        }

        .submit-button {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 13px;
            border: 0;
            border-radius: 9px;
            color: white;
            background: linear-gradient(100deg, #ffb044, #fc528a);
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 7px 17px #fb78924d;
        }

        .switch-page {
            margin: 22px 0 0;
            text-align: center;
            color: #858590;
            font-size: 13px;
        }

        .switch-page a {
            color: #ec4c78;
            font-weight: bold;
            text-decoration: none;
        }

        .message {
            padding: 11px 13px;
            margin: 0 0 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .error {
            color: #a51f39;
            background: #ffe8ec;
        }

        .success {
            color: #246d42;
            background: #e6f5e9;
        }

        .side-panel {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 45px;
            overflow: hidden;
            color: white;
            text-align: center;
            background: linear-gradient(
                155deg,
                #ffb443 0%,
                #ff8062 45%,
                #fa438f 100%
            );
        }

        .side-panel::before,
        .side-panel::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border: 35px solid #ffffff22;
            border-radius: 50%;
        }

        .side-panel::before {
            top: -160px;
            right: -90px;
        }

        .side-panel::after {
            bottom: -190px;
            left: -110px;
        }

        .side-content {
            position: relative;
            z-index: 1;
        }

        .book-icon {
            display: block;
            margin-bottom: 20px;
            font-size: 50px;
        }

        .side-content h2 {
            margin: 0 0 14px;
            font-size: 32px;
        }

        .side-content p {
            max-width: 280px;
            margin: 0 auto;
            font-size: 15px;
            line-height: 1.7;
        }

        @media (max-width: 700px) {
            body {
                padding: 16px;
            }

            .auth-card {
                grid-template-columns: 1fr;
            }

            .form-panel {
                padding: 35px 26px;
            }

            .side-panel {
                min-height: 220px;
                padding: 30px;
            }
        }
    </style>
</head>

<body>
    <main class="auth-card">
        <section class="form-panel">
            <p class="brand">✦ ReadSpace</p>

            <?php if (isset($_SESSION["user_id"])): ?>
                <h1>Welcome back!</h1>

                <p class="message success">
                    You are logged in as
                    <?= htmlspecialchars(
                        $_SESSION["username"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>.
                </p>

                <p class="subtitle">
                    Your account is ready. Explore books and connect
                    with other readers.
                </p>

                <a class="submit-button" href="index.php">
                    Explore Books
                </a>

                <form method="post" action="logout.php">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION["csrf_token"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >
                    <button class="submit-button" type="submit">
                        Log Out
                    </button>
                </form>
            <?php else: ?>
                <h1>Log In</h1>

                <p class="subtitle">
                    Continue your reading journey on ReadSpace.
                </p>

                <?php if ($error !== ""): ?>
                    <p class="message error">
                        <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                    </p>
                <?php endif; ?>

                <form method="post" action="login.php">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION["csrf_token"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >

                    <div class="field">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button class="submit-button" type="submit">
                        Log In
                    </button>
                </form>

                <p class="switch-page">
                    Don't have an account?
                    <a href="registration.php">Sign up now</a>
                </p>
            <?php endif; ?>
        </section>

        <aside class="side-panel">
            <div class="side-content">
                <span class="book-icon" aria-hidden="true">📖</span>
                <h2>Welcome Back!</h2>
                <p>
                    Discover new books and join conversations
                    with the ReadSpace community.
                </p>
            </div>
        </aside>
    </main>
</body>
</html>