<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    serverTime: { type: String, default: '' },
});

const modalOpen = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    is_default: false,
});

const openCreate = () => { resetForm(); modalOpen.value = true; };
const openEdit = (item) => {
    resetForm(); editing.value = item;
    form.name = item.name ?? '';
    form.is_default = item.is_default ?? false;
    modalOpen.value = true;
};
const closeModal = () => { modalOpen.value = false; resetForm(); };
const resetForm = () => { form.reset(); form.clearErrors(); form.name = ''; form.is_default = false; editing.value = null; };
const submit = () => {
    const opts = { preserveScroll: true, onSuccess: closeModal };
    if (editing.value) { form.transform((d) => ({ ...d, _method: 'put' })).post(`/roles/items/${editing.value.guid}`, opts); return; }
    form.post('/roles/items', opts);
};
const destroyItem = (item) => { form.delete(`/roles/items/${item.guid}`, { preserveScroll: true }); };
const confirmDelete = ref(null);
const confirmDestroy = (item) => { confirmDelete.value = item; };
const executeDestroy = () => {
    if (confirmDelete.value) {
        destroyItem(confirmDelete.value);
        confirmDelete.value = null;
    }
};
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />
        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
            </template>
        </AppNavbar>
        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Roles</h1>
                    <p>Kelola role/hak akses pengguna.</p>
                </div>
                <div class="page-actions">
                    <Link class="secondary-action" href="/permissions">
                        <span class="material-symbols-outlined">manage_accounts</span>
                        Permission
                    </Link>
                    <Link class="secondary-action" href="/users">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Users
                    </Link>
                    <button class="primary-action" type="button" @click="openCreate">
                        <span class="material-symbols-outlined fill">add_circle</span>
                        Tambah Role
                    </button>
                </div>
            </section>

            <section class="panel" style="width: min(100%, 1360px); margin-inline: auto;">
                <div class="panel__header">
                    <div><h2>Daftar Role</h2><p>{{ props.roles.length }} role</p></div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Role</th>
                                <th>Default</th>
                                <th>Jumlah User</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in props.roles" :key="item.guid">
                                <td><strong>{{ item.name }}</strong></td>
                                <td>
                                    <span v-if="item.is_default" class="status-badge status--available">
                                        <span class="material-symbols-outlined">check_circle</span> Default
                                    </span>
                                    <span v-else class="status-badge status--maintenance">Tidak</span>
                                </td>
                                <td>{{ item.users_count }}</td>
                                <td class="row-actions">
                                    <button class="icon-button" aria-label="Edit" @click="openEdit(item)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button v-if="!item.is_default" class="icon-button icon-button--danger" aria-label="Delete" @click="confirmDestroy(item)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="props.roles.length === 0" class="empty-state">Belum ada role.</div>
                </div>
            </section>
        </main>

        <div v-if="modalOpen" class="modal-backdrop">
            <form class="modal" @submit.prevent="submit">
                <div class="modal__header">
                    <div><h2>{{ editing ? 'Edit Role' : 'Tambah Role' }}</h2></div>
                    <button class="icon-button" type="button" @click="closeModal"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="form-grid">
                    <label class="form-grid__full"><span>Nama Role</span><input v-model="form.name" type="text" placeholder="Superadmin" /><small v-if="form.errors.name">{{ form.errors.name }}</small></label>
                    <label class="form-grid__full"><span>Default</span>
                        <select v-model="form.is_default">
                            <option :value="false">Tidak</option>
                            <option :value="true">Ya</option>
                        </select>
                    </label>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="closeModal">Batal</button>
                    <button class="primary-action" type="submit" :disabled="form.processing"><span class="material-symbols-outlined fill">save</span>Simpan</button>
                </div>
            </form>
        </div>
        <div v-if="confirmDelete" class="modal-backdrop">
            <div class="modal modal--confirm">
                <div class="modal__header">
                    <div><h2>Konfirmasi Hapus</h2></div>
                    <button class="icon-button" type="button" @click="confirmDelete = null"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="modal__body">
                    <p>Yakin ingin menghapus role <strong>{{ confirmDelete?.name }}</strong>?</p>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="confirmDelete = null">Batal</button>
                    <button class="primary-action primary-action--danger" type="button" :disabled="form?.processing" @click="executeDestroy">
                        <span class="material-symbols-outlined fill">delete</span>Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');
