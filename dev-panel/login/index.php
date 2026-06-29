<?php

require_once("../../app.php");

// If already logged in
if (isset($_SESSION['developer_id'])) {
    header("Location: /dev-panel/");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email !== "" && $password !== "") {

        $stmt = $dbconn->prepare("
            SELECT *
            FROM developers
            WHERE email = ?
            AND is_active = 1
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $developer = $result->fetch_assoc();

            if (password_verify($password, $developer["password_hash"])) {

                $_SESSION["developer_id"] = $developer["id"];
                $_SESSION["developer_name"] = $developer["full_name"];
                $_SESSION["developer_email"] = $developer["email"];
                $_SESSION["developer_role"] = $developer["role"];

                $time = time();

                $update = $dbconn->prepare("
                    UPDATE developers
                    SET last_login=?
                    WHERE id=?
                ");

                $update->bind_param(
                    "ii",
                    $time,
                    $developer["id"]
                );

                $update->execute();

                header("Location: /dev-panel/");
                exit;

            } else {
                $error = "Invalid email or password.";
            }

        } else {
            $error = "Invalid email or password.";
        }

    } else {

        $error = "Please enter your email and password.";

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Developer Login</title>

<style>

body{
    font-family:Arial,sans-serif;
    background:#111827;
    color:#fff;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    margin:0;
}

.card{
    width:380px;
    background:#1f2937;
    padding:30px;
    border-radius:10px;
}

input{
    width:100%;
    padding:12px;
    margin-top:6px;
    margin-bottom:18px;
    box-sizing:border-box;
}

button{
    width:100%;
    padding:12px;
    cursor:pointer;
}

.error{
    color:#ff8080;
    margin-bottom:15px;
}

</style>

</head>

<body>

<div class="card">

<h2>Developer Login</h2>

<?php if($error!=""): ?>

<div class="error">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>

<form method="POST">

<label>Email</label>

<input
type="email"
name="email"
required>

<label>Password</label>

<input
type="password"
name="password"
required>

<button type="submit">
Login
</button>

</form>

</div>

</body>

</html>
