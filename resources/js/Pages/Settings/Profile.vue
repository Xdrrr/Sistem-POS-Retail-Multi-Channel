<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const displayName = computed(() => authUser.value?.detail?.full_name || authUser.value?.username || 'User');
const displayRole = computed(() => authUser.value?.role || 'Staff');

const navItems = [
    { label: 'Home', icon: 'home', href: '/' },
    { label: 'New Sale', icon: 'add_shopping_cart', href: '#' },
    { label: 'Orders', icon: 'receipt_long', href: '#' },
    { label: 'Shift', icon: 'calendar_today', href: '#' },
    { label: 'Reports', icon: 'bar_chart', href: '#' },
    { label: 'Products', icon: 'inventory_2', href: '/catalog' },
    { label: 'Sync Center', icon: 'sync', href: '#' },
    { label: 'Settings', icon: 'settings', href: '/settings/profile', active: true },
];

const form = useForm({
    fullname: props.profile.fullname ?? '',
    email: props.profile.email ?? '',
    phone_number: props.profile.phone_number ?? '',
    gender: props.profile.gender ?? 'Tidak-Spesifik',
    address: props.profile.address ?? '',
    city: props.profile.city ?? '',
    province: props.profile.province ?? '',
    date_of_birth: props.profile.date_of_birth ?? '',
    password: '',
    confirm_password: '',
});

const submit = () => {
    form.put('/settings/profile', {
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
            form.confirm_password = '';
        },
    });
};
</script>

<template>
    <div class="dashboard-shell">
        <nav class="side-nav" aria-label="Main navigation">
            <div class="side-nav__main">
                <div class="branch-card">
                    <div class="branch-card__icon">
                        <span class="material-symbols-outlined">storefront</span>
                    </div>
                    <div class="branch-card__name">Main Branch</div>
                    <div class="branch-card__terminal">Terminal 01</div>
                </div>

                <div class="nav-list">
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        class="nav-item"
                        :class="{ 'nav-item--active': item.active }"
                        :href="item.href"
                    >
                        <span class="material-symbols-outlined" :class="{ fill: item.active }">{{ item.icon }}</span>
                        <span>{{ item.label }}</span>
                    </Link>
                </div>
            </div>

            <button class="nav-item nav-item--footer" type="button">
                <span class="material-symbols-outlined">help</span>
                <span>Help</span>
            </button>
        </nav>

        <header class="top-bar">
            <div class="top-bar__left">
                <div class="brand">RetailPOS</div>
            </div>

            <div class="top-bar__right">
                <Link class="icon-button" href="/catalog" aria-label="Catalog">
                    <span class="material-symbols-outlined">inventory_2</span>
                </Link>
                <div class="top-bar__divider"></div>
                <div class="profile">
                    <div>
                        <strong>{{ displayName }}</strong>
                        <span>{{ displayRole }}</span>
                    </div>
                    <div class="profile__avatar">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                </div>
                <Link class="icon-button" href="/logout" method="post" as="button" aria-label="Logout">
                    <span class="material-symbols-outlined">logout</span>
                </Link>
            </div>
        </header>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Profile Settings</h1>
                    <p>Perbarui data akun dan informasi operator POS.</p>
                </div>
                <Link class="secondary-action" href="/catalog">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Katalog
                </Link>
            </section>

            <section class="profile-grid">
                <aside class="profile-card">
                    <div class="profile-card__avatar">
                        <span class="material-symbols-outlined fill">person</span>
                    </div>
                    <strong>{{ displayName }}</strong>
                    <span>{{ form.email }}</span>
                    <div class="role-pill">{{ displayRole }}</div>
                </aside>

                <form class="settings-panel" @submit.prevent="submit">
                    <div class="settings-panel__header">
                        <h2>Data Profile</h2>
                        <span v-if="$page.props.flash?.success" class="success-pill">{{ $page.props.flash.success }}</span>
                    </div>

                    <div class="form-grid">
                        <label>
                            <span>Nama Lengkap</span>
                            <input v-model="form.fullname" type="text" placeholder="Nama lengkap" />
                            <small v-if="form.errors.fullname">{{ form.errors.fullname }}</small>
                        </label>
                        <label>
                            <span>Email</span>
                            <input v-model="form.email" type="email" placeholder="email@example.com" />
                            <small v-if="form.errors.email">{{ form.errors.email }}</small>
                        </label>
                        <label>
                            <span>No. Telepon</span>
                            <input v-model="form.phone_number" type="tel" placeholder="081234567890" />
                        </label>
                        <label>
                            <span>Gender</span>
                            <select v-model="form.gender">
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                                <option value="Tidak-Spesifik">Tidak-Spesifik</option>
                            </select>
                        </label>
                        <label>
                            <span>Kota</span>
                            <input v-model="form.city" type="text" placeholder="Bandung" />
                        </label>
                        <label>
                            <span>Provinsi</span>
                            <input v-model="form.province" type="text" placeholder="Jawa Barat" />
                        </label>
                        <label>
                            <span>Tanggal Lahir</span>
                            <input v-model="form.date_of_birth" type="date" />
                        </label>
                        <label>
                            <span>Password Baru</span>
                            <input v-model="form.password" type="password" placeholder="Kosongkan jika tidak diganti" />
                            <small v-if="form.errors.password">{{ form.errors.password }}</small>
                        </label>
                        <label>
                            <span>Konfirmasi Password</span>
                            <input v-model="form.confirm_password" type="password" placeholder="Konfirmasi password baru" />
                            <small v-if="form.errors.confirm_password">{{ form.errors.confirm_password }}</small>
                        </label>
                        <label class="form-grid__wide">
                            <span>Alamat</span>
                            <textarea v-model="form.address" rows="3" placeholder="Alamat toko/operator"></textarea>
                        </label>
                    </div>

                    <div class="settings-panel__actions">
                        <Link class="secondary-action" href="/logout" method="post" as="button">
                            <span class="material-symbols-outlined">logout</span>
                            Logout
                        </Link>
                        <button class="primary-action" type="submit" :disabled="form.processing">
                            <span class="material-symbols-outlined fill">save</span>
                            Simpan Profile
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

