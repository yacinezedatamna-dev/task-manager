/* =========================================================
   TaskFlow 2027 — Client App
   ========================================================= */
(function () {
    'use strict';

    const API  = 'api/tasks.php';
    const CSRF = document.querySelector('meta[name="csrf"]').content;

    /* ---------- State ---------- */
    let tasks = [];
    const filters = {
        search: '',
        priority: '',
    };

    /* ---------- Elements ---------- */
    const el = {
        searchInput:    document.getElementById('searchInput'),
        filterPriority: document.getElementById('filterPriority'),
        btnAddTask:     document.getElementById('btnAddTask'),
        modal:          document.getElementById('taskModal'),
        modalTitle:     document.getElementById('modalTitle'),
        modalClose:     document.getElementById('modalClose'),
        modalCancel:    document.getElementById('modalCancel'),
        form:           document.getElementById('taskForm'),
        taskId:         document.getElementById('taskId'),
        taskTitle:      document.getElementById('taskTitle'),
        taskDesc:       document.getElementById('taskDesc'),
        taskStatus:     document.getElementById('taskStatus'),
        taskPriority:   document.getElementById('taskPriority'),
        taskDue:        document.getElementById('taskDue'),
        themeToggle:    document.getElementById('themeToggle'),
        toastContainer: document.getElementById('toastContainer'),
        statTotal:      document.getElementById('statTotal'),
        statTodo:       document.getElementById('statTodo'),
        statDoing:      document.getElementById('statDoing'),
        statDone:       document.getElementById('statDone'),
    };

    /* =========================================================
       THEME
       ========================================================= */
    const THEME_KEY = 'tf_theme';
    const savedTheme = localStorage.getItem(THEME_KEY) || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);

    el.themeToggle.addEventListener('click', () => {
        const curr = document.documentElement.getAttribute('data-theme');
        const next = curr === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem(THEME_KEY, next);
    });

    /* =========================================================
       TOASTS
       ========================================================= */
    function toast(message, type = 'info') {
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.textContent = message;
        el.toastContainer.appendChild(t);

        setTimeout(() => {
            t.classList.add('removing');
            setTimeout(() => t.remove(), 300);
        }, 3000);
    }

    /* =========================================================
       API CALLS
       ========================================================= */
    async function apiFetch(url) {
        const res = await fetch(url, { credentials: 'same-origin' });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.error || 'حدث خطأ');
        return data;
    }

    async function apiPost(payload) {
        const res = await fetch(API, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...payload, csrf: CSRF }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.error || 'حدث خطأ');
        return data;
    }

    async function loadTasks() {
        try {
            const data = await apiFetch(API);
            tasks = data.tasks || [];
            render();
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    /* =========================================================
       RENDER
       ========================================================= */
    function render() {
        const visible = tasks.filter(t => {
            if (filters.priority && t.priority !== filters.priority) return false;
            if (filters.search) {
                const q = filters.search.toLowerCase();
                const inTitle = (t.title || '').toLowerCase().includes(q);
                const inDesc  = (t.description || '').toLowerCase().includes(q);
                if (!inTitle && !inDesc) return false;
            }
            return true;
        });

        const groups = { todo: [], doing: [], done: [] };
        visible.forEach(t => groups[t.status]?.push(t));

        ['todo', 'doing', 'done'].forEach(status => {
            const body = document.querySelector(`[data-drop="${status}"]`);
            const countEl = document.querySelector(`[data-count="${status}"]`);
            body.innerHTML = '';

            if (groups[status].length === 0) {
                const empty = document.createElement('div');
                empty.className = 'column-empty';
                empty.textContent = 'لا توجد مهام';
                body.appendChild(empty);
            } else {
                groups[status].forEach(t => body.appendChild(buildCard(t)));
            }
            if (countEl) countEl.textContent = groups[status].length;
        });

        updateStats(visible);
        bindDragDrop();
    }

    function buildCard(task) {
        const card = document.createElement('div');
        card.className = 'task-card' + (task.status === 'done' ? ' completed' : '');
        card.draggable = true;
        card.dataset.id = task.id;

        const priorityLabel = { low: 'منخفضة', medium: 'متوسطة', high: 'عالية' }[task.priority] || task.priority;

        const isOverdue = task.due_date && task.status !== 'done' &&
                          new Date(task.due_date) < new Date(new Date().toDateString());

        card.innerHTML = `
            <div class="task-title">${escapeHtml(task.title)}</div>
            ${task.description ? `<div class="task-desc">${escapeHtml(task.description)}</div>` : ''}
            <div class="task-meta">
                <span class="badge badge-${task.priority}">${priorityLabel}</span>
                ${task.due_date
                    ? `<span class="due-date ${isOverdue ? 'overdue' : ''}">
                          📅 ${formatDate(task.due_date)}
                       </span>`
                    : ''}
            </div>
            <div class="task-actions">
                <button type="button" class="btn-edit" title="تعديل">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    تعديل
                </button>
                <button type="button" class="btn-del" title="حذف">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    </svg>
                    حذف
                </button>
            </div>
        `;

        card.querySelector('.btn-edit').addEventListener('click', () => openModal(task));
        card.querySelector('.btn-del').addEventListener('click', () => confirmDelete(task));

        return card;
    }

    function updateStats(visible) {
        el.statTotal.textContent = visible.length;
        el.statTodo.textContent  = visible.filter(t => t.status === 'todo').length;
        el.statDoing.textContent = visible.filter(t => t.status === 'doing').length;
        el.statDone.textContent  = visible.filter(t => t.status === 'done').length;
    }

    /* =========================================================
       HELPERS
       ========================================================= */
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function formatDate(d) {
        try {
            const date = new Date(d);
            return date.toLocaleDateString('ar', { month: 'short', day: 'numeric', year: 'numeric' });
        } catch { return d; }
    }

    /* =========================================================
       DRAG & DROP
       ========================================================= */
    function bindDragDrop() {
        document.querySelectorAll('.task-card').forEach(card => {
            card.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/plain', card.dataset.id);
                e.dataTransfer.effectAllowed = 'move';
                card.classList.add('dragging');
            });
            card.addEventListener('dragend', () => card.classList.remove('dragging'));
        });

        document.querySelectorAll('.kanban-column').forEach(col => {
            col.addEventListener('dragover', e => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                col.classList.add('drag-over');
            });
            col.addEventListener('dragleave', e => {
                if (!col.contains(e.relatedTarget)) col.classList.remove('drag-over');
            });
            col.addEventListener('drop', async e => {
                e.preventDefault();
                col.classList.remove('drag-over');
                const id = parseInt(e.dataTransfer.getData('text/plain'), 10);
                const newStatus = col.dataset.status;
                const task = tasks.find(t => t.id === id);
                if (!task || task.status === newStatus) return;

                const oldStatus = task.status;
                task.status = newStatus;
                render();

                try {
                    await apiPost({ action: 'update', id, status: newStatus });
                    toast('تم تحديث الحالة', 'success');
                } catch (err) {
                    task.status = oldStatus;
                    render();
                    toast(err.message, 'error');
                }
            });
        });
    }

    /* =========================================================
       MODAL
       ========================================================= */
    function openModal(task = null) {
        el.form.reset();
        el.taskId.value = '';

        if (task) {
            el.modalTitle.textContent = 'تعديل المهمة';
            el.taskId.value      = task.id;
            el.taskTitle.value   = task.title;
            el.taskDesc.value    = task.description || '';
            el.taskStatus.value  = task.status;
            el.taskPriority.value = task.priority;
            el.taskDue.value     = task.due_date || '';
        } else {
            el.modalTitle.textContent = 'مهمة جديدة';
            el.taskStatus.value  = 'todo';
            el.taskPriority.value = 'medium';
        }

        el.modal.hidden = false;
        setTimeout(() => el.taskTitle.focus(), 50);
    }

    function closeModal() {
        el.modal.hidden = true;
    }

    el.btnAddTask.addEventListener('click', () => openModal());
    el.modalClose.addEventListener('click', closeModal);
    el.modalCancel.addEventListener('click', closeModal);
    el.modal.addEventListener('click', e => {
        if (e.target === el.modal) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !el.modal.hidden) closeModal();
    });

    /* =========================================================
       SUBMIT FORM
       ========================================================= */
    el.form.addEventListener('submit', async e => {
        e.preventDefault();

        const id       = el.taskId.value;
        const title    = el.taskTitle.value.trim();
        const desc     = el.taskDesc.value.trim();
        const status   = el.taskStatus.value;
        const priority = el.taskPriority.value;
        const due      = el.taskDue.value || null;

        if (!title) {
            toast('العنوان مطلوب', 'error');
            el.taskTitle.focus();
            return;
        }

        const payload = { title, description: desc, status, priority, due_date: due };

        try {
            if (id) {
                const data = await apiPost({ action: 'update', id: parseInt(id, 10), ...payload });
                const idx = tasks.findIndex(t => t.id === data.task.id);
                if (idx !== -1) tasks[idx] = data.task;
                toast('تم تحديث المهمة', 'success');
            } else {
                const data = await apiPost({ action: 'create', ...payload });
                tasks.unshift(data.task);
                toast('تمت إضافة المهمة', 'success');
            }
            closeModal();
            render();
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    /* =========================================================
       DELETE
       ========================================================= */
    function confirmDelete(task) {
        if (!confirm(`حذف المهمة "${task.title}"؟`)) return;

        apiPost({ action: 'delete', id: task.id })
            .then(() => {
                tasks = tasks.filter(t => t.id !== task.id);
                render();
                toast('تم حذف المهمة', 'success');
            })
            .catch(err => toast(err.message, 'error'));
    }

    /* =========================================================
       FILTERS
       ========================================================= */
    let searchTimeout;
    el.searchInput.addEventListener('input', e => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filters.search = e.target.value.trim();
            render();
        }, 200);
    });

    el.filterPriority.addEventListener('change', e => {
        filters.priority = e.target.value;
        render();
    });

    /* =========================================================
       BOOT
       ========================================================= */
    loadTasks();
})();
