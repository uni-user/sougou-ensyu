<?php
require_once 'LoginBusiness.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business = new LoginBusiness();
    $result = $business->login($_POST['username'], $_POST['password']);
    
    if ($result) {
        header('Location: main.php');
        exit;
    } else {
        $error = "ユーザー名またはパスワードが違います。";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>ログイン</title></head>
<body>
<form method="post">
    <input type="text" name="username" placeholder="ユーザー名" required>
    <input type="password" name="password" placeholder="パスワード" required>
    <button type="submit">ログイン</button>
</form>
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
