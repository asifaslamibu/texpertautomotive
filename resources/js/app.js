import './bootstrap';
import intlTelInput from 'intl-tel-input';
import 'intl-tel-input/styles';

/* ---------------------------------------------------------
   Scroll reveal — reveal [data-reveal] elements on enter
--------------------------------------------------------- */
function initReveal() {
    const els = document.querySelectorAll('[data-reveal]');
    if (!els.length) return;

    if (!('IntersectionObserver' in window)) {
        els.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }
    );

    els.forEach((el) => io.observe(el));
}

/* ---------------------------------------------------------
   Header shadow on scroll
--------------------------------------------------------- */
function initHeaderScroll() {
    const header = document.querySelector('[data-header]');
    if (!header) return;

    let ticking = false;
    let stuck = false;

    // The header stays visible at every scroll position; scrolling only compacts
    // it and lifts it off the page with a shadow.
    //
    // Compacting changes the header's height, which moves the page under it — so a
    // single threshold makes the header flip back and forth (a visible shake) while
    // scrolling around it. Two far-apart thresholds mean a flip can never re-trigger
    // itself: it compacts well down the page and only expands again near the top.
    const COMPACT_AT = 120;
    const EXPAND_AT = 40;

    const update = () => {
        const y = window.scrollY;
        if (!stuck && y > COMPACT_AT) stuck = true;
        else if (stuck && y < EXPAND_AT) stuck = false;

        header.classList.toggle('is-stuck', stuck);
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });

    update();
}

/* ---------------------------------------------------------
   Mobile slide-over menu + accordion
--------------------------------------------------------- */
document.addEventListener('click', (e) => {
    const panel = document.querySelector('[data-menu-panel]');
    if (panel) {
        if (e.target.closest('[data-menu-toggle]')) {
            panel.classList.remove('hidden');
            requestAnimationFrame(() => panel.classList.add('is-open'));
            document.body.style.overflow = 'hidden';
        }
        if (e.target.closest('[data-menu-close]') || e.target.closest('[data-menu-overlay]')) {
            panel.classList.remove('is-open');
            document.body.style.overflow = '';
            setTimeout(() => panel.classList.add('hidden'), 250);
        }
    }

    // mobile accordion
    const acc = e.target.closest('[data-acc-toggle]');
    if (acc) {
        const body = acc.nextElementSibling;
        const open = body.classList.toggle('hidden');
        acc.querySelector('[data-acc-icon]')?.classList.toggle('rotate-180', !open);
    }
});

/* ---------------------------------------------------------
   Admin sidebar (mobile)
--------------------------------------------------------- */
function setAdminSidebar(open) {
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    if (!sidebar || !overlay) return;

    sidebar.classList.toggle('-translate-x-full', !open);
    overlay.classList.toggle('hidden', !open);
    // stop the page behind from scrolling while the mobile menu is open
    document.body.classList.toggle('overflow-hidden', open);
}

document.addEventListener('click', (e) => {
    if (e.target.closest('[data-sidebar-toggle]')) {
        setAdminSidebar(true);
    } else if (e.target.closest('[data-sidebar-close], [data-sidebar-overlay], [data-sidebar] a')) {
        setAdminSidebar(false);
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setAdminSidebar(false);
});

// switching to desktop width: drop the mobile scroll lock
window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
    if (e.matches) setAdminSidebar(false);
});

