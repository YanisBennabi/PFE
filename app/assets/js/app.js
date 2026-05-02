/**
 * app.js
 * Gestion des filtres de la page Inventaire + notifications toast
 */

// ─────────────────────────────────────────────
// TOAST (notification en bas à droite)
// ─────────────────────────────────────────────
function showToast(message, type = 'success') {
    // Crée le conteneur s'il n'existe pas encore
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(container);
    }

    const colors = {
        success: { bg: '#d1fae5', border: '#6ee7b7', text: '#065f46', icon: '✓' },
        error:   { bg: '#fee2e2', border: '#fca5a5', text: '#991b1b', icon: '✕' },
        warning: { bg: '#fef3c7', border: '#fcd34d', text: '#92400e', icon: '⚠' },
    };
    const c = colors[type] || colors.success;

    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${c.bg};
        border: 1px solid ${c.border};
        color: ${c.text};
        padding: 14px 20px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 260px;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `<span style="font-size:18px;">${c.icon}</span> ${message}`;
    container.appendChild(toast);

    // Ajoute le style d'animation si pas encore présent
    if (!document.getElementById('toast-style')) {
        const style = document.createElement('style');
        style.id = 'toast-style';
        style.textContent = `
            @keyframes slideIn {
                from { opacity: 0; transform: translateX(30px); }
                to   { opacity: 1; transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);
    }

    // Disparaît après 3.5 secondes
    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 400);
    }, 3500);
}


// ─────────────────────────────────────────────
// FILTRES INVENTAIRE (equipements.php)
// ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    const form     = document.getElementById('filterForm');
    const grid     = document.getElementById('equipementsGrid');
    const counter  = document.getElementById('equipementsCounter');
    const resetBtn = document.getElementById('resetFilters');

    if (!form || !grid) return;

    async function fetchEquipements() {
        const formData = new FormData(form);
        const params   = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value.trim() !== '') params.append(key, value.trim());
        }

        const newUrl = params.toString()
            ? `${window.location.pathname}?${params.toString()}`
            : window.location.pathname;
        window.history.replaceState(null, '', newUrl);

        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-secondary" role="status"></div>
                <p class="text-muted mt-2 small">Chargement...</p>
            </div>`;

        try {
            const response = await fetch(`get_equipements.php?${params.toString()}`, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error(`Erreur serveur : ${response.status}`);

            const data = await response.json();

            if (counter) {
                const n = data.total;
                counter.textContent = `${n} équipement${n > 1 ? 's' : ''} trouvé${n > 1 ? 's' : ''}`;
            }

            grid.innerHTML = data.html;
            toggleResetBtn();

        } catch (err) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size:2rem;"></i>
                    <p class="text-danger mt-2">Erreur lors du chargement des équipements.</p>
                </div>`;
            console.error('fetchEquipements error:', err);
        }
    }

    function toggleResetBtn() {
        if (!resetBtn) return;
        const search = form.querySelector('[name="search"]')?.value.trim();
        const salle  = form.querySelector('[name="salle"]')?.value;
        const type   = form.querySelector('[name="type"]')?.value;
        resetBtn.style.display = (search || salle || type) ? 'inline-flex' : 'none';
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            form.reset();
            fetchEquipements();
        });
    }

    form.querySelectorAll('select').forEach(sel => {
        sel.addEventListener('change', () => fetchEquipements());
    });

    let debounceTimer;
    const searchInput = form.querySelector('[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchEquipements(), 400);
        });
    }

    fetchEquipements();
});