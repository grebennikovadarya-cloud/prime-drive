document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('contact-form');
    const status = document.getElementById('form-status');

    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправляем...';
        status.textContent = '';
        status.className = 'form-status';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' }
            });

            if (response.ok) {
                status.textContent = '✓ Спасибо! Заявка отправлена — мы свяжемся с вами в ближайшее время.';
                status.classList.add('success');
                form.reset();
            } else {
                const data = await response.json().catch(() => ({}));
                status.textContent = '✕ ' + (data.errors ? data.errors.map(e => e.message).join(', ') : 'Не удалось отправить. Попробуйте ещё раз.');
                status.classList.add('error');
            }
        } catch (err) {
            status.textContent = '✕ Ошибка сети. Проверьте интернет и попробуйте ещё раз.';
            status.classList.add('error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('reviewForm');
    const list = document.getElementById('reviewsList');
    const counter = document.getElementById('reviewsCount');
    const status = document.getElementById('reviewStatus');

    if (!form || !list) return;

    async function loadReviews() {
        try {
            const res = await fetch('reviews.php', {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            if (!res.ok) throw new Error('Ошибка загрузки');

            const reviews = await res.json();
            render(reviews);
        } catch (err) {
            list.innerHTML = '<div class="reviews-empty">Не удалось загрузить отзывы</div>';
            if (counter) counter.textContent = '';
        }
    }

    function render(reviews) {
        if (counter) {
            counter.textContent = reviews.length === 1
                ? '1 отзыв'
                : reviews.length + ' отзывов';
        }

        updateRatingSummary(reviews);

        if (!reviews.length) {
            list.innerHTML = '<div class="reviews-empty">Пока нет отзывов. Будьте первым!</div>';
            return;
        }

        list.innerHTML = reviews.map(r => `
            <article class="review-card">
                <div class="review-head">
                    <span class="review-name">${escapeHtml(r.name)}</span>
                    <span class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</span>
                </div>
                <p class="review-text">${escapeHtml(r.text)}</p>
                <div class="review-head" style="margin:0">
                    <span class="review-date">${formatDate(r.date)}</span>
                </div>
            </article>
        `).join('');
    }

    function updateRatingSummary(reviews) {
        const summaryEl = document.getElementById('ratingSummary');
        const numberEl = document.getElementById('avgRating');
        const starsEl = document.getElementById('avgStars');
        const countEl = document.getElementById('avgCount');
        const textEl = document.getElementById('avgText');

        if (!summaryEl || !numberEl) return;

        if (!reviews.length) {
            summaryEl.classList.add('is-empty');
            return;
        }

        summaryEl.classList.remove('is-empty');

        const sum = reviews.reduce((acc, r) => acc + Number(r.rating), 0);
        const avg = sum / reviews.length;
        const avgRounded = Math.round(avg * 10) / 10;

        const fullStars = Math.round(avg);
        const starsLine = '★'.repeat(fullStars) + '☆'.repeat(5 - fullStars);

        numberEl.textContent = avgRounded.toFixed(1);
        starsEl.textContent = starsLine;
        countEl.textContent = reviews.length === 1
            ? '1 отзыв'
            : reviews.length + ' отзывов';

        let text = '';
        if (avg >= 4.8) text = 'Превосходный рейтинг — клиенты в восторге.';
        else if (avg >= 4.5) text = 'Отличный рейтинг — клиенты очень довольны.';
        else if (avg >= 4.0) text = 'Хороший рейтинг — клиенты в целом довольны.';
        else if (avg >= 3.0) text = 'Средний рейтинг — есть над чем поработать.';
        else text = 'Низкий рейтинг — стоит разобраться с причинами.';

        textEl.textContent = text;
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправляем…';
        status.textContent = '';
        status.className = 'form-status';

        try {
            const res = await fetch('reviews.php', {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' }
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                status.textContent = '✓ Спасибо! Ваш отзыв добавлен.';
                status.classList.add('success');
                form.reset();
                await loadReviews();
            } else {
                const msg = data.errors
                    ? data.errors.join('; ')
                    : (data.error || 'Не удалось отправить отзыв');
                status.textContent = '✗ ' + msg;
                status.classList.add('error');
            }
        } catch (err) {
            status.textContent = '✗ Ошибка сети. Попробуйте ещё раз.';
            status.classList.add('error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });

    function formatDate(iso) {
        const d = new Date(iso);
        return d.toLocaleDateString('ru-RU', {
            day: 'numeric', month: 'long', year: 'numeric'
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    (function initStars() {
        const container = document.getElementById('ratingStars');
        const hidden = document.getElementById('reviewRating');
        const label = document.getElementById('ratingLabel');
        if (!container || !hidden) return;

        const stars = container.querySelectorAll('.star');

        const LABELS = {
            1: 'Ужасно',
            2: 'Плохо',
            3: 'Нормально',
            4: 'Хорошо',
            5: 'Отлично'
        };

        function paint(value) {
            stars.forEach(s => {
                const v = Number(s.dataset.value);
                s.classList.toggle('active', v <= value);
            });
            if (label) label.textContent = LABELS[value] || '';
        }

        paint(Number(hidden.value) || 5);

        stars.forEach(star => {
            star.addEventListener('click', function () {
                const value = Number(this.dataset.value);
                hidden.value = value;
                paint(value);
            });

            star.addEventListener('mouseenter', function () {
                const value = Number(this.dataset.value);
                paint(value);
            });
        });

        container.addEventListener('mouseleave', function () {
            paint(Number(hidden.value) || 5);
        });
    })();

    loadReviews();
});

document.addEventListener('DOMContentLoaded', function () {
    const descriptionEls = document.querySelectorAll('.car-description');
    if (!descriptionEls.length) return;

    fetch('get_descriptions.php')
        .then(res => res.json())
        .then(descriptions => {
            descriptionEls.forEach(el => {
                const id = el.dataset.carId;
                if (!descriptions[id]) return;

                el.textContent = descriptions[id];

                const wrap = el.closest('.car-description-wrap');
                const toggle = wrap ? wrap.querySelector('.car-description-toggle') : null;

                if (toggle) {
                    setTimeout(() => {
                        if (el.scrollHeight > el.clientHeight + 4) {
                            toggle.hidden = false;
                        }
                    }, 50);

                    toggle.addEventListener('click', function () {
                        const expanded = el.classList.toggle('is-expanded');
                        toggle.textContent = expanded
                            ? 'Свернуть'
                            : 'Показать полностью';
                    });
                }
            });
        })
        .catch(() => {
            descriptionEls.forEach(el => {
                el.textContent = 'Описание временно недоступно.';
            });
        });
});

document.addEventListener('DOMContentLoaded', function () {
    const typeFilter  = document.getElementById('filterType');
    const yearFilter  = document.getElementById('filterYear');
    const priceMin    = document.getElementById('priceMin');
    const priceMax    = document.getElementById('priceMax');
    const priceMinLabel = document.getElementById('priceMinLabel');
    const priceMaxLabel = document.getElementById('priceMaxLabel');
    const searchInput = document.getElementById('carSearch');
    const searchClear = document.getElementById('searchClear');
    const carsContainer = document.querySelector('.cars');

    if (!typeFilter || !carsContainer) return;

    const cards = carsContainer.querySelectorAll('.card');

    function formatPrice(n) {
        return '€' + n.toLocaleString('ru-RU').replace(/,/g, ' ');
    }

    function applyFilters() {
        const type = typeFilter.value;
        const year = yearFilter.value;
        const min  = parseInt(priceMin.value, 10);
        const max  = parseInt(priceMax.value, 10);
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        let visibleCount = 0;

        cards.forEach(card => {
            const cardType  = card.dataset.type;
            const cardYear  = card.dataset.year;
            const cardPrice = parseInt(card.dataset.price, 10);
            const cardName  = (card.querySelector('h3')?.textContent || '').toLowerCase();

            let visible = true;

            if (type && cardType !== type)               visible = false;
            if (year && cardYear !== year)               visible = false;
            if (cardPrice < min || cardPrice > max)      visible = false;
            if (query && !cardName.includes(query))      visible = false;

            if (visible) {
                card.classList.remove('is-hidden');
                visibleCount++;
            } else {
                card.classList.add('is-hidden');
            }
        });

        let emptyMsg = carsContainer.querySelector('.cars-empty-message');
        if (visibleCount === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.className = 'cars-empty-message';
                emptyMsg.textContent = 'По вашему запросу ничего не найдено. Попробуйте изменить фильтры.';
                carsContainer.appendChild(emptyMsg);
            }
        } else if (emptyMsg) {
            emptyMsg.remove();
        }
    }

    function updatePriceLabels() {
        let min = parseInt(priceMin.value, 10);
        let max = parseInt(priceMax.value, 10);

        if (min > max - 1000) {
            if (this === priceMin) {
                priceMin.value = max - 1000;
                min = max - 1000;
            } else {
                priceMax.value = min + 1000;
                max = min + 1000;
            }
        }

        priceMinLabel.textContent = formatPrice(parseInt(priceMin.value, 10));
        priceMaxLabel.textContent = formatPrice(parseInt(priceMax.value, 10));
        applyFilters();
    }

    typeFilter.addEventListener('change', applyFilters);
    yearFilter.addEventListener('change', applyFilters);

    if (priceMin && priceMax) {
        priceMin.addEventListener('input', updatePriceLabels);
        priceMax.addEventListener('input', updatePriceLabels);
    }

    if (searchInput && searchClear) {
        searchInput.addEventListener('input', function () {
            searchClear.classList.toggle('visible', this.value.length > 0);
            applyFilters();
        });

        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            searchClear.classList.remove('visible');
            applyFilters();
        });
    }
});

