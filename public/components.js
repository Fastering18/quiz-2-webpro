// components.js - Navigation components for myITS Classroom (Mahasiswa & Dosen)
// Optimized to prevent image flickering, external placeholder requests, and DOM jumps.

const DEFAULT_AVATAR = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300337F'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z'/%3E%3C/svg%3E";

function escHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (c) {
        return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c];
    });
}

function avatarSrc(user) {
    if (user?.user_id && user?.foto_profil_key) {
        return "/files/avatars/" + encodeURIComponent(user.user_id) + "?v=" + encodeURIComponent(user.foto_profil_key);
    }
    return DEFAULT_AVATAR;
}

class MyTopbar extends HTMLElement {
    connectedCallback() {
        // Read pre-hydrated user from SSR or local storage to render instantly without flickering
        let cached = window.__AUTH_USER__ || null;
        if (!cached) {
            try {
                const raw = localStorage.getItem('user');
                if (raw) cached = JSON.parse(raw);
            } catch (e) {}
        } else {
            try {
                cached._cachedAt = Date.now();
                localStorage.setItem('user', JSON.stringify(cached));
            } catch (e) {}
        }

        const userName = escHtml(cached?.nama_lengkap || cached?.username || 'Memuat...');
        const userCode = escHtml(cached?.mahasiswa?.nrp || cached?.dosen?.nip || cached?.username || '-');
        const userDept = escHtml(cached?.mahasiswa?.departemen || cached?.dosen?.departemen || (cached?.role ? cached.role.toUpperCase() : '-'));
        const userAvatar = avatarSrc(cached);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        this.innerHTML = `
        <header class="topbar">
            <div class="topbar-left">
                <a href="/portal" style="display:flex; align-items:center; gap:12px; text-decoration:none; color:inherit;">
                    <img class="topbar-logo" src="/image/logo_its_aseli.png" alt="Logo ITS" width="40" height="40">
                    <div class="topbar-brand">
                        <h1>myITS Classroom</h1>
                        <p>Institut Teknologi Sepuluh Nopember</p>
                    </div>
                </a>
            </div>

            <div class="topbar-right">
                <div class="user-info">
                    <span id="topbar-name" class="user-name">${userName}</span>
                    <div class="user-details">
                        <span id="topbar-nrp">${userCode}</span>
                        <div class="dot"></div>
                        <span id="topbar-dept">${userDept}</span>
                    </div>
                </div>

                <img id="topbar-avatar" class="user-avatar" src="${userAvatar}" alt="Avatar" width="32" height="32" style="background:#E2E8F0; object-fit:cover;" onerror="this.onerror=null;this.src='${DEFAULT_AVATAR}'">

                <button id="menuTrigger" class="btn-menu" aria-label="Buka menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6"  x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <div id="dropdownMenu" class="dropdown-menu d-none">
                    <a href="/profile" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#00337F" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Profil Saya
                    </a>
                    <div class="divider"></div>
                    <form method="POST" action="/logout" class="logout-form" onsubmit="try{localStorage.clear();sessionStorage.clear();}catch(err){}">
                        <input type="hidden" name="_token" value="${escHtml(csrf)}">
                        <button type="submit" id="btnLogout" class="dropdown-item text-danger">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B42318" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </header>
        `;

        // Update labels if new info comes in
        const applyUser = (u) => {
            if (!u) return;
            const nameEl = this.querySelector('#topbar-name');
            const nrpEl = this.querySelector('#topbar-nrp');
            const deptEl = this.querySelector('#topbar-dept');
            const avatarEl = this.querySelector('#topbar-avatar');

            if (nameEl) nameEl.textContent = u.nama_lengkap || u.username;
            if (nrpEl) nrpEl.textContent = u.mahasiswa?.nrp || u.dosen?.nip || u.username;
            if (deptEl) deptEl.textContent = u.mahasiswa?.departemen || u.dosen?.departemen || u.role?.toUpperCase();
            if (avatarEl) {
                avatarEl.src = avatarSrc(u);
            }
        };

        window.addEventListener('auth:user-updated', (event) => {
            applyUser(event.detail);
        });

        // If user was already provided by SSR window.__AUTH_USER__, skip /api/me fetch entirely
        if (!window.__AUTH_USER__) {
            const cachedAge = cached?._cachedAt ? (Date.now() - cached._cachedAt) : Infinity;
            if (!cached || cachedAge > 300000) {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
                fetch('/api/me', {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.user) {
                        data.user._cachedAt = Date.now();
                        localStorage.setItem('user', JSON.stringify(data.user));
                        applyUser(data.user);
                        window.dispatchEvent(new CustomEvent('auth:user-updated', { detail: data.user }));
                    }
                })
                .catch(() => {});
            }
        }