/* ---------------------------------------------------------
   Reviews slider (scroll-snap carousel)
--------------------------------------------------------- */
function initSliders() {
    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const slides = slider.querySelectorAll('[data-slide]');
        // controls may live inside the slider OR in the surrounding section (e.g. header arrows)
        const scope = slider.closest('section') || slider.parentElement || slider;
        const dotsWrap = slider.querySelector('[data-slider-dots]') || scope.querySelector('[data-slider-dots]');
        const prev = slider.querySelector('[data-slider-prev]') || scope.querySelector('[data-slider-prev]');
        const next = slider.querySelector('[data-slider-next]') || scope.querySelector('[data-slider-next]');
        if (!track || !slides.length) return;

        const gap = () => parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '0') || 0;
        const step = () => slides[0].getBoundingClientRect().width + gap(); // card + actual gap
        const perView = () => Math.max(1, Math.round(track.clientWidth / step()));

        // Heavy hero videos only load/play on tablet+ — on phones the slides show
        // their text on the dark background instead, so mobile never downloads the
        // multi-MB clips (big LCP + data win).
        const mqDesktop = window.matchMedia('(min-width: 768px)');
        const videosEnabled = () => mqDesktop.matches;

        // build dots (one per slide)
        if (dotsWrap) {
            dotsWrap.innerHTML = '';
            slides.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.className = 'slider-dot';
                dot.setAttribute('aria-label', `Go to review ${i + 1}`);
                dot.addEventListener('click', () => track.scrollTo({ left: step() * i, behavior: 'smooth' }));
                dotsWrap.appendChild(dot);
            });
        }

        const activeIndex = () => Math.round(track.scrollLeft / step());
        const syncDots = () => {
            if (!dotsWrap) return;
            const active = activeIndex();
            dotsWrap.querySelectorAll('.slider-dot').forEach((d, i) => d.classList.toggle('is-active', i === active));
        };

        // restart the active slide's video from the start; pause the others
        let lastActive = -1;
        const syncVideos = () => {
            const active = activeIndex();
            if (active === lastActive) return;
            lastActive = active;
            slides.forEach((slide, i) => {
                const v = slide.querySelector('video');
                if (!v) return;
                if (i === active && videosEnabled()) {
                    try { v.currentTime = 0; const p = v.play(); if (p) p.catch(() => {}); } catch (e) {}
                } else {
                    try { v.pause(); } catch (e) {}
                }
            });
        };

        prev?.addEventListener('click', () => track.scrollBy({ left: -step() * perView(), behavior: 'smooth' }));
        next?.addEventListener('click', () => {
            const max = track.scrollWidth - track.clientWidth - 4;
            if (track.scrollLeft >= max) track.scrollTo({ left: 0, behavior: 'smooth' });
            else track.scrollBy({ left: step() * perView(), behavior: 'smooth' });
        });

        track.addEventListener('scroll', () => { window.requestAnimationFrame(() => { syncDots(); syncVideos(); }); }, { passive: true });
        syncDots();
        syncVideos();

        // autoplay: video slides advance when the video finishes; other slides use the
        // interval. Keeps running after manual navigation; only pauses while hovered.
        if (!slider.hasAttribute('data-no-autoplay')) {
            const interval = parseInt(slider.dataset.interval, 10) || 5000;
            let timer = null;
            let hovering = false;

            const currentVideo = () => {
                if (!videosEnabled()) return null; // phones: videos never play, so the timer drives advance
                const slide = slides[activeIndex()];
                return slide ? slide.querySelector('video') : null;
            };

            const arm = () => {
                clearTimeout(timer);
                if (hovering) return;
                const v = currentVideo();
                if (v && !v.ended) return; // let the video drive the advance via its 'ended' event
                timer = setTimeout(() => next?.click(), interval);
            };

            // advance as soon as a slide's video finishes
            slides.forEach((slide) => {
                const v = slide.querySelector('video');
                if (v) v.addEventListener('ended', () => { if (!hovering) next?.click(); });
            });

            slider.addEventListener('mouseenter', () => { hovering = true; clearTimeout(timer); });
            slider.addEventListener('mouseleave', () => { hovering = false; arm(); });

            // re-arm whenever the active slide changes (covers autoplay AND manual arrows/dots)
            track.addEventListener('scroll', () => { window.requestAnimationFrame(arm); }, { passive: true });

            arm();
        }
    });
}

