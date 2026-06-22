<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    tables: { type: Array, default: () => [] },
    cabangs: { type: Array, default: () => [] },
    serverTime: { type: String, default: '' },
});

const tableList = ref([]);

const modalOpen = ref(false);
const confirmDelete = ref(null);
const editing = ref(null);
const currentPage = ref(1);

const filters = reactive({
    search: '',
    location: '',
    status: '',
    limit: 20,
    sort: 'ASC',
});

const form = useForm({
    table_number: '',
    capacity: 4,
    location: 'indoor',
    status: 'available',
});

watch(() => props.tables, (val) => {
    tableList.value = val ? [...val] : [];
}, { immediate: true });



const filteredTables = computed(() => {
    const q = filters.search.trim().toLowerCase();
    let items = tableList.value.filter((t) => {
        const matchSearch = !q || t.table_number.toLowerCase().includes(q);
        const matchLocation = !filters.location || t.location === filters.location;
        const matchStatus = !filters.status || t.status === filters.status;
        return matchSearch && matchLocation && matchStatus;
    });
    items = [...items].sort((a, b) => a.table_number.localeCompare(b.table_number));
    return filters.sort === 'DESC' ? items.reverse() : items;
});

const pageSize = computed(() => Math.max(1, Math.min(100, Number(filters.limit || 20))));
const meta = computed(() => {
    const last = Math.max(1, Math.ceil(filteredTables.value.length / pageSize.value));
    return { current_page: Math.min(currentPage.value, last), last_page: last, total: filteredTables.value.length };
});

const paginatedTables = computed(() => {
    const s = (meta.value.current_page - 1) * pageSize.value;
    return filteredTables.value.slice(s, s + pageSize.value);
});

const statusLabel = (s) => ({ available: 'Available', occupied: 'Occupied', reserved: 'Reserved', maintenance: 'Maintenance' })[s] ?? s;
const statusIcon = (s) => ({ available: 'check_circle', occupied: 'block', reserved: 'event', maintenance: 'build' })[s] ?? 'info';
const statusClass = (s) => `status--${s}`;

const resetFilters = () => { filters.search = ''; filters.location = ''; filters.status = ''; filters.limit = 20; filters.sort = 'ASC'; currentPage.value = 1; };

