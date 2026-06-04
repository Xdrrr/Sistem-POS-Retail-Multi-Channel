<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    username: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post('/login', {
        preserveScroll: true,
    });
};
</script>

<template>
    <main class="auth-shell">
        <section class="auth-panel">
            <div class="auth-panel__brand">
                <div class="brand-mark">
                    <span class="material-symbols-outlined fill">storefront</span>
                </div>
                <div>
                    <strong>RetailPOS</strong>
                    <span>Main Branch - Terminal 01</span>
                </div>
            </div>

            <div class="auth-copy">
                <span class="eyebrow">Cashier Access</span>
                <h1>Masuk ke dashboard POS</h1>
                <p>Kelola transaksi, shift, laporan, dan katalog produk dari satu layar operasional.</p>
            </div>

            <div class="auth-stats">
                <div>
                    <span class="material-symbols-outlined">receipt_long</span>
                    <strong>142</strong>
                    <small>Transactions</small>
                </div>
                <!-- <div>
                    <span class="material-symbols-outlined">cloud_sync</span>
                    <strong>0</strong>
                    <small>Pending Sync</small>
                </div> -->
            </div>
        </section>

        <section class="form-panel" aria-label="Login form">
            <div class="form-panel__header">
                <h2>Login</h2>
                <p>Gunakan akun kasir atau admin toko.</p>
            </div>

            <form class="auth-form" @submit.prevent="submit">
                <label>
                    <span>Email</span>
                    <input v-model="form.username" type="email" placeholder="admin@example.com" autocomplete="username" />
                    <small v-if="form.errors.username">{{ form.errors.username }}</small>
                </label>

                <label>
                    <span>Password</span>
                    <input v-model="form.password" type="password" placeholder="••••••••" autocomplete="current-password" />
                    <small v-if="form.errors.password">{{ form.errors.password }}</small>
                </label>

                <div class="form-row">
                    <label class="check-row">
                        <input v-model="form.remember" type="checkbox" />
                        <span>Ingat sesi</span>
                    </label>
                    <button class="text-button" type="button">Reset</button>
                </div>

                <button class="primary-action" type="submit" :disabled="form.processing">
                    <span class="material-symbols-outlined fill">login</span>
                    Masuk
                </button>
            </form>

            <div class="switch-auth">
                <span>Belum punya akun?</span>
                <Link href="/register">Daftar</Link>
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
    grid-template-columns: minmax(360px, 0.95fr) minmax(420px, 1.05fr);
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.auth-panel {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
    justify-content: space-between;
    background: #000666;
    color: #ffffff;
    padding: 40px;
}

.auth-panel__brand,
.brand-mark,
.auth-stats,
.auth-stats div,
.primary-action,
.form-row,
.check-row,
.switch-auth {
    display: flex;
    align-items: center;
}

.auth-panel__brand {
    gap: 14px;
}

.brand-mark {
    width: 48px;
    height: 48px;
    justify-content: center;
    border-radius: 8px;
    background: #1b6d24;
}

.auth-panel__brand strong {
    display: block;
    font-size: 24px;
    font-weight: 800;
}

.auth-panel__brand span,
.auth-copy p,
.auth-stats small {
    color: #bdc2ff;
}

.auth-copy {
    max-width: 520px;
}

.eyebrow {
    color: #a0f399;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.auth-copy h1 {
    margin: 14px 0 10px;
    font-size: 46px;
    font-weight: 800;
    line-height: 54px;
}

.auth-copy p {
    margin: 0;
    font-size: 17px;
    line-height: 28px;
}

.auth-stats {
    gap: 12px;
}

.auth-stats div {
    min-width: 150px;
    gap: 10px;
    border: 1px solid rgb(255 255 255 / 18%);
    border-radius: 8px;
    background: rgb(255 255 255 / 8%);
    padding: 14px;
}

.auth-stats strong,
.auth-stats small {
    display: block;
}

.auth-stats strong {
    font-size: 22px;
}

.form-panel {
    display: flex;
    width: min(100%, 520px);
    flex-direction: column;
    justify-content: center;
    margin: 0 auto;
    padding: 32px;
}

.form-panel__header h2 {
    margin: 0;
    color: #000666;
    font-size: 34px;
}

.form-panel__header p {
    margin: 6px 0 28px;
    color: #454652;
}

.auth-form {
    display: grid;
    gap: 16px;
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

.form-row {
    justify-content: space-between;
    gap: 12px;
}

.check-row {
    gap: 8px;
    color: #454652;
    font-size: 14px;
}

.check-row input {
    width: 16px;
    height: 16px;
}

button {
    border: 0;
    font: inherit;
    cursor: pointer;
}

.text-button,
.switch-auth a {
    background: transparent;
    color: #000666;
    font-weight: 800;
    text-decoration: none;
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

@media (max-width: 860px) {
    .auth-shell {
        grid-template-columns: 1fr;
    }

    .auth-panel {
        min-height: auto;
        gap: 32px;
        padding: 28px;
    }

    .auth-copy h1 {
        font-size: 34px;
        line-height: 42px;
    }
}
</style>
