(function () {
    'use strict';

    var main = document.getElementById('pushbox-main');
    var search = document.getElementById('pushbox-search');
    var phone = document.querySelector('.pushbox-phone');
    var deviceDialog = document.getElementById('pushbox-device-dialog');
    var announcement = document.getElementById('pushbox-announcement');
    var selectionForm = document.getElementById('pushbox-selection');
    var selectedClass = selectionForm && selectionForm.querySelector('[name="class"]');
    var pageKey = window.location.pathname + '|' + (selectedClass ? selectedClass.value : '');
    var storageKey = 'pushbox.workspace';
    var saved = {};
    try { saved = JSON.parse(window.sessionStorage.getItem(storageKey) || '{}'); } catch (_) {}

    function announce(message) {
        if (announcement) announcement.textContent = message;
    }

    function saveWorkspace() {
        var groups = {};
        document.querySelectorAll('[data-search-group]').forEach(function (group) {
            var label = group.querySelector('summary span');
            if (label) groups[label.textContent] = originalGroups && originalGroups.has(group) ? originalGroups.get(group) : group.open;
        });
        try {
            window.sessionStorage.setItem(storageKey, JSON.stringify({
                pageKey: pageKey,
                scrollTop: main ? main.scrollTop : 0,
                search: search ? search.value : '',
                expanded: phone ? phone.dataset.expanded === 'true' : false,
                groups: groups
            }));
        } catch (_) {}
    }

    var wrap = document.getElementById('pushbox-sidebar-wrap');
    var overlay = document.getElementById('pushbox-overlay');
    var toggle = document.getElementById('pushbox-menu-toggle');
    var iconMenu = document.getElementById('pushbox-icon-menu');
    var iconClose = document.getElementById('pushbox-icon-close');
    var desktop = window.matchMedia('(min-width: 1024px)');

    function setMenuOpen(open) {
        if (!wrap || !overlay || !toggle) return;
        wrap.classList.toggle('-translate-x-full', !open);
        overlay.classList.toggle('hidden', !open);
        if (iconMenu) iconMenu.classList.toggle('hidden', open);
        if (iconClose) iconClose.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        toggle.setAttribute('aria-expanded', String(open));
        wrap.inert = !open && !desktop.matches;
        if (main) main.inert = open && !desktop.matches;
    }
    if (toggle) toggle.addEventListener('click', function () {
        var open = toggle.getAttribute('aria-expanded') !== 'true';
        setMenuOpen(open);
        if (open && search) search.focus();
    });
    if (overlay) overlay.addEventListener('click', function () { setMenuOpen(false); toggle.focus(); });
    desktop.addEventListener('change', function () { setMenuOpen(desktop.matches); });
    setMenuOpen(desktop.matches);

    if (search) {
        search.value = typeof saved.search === 'string' ? saved.search : '';
        var originalGroups = new Map();
        document.querySelectorAll('[data-search-group]').forEach(function (group) {
            var label = group.querySelector('summary span');
            if (label && saved.groups && typeof saved.groups[label.textContent] === 'boolean') group.open = saved.groups[label.textContent];
            originalGroups.set(group, group.open);
            group.addEventListener('toggle', function () { if (!search.value.trim()) originalGroups.set(group, group.open); });
        });
        function filterNotifications() {
            var query = search.value.trim().toLocaleLowerCase();
            var visible = 0;
            document.querySelectorAll('[data-search-item]').forEach(function (item) {
                var matches = (item.dataset.searchText || '').toLocaleLowerCase().indexOf(query) !== -1;
                item.hidden = !matches;
                if (matches) visible++;
            });
            document.querySelectorAll('[data-search-group]').forEach(function (group) {
                var hasMatches = Array.from(group.querySelectorAll('[data-search-item]')).some(function (item) { return !item.hidden; });
                group.hidden = !hasMatches;
                group.open = query ? hasMatches : originalGroups.get(group);
            });
            var empty = document.getElementById('pushbox-search-empty');
            if (empty) empty.hidden = visible > 0 || !query;
        }
        search.addEventListener('input', filterNotifications);
        search.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                var first = document.querySelector('[data-search-item]:not([hidden])');
                if (first) { event.preventDefault(); first.focus(); }
            }
        });
        filterNotifications();
    }

    function setPreviewExpanded(expanded) {
        if (!phone) return;
        phone.dataset.expanded = String(expanded);
        document.querySelectorAll('[data-preview-state]').forEach(function (button) {
            button.setAttribute('aria-pressed', String(button.dataset.previewState === String(expanded)));
        });
        var chevron = document.querySelector('[data-toggle-preview]');
        if (chevron) {
            chevron.setAttribute('aria-expanded', String(expanded));
            chevron.setAttribute('aria-label', expanded ? 'Collapse notification' : 'Expand notification');
        }
        var truncated = Array.from(phone.querySelectorAll('[data-preview-title], [data-preview-body]')).some(function (text) { return text.scrollHeight > text.clientHeight + 1; });
        var note = document.getElementById('pushbox-preview-note');
        if (note) note.textContent = expanded ? 'Expanded preview · Full notification content.' : (truncated ? 'Collapsed preview · Some text is truncated. Expand to read it all.' : 'Collapsed notification preview.');
    }
    document.querySelectorAll('[data-preview-state]').forEach(function (button) {
        button.addEventListener('click', function () { setPreviewExpanded(button.dataset.previewState === 'true'); saveWorkspace(); });
    });
    var previewToggle = document.querySelector('[data-toggle-preview]');
    if (previewToggle) previewToggle.addEventListener('click', function () { setPreviewExpanded(phone.dataset.expanded !== 'true'); saveWorkspace(); });
    setPreviewExpanded(saved.expanded === true);
    window.addEventListener('resize', function () { if (phone) setPreviewExpanded(phone.dataset.expanded === 'true'); });

    var deviceOpener = null;
    function openDevice(opener) {
        if (!deviceDialog) return;
        deviceOpener = opener;
        if (!deviceDialog.open) deviceDialog.showModal();
    }
    if (deviceDialog) {
        deviceDialog.addEventListener('close', function () { if (deviceOpener) deviceOpener.focus(); });
        deviceDialog.addEventListener('click', function (event) {
            var bounds = deviceDialog.getBoundingClientRect();
            if (event.target === deviceDialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) deviceDialog.close();
        });
        if (deviceDialog.dataset.open === 'true') openDevice(document.querySelector('[data-open-device]'));
    }

    function fallbackCopy(value) {
        var field = document.createElement('textarea');
        field.value = value;
        field.style.cssText = 'position:fixed;left:-9999px;top:0';
        document.body.appendChild(field);
        field.select();
        var copied = document.execCommand('copy');
        field.remove();
        if (!copied) throw new Error('Copy unavailable');
    }
    document.addEventListener('click', async function (event) {
        var opener = event.target.closest('[data-open-device]');
        if (opener) { openDevice(opener); return; }
        if (event.target.closest('[data-close-device]')) { if (deviceDialog) deviceDialog.close(); return; }
        var copy = event.target.closest('[data-copy-source], [data-copy-value]');
        if (!copy) return;
        var source = copy.dataset.copySource ? document.getElementById(copy.dataset.copySource) : null;
        var value = source ? source.textContent : copy.dataset.copyValue;
        if (typeof value !== 'string') return;
        try {
            try {
                if (!navigator.clipboard || !navigator.clipboard.writeText) throw new Error('Clipboard API unavailable');
                await navigator.clipboard.writeText(value);
            } catch (_) { fallbackCopy(value); }
            var original = copy.textContent;
            copy.textContent = 'Copied';
            announce('Copied ' + (copy.dataset.copyLabel || 'value') + '.');
            window.setTimeout(function () { copy.textContent = original; }, 1800);
        } catch (_) { announce('Copy unavailable. Select the text and copy it manually.'); }
    });

    document.addEventListener('keydown', function (event) {
        var editing = event.target.matches('input, textarea, select, [contenteditable="true"]');
        if (event.key === '/' && !editing && !event.metaKey && !event.ctrlKey && !event.altKey && !(deviceDialog && deviceDialog.open)) {
            event.preventDefault();
            if (!desktop.matches) setMenuOpen(true);
            if (search) search.focus();
        }
        if (event.key === 'Escape' && !(deviceDialog && deviceDialog.open)) {
            if (event.target === search && search.value) { search.value = ''; search.dispatchEvent(new Event('input')); }
            else if (!desktop.matches) { setMenuOpen(false); if (toggle) toggle.focus(); }
        }
    });
    document.querySelectorAll('[data-preserve-scroll]').forEach(function (form) { form.addEventListener('submit', saveWorkspace); });
    document.querySelectorAll('[data-auto-submit]').forEach(function (select) {
        select.addEventListener('change', function () { saveWorkspace(); select.form.requestSubmit(); });
    });
    window.addEventListener('beforeunload', saveWorkspace);
    if (main && saved.pageKey === pageKey && typeof saved.scrollTop === 'number') main.scrollTop = saved.scrollTop;

    var sendForm = document.getElementById('pushbox-send-form');
    if (sendForm && window.fetch && window.DOMParser) {
        var sending = false;
        sendForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (sending) return;
            sending = true;
            var button = sendForm.querySelector('[type="submit"]');
            var label = sendForm.querySelector('[data-send-label]');
            var icon = sendForm.querySelector('.pushbox-send-icon');
            var feedback = document.getElementById('pushbox-send-feedback');
            var body = new FormData(sendForm);
            var spinner = document.createElement('span');
            spinner.className = 'pushbox-send-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            if (icon) icon.hidden = true;
            button.prepend(spinner);
            button.disabled = true;
            label.textContent = 'Sending…';
            sendForm.setAttribute('aria-busy', 'true');
            announce('Sending notification.');
            var controller = new AbortController();
            var timeout = window.setTimeout(function () { controller.abort(); }, 45000);
            try {
                var response = await fetch(sendForm.action, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
                if (!response.ok) {
                    if (response.status === 429) throw new Error('Too many send attempts. Wait a minute before trying again.');
                    if (response.status === 419) throw new Error('Your session expired. Reload the page before sending again.');
                    if (response.status === 403) throw new Error('Sending is unavailable in this environment. Check the Pushbox configuration.');
                    throw new Error('The server could not confirm the result. Review the server logs before retrying.');
                }
                var html = await response.text();
                var documentResult = new DOMParser().parseFromString(html, 'text/html');
                var result = documentResult.getElementById('pushbox-send-feedback');
                if (!result || !result.textContent.trim()) throw new Error('No send result was returned. Review the server logs before retrying.');
                feedback.innerHTML = result.innerHTML;
                announce(feedback.querySelector('h2, strong') ? feedback.querySelector('h2, strong').textContent : 'Send completed.');
            } catch (error) {
                var box = document.createElement('div');
                box.className = 'pushbox-result pushbox-result-error';
                box.setAttribute('role', 'alert');
                var title = document.createElement('strong');
                title.textContent = 'Could not confirm the send result';
                var message = document.createElement('p');
                message.textContent = error.name === 'AbortError' || error instanceof TypeError ? 'The connection was interrupted. The message may have been sent; check before retrying.' : error.message;
                box.append(title, message);
                feedback.replaceChildren(box);
                announce(title.textContent);
            } finally {
                window.clearTimeout(timeout);
                sending = false;
                button.disabled = false;
                label.textContent = 'Send notification';
                spinner.remove();
                if (icon) icon.hidden = false;
                sendForm.removeAttribute('aria-busy');
            }
        });
    }
})();
