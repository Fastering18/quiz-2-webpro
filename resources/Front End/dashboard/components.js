// dashboard/components.js
// Web Components untuk halaman dashboard MAHASISWA.
// Path relatif berjalan dari folder /dashboard/

// ============================================
// KOMPONEN TOP BAR
// ============================================
class MyTopbar extends HTMLElement {
    connectedCallback() {
        this.innerHTML = `
        <header class="topbar">
            <div class="topbar-left">
                <img class="topbar-logo" src="./image/logo_its_aseli.png" alt="Logo ITS">
                <div class="topbar-brand">
                    <h1>myITS Classroom</h1>
                    <p>Institut Teknologi Sepuluh Nopember</p>
                </div>
            </div>

            <div class="topbar-right">
                <div class="user-info">
                    <span id="topbar-name" class="user-name">Nama Mahasiswa</span>
                    <div class="user-details">
                        <span id="topbar-nrp">NRP</span>
                        <div class="dot"></div>
                        <span id="topbar-dept">Departemen</span>
                    </div>
                </div>

                <img id="topbar-avatar" class="user-avatar" src="https://placehold.co/32x32" alt="Avatar">

                <button id="menuTrigger" class="btn-menu" aria-label="Buka menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6"  x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>

                <div id="dropdownMenu" class="dropdown-menu d-none">
                    <a href="profile.html" class="dropdown-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#00337F" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Profil Saya
                    </a>
                    <div class="divider"></div>
                    <a href="../login.html" class="dropdown-item text-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B42318" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Keluar
                    </a>
                </div>
            </div>
        </header>
        `;
    }
}
customElements.define('my-topbar', MyTopbar);


// ============================================
// KOMPONEN SIDE BAR — MAHASISWA
// ============================================
class MySidebar extends HTMLElement {
    connectedCallback() {
        const activePage = this.getAttribute('active') || '';

        const menuItems = [
            { id: 'portal',    name: 'Portal',              url: 'portal.html',      icon: 'Portal.svg' },
            { id: 'classroom', name: 'Classroom',           url: 'classroom.html',   icon: 'Books.svg' },
            { id: 'library',   name: 'Perpustakaan',        url: 'library.html',     icon: 'Literature.svg' },
            { id: 'krs',       name: 'Kartu Rencana Studi', url: 'krs.html',         icon: 'Page.svg' },
            { id: 'admin',     name: 'Lapor Admin',         url: 'lapor-admin.html', icon: 'Speech Bubble.svg' },
            { id: 'profile',   name: 'Profil',              url: 'profile.html',     icon: 'User.svg' },
        ];

        const navHtml = menuItems.map(function (item) {
            const isActive = activePage === item.id ? 'active' : '';
            return `<a href="${item.url}" class="nav-item ${isActive}">
                <img src="./svg/${item.icon}" alt="" class="nav-icon" aria-hidden="true">
                ${item.name}
            </a>`;
        }).join('');

        this.innerHTML = `<aside class="sidebar"><nav class="sidebar-nav">${navHtml}</nav></aside>`;
    }
}
customElements.define('my-sidebar', MySidebar);
