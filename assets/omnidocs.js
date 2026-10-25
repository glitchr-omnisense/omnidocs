/*
 * omnidocs.js - the reader's side of a manual omnidocs built, no dependency:
 *
 *  - the search: the index the site wrote (Omnidocs\Search\SearchIndex, one
 *    JSON file) downloaded once, at the first search, and searched here.
 *    ⌘ K (Ctrl K off a Mac) or "/" opens it from anywhere on the page, the
 *    arrows move through the hits, Enter opens one, Escape closes. The
 *    browser's own find in page (⌘ F, Ctrl F) is left alone: it stays the
 *    browser's. A hit is a page, a heading (its section) or an API entry,
 *    the words found marked;
 *  - code tabs ([data-doc-tabs]): one panel shown at a time, the tab chosen
 *    kept for the next tabs of the same name (localStorage when it works);
 *  - a copy button on every code frame (.doc-code);
 *  - the table of contents ([data-omnidocs-toc], a manual read as one page):
 *    the heading being read marked in it (.is-current, aria-current), its
 *    section opened (.is-open), kept in sight in the sidebar's own scroll
 *    ([data-omnidocs-toc-scroll]), the address following it (#anchor, by
 *    history.replaceState: nothing added to the history); on a phone a
 *    button [data-omnidocs-toc-toggle] (aria-controls: the sidebar) opens
 *    and closes it.
 *
 * The page says where its index is and what to call things:
 *
 *   <button type="button" data-omnidocs-search="/search.json"
 *           data-text-placeholder="Search the manual" data-text-none="Nothing for “%q%”"
 *           data-text-kinds='{"page":"Page","heading":"Section","symbol":"API"}'>
 *     Search <kbd data-omnidocs-key="mod">⌘ K</kbd>
 *   </button>
 *
 * [data-omnidocs-key="mod"] reads "⌘ K" on a Mac and "Ctrl K" elsewhere. A
 * search page of its own ([data-omnidocs-search-page]) shows the hits in the
 * page, under its field, the question in the address (?q=).
 *
 * The page may be swapped in by a navigation without reloads (flowjs'
 * Transparent): everything is delegated from the document and set up again
 * on "transparent:load". Loading this file twice does nothing.
 */
