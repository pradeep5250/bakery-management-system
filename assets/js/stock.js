(function () {
    const modalEl = document.getElementById('stockModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    document.querySelectorAll('[data-stock]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('stockPid').value = btn.getAttribute('data-stock');
            document.getElementById('stockPname').textContent = btn.getAttribute('data-name');
            modal.show();
        });
    });
    document.getElementById('stockForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        data.quantity = Number(data.quantity);
        await appJson('api/stock/adjust.php', data);
    });
})();