/* ---------------------------------------------------------
   Brand strip — continuous auto-scroll (marquee) with side arrows.
   markup: [data-brand-scroller] > [data-brand-track] (list duplicated x2),
   plus [data-brand-prev] / [data-brand-next] buttons.
--------------------------------------------------------- */
function initBrandScroller() {
    document.querySelectorAll('[data-brand-scroller]').forEach((scope) => {
        const track = scope.querySelector('[data-brand-track]');
        if (!track) return;
        const prev = scope.querySelector('[data-brand-prev]');
        const next = scope.querySelector('[data-brand-next]');
        let paused = false;

        const half = () => track.scrollWidth / 2; // list is duplicated, so half === one full set

        // The strip inherits `scroll-behavior: smooth`, which turns every tiny
        // step of the marquee into its own animation — the drift then stutters and
        // runs at the browser's pace instead of ours. The arrows still ask for
        // smooth scrolling explicitly, so only this drift becomes instant.
        track.style.scrollBehavior = 'auto';

        // Speed is per second, not per frame — a 120 Hz phone drew twice as many
        // frames and so scrolled twice as fast. Phones get a gentler pace.
        const SPEED_DESKTOP = 34;   // px per second
        const SPEED_PHONE = 20;
        const phone = window.matchMedia('(max-width: 640px)');
        const slowMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        // The browser stores scrollLeft in whole pixels, so adding a fraction of a
        // pixel per frame either vanishes (nothing moves) or rounds up to a full
        // pixel (far too fast). Keep our own position and hand over whole pixels.
        let pos = track.scrollLeft;
        let last = 0;

        const step = (now) => {
            const elapsed = last ? Math.min(100, now - last) : 0;   // ignore long gaps (tab was hidden)
            last = now;

            if (!paused && !document.hidden && half() > 0) {
                pos += (phone.matches ? SPEED_PHONE : SPEED_DESKTOP) * (elapsed / 1000);
                if (pos >= half()) pos -= half();
                else if (pos < 0) pos += half();
                track.scrollLeft = pos;
            } else {
                pos = track.scrollLeft;   // the buyer (or an arrow) moved it — follow along
            }
            requestAnimationFrame(step);
        };

        scope.addEventListener('mouseenter', () => (paused = true));
        scope.addEventListener('mouseleave', () => (paused = false));

        // touch: hold still while a finger is on the strip, then drift again
        scope.addEventListener('touchstart', () => { paused = true; }, { passive: true });
        scope.addEventListener('touchend', () => {
            clearTimeout(scope._brandT);
            scope._brandT = setTimeout(() => (paused = false), 1200);
        }, { passive: true });

        // someone who asked for less motion gets a strip they scroll themselves
        if (slowMotion.matches) return;

        const nudge = (dir) => {
            paused = true;
            track.scrollBy({ left: dir * 260, behavior: 'smooth' });
            clearTimeout(scope._brandT);
            scope._brandT = setTimeout(() => (paused = false), 1400);
        };
        prev?.addEventListener('click', () => nudge(-1));
        next?.addEventListener('click', () => nudge(1));

        // a hidden tab pauses; picking up where it left off avoids a jump
        document.addEventListener('visibilitychange', () => { last = 0; });

        requestAnimationFrame(step);
    });
}

/* ---------------------------------------------------------
   Bulk select + delete on admin data tables
   markup: [data-bulk] wraps a [data-bulk-all] header checkbox,
   [data-bulk-item] row checkboxes, and a [data-bulk-bar] action bar
   with a [data-bulk-count] counter.
--------------------------------------------------------- */
function initBulk() {
    document.querySelectorAll('[data-bulk]').forEach((scope) => {
        const all = scope.querySelector('[data-bulk-all]');
        const bar = scope.querySelector('[data-bulk-bar]');
        const count = scope.querySelector('[data-bulk-count]');
        const items = () => Array.from(scope.querySelectorAll('[data-bulk-item]'));

        const sync = () => {
            const list = items();
            const sel = list.filter((i) => i.checked).length;
            if (bar) bar.classList.toggle('hidden', sel === 0);
            if (count) count.textContent = sel;
            if (all) { all.checked = sel > 0 && sel === list.length; all.indeterminate = sel > 0 && sel < list.length; }
        };

        if (all) all.addEventListener('change', () => { items().forEach((i) => (i.checked = all.checked)); sync(); });
        scope.addEventListener('change', (e) => { if (e.target.matches('[data-bulk-item]')) sync(); });

        // Each [data-bulk-submit] posts the checked ids to its own action. Delete is the
        // default; data-bulk-method / -confirm / -fields let a list add other actions
        // (data-bulk-fields is a comma-separated list of selectors whose values ride along).
        scope.querySelectorAll('[data-bulk-submit]').forEach((submit) => submit.addEventListener('click', () => {
            const ids = items().filter((i) => i.checked).map((i) => i.value);
            if (!ids.length) return;

            const method  = submit.dataset.bulkMethod || 'DELETE';
            const confirmTpl = submit.dataset.bulkConfirm
                || 'Delete %n selected item(s)? This cannot be undone.';
            if (!confirm(confirmTpl.replace('%n', ids.length))) return;

            const token = (scope.querySelector('input[name="_token"]') || {}).value || (document.querySelector('meta[name="csrf-token"]') || {}).content;
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = submit.dataset.bulkAction;
            f.innerHTML = '<input type="hidden" name="_token" value="' + token + '"><input type="hidden" name="_method" value="' + method + '">';

            const add = (name, value) => {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = name; i.value = value;
                f.appendChild(i);
            };

            ids.forEach((id) => add('ids[]', id));

            (submit.dataset.bulkFields || '').split(',').map((s) => s.trim()).filter(Boolean).forEach((sel) => {
                const field = scope.querySelector(sel);
                if (!field) return;
                add(field.dataset.bulkName || field.name, field.value);
            });

            document.body.appendChild(f);
            f.submit();
        }));

        sync();
    });
}

