<?php
require_once 'config.php';
redirectIfLoggedIn();

$error  = '';
$old    = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? null)) {
        $error = 'انتهت صلاحية الجلسة، حاول مرة أخرى.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';
        $old      = ['name' => $name, 'email' => $email];

        if ($name === '' || $email === '' || $password === '') {
            $error = 'يرجى تعبئة جميع الحقول.';
        } elseif (mb_strlen($name) < 3) {
            $error = 'الاسم يجب أن يكون 3 أحرف على الأقل.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'صيغة البريد الإلكتروني غير صحيحة.';
        } elseif (strlen($password) < 6) {
            $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
        } elseif ($password !== $confirm) {
            $error = 'كلمتا المرور غير متطابقتين.';
        } else {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'هذا البريد مُسجّل بالفعل.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins  = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $ins->execute([$name, $email, $hash]);

                session_regenerate_id(true);
                $_SESSION['user_id']    = (int)$pdo->lastInsertId();
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                header('Location: dashboard.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إنشاء حساب — TaskFlow</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-orb auth-orb-1"></div>
<div class="auth-orb auth-orb-2"></div>

<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-logo">TF</div>
            <h1>ابدأ الآن</h1>
            <p>أنشئ حسابك في ثوانٍ.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form" novalidate>
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

            <div class="field">
                <label for="name">الاسم الكامل</label>
                <input type="text" id="name" name="name"
                       value="<?= e($old['name']) ?>"
                       placeholder="محمد أحمد" required autofocus>
            </div>

            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email"
                       value="<?= e($old['email']) ?>"
                       placeholder="you@example.com" required>
            </div>

            <div class="field">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password"
                       placeholder="6 أحرف على الأقل" required>
            </div>

            <div class="field">
                <label for="confirm">تأكيد كلمة المرور</label>
                <input type="password" id="confirm" name="confirm"
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                إنشاء الحساب
            </button>
        </form>

        <p class="auth-switch">
            لديك حساب بالفعل؟
            <a href="index.php">سجّل الدخول</a>
        </p>
    </div>
</main>
</body>
</html>
