<?php
require_once 'config.php';
requireLogin();

$userName  = $_SESSION['user_name']  ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$initial   = mb_strtoupper(mb_substr($userName, 0, 1));
$csrf      = csrfToken();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf" content="<?= e($csrf) ?>">
<title>لوحة المهام — TaskFlow</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">

<!-- ============ HEADER ============ -->
<header class="app-header">
    <div class="header-brand">
        <div class="brand-logo brand-logo-sm">TF</div>
        <span class="brand-name">TaskFlow</span>
    </div>

    <div class="header-actions">
        <button class="icon-btn" id="themeToggle" title="تبديل الوضع">
            <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
            </svg>
            <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="4"/>
                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
            </svg>
        </button>

        <div class="user-chip" title="<?= e($userEmail) ?>">
            <div class="avatar"><?= e($initial) ?></div>
            <span class="user-name"><?= e($userName) ?></span>
        </div>

        <a href="logout.php" class="btn btn-ghost btn-sm">خروج</a>
    </div>
</header>

<!-- ============ MAIN ============ -->
<main class="app-main">

    <!-- Stats -->
    <section class="stats-grid">
        <div class="stat-card" data-accent="accent">
            <span class="stat-label">إجمالي المهام</span>
            <span class="stat-value" id="statTotal">0</span>
        </div>
        <div class="stat-card" data-accent="warning">
            <span class="stat-label">قيد الانتظار</span>
            <span class="stat-value" id="statTodo">0</span>
        </div>
        <div class="stat-card" data-accent="accent-2">
            <span class="stat-label">قيد التنفيذ</span>
            <span class="stat-value" id="statDoing">0</span>
        </div>
        <div class="stat-card" data-accent="success">
            <span class="stat-label">مكتملة</span>
            <span class="stat-value" id="statDone">0</span>
        </div>
    </section>

    <!-- Toolbar -->
    <section class="toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" id="searchInput" placeholder="بحث في المهام...">
            </div>

            <select id="filterPriority" class="select">
                <option value="">كل الأولويات</option>
                <option value="high">عالية</option>
                <option value="medium">متوسطة</option>
                <option value="low">منخفضة</option>
            </select>
        </div>

        <button class="btn btn-primary" id="btnAddTask">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            مهمة جديدة
        </button>
    </section>

    <!-- Kanban -->
    <section class="kanban" id="kanban">
        <div class="kanban-column" data-status="todo">
            <header class="column-header">
                <span class="dot dot-warning"></span>
                <h3>قيد الانتظار</h3>
                <span class="count" data-count="todo">0</span>
            </header>
            <div class="column-body" data-drop="todo"></div>
        </div>

        <div class="kanban-column" data-status="doing">
            <header class="column-header">
                <span class="dot dot-accent-2"></span>
                <h3>قيد التنفيذ</h3>
                <span class="count" data-count="doing">0</span>
            </header>
            <div class="column-body" data-drop="doing"></div>
        </div>

        <div class="kanban-column" data-status="done">
            <header class="column-header">
                <span class="dot dot-success"></span>
                <h3>مكتملة</h3>
                <span class="count" data-count="done">0</span>
            </header>
            <div class="column-body" data-drop="done"></div>
        </div>
    </section>
</main>

<!-- ============ MODAL ============ -->
<div class="modal-backdrop" id="taskModal" hidden>
    <div class="modal">
        <header class="modal-header">
            <h2 id="modalTitle">مهمة جديدة</h2>
            <button class="icon-btn" id="modalClose" type="button" title="إغلاق">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <form id="taskForm" class="modal-form" novalidate>
            <input type="hidden" id="taskId">

            <div class="field">
                <label for="taskTitle">العنوان *</label>
                <input type="text" id="taskTitle" placeholder="اكتب عنوان المهمة" maxlength="255" required>
            </div>

            <div class="field">
                <label for="taskDesc">الوصف</label>
                <textarea id="taskDesc" rows="3" placeholder="تفاصيل إضافية (اختياري)" maxlength="2000"></textarea>
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="taskStatus">الحالة</label>
                    <select id="taskStatus" class="select">
                        <option value="todo">قيد الانتظار</option>
                        <option value="doing">قيد التنفيذ</option>
                        <option value="done">مكتملة</option>
                    </select>
                </div>

                <div class="field">
                    <label for="taskPriority">الأولوية</label>
                    <select id="taskPriority" class="select">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label for="taskDue">تاريخ الاستحقاق</label>
                <input type="date" id="taskDue">
            </div>

            <footer class="modal-footer">
                <button type="button" class="btn btn-ghost" id="modalCancel">إلغاء</button>
                <button type="submit" class="btn btn-primary" id="modalSave">حفظ</button>
            </footer>
        </form>
    </div>
</div>

<!-- ============ TOASTS ============ -->
<div class="toast-container" id="toastContainer"></div>

<script src="assets/js/app.js"></script>
</body>
</html>