/* ---------------------------------------------------------
   Background videos — apply admin-set playback speed (data-speed)
--------------------------------------------------------- */
function initVideoSpeeds() {
    document.querySelectorAll('video[data-speed]').forEach((video) => {
        const rate = parseFloat(video.dataset.speed);
        if (!rate || rate <= 0 || rate === 1) return;
        const apply = () => { try { video.playbackRate = rate; } catch (e) {} };
        apply();
        video.addEventListener('loadedmetadata', apply);
        video.addEventListener('play', apply);
    });
}

/* ---------------------------------------------------------
   Checkout order summary — folds open/closed on phones
--------------------------------------------------------- */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-summary-toggle]');
    if (!btn) return;

    const body = document.querySelector('[data-summary-body]');
    if (!body) return;

    const closed = body.classList.toggle('hidden');
    btn.setAttribute('aria-expanded', String(!closed));
    const label = btn.querySelector('[data-summary-toggle-text]');
    if (label) label.textContent = closed ? 'Show order summary' : 'Hide order summary';
    btn.querySelector('[data-summary-chevron]')?.classList.toggle('rotate-180', !closed);
});

/* ---------------------------------------------------------
   Phone fields ([data-phone-intl]) — country picker with flags.
   The flag follows whatever country the typed number belongs to, the
   number is formatted as the buyer types, and only digits get through.
   On submit the field carries the full international number (+1 214…).
--------------------------------------------------------- */
function initPhoneInputs() {
    document.querySelectorAll('[data-phone-intl]').forEach((input) => {
        if (input.dataset.itiReady) return;
        input.dataset.itiReady = '1';

        const iti = intlTelInput(input, {
            initialCountry: input.dataset.country || 'us',
            countryOrder: ['us', 'ca', 'gb'],
            // the dial code lives in the field, so typing +44… switches the flag
            separateDialCode: false,
            nationalMode: false,
            // parses / formats / validates per country; loaded on demand
            loadUtils: () => import('intl-tel-input/utils'),
            formatAsYouType: true,
            strictMode: true,          // digits only, capped at the country's length
        });
        input.itiInstance = iti;

        // hand the server the full international number
        input.form?.addEventListener('submit', () => {
            const full = iti.getNumber();
            if (full) input.value = full;
        }, true);
    });
}

// The exact count is only quoted while stock is low (matches Product::SHOW_COUNT_BELOW).
const SHOW_COUNT_BELOW = 25;
function stockLimitMessage(max) {
    return max < SHOW_COUNT_BELOW
        ? `Only ${max} in stock — you can't add more than that.`
        : "That's the most you can order of this item.";
}

/* ---------------------------------------------------------
   Quantity steppers (data-qty groups)
--------------------------------------------------------- */
// typing a number straight into the box: clip it to the stock too
document.addEventListener('change', (e) => {
    const input = e.target.closest('[data-qty-input]');
    if (!input) return;

    const max = parseInt(input.getAttribute('max'), 10);
    const v = parseInt(input.value, 10) || 1;
    if (max > 0 && v > max) {
        input.value = max;
        cartToast(stockLimitMessage(max));
    } else if (v < 1) {
        input.value = 1;
    }
}, true);


document.addEventListener('click', (e) => {
    const dec = e.target.closest('[data-qty-dec]');
    const inc = e.target.closest('[data-qty-inc]');
    if (!dec && !inc) return;
    const group = (dec || inc).closest('[data-qty]');
    const input = group?.querySelector('[data-qty-input]');
    if (!input) return;
    const before = parseInt(input.value, 10) || 1;
    let v = inc ? before + 1 : Math.max(1, before - 1);

    // never step past the stock on hand (max="…" on the input), and say why
    const max = parseInt(input.getAttribute('max'), 10);
    if (max > 0 && v > max) {
        v = max;
        cartToast(stockLimitMessage(max));
    }

    input.value = v;
    // already at the limit: nothing changed, so don't re-submit the cart
    if (v !== before) input.dispatchEvent(new Event('change', { bubbles: true }));
});