const openCreate = () => { resetForm(); modalOpen.value = true; };
const openEdit = (item) => {
    resetForm(); editing.value = item;
    form.table_number = item.table_number ?? '';
    form.capacity = item.capacity ?? 4;
    form.location = item.location ?? 'indoor';
    form.status = item.status === 'maintenance' ? 'maintenance' : 'available';
    modalOpen.value = true;
};
const closeModal = () => { modalOpen.value = false; resetForm(); };
const resetForm = () => { form.reset(); form.clearErrors(); form.table_number = ''; form.capacity = 4; form.location = 'indoor'; form.status = 'available'; editing.value = null; };
const submit = () => {
    const opts = { preserveScroll: true, onSuccess: closeModal };
    if (editing.value) { form.transform((d) => ({ ...d, _method: 'put' })).post(`/tables/items/${editing.value.guid}`, opts); return; }
    form.post('/tables/items', opts);
};
const confirmDestroy = (item) => { confirmDelete.value = item; };
const executeDestroy = () => {
    if (!confirmDelete.value) return;
    form.delete(`/tables/items/${confirmDelete.value.guid}`, { preserveScroll: true });
    confirmDelete.value = null;
};
const changePage = (p) => { currentPage.value = Math.max(1, Math.min(meta.value.last_page, p)); };
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
                    <h1>Meja Restoran</h1>
                    <p>Kelola master data meja dan pantau status terkini.</p>
                </div>
                <div class="page-actions">
                    <Link class="secondary-action" href="/reservations">
                        <span class="material-symbols-outlined">event_seat</span>
                        Reservasi
                    </Link>
                    <button class="primary-action" type="button" @click="openCreate">
                        <span class="material-symbols-outlined fill">add_circle</span>
                        Tambah Meja
                    </button>
                </div>
            </section>

            <section class="inventory-layout">
                <aside class="filter-panel">
                    <div class="filter-panel__head"><strong>Filter</strong></div>
                    <label><span>Search</span><input v-model="filters.search" type="text" placeholder="Cari meja..." /></label>
                    <label><span>Location</span>
                        <select v-model="filters.location">
                            <option value="">Semua</option>
                            <option value="indoor">Indoor</option>
                            <option value="outdoor">Outdoor</option>
                        </select>
                    </label>
                    <label><span>Status</span>
                        <select v-model="filters.status">
                            <option value="">Semua</option>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="reserved">Reserved</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </label>
                    <label><span>Pagination</span><input v-model.number="filters.limit" type="number" min="1" max="100" /></label>
                    <label><span>Sort</span>
                        <select v-model="filters.sort">
                            <option value="ASC">A-Z</option>
                            <option value="DESC">Z-A</option>
                        </select>
                    </label>
                    <button class="secondary-action" type="button" @click="resetFilters">
                        <span class="material-symbols-outlined">restart_alt</span>Reset
                    </button>
                </aside>

                <section class="panel">
                    <div class="panel__header">
                        <div><h2>Daftar Meja</h2><p>{{ meta.total }} meja</p></div>
                        <div class="panel-actions">
                            <div class="pager">
                                <button class="icon-button" :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </button>
                                <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
                                <button class="icon-button" :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Meja</th>
                                    <th>Lokasi</th>
                                    <th>Kapasitas</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in paginatedTables" :key="item.guid">
                                    <td><strong>{{ item.table_number }}</strong></td>
                                    <td>{{ item.location }}</td>
                                    <td>{{ item.capacity }} orang</td>
                                    <td>
                                        <span class="status-badge" :class="statusClass(item.status)">
                                            <span class="material-symbols-outlined">{{ statusIcon(item.status) }}</span>
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </td>
                                    <td class="row-actions">
                                        <button class="icon-button" aria-label="Edit" @click="openEdit(item)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <button class="icon-button icon-button--danger" aria-label="Delete" @click="confirmDestroy(item)">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="paginatedTables.length === 0" class="empty-state">Tidak ada meja sesuai filter.</div>
                    </div>
                </section>
            </section>
        </main>

        <div v-if="confirmDelete" class="modal-backdrop">
            <div class="modal modal--confirm">
                <div class="modal__header">
                    <div><h2>Konfirmasi Hapus</h2></div>
                    <button class="icon-button" type="button" @click="confirmDelete = null"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="modal__body">
                    <p>Yakin ingin menghapus meja <strong>{{ confirmDelete?.table_number }}</strong>?</p>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="confirmDelete = null">Batal</button>
                    <button class="primary-action primary-action--danger" type="button" :disabled="form.processing" @click="executeDestroy">
                        <span class="material-symbols-outlined fill">delete</span>Hapus
                    </button>
                </div>
            </div>
        </div>

        <div v-if="modalOpen" class="modal-backdrop">
            <form class="modal" @submit.prevent="submit">
                <div class="modal__header">
                    <div><h2>{{ editing ? 'Edit Meja' : 'Tambah Meja' }}</h2></div>
                    <button class="icon-button" type="button" @click="closeModal"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="form-grid">
                    <label><span>Nomor Meja</span><input v-model="form.table_number" type="text" placeholder="A1" /><small v-if="form.errors.table_number">{{ form.errors.table_number }}</small></label>
                    <label><span>Kapasitas</span><input v-model.number="form.capacity" type="number" min="1" /><small v-if="form.errors.capacity">{{ form.errors.capacity }}</small></label>
                    <label><span>Lokasi</span>
                        <select v-model="form.location">
                            <option value="indoor">Indoor</option>
                            <option value="outdoor">Outdoor</option>
                        </select>
                    </label>
                    <label><span>Status</span>
                        <select v-model="form.status">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </label>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="closeModal">Batal</button>
                    <button class="primary-action" type="submit" :disabled="form.processing"><span class="material-symbols-outlined fill">save</span>Simpan</button>
                </div>
            </form>
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
.primary-action, .secondary-action { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; padding: 0 14px; font-weight: 800; }
.primary-action { background: #1b6d24; color: #fff; }
.primary-action--danger { background: #ba1a1a; }
.secondary-action { border: 1px solid #c6c5d4; background: #fff; color: #000666; }
.icon-button { display: inline-grid; width: 40px; height: 40px; place-items: center; border-radius: 999px; background: transparent; color: #454652; text-decoration: none; }
.icon-button:hover { background: #edeeef; }
.icon-button:disabled { opacity: 0.45; cursor: not-allowed; }
.icon-button--danger { color: #ba1a1a; }
.inventory-layout { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 16px; align-items: start; width: min(100%, 1360px); margin-inline: auto; }
.filter-panel, .panel { border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; }
.filter-panel { display: grid; gap: 12px; padding: 14px; }
.filter-panel__head { display: flex; align-items: center; justify-content: space-between; }
.filter-panel label > span { display: block; margin-bottom: 6px; color: #454652; font-size: 12px; font-weight: 800; }
.filter-panel input, .filter-panel select { width: 100%; height: 42px; padding: 0 12px; font: inherit; border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; color: #191c1d; outline: 0; }
.filter-panel .secondary-action { width: 100%; }
.panel { overflow: hidden; }
.panel__header { display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid #c6c5d4; padding: 14px; }
.panel h2 { margin: 0; font-size: 18px; }
.panel p { margin: 3px 0 0; color: #454652; }
.panel-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.pager { display: flex; align-items: center; gap: 8px; color: #5c5f66; font-size: 13px; font-weight: 800; white-space: nowrap; }
.table-wrap { overflow-x: auto; }
table { width: 100%; min-width: 500px; border-collapse: collapse; }
th, td { border-bottom: 1px solid #edeeef; padding: 14px; text-align: left; vertical-align: middle; }
th { color: #454652; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
td { color: #191c1d; }
.row-actions { text-align: right; white-space: nowrap; display: flex; justify-content: flex-end; gap: 4px; }
.status-badge { display: inline-flex; align-items: center; gap: 4px; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 800; white-space: nowrap; }
.status-badge .material-symbols-outlined { font-size: 16px; }
.status--available { background: #dcfce7; color: #166534; }
.status--occupied { background: #fee2e2; color: #991b1b; }
.status--reserved { background: #fff7d6; color: #8a5a00; }
.status--maintenance { background: #e7e8e9; color: #454652; }
.empty-state { padding: 32px; color: #5d6268; text-align: center; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; background: rgb(0 0 0 / 36%); padding: 18px; }
.modal { width: min(100%, 460px); border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; box-shadow: 0 18px 48px rgb(15 23 42 / 20%); }
.modal__header, .modal__actions { display: flex; align-items: center; }
.modal__header { justify-content: space-between; gap: 14px; border-bottom: 1px solid #c6c5d4; padding: 18px; }
.modal h2 { margin: 0; color: #000666; font-size: 20px; }
.modal--confirm .modal__body { padding: 18px; }
.modal--confirm .modal__body p { margin: 0; font-size: 15px; line-height: 1.5; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; padding: 18px; }
label > span { display: block; margin-bottom: 6px; color: #454652; font-size: 12px; font-weight: 800; }
input, select { width: 100%; height: 42px; padding: 0 12px; font: inherit; border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; color: #191c1d; outline: 0; }
small { display: block; margin-top: 6px; color: #ba1a1a; font-weight: 700; }
.modal__actions { justify-content: flex-end; gap: 10px; padding: 18px; }
@media (max-width: 1000px) { .inventory-layout { grid-template-columns: 1fr; } }
@media (max-width: 720px) { :global(body) { overflow: auto; } .content { height: auto; margin-left: 0; padding: 18px 14px 92px; } .brand { font-size: 24px; } .page-title { align-items: stretch; flex-direction: column; } .page-actions { align-self: flex-start; } .form-grid { grid-template-columns: 1fr; } }
</style>