:global(body) {
    margin: 0;
    overflow: hidden;
    background: #f8f9fa;
}

* {
    box-sizing: border-box;
}

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    line-height: 1;
}

.material-symbols-outlined.fill {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.dashboard-shell {
    min-height: 100vh;
    width: 100vw;
    overflow: hidden;
    background: #f8f9fa;
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.side-nav {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 40;
    display: flex;
    width: 80px;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid #c6c5d4;
    background: #ffffff;
    padding: 24px 8px;
}

.side-nav__main,
.nav-list {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.branch-card {
    margin-bottom: 24px;
    width: 100%;
    text-align: center;
}

.branch-card__icon {
    display: grid;
    width: 48px;
    height: 48px;
    margin: 0 auto 8px;
    place-items: center;
    border-radius: 8px;
    background: #1a237e;
    color: #ffffff;
}

.branch-card__name,
.branch-card__terminal,
.nav-item span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 10px;
    font-weight: 700;
}

.branch-card__name {
    color: #191c1d;
    font-size: 11px;
}

.branch-card__terminal {
    color: #454652;
}

.nav-list {
    gap: 8px;
    width: 100%;
}

button,
a {
    font: inherit;
}

button {
    border: 0;
    cursor: pointer;
}

.nav-item {
    display: flex;
    min-height: 54px;
    width: 56px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border-radius: 8px;
    background: transparent;
    color: #454652;
    text-decoration: none;
}

.nav-item:hover {
    background: #e7e8e9;
}

.nav-item--active {
    background: #a0f399;
    color: #217128;
}

.nav-item--footer {
    width: 100%;
}

.top-bar {
    position: fixed;
    top: 0;
    left: 80px;
    z-index: 50;
    display: flex;
    width: calc(100vw - 80px);
    height: 64px;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #c6c5d4;
    background: #f8f9fa;
    padding: 0 16px;
}

.top-bar__left,
.top-bar__right,
.profile,
.page-title,
.settings-panel__header,
.settings-panel__actions {
    display: flex;
    align-items: center;
}

.top-bar__right {
    gap: 8px;
}

.brand {
    color: #1a237e;
    font-size: 32px;
    font-weight: 800;
}

.icon-button {
    display: grid;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    place-items: center;
    border-radius: 999px;
    background: transparent;
    color: #454652;
    text-decoration: none;
}

.icon-button:hover {
    background: #edeeef;
}

.top-bar__divider {
    width: 1px;
    height: 32px;
    margin: 0 8px;
    background: #c6c5d4;
}

.profile {
    gap: 12px;
    padding-left: 8px;
}

.profile > div:first-child {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.profile strong {
    color: #000666;
    font-size: 14px;
    font-weight: 600;
}

.profile span {
    color: #454652;
    font-size: 10px;
    font-weight: 700;
}

.profile__avatar {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 1px solid #c6c5d4;
    border-radius: 999px;
    background: #1a237e;
    color: #ffffff;
}

.content {
    height: calc(100vh - 64px);
    margin-left: 80px;
    margin-top: 64px;
    overflow-y: auto;
    padding: 24px;
}

.page-title,
.profile-grid {
    width: min(100%, 1180px);
    margin-inline: auto;
}

.page-title {
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.page-title h1 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.page-title p {
    margin: 2px 0 0;
    color: #454652;
}

.profile-grid {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 12px;
}

.profile-card,
.settings-panel {
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
}

.profile-card {
    display: flex;
    min-height: 260px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    text-align: center;
}

.profile-card__avatar {
    display: grid;
    width: 72px;
    height: 72px;
    margin-bottom: 14px;
    place-items: center;
    border-radius: 999px;
    background: #000666;
    color: #ffffff;
}

.profile-card__avatar span {
    font-size: 38px;
}

.profile-card strong {
    color: #000666;
    font-size: 20px;
}

.profile-card > span {
    margin-top: 4px;
    color: #454652;
}

.role-pill,
.success-pill {
    width: max-content;
    border-radius: 999px;
    background: #a0f399;
    color: #217128;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 800;
}

.role-pill {
    margin-top: 14px;
}

.settings-panel {
    overflow: hidden;
}

.settings-panel__header {
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #c6c5d4;
    padding: 18px;
}

.settings-panel h2 {
    margin: 0;
    color: #000666;
    font-size: 22px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    padding: 18px;
}

.form-grid__wide {
    grid-column: 1 / -1;
}

.form-grid label > span {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 800;
}

input,
select,
textarea {
    width: 100%;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    color: #191c1d;
    outline: 0;
}

input,
select {
    height: 42px;
    padding: 0 12px;
}

textarea {
    resize: vertical;
    padding: 10px 12px;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #000666;
    box-shadow: 0 0 0 3px rgb(0 6 102 / 12%);
}

small {
    display: block;
    margin-top: 6px;
    color: #ba1a1a;
    font-weight: 700;
}

.settings-panel__actions {
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #edeeef;
    padding: 18px;
}

.primary-action,
.secondary-action {
    display: inline-flex;
    min-height: 42px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 8px;
    padding: 0 14px;
    font-weight: 800;
    text-decoration: none;
}

.primary-action {
    border: 0;
    background: #1b6d24;
    color: #ffffff;
}

.secondary-action {
    border: 1px solid #c6c5d4;
    background: #ffffff;
    color: #000666;
}

@media (max-width: 900px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    :global(body) {
        overflow: auto;
    }

    .dashboard-shell {
        overflow: visible;
    }

    .side-nav {
        inset: auto 0 0 0;
        width: 100%;
        height: 72px;
        flex-direction: row;
        padding: 8px;
        border-top: 1px solid #c6c5d4;
        border-right: 0;
    }

    .branch-card,
    .nav-item:nth-child(n + 6),
    .nav-item--footer {
        display: none;
    }

    .nav-list,
    .side-nav__main {
        flex: 1;
        flex-direction: row;
        justify-content: space-around;
    }

    .top-bar {
        left: 0;
        width: 100%;
    }

    .profile > div:first-child {
        display: none;
    }

    .content {
        height: auto;
        min-height: calc(100vh - 64px);
        margin-left: 0;
        padding: 18px 14px 92px;
    }

    .page-title {
        align-items: flex-start;
        flex-direction: column;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