/* ---------------------------------------------------------
   Load more (products listing) — append next page via AJAX
--------------------------------------------------------- */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-load-more]');
    if (!btn) return;
    const url = btn.dataset.next;
    if (!url) return;

    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Loading…';

    try {
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const grid = document.querySelector('[data-products-grid]');
        const newItems = doc.querySelectorAll('[data-products-grid] > *');

        newItems.forEach((node) => {
            node.classList.add('is-visible'); // reveal appended cards immediately
            grid.appendChild(node);
        });

        const shown = document.querySelector('[data-shown-count]');
        if (shown) shown.textContent = grid.children.length;

        const nextUrl = doc.querySelector('[data-load-more]')?.dataset.next;
        if (nextUrl) {
            btn.dataset.next = nextUrl;
            btn.disabled = false;
            btn.textContent = original;
        } else {
            btn.closest('[data-load-more-wrap]')?.remove();
        }
    } catch (err) {
        btn.disabled = false;
        btn.textContent = original;
    }
});

/* ---------------------------------------------------------
   Loading overlay for slow form submits (e.g. CSV import)
--------------------------------------------------------- */
document.addEventListener('submit', (e) => {
    const form = e.target.closest('[data-loading-form]');
    if (!form) return; // only fires after the browser's own required-field validation passes
    const overlay = document.querySelector('[data-loading-overlay]');
    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((b) => {
        b.disabled = true;
        b.classList.add('opacity-60', 'cursor-wait');
    });
});


/* ---------------------------------------------------------
   Product gallery — click a thumbnail to swap the main image
--------------------------------------------------------- */
document.addEventListener('click', (e) => {
    const thumb = e.target.closest('[data-gallery-thumb]');
    if (!thumb) return;
    const gallery = thumb.closest('[data-gallery]');
    const main = gallery?.querySelector('[data-gallery-main]');
    if (main) main.src = thumb.dataset.img;
    gallery?.querySelectorAll('[data-gallery-thumb]').forEach((t) => {
        t.classList.toggle('border-amber-400', t === thumb);
        t.classList.toggle('border-slate-200', t !== thumb);
    });
});

/* ---------------------------------------------------------
   Image upload live preview (admin product form)
--------------------------------------------------------- */
document.addEventListener('change', (e) => {
    const input = e.target.closest('[data-image-input]');
    if (!input || !input.files?.[0]) return;
    // Scope the preview to the input's own field group so multiple uploads
    // (e.g. header + footer logo) update independently; fall back to the page.
    const scope = input.closest('[data-image-field]') || document;
    const preview = scope.querySelector('[data-image-preview]');
    if (preview) {
        preview.src = URL.createObjectURL(input.files[0]);
        preview.classList.remove('hidden');
    }
    scope.querySelector('[data-image-placeholder]')?.classList.add('hidden');
});

/* ---------------------------------------------------------
   Searchable combobox (e.g. category picker on product form)
   Markup: [data-combobox] wrapping a hidden [data-combobox-value],
   a text [data-combobox-input], and a [data-combobox-list] of
   [data-combobox-option] items (each with data-value + data-label).
--------------------------------------------------------- */
function comboFilter(cb, term) {
    const list = cb.querySelector('[data-combobox-list]');
    if (!list) return;
    list.classList.remove('hidden');
    const q = (term || '').trim().toLowerCase();
    let visible = 0;
    cb.querySelectorAll('[data-combobox-option]').forEach((li) => {
        const show = q === '' || li.textContent.toLowerCase().includes(q);
        li.classList.toggle('hidden', !show);
        if (show) visible++;
    });
    cb.querySelector('[data-combobox-empty]')?.classList.toggle('hidden', visible > 0);
}

function comboClose(cb) {
    cb.querySelector('[data-combobox-list]')?.classList.add('hidden');
    // restore the text input to the chosen label (or clear if nothing chosen)
    const hidden = cb.querySelector('[data-combobox-value]');
    const input = cb.querySelector('[data-combobox-input]');
    if (!hidden || !input) return;
    const chosen = cb.querySelector(`[data-combobox-option][data-value="${hidden.value}"]`);
    input.value = hidden.value && chosen ? chosen.dataset.label : '';
}

