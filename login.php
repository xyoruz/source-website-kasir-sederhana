<?php
session_start();
header("X-Frame-Options: DENY");

if(isset($_SESSION['admin'])) { header("Location: index.php"); exit; }

if (!isset($_SESSION['captcha_ans'])) {
    $n1 = rand(1, 9); $n2 = rand(1, 9);
    $_SESSION['captcha_ans'] = $n1 + $n2;
    $_SESSION['captcha_txt'] = "$n1 + $n2";
}

if(isset($_POST['login'])){
    $u = $_POST['username'];
    $p = $_POST['password'];
    $a = $_POST['captcha'];

    if($u === "xailla" && $p === "xailla" && $a == $_SESSION['captcha_ans']){
        session_regenerate_id();
        $_SESSION['admin'] = true;
        unset($_SESSION['captcha_ans']);
        header("Location: index.php");
        exit;
    } else {
        $error = "Gagal! Data atau Captcha salah.";
        $n1 = rand(1, 9); $n2 = rand(1, 9);
        $_SESSION['captcha_ans'] = $n1 + $n2;
        $_SESSION['captcha_txt'] = "$n1 + $n2";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Xailla</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #0f172a; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; width: 280px; text-align: center; border: 1px solid rgba(255,255,255,0.1); color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h2 { font-size: 1.2rem; letter-spacing: 3px; color: #3b82f6; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 8px 0; border-radius: 8px; border: none; background: rgba(255,255,255,0.1); color: white; box-sizing: border-box; outline: none; }
        .cap { font-size: 0.8rem; background: rgba(59,130,246,0.15); padding: 8px; border-radius: 8px; margin: 10px 0; border: 1px solid rgba(59,130,246,0.3); }
        button { width: 100%; padding: 10px; border-radius: 8px; border: none; background: #3b82f6; color: white; font-weight: bold; cursor: pointer; margin-top: 10px; }
        button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="box">
        <h2>XAILLA</h2>
        <?php if(isset($error)) echo "<p style='color:#ef4444; font-size:0.75rem'>$error</p>"; ?>
        <form method="POST" autocomplete="off">
            <input type="text" name="username" placeholder="User" required>
            <input type="password" name="password" placeholder="Pass" required>
            <div class="cap">🤖 Hitung: <?php echo $_SESSION['captcha_txt']; ?></div>
            <input type="number" name="captcha" placeholder="?" required>
            <button type="submit" name="login">MASUK</button>
        </form>
    </div>
</body>
</html>
