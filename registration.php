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
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $birthDate = $_POST["birth_date"] ?? "";

        $date = DateTimeImmutable::createFromFormat("!Y-m-d", $birthDate);
        $validDate = $date && $date->format("Y-m-d") === $birthDate;

        if (strlen($username) < 3 || strlen($username) > 50) {
            $error = "Username must be between 3 and 50 characters.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $error = "Enter a valid email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!$validDate || $date > new DateTimeImmutable("today")) {
            $error = "Enter a valid date of birth.";
        } else {
            $age = $date->diff(new DateTimeImmutable("today"))->y;

            if ($age < 13) {
                $error = "You must be at least 13 years old to register.";
            } else {
                $isMinor = $age < 18 ? 1 : 0;
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO users
                         (username, email, password_hash, birth_date, is_minor)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param(
                        "ssssi",
                        $username,
                        $email,
                        $passwordHash,
                        $birthDate,
                        $isMinor
                    );
                    $stmt->execute();

                    header("Location: registration.php?success=1");
                    exit;
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $error = "This username or email is already registered.";
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                }
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
    <title>Sign Up | ReadSpace</title>
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
            min-height: 570px;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            overflow: hidden;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 20px 50px #f94c8740;
        }

        .form-panel {
            padding: 48px 55px;
        }

        .brand {
            margin: 0 0 35px;
            font-size: 20px;
            font-weight: 800;
            color: #f06073;
            letter-spacing: -0.5px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 29px;
        }

        .subtitle {
            margin: 0 0 25px;
            color: #858590;
            font-size: 14px;
            line-height: 1.5;
        }

        .field {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .field input {
            width: 100%;
            height: 42px;
            padding: 0 13px;
            border: 1px solid #e3dfe3;
            border-radius: 9px;
            outline: none;
            font: inherit;
            font-size: 14px;
            background: #fff;
        }

        .field input:focus {
            border-color: #fa7b83;
            box-shadow: 0 0 0 3px #fa7b8326;
        }

        .submit-button {
            width: 100%;
            height: 44px;
            margin-top: 9px;
            border: 0;
            border-radius: 9px;
            color: white;
            background: linear-gradient(100deg, #ffb044, #fc528a);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 7px 17px #fb78924d;
        }

        .submit-button:hover {
            filter: brightness(0.96);
        }

        .message {
            padding: 10px 12px;
            margin: 0 0 16px;
            border-radius: 8px;
            font-size: 13px;
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
            background: linear-gradient(155deg, #ffb443 0%, #ff8062 45%, #fa438f 100%);
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

            .brand {
                margin-bottom: 25px;
            }
        }
        .login-prompt {
    margin-top: 20px;
    text-align: center;
    color: #777080;
}

.login-prompt a {
    display: inline-block;
    margin-left: 6px;
    padding: 10px 18px;
    border-radius: 12px;
    background: #292437;
    color: white;
    font-weight: 700;
    text-decoration: none;
}

.login-prompt a:hover {
    background: #ee5b89;
}
    </style>
</head>
<body>
    <main class="auth-card">
        <section class="form-panel">
            <p class="brand">✦ ReadSpace</p>

            <h1>Create an account</h1>
            <p class="subtitle">
                Join ReadSpace to discover books and share your stories.
            </p>

            <?php if ($error !== ""): ?>
                <p class="message error">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_GET["success"])): ?>
                <p class="message success">
                    Your account has been created successfully!
                </p>
            <?php endif; ?>

            <form method="post" action="registration.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"
                >

                <div class="field">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        name="username"
                        maxlength="50"
                        placeholder="Enter your username"
                        required
                    >
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        maxlength="100"
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
                        minlength="8"
                        placeholder="At least 8 characters"
                        required
                    >
                </div>

                <div class="field">
                    <label for="birth_date">Date of birth</label>
                    <input
                        id="birth_date"
                        type="date"
                        name="birth_date"
                        required
                    >
                </div>

                <button class="submit-button" type="submit">
                    Sign Up
                </button>
            </form>
            <p class="login-prompt">
    Already have an account?
    <a href="login.php">Log in</a>
</p>
        </section>

        <aside class="side-panel">
            <div class="side-content">
                <span class="book-icon" aria-hidden="true">📖</span>
                <h2>Welcome to ReadSpace!</h2>
                <p>
                    Discover new books, share reviews, and connect
                    with a community of readers.
                </p>
            </div>
        </aside>
    </main>
</body>
</html>