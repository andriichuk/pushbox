(() => {
    'use strict';

    const main = document.getElementById('pushbox-main');
    const search = document.getElementById('pushbox-search');
    const phone = document.querySelector('.pushbox-phone');
    const deviceDialog = document.getElementById('pushbox-device-dialog');
    const announcement = document.getElementById('pushbox-announcement');
    const selectedClass = document.querySelector('#pushbox-selection [name="class"]');
    const pageKey = `${window.location.pathname}|${selectedClass?.value ?? ''}`;
    const storageKey = 'pushbox.workspace';
    const savedWorkspace = readWorkspace();
    const originalGroups = new Map();

    function announce(message) {
        if (announcement) {
            announcement.textContent = message;
        }
    }

    function readWorkspace() {
        try {
            const saved = JSON.parse(window.sessionStorage.getItem(storageKey) ?? '{}');
            return saved && typeof saved === 'object' && !Array.isArray(saved) ? saved : {};
        } catch {
            return {};
        }
    }

    function saveWorkspace() {
        const groups = {};

        document.querySelectorAll('[data-search-group]').forEach((group) => {
            const label = group.querySelector('summary span');
            if (label) {
                groups[label.textContent] = originalGroups.get(group) ?? group.open;
            }
        });

        try {
            window.sessionStorage.setItem(storageKey, JSON.stringify({
                pageKey,
                scrollTop: main?.scrollTop ?? 0,
                search: search?.value ?? '',
                expanded: phone?.dataset.expanded === 'true',
                groups,
            }));
        } catch {
            // The workspace remains usable when session storage is unavailable.
        }
    }

    const sidebar = document.getElementById('pushbox-sidebar-wrap');
    const overlay = document.getElementById('pushbox-overlay');
    const menuToggle = document.getElementById('pushbox-menu-toggle');
    const menuIcon = document.getElementById('pushbox-icon-menu');
    const closeIcon = document.getElementById('pushbox-icon-close');
    const desktop = window.matchMedia('(min-width: 1024px)');

    function setMenuOpen(open) {
        if (!sidebar || !overlay || !menuToggle) {
            return;
        }

        sidebar.classList.toggle('-translate-x-full', !open);
        overlay.classList.toggle('hidden', !open);
        menuIcon?.classList.toggle('hidden', open);
        closeIcon?.classList.toggle('hidden', !open);
        menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        menuToggle.setAttribute('aria-expanded', String(open));
        sidebar.inert = !open && !desktop.matches;

        if (main) {
            main.inert = open && !desktop.matches;
        }
    }

    menuToggle?.addEventListener('click', () => {
        const open = menuToggle.getAttribute('aria-expanded') !== 'true';
        setMenuOpen(open);
        if (open) {
            search?.focus();
        }
    });

    overlay?.addEventListener('click', () => {
        setMenuOpen(false);
        menuToggle?.focus();
    });

    desktop.addEventListener('change', () => setMenuOpen(desktop.matches));
    setMenuOpen(desktop.matches);

    if (search) {
        search.value = typeof savedWorkspace.search === 'string' ? savedWorkspace.search : '';

        const groups = document.querySelectorAll('[data-search-group]');
        const items = document.querySelectorAll('[data-search-item]');
        const empty = document.getElementById('pushbox-search-empty');

        groups.forEach((group) => {
            const label = group.querySelector('summary span');
            const savedOpen = label ? savedWorkspace.groups?.[label.textContent] : undefined;
            if (typeof savedOpen === 'boolean') {
                group.open = savedOpen;
            }

            originalGroups.set(group, group.open);
            group.addEventListener('toggle', () => {
                if (!search.value.trim()) {
                    originalGroups.set(group, group.open);
                }
            });
        });

        function filterNotifications() {
            const query = search.value.trim().toLocaleLowerCase();
            let visible = 0;

            items.forEach((item) => {
                const matches = (item.dataset.searchText ?? '').toLocaleLowerCase().includes(query);
                item.hidden = !matches;
                if (matches) {
                    visible++;
                }
            });

            groups.forEach((group) => {
                const hasMatches = [...group.querySelectorAll('[data-search-item]')]
                    .some((item) => !item.hidden);
                group.hidden = !hasMatches;
                group.open = query ? hasMatches : originalGroups.get(group);
            });

            if (empty) {
                empty.hidden = visible > 0 || !query;
            }
        }

        search.addEventListener('input', filterNotifications);
        search.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowDown') {
                return;
            }

            const first = document.querySelector('[data-search-item]:not([hidden])');
            if (first) {
                event.preventDefault();
                first.focus();
            }
        });

        filterNotifications();
    }

    function previewDescription(expanded) {
        const hasImage = phone.querySelector('[data-notification-image]') !== null;
        if (expanded) {
            return hasImage
                ? 'Expanded preview · Full notification content and image.'
                : 'Expanded preview · Full notification content.';
        }

        if (hasImage) {
            return 'Collapsed preview · Expand to see the full notification and image.';
        }

        const truncated = [...phone.querySelectorAll('[data-preview-title], [data-preview-body]')]
            .some((text) => text.scrollHeight > text.clientHeight + 1);

        return truncated
            ? 'Collapsed preview · Some text is truncated. Expand to read it all.'
            : 'Collapsed notification preview.';
    }

    function setPreviewExpanded(expanded) {
        if (!phone) {
            return;
        }

        phone.dataset.expanded = String(expanded);
        document.querySelectorAll('[data-preview-state]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.previewState === String(expanded)));
        });

        const chevron = document.querySelector('[data-toggle-preview]');
        chevron?.setAttribute('aria-expanded', String(expanded));
        chevron?.setAttribute('aria-label', expanded ? 'Collapse notification' : 'Expand notification');

        const note = document.getElementById('pushbox-preview-note');
        if (note) {
            note.textContent = previewDescription(expanded);
        }
    }

    document.querySelectorAll('[data-preview-state]').forEach((button) => {
        button.addEventListener('click', () => {
            setPreviewExpanded(button.dataset.previewState === 'true');
            saveWorkspace();
        });
    });

    document.querySelector('[data-toggle-preview]')?.addEventListener('click', () => {
        setPreviewExpanded(phone?.dataset.expanded !== 'true');
        saveWorkspace();
    });

    setPreviewExpanded(savedWorkspace.expanded === true);
    window.addEventListener('resize', () => setPreviewExpanded(phone?.dataset.expanded === 'true'));

    document.querySelectorAll('[data-notification-image]').forEach((image) => {
        function showImageError() {
            image.hidden = true;
            const error = image.closest('[data-pushbox-image]')?.querySelector('.pushbox-image-error');
            if (error) {
                error.hidden = false;
            }
        }

        image.addEventListener('error', showImageError);
        if (image.complete && image.naturalWidth === 0) {
            showImageError();
        }
    });

    let deviceOpener = null;

    function openDevice(opener) {
        if (!deviceDialog) {
            return;
        }

        deviceOpener = opener;
        if (!deviceDialog.open) {
            deviceDialog.showModal();
        }
    }

    deviceDialog?.addEventListener('close', () => deviceOpener?.focus());
    deviceDialog?.addEventListener('click', (event) => {
        if (event.target !== deviceDialog) {
            return;
        }

        const { left, right, top, bottom } = deviceDialog.getBoundingClientRect();
        const outsideDialog = event.clientX < left || event.clientX > right
            || event.clientY < top || event.clientY > bottom;
        if (outsideDialog) {
            deviceDialog.close();
        }
    });

    if (deviceDialog?.dataset.open === 'true') {
        openDevice(document.querySelector('[data-open-device]'));
    }

    async function copyText(value) {
        try {
            await navigator.clipboard.writeText(value);
            return;
        } catch {
            // HTTP workspaces may not have access to the Clipboard API.
        }

        const field = document.createElement('textarea');
        field.value = value;
        field.style.cssText = 'position:fixed;left:-9999px;top:0';
        document.body.appendChild(field);

        try {
            field.select();
            if (!document.execCommand('copy')) {
                throw new Error('Copy unavailable');
            }
        } finally {
            field.remove();
        }
    }

    document.addEventListener('click', async (event) => {
        const opener = event.target.closest('[data-open-device]');
        if (opener) {
            openDevice(opener);
            return;
        }

        if (event.target.closest('[data-close-device]')) {
            deviceDialog?.close();
            return;
        }

        const button = event.target.closest('[data-copy-source], [data-copy-value]');
        if (!button) {
            return;
        }

        const source = button.dataset.copySource ? document.getElementById(button.dataset.copySource) : null;
        const value = source ? source.textContent : button.dataset.copyValue;
        if (typeof value !== 'string') {
            return;
        }

        try {
            await copyText(value);
            const originalLabel = button.textContent;
            button.textContent = 'Copied';
            announce(`Copied ${button.dataset.copyLabel || 'value'}.`);
            window.setTimeout(() => {
                button.textContent = originalLabel;
            }, 1800);
        } catch {
            announce('Copy unavailable. Select the text and copy it manually.');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (deviceDialog?.open) {
            return;
        }

        const editing = event.target.matches('input, textarea, select, [contenteditable="true"]');
        const searchShortcut = event.key === '/' && !editing
            && !event.metaKey && !event.ctrlKey && !event.altKey;
        if (searchShortcut) {
            event.preventDefault();
            if (!desktop.matches) {
                setMenuOpen(true);
            }
            search?.focus();
        }

        if (event.key === 'Escape') {
            if (event.target === search && search.value) {
                search.value = '';
                search.dispatchEvent(new Event('input'));
            } else if (!desktop.matches) {
                setMenuOpen(false);
                menuToggle?.focus();
            }
        }
    });

    document.querySelectorAll('[data-preserve-scroll]').forEach((form) => {
        form.addEventListener('submit', saveWorkspace);
    });

    document.querySelectorAll('[data-auto-submit]').forEach((select) => {
        select.addEventListener('change', () => {
            saveWorkspace();
            select.form.requestSubmit();
        });
    });

    window.addEventListener('beforeunload', saveWorkspace);
    if (main && savedWorkspace.pageKey === pageKey && typeof savedWorkspace.scrollTop === 'number') {
        main.scrollTop = savedWorkspace.scrollTop;
    }

    function sendErrorMessage(status) {
        switch (status) {
            case 429:
                return 'Too many send attempts. Wait a minute before trying again.';
            case 419:
                return 'Your session expired. Reload the page before sending again.';
            case 403:
                return 'Sending is unavailable in this environment. Check the Pushbox configuration.';
            default:
                return 'The server could not confirm the result. Review the server logs before retrying.';
        }
    }

    function showSendError(feedback, error) {
        const box = document.createElement('div');
        box.className = 'pushbox-result pushbox-result-error';
        box.setAttribute('role', 'alert');

        const title = document.createElement('strong');
        title.textContent = 'Could not confirm the send result';

        const message = document.createElement('p');
        const interrupted = error.name === 'AbortError' || error instanceof TypeError;
        message.textContent = interrupted
            ? 'The connection was interrupted. The message may have been sent; check before retrying.'
            : error.message;

        box.append(title, message);
        feedback.replaceChildren(box);
        announce(title.textContent);
    }

    const sendForm = document.getElementById('pushbox-send-form');
    if (sendForm && window.fetch && window.DOMParser) {
        let sending = false;
        const button = sendForm.querySelector('[type="submit"]');
        const label = sendForm.querySelector('[data-send-label]');
        const icon = sendForm.querySelector('.pushbox-send-icon');
        const feedback = document.getElementById('pushbox-send-feedback');

        sendForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (sending) {
                return;
            }

            sending = true;
            const body = new FormData(sendForm);
            const spinner = document.createElement('span');
            spinner.className = 'pushbox-send-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            if (icon) {
                icon.hidden = true;
            }
            button.prepend(spinner);
            button.disabled = true;
            label.textContent = 'Sending…';
            sendForm.setAttribute('aria-busy', 'true');
            announce('Sending notification.');

            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 45000);

            try {
                const response = await fetch(sendForm.action, {
                    method: 'POST',
                    body,
                    credentials: 'same-origin',
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error(sendErrorMessage(response.status));
                }

                const html = await response.text();
                const resultDocument = new DOMParser().parseFromString(html, 'text/html');
                const result = resultDocument.getElementById('pushbox-send-feedback');
                if (!result?.textContent.trim()) {
                    throw new Error('No send result was returned. Review the server logs before retrying.');
                }

                feedback.innerHTML = result.innerHTML;
                announce(feedback.querySelector('h2, strong')?.textContent ?? 'Send completed.');
            } catch (error) {
                showSendError(feedback, error);
            } finally {
                window.clearTimeout(timeout);
                sending = false;
                button.disabled = false;
                label.textContent = 'Send notification';
                spinner.remove();
                if (icon) {
                    icon.hidden = false;
                }
                sendForm.removeAttribute('aria-busy');
            }
        });
    }
})();