// open + filter as the user focuses / types
document.addEventListener('focusin', (e) => {
    const input = e.target.closest('[data-combobox-input]');
    if (!input) return;
    comboFilter(input.closest('[data-combobox]'), '');
});
document.addEventListener('input', (e) => {
    const input = e.target.closest('[data-combobox-input]');
    if (!input) return;
    comboFilter(input.closest('[data-combobox]'), input.value);
});

// choose an option
document.addEventListener('click', (e) => {
    const opt = e.target.closest('[data-combobox-option]');
    if (opt) {
        const cb = opt.closest('[data-combobox]');
        cb.querySelector('[data-combobox-value]').value = opt.dataset.value;
        cb.querySelector('[data-combobox-input]').value = opt.dataset.label;
        cb.querySelector('[data-combobox-list]').classList.add('hidden');
        return;
    }
    // click outside any open combobox -> close it
    document.querySelectorAll('[data-combobox]').forEach((cb) => {
        if (!cb.contains(e.target)) comboClose(cb);
    });
});

// keyboard: Enter picks the first match, Escape closes
document.addEventListener('keydown', (e) => {
    const input = e.target.closest('[data-combobox-input]');
    if (!input) return;
    const cb = input.closest('[data-combobox]');
    if (e.key === 'Enter') {
        e.preventDefault();
        cb.querySelector('[data-combobox-option]:not(.hidden)')?.click();
    } else if (e.key === 'Escape') {
        comboClose(cb);
    }
});

/* ---------------------------------------------------------
   AJAX cart + slide-out drawer
--------------------------------------------------------- */
// also used by the checkout page's own scripts
window.cartToast = (msg) => cartToast(msg);

function cartToast(msg) {
    if (!msg) return;

    // one at a time — a second message replaces the first
    document.querySelectorAll('[data-cart-toast]').forEach((el) => el.remove());

    const t = document.createElement('div');
    t.setAttribute('data-cart-toast', '');
    t.setAttribute('role', 'status');
    // phones: a full-width card under the header, so a long line reads properly.
    // wider screens keep the pill.
    // centring is done by the inline transform below — a Tailwind translate class
    // would be a second, separate shift in v4 and push the toast off-centre
    t.className = 'fixed left-1/2 top-20 z-[60] flex w-[calc(100%-1.5rem)] max-w-sm items-start gap-2.5 '
        + 'rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold leading-snug text-white shadow-xl shadow-slate-900/20 '
        + 'sm:w-auto sm:max-w-lg sm:items-center sm:rounded-full sm:px-5';

    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('viewBox', '0 0 24 24');
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '2');
    icon.setAttribute('stroke-linecap', 'round');
    icon.setAttribute('class', 'mt-0.5 h-4 w-4 shrink-0 text-amber-400 sm:mt-0');
    icon.innerHTML = '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>';

    const text = document.createElement('span');
    text.className = 'min-w-0';
    text.textContent = msg;

    t.append(icon, text);
    document.body.appendChild(t);

    // slide in, hold, fade out
    t.style.transition = 'opacity .25s ease, transform .25s ease';
    t.style.opacity = '0';
    t.style.transform = 'translate(-50%, -0.5rem)';
    requestAnimationFrame(() => {
        t.style.opacity = '1';
        t.style.transform = 'translate(-50%, 0)';
    });

    setTimeout(() => {
        t.style.opacity = '0';
        t.style.transform = 'translate(-50%, -0.5rem)';
        setTimeout(() => t.remove(), 300);
    }, 2800);
}

function updateCartUI(data) {
    document.querySelectorAll('[data-cart-count]').forEach((el) => {
        el.textContent = data.count;
        el.classList.toggle('hidden', !(data.count > 0));
    });
    const contents = document.querySelector('[data-cart-contents]');
    if (contents && typeof data.html === 'string') contents.innerHTML = data.html;
}

function openCartDrawer() {
    const d = document.querySelector('[data-cart-drawer]');
    if (!d) return;
    d.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        d.querySelector('[data-cart-panel]')?.classList.remove('translate-x-full');
        d.querySelector('[data-cart-overlay]')?.classList.remove('opacity-0');
    });
}