:global(body) { margin: 0; overflow: hidden; background: #f8f9fa; }
* { box-sizing: border-box; }
button, a { font: inherit; }
button { border: 0; cursor: pointer; }
.dashboard-shell { min-height: 100vh; background: #f8f9fa; color: #191c1d; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
.brand { color: #1a237e; font-size: 32px; font-weight: 800; line-height: 40px; }
.content { height: calc(100vh - 64px); margin-left: 80px; margin-top: 64px; overflow-y: auto; padding: 24px; }
.page-title { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 12px; width: min(100%, 1360px); margin-inline: auto; }
.page-title h1 { margin: 0; font-size: 32px; font-weight: 700; }
.page-title p { margin: 3px 0 0; color: #454652; }
.page-actions { display: flex; gap: 8px; flex-shrink: 0; }
.primary-action, .secondary-action { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; padding: 0 14px; font-weight: 800; text-decoration: none; }
.primary-action { background: #1b6d24; color: #fff; }
.secondary-action { border: 1px solid #c6c5d4; background: #fff; color: #000666; }
.icon-button { display: inline-grid; width: 40px; height: 40px; place-items: center; border-radius: 999px; background: transparent; color: #454652; text-decoration: none; }
.icon-button:hover { background: #edeeef; }
.icon-button:disabled { opacity: 0.45; cursor: not-allowed; }
.icon-button--danger { color: #ba1a1a; }
.panel { border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; overflow: hidden; }
.panel__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid #c6c5d4; padding: 14px; }
.panel h2 { margin: 0; font-size: 18px; }
.panel p { margin: 3px 0 0; color: #454652; }
.table-wrap { overflow-x: auto; }
table { width: 100%; min-width: 500px; border-collapse: collapse; }
th, td { border-bottom: 1px solid #edeeef; padding: 14px; text-align: left; vertical-align: middle; }
th { color: #454652; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
td { color: #191c1d; }
.row-actions { text-align: right; white-space: nowrap; display: flex; justify-content: flex-end; gap: 4px; }
.status-badge { display: inline-flex; align-items: center; gap: 4px; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 800; white-space: nowrap; }
.status-badge .material-symbols-outlined { font-size: 16px; }
.status--available { background: #dcfce7; color: #166534; }
.status--maintenance { background: #e7e8e9; color: #454652; }
.empty-state { padding: 32px; color: #5d6268; text-align: center; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; background: rgb(0 0 0 / 36%); padding: 18px; }
.modal { width: min(100%, 460px); border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; box-shadow: 0 18px 48px rgb(15 23 42 / 20%); overflow-y: auto; max-height: 90vh; }
.modal__header, .modal__actions { display: flex; align-items: center; }
.modal__header { justify-content: space-between; gap: 14px; border-bottom: 1px solid #c6c5d4; padding: 18px; }
.modal h2 { margin: 0; color: #000666; font-size: 20px; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; padding: 18px; }
.form-grid__full { grid-column: 1 / -1; }
label > span { display: block; margin-bottom: 6px; color: #454652; font-size: 12px; font-weight: 800; }
input, select, textarea { width: 100%; padding: 0 12px; font: inherit; border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; color: #191c1d; outline: 0; }
input, select { height: 42px; }
textarea { padding: 10px 12px; resize: vertical; }
small { display: block; margin-top: 6px; color: #ba1a1a; font-weight: 700; }
.modal--confirm .modal__body { padding: 18px; }
.modal--confirm .modal__body p { margin: 0; font-size: 15px; line-height: 1.5; }
.primary-action--danger { background: #ba1a1a; }
.modal__actions { justify-content: flex-end; gap: 10px; padding: 18px; }
@media (max-width: 720px) { :global(body) { overflow: auto; } .content { height: auto; margin-left: 0; padding: 18px 14px 92px; } .brand { font-size: 24px; } .page-title { align-items: stretch; flex-direction: column; } .page-actions { align-self: flex-start; } .form-grid { grid-template-columns: 1fr; } }
</style>