        // Hamburger & Dropdown menu toggle
        const menuTrigger = this.querySelector('#menuTrigger');
        const dropdownMenu = this.querySelector('#dropdownMenu');
        const topbarAvatar = this.querySelector('#topbar-avatar');

        if (menuTrigger && dropdownMenu) {
            menuTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('d-none');
            });
        }

        if (topbarAvatar && dropdownMenu) {
            topbarAvatar.style.cursor = 'pointer';
            topbarAvatar.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('d-none');
            });
        }

        document.addEventListener('click', (e) => {
            if (dropdownMenu && !dropdownMenu.classList.contains('d-none')) {
                if (!this.contains(e.target)) {
                    dropdownMenu.classList.add('d-none');
                }
            }
        });

    }
}
if (!customElements.get('my-topbar')) {
    customElements.define('my-topbar', MyTopbar);
}

class MySidebar extends HTMLElement {
    connectedCallback() {
        this.render();
        this._onAuthUpdate = () => this.render();
        window.addEventListener('auth:user-updated', this._onAuthUpdate);
    }

    disconnectedCallback() {
        if (this._onAuthUpdate) {
            window.removeEventListener('auth:user-updated', this._onAuthUpdate);
        }
    }

    render() {
        const activePage = this.getAttribute('active') || '';
        let isDosen = false;
        let isAdmin = false;

        const metaRole = document.querySelector('meta[name="user-role"]')?.content;
        if (metaRole === 'admin') isAdmin = true;
        if (metaRole === 'dosen') isDosen = true;

        let u = window.__AUTH_USER__ || null;
        if (!u) {
            try {
                const raw = localStorage.getItem('user');
                if (raw) u = JSON.parse(raw);
            } catch (e) {}
        }
        if (u) {
            if (u.role === 'dosen') isDosen = true;
            if (u.role === 'admin') isAdmin = true;
        }

        const menuItems = [
            { id: 'portal',    name: 'Portal',                     url: '/portal',      icon: 'Portal.svg' },
            { id: 'classroom', name: 'Classroom',                  url: '/classroom',   icon: 'Books.svg' },
            { id: 'library',   name: 'Perpustakaan',               url: '/library',     icon: 'Literature.svg' },
            { id: 'krs',       name: isDosen ? 'Perwalian' : 'Kartu Rencana Studi', url: '/krs', icon: 'Page.svg' },
        ];

        // Admin Panel: strictly visible for admin role only, positioned right above Lapor Admin
        if (isAdmin) {
            menuItems.push({ id: 'admin-panel', name: 'Admin Panel', url: '/admin-panel', icon: 'Admin.svg' });
        }

        menuItems.push(
            { id: 'admin',     name: 'Lapor Admin',                url: '/lapor-admin', icon: 'Speech Bubble.svg' },
            { id: 'profile',   name: 'Profil',                     url: '/profile',     icon: 'User.svg' },
        );

        const navHtml = menuItems.map(function (item) {
            const isActive = activePage === item.id ? 'active' : '';
            return `<a href="${item.url}" class="nav-item ${isActive}">
                <img src="/svg/${item.icon}" alt="" class="nav-icon" width="20" height="20" aria-hidden="true">
                ${item.name}
            </a>`;
        }).join('');

        this.innerHTML = `<aside class="sidebar"><nav class="sidebar-nav">${navHtml}</nav></aside>`;
    }
}
if (!customElements.get('my-sidebar')) {
    customElements.define('my-sidebar', MySidebar);
}
