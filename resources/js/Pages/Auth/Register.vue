<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    fullname: '',
    email: '',
    phone_number: '',
    password: '',
    confirm_password: '',
});

const submit = () => {
    form.post('/register', {
        preserveScroll: true,
    });
};
</script>

<template>
    <main class="auth-shell">
        <section class="form-panel" aria-label="Register form">
            <div class="form-panel__header">
                <div class="brand-line">
                    <span class="material-symbols-outlined fill">storefront</span>
                    <strong>RetailPOS</strong>
                </div>
                <h1>Daftar Akun</h1>
                <p>Buat akun operasional untuk mengakses POS dan katalog produk.</p>
            </div>

            <form class="auth-form" @submit.prevent="submit">
                <label>
                    <span>Nama Lengkap</span>
                    <input v-model="form.fullname" type="text" placeholder="John Doe" autocomplete="name" />
                    <small v-if="form.errors.fullname">{{ form.errors.fullname }}</small>
                </label>

                <label>
                    <span>Email</span>
                    <input v-model="form.email" type="email" placeholder="john@example.com" autocomplete="email" />
                    <small v-if="form.errors.email">{{ form.errors.email }}</small>
                </label>

                <label>
                    <span>No. Telepon</span>
                    <input v-model="form.phone_number" type="tel" placeholder="081234567890" autocomplete="tel" />
                </label>

                <div class="split">
                    <label>
                    <span>Password</span>
                    <input v-model="form.password" type="password" placeholder="••••••••" autocomplete="new-password" />
                        <small v-if="form.errors.password">{{ form.errors.password }}</small>
                    </label>
                    <label>
                        <span>Konfirmasi</span>
                        <input v-model="form.confirm_password" type="password" placeholder="••••••••" autocomplete="new-password" />
                        <small v-if="form.errors.confirm_password">{{ form.errors.confirm_password }}</small>
                    </label>
                </div>

                <button class="primary-action" type="submit" :disabled="form.processing">
                    <span class="material-symbols-outlined fill">person_add</span>
                    Daftar
                </button>
            </form>

            <div class="switch-auth">
                <span>Sudah punya akun?</span>
                <Link href="/login">Login</Link>
            </div>
        </section>

        <section class="info-panel">
            <div class="info-card">
                <span class="material-symbols-outlined fill">inventory_2</span>
                <h2>Catalog ready</h2>
                <p>Kelola kategori, group, dan produk untuk alur penjualan yang lebih rapi.</p>
            </div>
            <div class="info-list">
                <div>
                    <span class="material-symbols-outlined">verified_user</span>
                    <strong>Role aware</strong>
                </div>
                <div>
                    <span class="material-symbols-outlined">point_of_sale</span>
                    <strong>POS friendly</strong>
                </div>
                <div>
                    <span class="material-symbols-outlined">sync</span>
                    <strong>Sync ready</strong>
                </div>
            </div>
        </section>
    </main>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

:global(body) {
    margin: 0;
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

.auth-shell {
    display: grid;
    min-height: 100vh;
    grid-template-columns: minmax(420px, 1fr) minmax(360px, 0.9fr);
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.form-panel {
    display: flex;
    width: min(100%, 560px);
    flex-direction: column;
    justify-content: center;
    margin: 0 auto;
    padding: 32px;
}

.brand-line,
.primary-action,
.switch-auth,
.info-list div {
    display: flex;
    align-items: center;
}

.brand-line {
    gap: 10px;
    color: #000666;
}

.brand-line span {
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border-radius: 8px;
    background: #1b6d24;
    color: #ffffff;
}

.brand-line strong {
    font-size: 24px;
    font-weight: 800;
}

h1 {
    margin: 28px 0 6px;
    color: #000666;
    font-size: 36px;
    line-height: 44px;
}

p {
    margin: 0;
    color: #454652;
}

.auth-form {
    display: grid;
    gap: 16px;
    margin-top: 28px;
}

.split {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.auth-form label > span {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 800;
}

input {
    width: 100%;
    height: 44px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    padding: 0 12px;
    color: #191c1d;
    outline: 0;
}

input:focus {
    border-color: #000666;
    box-shadow: 0 0 0 3px rgb(0 6 102 / 12%);
}

small {
    display: block;
    margin-top: 6px;
    color: #ba1a1a;
    font-weight: 700;
}

button {
    border: 0;
    font: inherit;
    cursor: pointer;
}

.primary-action {
    height: 46px;
    justify-content: center;
    gap: 8px;
    border-radius: 8px;
    background: #1b6d24;
    color: #ffffff;
    font-weight: 800;
}

.switch-auth {
    justify-content: center;
    gap: 8px;
    margin-top: 24px;
    color: #454652;
}

.switch-auth a {
    color: #000666;
    font-weight: 800;
    text-decoration: none;
}

.info-panel {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
    justify-content: center;
    gap: 14px;
    background: #000666;
    padding: 40px;
}

.info-card,
.info-list div {
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 8px;
    background: rgb(255 255 255 / 8%);
    color: #ffffff;
}

.info-card {
    padding: 28px;
}

.info-card > span {
    display: grid;
    width: 56px;
    height: 56px;
    place-items: center;
    border-radius: 8px;
    background: #a0f399;
    color: #217128;
    font-size: 32px;
}

.info-card h2 {
    margin: 24px 0 8px;
    font-size: 34px;
}

.info-card p {
    color: #bdc2ff;
    line-height: 26px;
}

.info-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}

.info-list div {
    gap: 10px;
    padding: 14px;
}

.info-list span {
    color: #a0f399;
}

@media (max-width: 860px) {
    .auth-shell {
        grid-template-columns: 1fr;
    }

    .info-panel {
        min-height: auto;
    }
}

@media (max-width: 560px) {
    .split {
        grid-template-columns: 1fr;
    }
}
</style>
