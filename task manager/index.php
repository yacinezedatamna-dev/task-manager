<?php
require_once 'config.php';
redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? null)) {
        $error = 'انتهت صلاحية الجلسة، حاول مرة أخرى.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'يرجى تعبئة جميع الحقول.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email']= $user['email'];
                header('Location: dashboard.php');
                exit;
            }
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — TaskFlow</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-orb auth-orb-1"></div>
<div class="auth-orb auth-orb-2"></div>

<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-logo">TF</div>
            <h1>TaskFlow</h1>
            <p>نظّم مهامك، أنجز أكثر، بذكاء.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form" novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com" required autofocus>
            </div>

            <div class="field">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password"
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                تسجيل الدخول
            </button>
        </form>

        <p class="auth-switch">
            ليس لديك حساب؟
            <a href="register.php">أنشئ حسابًا جديدًا</a>
        </p>
    </div>
</main>
</body>
</html>
