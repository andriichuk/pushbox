(function () {
    var wrap = document.getElementById('pushbox-sidebar-wrap');
    var overlay = document.getElementById('pushbox-overlay');
    var toggle = document.getElementById('pushbox-menu-toggle');
    var iconMenu = document.getElementById('pushbox-icon-menu');
    var iconClose = document.getElementById('pushbox-icon-close');
    if (!wrap || !overlay || !toggle) return;

    function setOpen(open) {
        if (open) {
            wrap.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            if (iconMenu) iconMenu.classList.add('hidden');
            if (iconClose) iconClose.classList.remove('hidden');
            toggle.setAttribute('aria-label', 'Close menu');
        } else {
            wrap.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            if (iconMenu) iconMenu.classList.remove('hidden');
            if (iconClose) iconClose.classList.add('hidden');
            toggle.setAttribute('aria-label', 'Open menu');
        }
    }

    var mq = window.matchMedia('(min-width: 1024px)');
    function onResize() {
        if (mq.matches) setOpen(true);
        else setOpen(false);
    }

    toggle.addEventListener('click', function () {
        var open = wrap.classList.contains('-translate-x-full');
        setOpen(open);
    });
    overlay.addEventListener('click', function () { setOpen(false); });
    mq.addEventListener('change', onResize);
    onResize();
})();
