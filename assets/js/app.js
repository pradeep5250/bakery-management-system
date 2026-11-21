(function () {
    const base = (window.APP && APP.baseUrl) || '/';

    window.toast = function (message, type) {
        const wrap = document.getElementById('toastWrap');
        if (!wrap) { alert(message); return; }
        const el = document.createElement('div');
        el.className = 'app-toast';
        el.textContent = message;
        if (type === 'error') el.style.background = '#B42318';
        wrap.appendChild(el);
        setTimeout(() => el.remove(), 3200);
    };

    window.appJson = async function (path, payload, method) {
        method = method || 'POST';
        try {
            const res = await fetch(base + path.replace(/^\//, ''), {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (APP && APP.csrf) || ''
                },
                body: method === 'GET' ? undefined : JSON.stringify(Object.assign({ csrf_token: APP.csrf }, payload || {}))
            });
            const json = await res.json();
            if (!json.success) toast(json.message || 'Something went wrong.', 'error');
            else if (json.message && json.message !== 'OK') toast(json.message);
            if (json.success && method !== 'GET') setTimeout(() => location.reload(), 400);
            return json;
        } catch (e) {
            toast('Server connection unavailable. Please reconnect before completing the bill.', 'error');
            return { success: false, message: 'Server unavailable.' };
        }
    };

    window.appPost = function (form) {
        const data = Object.fromEntries(new FormData(form).entries());
        data.csrf_token = APP.csrf;
        const action = form.getAttribute('action') || '';
        const path = action.replace(base, '').replace(/^\//, '');
        appJson(path, data);
        return false;
    };

    window.appConfirm = function (title, message, onYes) {
        const overlay = document.createElement('div');
        overlay.className = 'confirm-overlay show';
        overlay.innerHTML = `<div class="confirm-box"><h3 class="h5">${title}</h3><p>${message}</p>
            <div class="d-flex gap-2 justify-content-end"><button class="btn btn-outline-dark" id="cNo">CANCEL</button>
            <button class="btn btn-primary" id="cYes">CONFIRM</button></div></div>`;
        document.body.appendChild(overlay);
        overlay.querySelector('#cNo').onclick = () => overlay.remove();
        overlay.querySelector('#cYes').onclick = () => { overlay.remove(); onYes(); };
    };

    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    function closeSide() { sidebar?.classList.remove('open'); backdrop?.classList.remove('show'); }
    toggle?.addEventListener('click', () => { sidebar?.classList.toggle('open'); backdrop?.classList.toggle('show'); });
    backdrop?.addEventListener('click', closeSide);

    async function loadNotes() {
        const box = document.getElementById('notifyList');
        if (!box) return;
        try {
            const res = await fetch(base + 'api/notifications/list.php', { headers: { Accept: 'application/json' } });
            const json = await res.json();
            const items = (json.data && json.data.items) || [];
            box.innerHTML = items.length ? items.map(n => `<div class="notify-item"><strong>${n.title}</strong><div>${n.message}</div><small>${n.created_at}</small></div>`).join('') : '<div class="notify-item">No notifications.</div>';
        } catch (e) {
            box.textContent = 'Unable to load notifications.';
        }
    }
    loadNotes();

    const imageInput = document.getElementById('productImageInput');
    const imagePreview = document.getElementById('imagePreview');
    const pickImageBtn = document.getElementById('pickImageBtn');
    pickImageBtn?.addEventListener('click', () => imageInput?.click());
    imageInput?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file || !imagePreview) return;
        imagePreview.src = URL.createObjectURL(file);
        const remove = document.getElementById('removeImage');
        if (remove) remove.checked = false;
    });

    window.addEventListener('offline', () => toast('Server connection unavailable. Please reconnect before completing the bill.', 'error'));
})();