(function (root) {
    'use strict';
    if (root.Omnidocs) { root.Omnidocs.setup(); return; }

    var document = root.document;
    var MAX = 40;
    var indexes = {};
    var palette = null;
    var state = { hits: [], active: 0, trigger: null, entries: null, opener: null };
    var uid = 0;

    // ---- searching (pure: Omnidocs.search, tested on its own) ---------------------

    function fold(text) {
        return String(text || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    function words(query) {
        return fold(query).trim().split(/[\s,]+/).filter(Boolean);
    }

    function prepare(entries) {
        entries.forEach(function (e) {
            e._t = fold(e.t);
            e._all = fold([e.t, e.d, e.h, e.x, e.p, e.s].join(' '));
        });
        return entries;
    }

    // Every word must be found; a word in the title counts more, at a word's start more
    // again, the whole title most; an API entry named exactly leads.
    function score(entry, list, whole) {
        var s = 0;
        for (var i = 0; i < list.length; i++) {
            var word = list[i];
            if (entry._all.indexOf(word) === -1) return -1;
            var at = entry._t.indexOf(word);
            if (at === -1) { s += 1; continue; }
            s += 10;
            if (at === 0 || /[^a-z0-9]/.test(entry._t.charAt(at - 1))) s += 5;
        }
        if (entry._t === whole) s += 100;
        else if (entry.k === 'symbol' && (entry._t.slice(-whole.length - 1) === '.' + whole || entry._t.slice(-whole.length - 1) === '#' + whole)) s += 60;
        if (entry.k === 'page') s += 2;
        return s;
    }

    function rank(entries, query, max) {
        var list = words(query);
        if (!list.length) return [];
        var whole = list.join(' ');
        var found = [];
        for (var i = 0; i < entries.length; i++) {
            var s = score(entries[i], list, whole);
            if (s >= 0) found.push([s, i]);
        }
        found.sort(function (a, b) { return b[0] - a[0] || a[1] - b[1]; });
        return found.slice(0, max || MAX).map(function (f) { return entries[f[1]]; });
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // The words found, marked - on the text as written, accents and case as they are.
    function mark(text, query) {
        text = String(text || '');
        var list = words(query);
        if (!list.length) return escapeHtml(text);
        var folded = fold(text);
        var hits = [];
        list.forEach(function (word) {
            var from = 0, at;
            while (word && (at = folded.indexOf(word, from)) !== -1) { hits.push([at, at + word.length]); from = at + word.length; }
        });
        if (!hits.length) return escapeHtml(text);
        hits.sort(function (a, b) { return a[0] - b[0] || b[1] - a[1]; });
        // Hits that touch or overlap are one mark.
        var merged = [hits[0]];
        hits.slice(1).forEach(function (h) {
            var last = merged[merged.length - 1];
            if (h[0] <= last[1]) last[1] = Math.max(last[1], h[1]);
            else merged.push(h);
        });
        var out = '', last = 0;
        merged.forEach(function (h) {
            out += escapeHtml(text.slice(last, h[0])) + '<mark>' + escapeHtml(text.slice(h[0], h[1])) + '</mark>';
            last = h[1];
        });
        return out + escapeHtml(text.slice(last));
    }

    // A long text, cut around its first word found.
    function around(text, query, length) {
        text = String(text || '');
        length = length || 150;
        if (text.length <= length) return text;
        var list = words(query), folded = fold(text), at = -1;
        for (var i = 0; i < list.length && at === -1; i++) at = folded.indexOf(list[i]);
        var start = Math.max(0, Math.min(at - 40, text.length - length));
        return (start > 0 ? '…' : '') + text.slice(start, start + length).trim() + (start + length < text.length ? '…' : '');
    }

    // ---- the index ----------------------------------------------------------------

    function load(url) {
        if (!indexes[url]) {
            indexes[url] = fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (index) { return prepare(index.entries || []); });
            indexes[url].catch(function () { delete indexes[url]; });
        }
        return indexes[url];
    }

    function texts(from) {
        var kinds = {};
        try { kinds = JSON.parse(from.getAttribute('data-text-kinds') || '{}'); } catch (e) { kinds = {}; }
        return {
            placeholder: from.getAttribute('data-text-placeholder') || 'Search',
            none: from.getAttribute('data-text-none') || 'Nothing found for “%q%”',
            hint: from.getAttribute('data-text-hint') || '',
            error: from.getAttribute('data-text-error') || 'The search index could not be read.',
            close: from.getAttribute('data-text-close') || 'Close',
            kinds: kinds
        };
    }

    function hitHtml(e, query, i, labels, id) {
        var kind = labels.kinds[e.k] || e.k;
        var title = e.k === 'symbol' && e.s ? e.s : e.t;
        return '<a class="omnidocs-hit is-' + escapeHtml(e.k) + (i === 0 ? ' is-active' : '') + '" role="option" id="' + id + '-' + i + '" aria-selected="' + (i === 0) + '" href="' + escapeHtml(e.u) + '">' +
            '<span class="omnidocs-hit-kind">' + escapeHtml(kind) + '</span>' +
            '<span class="omnidocs-hit-title">' + mark(title, query) + '</span>' +
            (e.p ? '<span class="omnidocs-hit-page">' + escapeHtml(e.p) + '</span>' : '') +
            (e.d ? '<span class="omnidocs-hit-text">' + mark(around(e.d, query), query) + '</span>' : '') +
            '</a>';
    }

    function show(list, entries, query, labels, id) {
        var hits = rank(entries, query);
        state.hits = hits;
        state.active = 0;
        if (!query.trim()) {
            list.innerHTML = labels.hint ? '<p class="omnidocs-empty">' + escapeHtml(labels.hint) + '</p>' : '';
        } else if (!hits.length) {
            list.innerHTML = '<p class="omnidocs-empty">' + escapeHtml(labels.none.replace('%q%', query.trim())) + '</p>';
        } else {
            list.innerHTML = hits.map(function (e, i) { return hitHtml(e, query, i, labels, id); }).join('');
        }
        return hits;
    }

    function move(list, input, delta) {
        var links = list.querySelectorAll('.omnidocs-hit');
        if (!links.length) return;
        if (links[state.active]) { links[state.active].classList.remove('is-active'); links[state.active].setAttribute('aria-selected', 'false'); }
        state.active = (state.active + delta + links.length) % links.length;
        var now = links[state.active];
        now.classList.add('is-active');
        now.setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', now.id);
        now.scrollIntoView({ block: 'nearest' });
    }

    function keys(event, list, input, onOpen) {
        if (event.key === 'ArrowDown') { event.preventDefault(); move(list, input, 1); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); move(list, input, -1); }
        else if (event.key === 'Enter') {
            var hit = list.querySelectorAll('.omnidocs-hit')[state.active];
            if (hit) { event.preventDefault(); if (onOpen) onOpen(); hit.click(); }
        }
    }

    // ---- the palette ------------------------------------------------------------

    function build() {
        var id = 'omnidocs-search-' + (++uid);
        palette = document.createElement('div');
        palette.className = 'omnidocs-palette';
        palette.hidden = true;
        palette.setAttribute('data-transparent-ignore', '');
        palette.innerHTML =
            '<div class="omnidocs-backdrop" data-omnidocs-close></div>' +
            '<div class="omnidocs-panel" role="dialog" aria-modal="true">' +
                '<div class="omnidocs-field">' +
                    '<input type="search" autocomplete="off" spellcheck="false" role="combobox" aria-expanded="true" aria-controls="' + id + '" aria-autocomplete="list">' +
                    '<button type="button" class="omnidocs-close" data-omnidocs-close><kbd>Esc</kbd></button>' +
                '</div>' +
                '<div class="omnidocs-results" id="' + id + '" role="listbox"></div>' +
                '<div class="omnidocs-foot" aria-hidden="true"><span><kbd>↑</kbd><kbd>↓</kbd></span><span><kbd>↵</kbd></span><span><kbd>Esc</kbd></span></div>' +
            '</div>';
        document.body.appendChild(palette);
        var input = palette.querySelector('input');
        var list = palette.querySelector('.omnidocs-results');
        input.addEventListener('input', function () {
            if (state.entries) show(list, state.entries, input.value, texts(state.trigger), id);
        });
        input.addEventListener('keydown', function (event) { keys(event, list, input, close); });
        list.addEventListener('mousemove', function (event) {
            var hit = event.target.closest && event.target.closest('.omnidocs-hit');
            if (!hit) return;
            var links = Array.prototype.slice.call(list.querySelectorAll('.omnidocs-hit'));
            var i = links.indexOf(hit);
            if (i !== -1 && i !== state.active) move(list, input, i - state.active);
        });
        list.addEventListener('click', function (event) {
            if (event.target.closest && event.target.closest('.omnidocs-hit')) close(true);
        });
        palette.addEventListener('click', function (event) {
            if (event.target.closest && event.target.closest('[data-omnidocs-close]')) close();
        });
        palette._id = id;
    }

    function trigger() {
        return document.querySelector('[data-omnidocs-search]');
    }

    function open(from) {
        from = from || trigger();
        if (!from) return false;
        if (!palette || !palette.isConnected) build();
        state.trigger = from;
        state.opener = document.activeElement;
        var labels = texts(from);
        var input = palette.querySelector('input');
        var list = palette.querySelector('.omnidocs-results');
        input.placeholder = labels.placeholder;
        input.setAttribute('aria-label', labels.placeholder);
        palette.querySelector('.omnidocs-close').setAttribute('aria-label', labels.close);
        palette.querySelector('.omnidocs-panel').setAttribute('aria-label', labels.placeholder);
        palette.hidden = false;
        document.documentElement.classList.add('omnidocs-searching');
        input.focus();
        input.select();
        load(from.getAttribute('data-omnidocs-search')).then(function (entries) {
            state.entries = entries;
            show(list, entries, input.value, labels, palette._id);
        }, function () {
            list.innerHTML = '<p class="omnidocs-empty">' + escapeHtml(labels.error) + '</p>';
        });
        if (!state.entries) list.innerHTML = '';
        return true;
    }

    function close(leaving) {
        if (!palette || palette.hidden) return;
        palette.hidden = true;
        document.documentElement.classList.remove('omnidocs-searching');
        if (!leaving && state.opener && state.opener.focus && state.opener.isConnected) state.opener.focus();
    }

    function isOpen() {
        return !!palette && !palette.hidden;
    }

    function typing(target) {
        return !!target && (/^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName) || target.isContentEditable);
    }

    // ---- the search page ----------------------------------------------------------

    function setupPage(page) {
        if (page.getAttribute('data-ready')) return;
        page.setAttribute('data-ready', '1');
        var input = page.querySelector('input[type="search"]');
        var list = page.querySelector('[data-omnidocs-results]');
        var labels = texts(page);
        var id = 'omnidocs-page-' + (++uid);
        if (!input || !list) return;
        list.id = list.id || id;
        input.value = new URLSearchParams(root.location.search).get('q') || input.value;
        load(page.getAttribute('data-omnidocs-search-page')).then(function (entries) {
            var run = function () {
                show(list, entries, input.value, labels, list.id);
                var q = input.value.trim();
                try { history.replaceState(history.state, '', root.location.pathname + (q ? '?q=' + encodeURIComponent(q) : '')); } catch (e) { /* a sandboxed page */ }
            };
            input.addEventListener('input', run);
            input.addEventListener('keydown', function (event) { keys(event, list, input); });
            if (input.form) input.form.addEventListener('submit', function (event) { event.preventDefault(); run(); });
            show(list, entries, input.value, labels, list.id);
        }, function () {
            list.innerHTML = '<p class="omnidocs-empty">' + escapeHtml(labels.error) + '</p>';
        });
    }

    // ---- tabs and copy buttons ----------------------------------------------------

    function stored(name) {
        try { return root.localStorage.getItem('omnidocs.tab.' + name); } catch (e) { return null; }
    }

    function store(name, value) {
        try { root.localStorage.setItem('omnidocs.tab.' + name, value); } catch (e) { /* private mode */ }
    }

    function tabsKey(tabs) {
        return Array.prototype.map.call(tabs.querySelectorAll('[role="tab"]'), function (t) { return t.textContent.trim(); }).join('|');
    }

    function select(tabs, tab, remember) {
        Array.prototype.forEach.call(tabs.querySelectorAll('[role="tab"]'), function (t) {
            var on = t === tab;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            var panel = document.getElementById(t.getAttribute('aria-controls'));
            if (panel) panel.hidden = !on;
        });
        if (remember) {
            var key = tabsKey(tabs), title = tab.textContent.trim();
            store(key, title);
            // The same choice in the other tabs of the same names on the page (YAML / PHP...).
            Array.prototype.forEach.call(document.querySelectorAll('[data-doc-tabs].is-ready'), function (other) {
                if (other === tabs || tabsKey(other) !== key) return;
                Array.prototype.forEach.call(other.querySelectorAll('[role="tab"]'), function (t) { if (t.textContent.trim() === title) select(other, t, false); });
            });
        }
    }

    function setupTabs(tabs) {
        if (tabs.classList.contains('is-ready')) return;
        tabs.classList.add('is-ready');
        var all = tabs.querySelectorAll('[role="tab"]');
        if (!all.length) return;
        var want = stored(tabsKey(tabs)), first = all[0];
        Array.prototype.forEach.call(all, function (t) { if (want && t.textContent.trim() === want) first = t; });
        select(tabs, first, false);
    }

    function setupCopy(frame, label) {
        if (frame.querySelector('.doc-code-copy')) return;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'doc-code-copy';
        button.textContent = label;
        frame.appendChild(button);
    }


    // ---- the table of contents: sticks, follows the reading ---------------------

    var spy = null;

    function setupToc(nav) {
        var links = Array.prototype.filter.call(nav.querySelectorAll('a[href*="#"]'), function (a) {
            var href = a.getAttribute('href');
            return href.charAt(0) === '#' || href.split('#')[0] === '' || a.pathname === root.location.pathname;
        });
        var items = [];
        links.forEach(function (a) {
            var id = decodeURIComponent(a.hash.slice(1));
            var target = id && document.getElementById(id);
            if (target) items.push({ link: a, target: target, id: id });
        });
        var scroller = nav.closest('[data-omnidocs-toc-scroll]') || nav;
        var current = null, moved = false, frame = 0;

        function offset() {
            var padding = parseFloat(root.getComputedStyle(document.documentElement).scrollPaddingTop) || 0;
            return padding + Math.min(160, root.innerHeight * 0.25);
        }

        function keepInView(link) {
            var box = scroller.getBoundingClientRect(), at = link.getBoundingClientRect();
            if (scroller.scrollHeight <= scroller.clientHeight + 1) return;
            if (at.top < box.top + 8) scroller.scrollTop -= box.top + 8 - at.top + box.height / 3;
            else if (at.bottom > box.bottom - 8) scroller.scrollTop += at.bottom - box.bottom + 8 + box.height / 3;
        }

        function mark(item) {
            if (item === current) return;
            if (current) {
                current.link.classList.remove('is-current');
                current.link.removeAttribute('aria-current');
            }
            Array.prototype.forEach.call(nav.querySelectorAll('.is-open'), function (li) { li.classList.remove('is-open'); });
            current = item;
            if (item) {
                item.link.classList.add('is-current');
                item.link.setAttribute('aria-current', 'location');
                for (var li = item.link.closest('li'); li && nav.contains(li); li = li.parentNode.closest('li')) li.classList.add('is-open');
                keepInView(item.link);
            }
            // The address follows the reading, adding nothing to the history.
            if (moved) {
                try {
                    var url = root.location.pathname + root.location.search + (item ? '#' + encodeURIComponent(item.id).replace(/%2F/g, '/') : '');
                    if (url !== root.location.pathname + root.location.search + root.location.hash) history.replaceState(history.state, '', url);
                } catch (e) { /* a sandboxed page */ }
            }
        }

        function measure() {
            frame = 0;
            var line = offset(), found = null;
            for (var i = 0; i < items.length; i++) {
                if (items[i].target.getBoundingClientRect().top <= line) found = items[i];
                else break;
            }
            // At the very bottom, the last heading shown is the one read.
            if (root.innerHeight + root.scrollY >= document.documentElement.scrollHeight - 2 && items.length) {
                for (var j = items.length - 1; j >= 0; j--) {
                    if (items[j].target.getBoundingClientRect().top < root.innerHeight) { found = items[j]; break; }
                }
            }
            mark(found);
        }

        function onScroll() {
            moved = true;
            if (!frame) frame = root.requestAnimationFrame(measure);
        }

        if (spy) spy();
        root.addEventListener('scroll', onScroll, { passive: true });
        root.addEventListener('resize', onScroll, { passive: true });
        spy = function () {
            root.removeEventListener('scroll', onScroll);
            root.removeEventListener('resize', onScroll);
        };
        measure();
    }

    function toggleToc(button, open) {
        var panel = document.getElementById(button.getAttribute('aria-controls'));
        if (!panel) return;
        open = open === undefined ? button.getAttribute('aria-expanded') !== 'true' : open;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('is-open', open);
        document.documentElement.classList.toggle('omnidocs-toc-open', open);
    }

    function setup() {
        document.documentElement.classList.add('omnidocs-ready');
        var mac = /Mac|iPhone|iPad|iPod/.test((root.navigator && (root.navigator.userAgentData && root.navigator.userAgentData.platform || root.navigator.platform)) || '');
        document.documentElement.classList.toggle('omnidocs-mac', mac);
        Array.prototype.forEach.call(document.querySelectorAll('[data-omnidocs-key="mod"]'), function (key) {
            key.textContent = mac ? '⌘ K' : 'Ctrl K';
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-doc-tabs]'), setupTabs);
        var copy = document.documentElement.getAttribute('data-omnidocs-copy') || 'Copy';
        Array.prototype.forEach.call(document.querySelectorAll('.doc-code'), function (frame) { setupCopy(frame, copy); });
        Array.prototype.forEach.call(document.querySelectorAll('[data-omnidocs-search-page]'), setupPage);
        var nav = document.querySelector('[data-omnidocs-toc]');
        if (nav) setupToc(nav);
        else if (spy) { spy(); spy = null; }
    }

    // ---- delegated ----------------------------------------------------------------

    document.addEventListener('keydown', function (event) {
        // ⌘ K / Ctrl K: the search, from anywhere (a field included). ⌘ F and Ctrl F are not looked at.
        if ((event.key === 'k' || event.key === 'K') && (event.metaKey || event.ctrlKey) && !event.altKey && !event.shiftKey) {
            if (isOpen()) { event.preventDefault(); close(); return; }
            if (trigger()) { event.preventDefault(); open(); }
            return;
        }
        if (event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey && !typing(event.target) && !isOpen()) {
            var page = document.querySelector('[data-omnidocs-search-page] input[type="search"]');
            if (page) { event.preventDefault(); page.focus(); return; }
            if (trigger()) { event.preventDefault(); open(); }
            return;
        }
        if (event.key === 'Escape' && isOpen()) { event.preventDefault(); close(); return; }
        var drawer = document.querySelector('[data-omnidocs-toc-toggle][aria-expanded="true"]');
        if (event.key === 'Escape' && drawer) { toggleToc(drawer, false); drawer.focus(); }
    });

    document.addEventListener('click', function (event) {
        var from = event.target.closest && event.target.closest('[data-omnidocs-search]');
        if (from) { event.preventDefault(); open(from); return; }
        var tab = event.target.closest && event.target.closest('[data-doc-tabs] [role="tab"]');
        if (tab) { select(tab.closest('[data-doc-tabs]'), tab, true); return; }
        var toggle = event.target.closest && event.target.closest('[data-omnidocs-toc-toggle]');
        if (toggle) { event.preventDefault(); toggleToc(toggle); return; }
        // A link of the open contents (a phone's drawer) closes it on its way.
        var opened = document.querySelector('[data-omnidocs-toc-toggle][aria-expanded="true"]');
        if (opened && event.target.closest && event.target.closest('[data-omnidocs-toc] a')) toggleToc(opened, false);
        var button = event.target.closest && event.target.closest('.doc-code-copy');
        if (button) {
            var code = button.parentNode.querySelector('code');
            if (!code || !root.navigator.clipboard) return;
            root.navigator.clipboard.writeText(code.innerText).then(function () {
                var label = button.textContent;
                button.textContent = document.documentElement.getAttribute('data-omnidocs-copied') || 'Copied';
                button.classList.add('is-done');
                setTimeout(function () { button.textContent = label; button.classList.remove('is-done'); }, 1500);
            }, function () { /* refused: nothing copied */ });
        }
    });

    document.addEventListener('keydown', function (event) {
        var tab = event.target.closest && event.target.closest('[data-doc-tabs] [role="tab"]');
        if (!tab || (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft')) return;
        var all = Array.prototype.slice.call(tab.parentNode.querySelectorAll('[role="tab"]'));
        var next = all[(all.indexOf(tab) + (event.key === 'ArrowRight' ? 1 : -1) + all.length) % all.length];
        event.preventDefault();
        select(tab.closest('[data-doc-tabs]'), next, true);
        next.focus();
    });

    root.addEventListener('transparent:load', function () { close(true); setup(); });

    root.Omnidocs = {
        setup: setup,
        open: open,
        close: close,
        isOpen: isOpen,
        search: { fold: fold, prepare: prepare, rank: rank, mark: mark, around: around }
    };
    if (typeof module === 'object' && module.exports) module.exports = root.Omnidocs;

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup);
    else setup();
})(typeof window !== 'undefined' ? window : this);
