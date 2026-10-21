<?php
$config = require __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка — модерация отзывов</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0a0a0a;
            color: #fff;
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
        }

        h1 { margin: 0 0 8px; font-size: 28px; }
        .sub { color: rgba(255,255,255,.5); margin-bottom: 32px; }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            padding-bottom: 0;
        }

        .tab {
            background: none;
            border: none;
            color: rgba(255,255,255,.5);
            padding: 12px 20px;
            font-size: 15px;
            font-family: inherit;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: .2s;
        }

        .tab:hover { color: #fff; }
        .tab.active {
            color: #fff;
            border-bottom-color: #3b82f6;
        }

        .review-card {
            background: #141414;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .review-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .review-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .review-meta {
            font-size: 12px;
            color: rgba(255,255,255,.4);
        }

        .review-stars {
            color: #f5b301;
            letter-spacing: 2px;
            font-size: 15px;
        }

        .review-text {
            color: rgba(255,255,255,.8);
            line-height: 1.5;
            margin: 12px 0 16px;
            white-space: pre-wrap;
        }

        .review-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
            transition: .2s;
        }

        .btn-approve {
            background: #16a34a;
            color: #fff;
        }
        .btn-approve:hover { background: #15803d; }

        .btn-delete {
            background: rgba(255,255,255,.08);
            color: rgba(255,255,255,.7);
        }
        .btn-delete:hover {
            background: #dc2626;
            color: #fff;
        }

        .btn:disabled {
            opacity: .5;
            cursor: wait;
        }

        .empty {
            text-align: center;
            color: rgba(255,255,255,.4);
            padding: 60px 20px;
            background: #141414;
            border-radius: 14px;
        }

        .status-ok {
            background: #16a34a;
            color: #fff;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            display: inline-block;
            margin-left: 8px;
        }

        .error-box {
            background: #7f1d1d;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        #login-error {
            color: #ff5a5a;
            margin-top: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">

    <h1>Модерация отзывов</h1>
    <div class="sub">Prime Drive — админ-панель</div>

    <div id="login-box" style="display:none;">
        <div class="error-box">
            <h3 style="margin-top:0">Введите пароль администратора</h3>
            <input
                type="password"
                id="token-input"
                placeholder="Токен из config.php"
                style="width:100%;padding:12px;border-radius:8px;border:1px solid rgba(255,255,255,.2);background:#0a0a0a;color:#fff;font-family:inherit;font-size:15px;margin-bottom:12px;"
            >
            <button class="btn btn-approve" id="login-btn">Войти</button>
            <div id="login-error"></div>
        </div>
    </div>

    <div id="main-ui" style="display:none;">

        <div class="tabs">
            <button class="tab active" data-tab="pending">На модерации (<span id="count-pending">0</span>)</button>
            <button class="tab" data-tab="approved">Опубликованные (<span id="count-approved">0</span>)</button>
        </div>

        <div id="reviews-list"></div>
    </div>

</div>

<script>
(function () {
    const STORAGE_KEY = 'prime_drive_admin_token';
    let token = localStorage.getItem(STORAGE_KEY) || '';
    let currentTab = 'pending';
    let allReviews = [];

    const loginBox  = document.getElementById('login-box');
    const mainUI    = document.getElementById('main-ui');
    const listEl    = document.getElementById('reviews-list');
    const tokenInput = document.getElementById('token-input');
    const loginBtn   = document.getElementById('login-btn');
    const loginError = document.getElementById('login-error');

    function showLogin() {
        loginBox.style.display = '';
        mainUI.style.display = 'none';
    }

    function showMain() {
        loginBox.style.display = 'none';
        mainUI.style.display = '';
    }

    loginBtn.addEventListener('click', function () {
        const val = tokenInput.value.trim();
        if (!val) {
            loginError.textContent = 'Введите токен';
            return;
        }
        token = val;
        localStorage.setItem(STORAGE_KEY, token);
        loadAndRender();
    });

    tokenInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') loginBtn.click();
    });

    async function loadAndRender() {
        try {
            const res = await fetch('reviews.php?all=1', {
                headers: {
                    'Accept': 'application/json',
                    'X-Admin-Token': token
                }
            });

            if (res.status === 403) {
                localStorage.removeItem(STORAGE_KEY);
                token = '';
                showLogin();
                return;
            }

            if (!res.ok) throw new Error('load error');

            allReviews = await res.json();
            showMain();
            render();
        } catch (err) {
            showLogin();
            loginError.textContent = 'Ошибка загрузки. Проверьте пароль.';
        }
    }

    function render() {
        const pending  = allReviews.filter(r => r.approved === 0);
        const approved = allReviews.filter(r => r.approved === 1);

        document.getElementById('count-pending').textContent  = pending.length;
        document.getElementById('count-approved').textContent = approved.length;

        const list = currentTab === 'pending' ? pending : approved;

        if (!list.length) {
            listEl.innerHTML = '<div class="empty">' +
                (currentTab === 'pending'
                    ? 'Нет отзывов на модерации'
                    : 'Нет опубликованных отзывов') +
                '</div>';
            return;
        }

        listEl.innerHTML = list.map(r => `
            <div class="review-card">
                <div class="review-head">
                    <div>
                        <div class="review-name">
                            ${escapeHtml(r.name)}
                            ${r.approved === 1 ? '<span class="status-ok">опубликован</span>' : ''}
                        </div>
                        <div class="review-meta">${formatDate(r.date)}</div>
                    </div>
                    <div class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div>
                </div>
                <div class="review-text">${escapeHtml(r.text)}</div>
                <div class="review-actions">
                    ${r.approved === 0
                        ? `<button class="btn btn-approve" data-action="approve" data-id="${r.id}">✓ Одобрить</button>`
                        : ''
                    }
                    <button class="btn btn-delete" data-action="delete" data-id="${r.id}">Удалить</button>
                </div>
            </div>
        `).join('');
    }

    listEl.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const id     = btn.dataset.id;

        if (action === 'delete' && !confirm('Удалить отзыв навсегда?')) return;

        btn.disabled = true;

        try {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('id', id);

            const res = await fetch('reviews.php', {
                method: 'POST',
                body: fd,
                headers: {
                    'Accept': 'application/json',
                    'X-Admin-Token': token
                }
            });

            if (res.status === 403) {
                localStorage.removeItem(STORAGE_KEY);
                token = '';
                showLogin();
                return;
            }

            if (!res.ok) throw new Error('action failed');

            await loadAndRender();
        } catch (err) {
            alert('Не удалось выполнить действие');
            btn.disabled = false;
        }
    });

    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentTab = this.dataset.tab;
            render();
        });
    });

    function formatDate(iso) {
        const d = new Date(iso);
        return d.toLocaleDateString('ru-RU', {
            day: 'numeric', month: 'long', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    if (token) {
        loadAndRender();
    } else {
        showLogin();
    }
})();
</script>

</body>
</html>