function closeCartDrawer() {
    const d = document.querySelector('[data-cart-drawer]');
    if (!d) return;
    d.querySelector('[data-cart-panel]')?.classList.add('translate-x-full');
    d.querySelector('[data-cart-overlay]')?.classList.add('opacity-0');
    document.body.style.overflow = '';
    setTimeout(() => d.classList.add('hidden'), 300);
}

async function submitCartForm(form, { open = false } = {}) {
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: new FormData(form),
        });
        if (!res.ok) throw new Error('Request failed');
        const data = await res.json();
        if (data.track) window.trackEcom?.add_to_cart(data.track);
        updateCartUI(data);
        if (open) openCartDrawer();
        cartToast(data.flash);
    } catch (err) {
        form.submit(); // graceful no-JS-style fallback (full page submit)
    }
}

// intercept add / update / remove / clear cart forms
document.addEventListener('submit', (e) => {
    const form = e.target.closest('[data-cart-add], [data-cart-update], [data-cart-remove], [data-cart-clear]');
    if (!form) return;
    // "Buy Now" submits natively so the server can redirect straight to checkout.
    if (e.submitter && e.submitter.hasAttribute('data-buy-now')) return;
    e.preventDefault();
    submitCartForm(form, { open: form.hasAttribute('data-cart-add') });
});

// qty stepper change inside the drawer → push the update
document.addEventListener('change', (e) => {
    const input = e.target.closest('[data-qty-input]');
    const form = input?.closest('[data-cart-update]');
    if (form) submitCartForm(form);
});

// open / close the drawer
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-cart-open]')) {
        e.preventDefault();
        openCartDrawer();
    } else if (e.target.closest('[data-cart-close]') || e.target.closest('[data-cart-overlay]')) {
        closeCartDrawer();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCartDrawer();
});

/* ---------------------------------------------------------
   Header search autocomplete (live suggestions dropdown)
--------------------------------------------------------- */
function hideSearch(box) {
    if (!box) return;
    box.classList.add('hidden');
    box.innerHTML = '';
}

function renderSearch(data, box, form, term) {
    const products = data.products || [];
    const q = data.query || term;
    box.innerHTML = '';

    if (!products.length) {
        const empty = document.createElement('div');
        empty.className = 'px-4 py-6 text-center text-sm text-slate-500';
        empty.textContent = `No products match “${q}”.`;
        box.appendChild(empty);
        box.classList.remove('hidden');
        return;
    }

    products.forEach((p) => {
        const a = document.createElement('a');
        a.href = p.url;
        a.className = 'flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50';

        const img = document.createElement('img');
        img.src = p.image || '';
        img.alt = '';
        img.loading = 'lazy';
        img.className = 'h-11 w-11 shrink-0 rounded-lg border border-slate-200 bg-slate-50 object-cover';

        const info = document.createElement('div');
        info.className = 'min-w-0 flex-1';
        const name = document.createElement('p');
        name.className = 'truncate text-sm font-semibold text-slate-800';
        name.textContent = p.name;
        const meta = document.createElement('p');
        meta.className = 'truncate text-xs text-slate-400';
        meta.textContent = [p.brand, p.sku ? 'SKU: ' + p.sku : ''].filter(Boolean).join(' · ');
        info.appendChild(name);
        info.appendChild(meta);

        const price = document.createElement('span');
        price.className = 'shrink-0 text-sm font-bold text-slate-900';
        price.textContent = p.price;

        a.appendChild(img);
        a.appendChild(info);
        a.appendChild(price);
        box.appendChild(a);
    });

    const all = document.createElement('a');
    all.href = (form.getAttribute('action') || '') + '?q=' + encodeURIComponent(q);
    all.className = 'block border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-center text-sm font-bold text-amber-600 hover:bg-amber-50';
    all.textContent = `View all results for “${q}” →`;
    box.appendChild(all);

    box.classList.remove('hidden');
}

async function runSearch(term, box, form) {
    const base = document.querySelector('meta[name="search-suggest-url"]')?.content;
    if (!base) return;
    try {
        const res = await fetch(base + '?q=' + encodeURIComponent(term), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        if (!res.ok) throw new Error('bad status');
        renderSearch(await res.json(), box, form, term);
    } catch (err) {
        hideSearch(box);
    }
}

function searchFromInput(input) {
    const form = input.closest('[data-search]');
    const box = form?.querySelector('[data-search-results]');
    if (!box) return;
    const term = input.value.trim();
    clearTimeout(input._searchTimer);
    if (term.length < 2) {
        hideSearch(box);
        return;
    }
    input._searchTimer = setTimeout(() => runSearch(term, box, form), 220);
}

document.addEventListener('input', (e) => {
    const input = e.target.closest('[data-search-input]');
    if (input) searchFromInput(input);
});
document.addEventListener('focusin', (e) => {
    const input = e.target.closest('[data-search-input]');
    if (input && input.value.trim().length >= 2) searchFromInput(input);
});
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-search]')) return; // clicks inside a search box keep it open
    document.querySelectorAll('[data-search-results]').forEach(hideSearch);
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.querySelectorAll('[data-search-results]').forEach(hideSearch);
});

/* ---------------------------------------------------------
   Admin brands — toggle Show/Hide via AJAX (no reload)
--------------------------------------------------------- */
document.addEventListener('submit', (e) => {
    const form = e.target.closest('[data-brand-toggle]');
    if (!form) return;
    e.preventDefault();
    const pill = form.querySelector('[data-toggle-pill]');
    if (pill) pill.disabled = true;

    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body: new FormData(form),
    })
        .then((r) => (r.ok ? r.json() : Promise.reject(r)))
        .then((data) => {
            if (pill) {
                pill.textContent = data.value ? 'On' : 'Off';
                pill.className = 'rounded-full border px-3 py-1 text-[11px] font-bold '
                    + (data.value ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-500');
                pill.disabled = false;
            }
        })
        .catch(() => form.submit()); // fall back to a normal submit on error
});

/* ---------------------------------------------------------
   Categories filter — expand / collapse all subcategory branches
--------------------------------------------------------- */
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-acc-toggle-all]');
    if (!btn) return;
    // scope to the Categories group this button lives in
    const scope = btn.closest('details') || document;
    const branches = scope.querySelectorAll('details.cat-acc');
    if (!branches.length) return;
    const anyOpen = [...branches].some((d) => d.open);
    branches.forEach((d) => { d.open = !anyOpen; });
    btn.textContent = anyOpen ? 'Expand all' : 'Collapse all';
});

/* ---------------------------------------------------------
   Brand filter — client-side search of the checkbox list
--------------------------------------------------------- */
document.addEventListener('input', (e) => {
    const box = e.target.closest('[data-brand-search]');
    if (!box) return;
    const term = box.value.trim().toLowerCase();
    box.closest('details')?.querySelectorAll('[data-brand-item]').forEach((li) => {
        const name = li.querySelector('[data-brand-name]')?.textContent.toLowerCase() || '';
        li.classList.toggle('hidden', term !== '' && !name.includes(term));
    });
});

/* ---------------------------------------------------------
   Newsletter subscribe — AJAX, inline success message
--------------------------------------------------------- */
function initNewsletter() {
    const token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    document.querySelectorAll('[data-newsletter]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button');
            const msg = form.parentElement.querySelector('[data-newsletter-msg]') || form.querySelector('[data-newsletter-msg]');
            const emailInput = form.querySelector('input[name="email"]');
            if (!emailInput || !emailInput.value.trim()) return;
            const original = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = '…'; }
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: emailInput.value.trim(), source: (form.querySelector('input[name="source"]') || {}).value || 'website' }),
                });
                const data = await res.json().catch(() => ({}));
                if (msg) {
                    msg.textContent = res.ok ? (data.message || "You're in! Check your inbox.") : (data.message || (data.errors && data.errors.email ? data.errors.email[0] : 'Please enter a valid email.'));
                    msg.classList.remove('hidden');
                }
                if (res.ok) emailInput.value = '';
            } catch (err) {
                if (msg) { msg.textContent = 'Something went wrong — please try again.'; msg.classList.remove('hidden'); }
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = original; }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initReveal();
    initHeaderScroll();
    initSliders();
    initVideoSpeeds();
    initBrandScroller();
    initBulk();
    initNewsletter();
    initPhoneInputs();

    // auto-dismiss flash
    const flash = document.querySelector('[data-flash]');
    if (flash) setTimeout(() => { flash.style.transition = 'opacity .4s'; flash.style.opacity = '0'; setTimeout(() => flash.remove(), 400); }, 4000);
});
