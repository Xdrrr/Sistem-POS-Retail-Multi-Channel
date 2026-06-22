<script setup>
import { Link, useForm, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    reservations: { type: Array, default: () => [] },
    cabangs: { type: Array, default: () => [] },
    tables: { type: Array, default: () => [] },
    serverTime: { type: String, default: '' },
});

const showMap = ref(true);
const modalOpen = ref(false);
const editing = ref(null);
const currentPage = ref(1);
const tableList = ref([]);
let pollTimer = null;

watch(() => props.tables, (val) => {
    tableList.value = val ? [...val] : [];
}, { immediate: true });

const startPolling = () => {
    pollTimer = setInterval(() => {
        router.reload({ only: ['tables'], preserveScroll: true, preserveState: true });
    }, 10000);
};

onMounted(() => {
    if (showMap.value) startPolling();
});

watch(showMap, (val) => {
    if (val) {
        startPolling();
    } else {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }
});

onUnmounted(() => {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
});

const filters = reactive({
    search: '',
    status: '',
    type: '',
    table_number: '',
    date_from: '',
    date_to: '',
    limit: 20,
    sort: 'ASC',
});

const filteredReservations = computed(() => {
    const q = filters.search.trim().toLowerCase();
    let items = props.reservations.filter((r) => {
        const matchSearch = !q || r.customer_name?.toLowerCase().includes(q) || r.table_number?.toLowerCase().includes(q);
        const matchStatus = !filters.status || r.status === filters.status;
        const matchType = !filters.type || r.type === filters.type;
        const matchTable = !filters.table_number || r.table_number === filters.table_number;
        const matchDateFrom = !filters.date_from || r.reservation_date >= filters.date_from;
        const matchDateTo = !filters.date_to || r.reservation_date <= filters.date_to;
        return matchSearch && matchStatus && matchType && matchTable && matchDateFrom && matchDateTo;
    });
    items = [...items].sort((a, b) => a.reservation_date?.localeCompare(b.reservation_date) || 0);
    return filters.sort === 'DESC' ? items.reverse() : items;
});

const pageSize = computed(() => Math.max(1, Math.min(100, Number(filters.limit || 20))));
const meta = computed(() => {
    const last = Math.max(1, Math.ceil(filteredReservations.value.length / pageSize.value));
    return { current_page: Math.min(currentPage.value, last), last_page: last, total: filteredReservations.value.length };
});

const paginatedReservations = computed(() => {
    const s = (meta.value.current_page - 1) * pageSize.value;
    return filteredReservations.value.slice(s, s + pageSize.value);
});

const resetFilters = () => {
    filters.search = ''; filters.status = ''; filters.type = ''; filters.table_number = '';
    filters.date_from = ''; filters.date_to = ''; filters.limit = 20; filters.sort = 'ASC'; currentPage.value = 1;
};
const changePage = (p) => { currentPage.value = Math.max(1, Math.min(meta.value.last_page, p)); };

const form = useForm({
    table_number: '',
    customer_name: '',
    customer_phone: '',
    guest_count: 1,
    reservation_date: new Date().toISOString().slice(0, 10),
    reservation_time: '12:00',
    end_time: '14:00',
    type: 'booking',
    notes: '',
    status: 'pending',
});

const statusLabel = (s) => ({ occupied: 'Occupied', pending: 'Pending', confirmed: 'Confirmed', seated: 'Seated', completed: 'Completed', cancelled: 'Cancelled' })[s] ?? s;
const statusClass = (s) => ({ occupied: 'badge--occupied', pending: 'badge--pending', confirmed: 'badge--confirmed', seated: 'badge--seated', completed: 'badge--completed', cancelled: 'badge--cancelled' })[s] ?? '';

const formatDate = (v) => { if (!v) return '-'; const d = new Date(v + 'T00:00:00'); return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); };

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.transform((d) => d);
    form.table_number = '';
    form.customer_name = '';
    form.customer_phone = '';
    form.guest_count = 1;
    form.reservation_date = new Date().toISOString().slice(0, 10);
    form.reservation_time = '12:00';
    form.end_time = '14:00';
    form.type = 'booking';
    form.notes = '';
    form.status = 'pending';
    editing.value = null;
};

const openCreate = () => {
    resetForm();
    modalOpen.value = true;
};

const openEdit = (item) => {
    resetForm();
    editing.value = item;
    form.table_number = item.table_number ?? '';
    form.customer_name = item.customer_name ?? '';
    form.customer_phone = item.customer_phone ?? '';
    form.guest_count = item.guest_count ?? 1;
    form.reservation_date = item.reservation_date ?? '';
    form.reservation_time = item.reservation_time ?? '12:00';
    form.end_time = item.end_time ?? '14:00';
    form.type = item.type ?? 'booking';
    form.notes = item.notes ?? '';
    form.status = item.status ?? 'pending';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    resetForm();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: closeModal };
    if (editing.value) {
        form.transform((d) => ({ ...d, _method: 'put' })).post(`/reservations/items/${editing.value.guid}`, options);
        return;
    }
    form.post('/reservations/items', options);
};

const destroyItem = (item) => {
    form.delete(`/reservations/items/${item.guid}`, { preserveScroll: true });
};

const confirmDelete = ref(null);
const confirmDestroy = (item) => { confirmDelete.value = item; };
const executeDestroy = () => {
    if (confirmDelete.value) {
        destroyItem(confirmDelete.value);
        confirmDelete.value = null;
    }
};

const releaseTarget = ref(null);
const confirmRelease = (table) => { releaseTarget.value = table; };
const submitRelease = (type) => {
    if (!releaseTarget.value) return;
    const guid = type === 'reservation' ? releaseTarget.value.reservation_guid : releaseTarget.value.order_guid;
    const url = type === 'reservation' ? `/reservations/${guid}/release` : `/orders/${guid}/release-table`;
    releaseTarget.value = null;
    router.post(url);
};

const editReservation = (table) => {
    const reservation = props.reservations.find((r) => r.guid === table.reservation_guid);
    if (reservation) openEdit(reservation);
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
                    <h1>Reservasi Meja</h1>
                    <p>Kelola reservasi meja makan pelanggan.</p>
                </div>
                <div class="page-actions">
                    <button class="secondary-action" type="button" @click="showMap = !showMap">
                        <span class="material-symbols-outlined">table_restaurant</span>
                        {{ showMap ? 'Tutup' : 'Map' }} Meja
                    </button>
                    <Link class="secondary-action" href="/tables">
                        <span class="material-symbols-outlined">grid_view</span>
                        Kelola Meja
                    </Link>
                    <button class="primary-action" type="button" @click="openCreate">
                        <span class="material-symbols-outlined fill">add_circle</span>
                        Tambah Reservasi
                    </button>
                </div>
            </section>

            <section v-if="showMap" class="table-map-section">
                <div class="map-legend">
                    <span class="legend-item"><span class="dot dot--available"></span> Available</span>
                    <span class="legend-item"><span class="dot dot--occupied"></span> Occupied</span>
                    <span class="legend-item"><span class="dot dot--reserved"></span> Reserved</span>
                    <span class="legend-item"><span class="dot dot--maintenance"></span> Maintenance</span>
                </div>
                <div class="map-grid">
                    <div v-for="t in tableList" :key="t.guid" class="map-card" :class="`map--${t.status}`">
                        <span class="map-card__number">{{ t.table_number }}</span>
                        <span class="map-card__capacity">{{ t.capacity }} org</span>
                        <span class="map-card__status">{{ statusLabel(t.status) }}</span>
                        <span v-if="t.status === 'reserved' && t.reservation_time" class="map-card__time">{{ t.reservation_time }}{{ t.end_time ? ' - ' + t.end_time : '' }}</span>
                        <div v-if="t.status === 'occupied'" class="map-card__actions">
                            <button class="map-action map-action--danger" type="button" @click="confirmRelease(t)">Kosongkan</button>
                        </div>
                        <div v-if="t.status === 'reserved'" class="map-card__actions">
                            <button class="map-action" type="button" @click="editReservation(t)">Edit</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="inventory-layout">
                <aside class="filter-panel">
                    <div class="filter-panel__head"><strong>Filter</strong></div>
                    <label><span>Search</span><input v-model="filters.search" type="text" placeholder="Cari nama/meja..." /></label>
                    <label><span>Status</span>
                        <select v-model="filters.status">
                            <option value="">Semua</option>
                            <option value="occupied">Occupied</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="seated">Seated</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </label>
                    <label><span>Tipe</span>
                        <select v-model="filters.type">
                            <option value="">Semua</option>
                            <option value="booking">Booking</option>
                            <option value="walkin">Walk-in</option>
                        </select>
                    </label>
                    <label><span>Meja</span>
                        <select v-model="filters.table_number">
                            <option value="">Semua</option>
                            <option v-for="t in tables" :key="t.guid" :value="t.table_number">{{ t.table_number }}</option>
                        </select>
                    </label>
                    <label><span>Dari Tanggal</span><input v-model="filters.date_from" type="date" /></label>
                    <label><span>Sampai Tanggal</span><input v-model="filters.date_to" type="date" /></label>
                    <label><span>Pagination</span><input v-model.number="filters.limit" type="number" min="1" max="100" /></label>
                    <label><span>Sort</span>
                        <select v-model="filters.sort">
                            <option value="ASC">Terlama</option>
                            <option value="DESC">Terbaru</option>
                        </select>
                    </label>
                    <button class="secondary-action" type="button" @click="resetFilters">
                        <span class="material-symbols-outlined">restart_alt</span>Reset
                    </button>
                </aside>

                <section class="panel">
                    <div class="panel__header">
                        <div><h2>Daftar Reservasi</h2><p>{{ meta.total }} reservasi</p></div>
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
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Tamu</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in paginatedReservations" :key="item.guid">
                                    <td><strong>{{ item.table_number }}</strong></td>
                                    <td>{{ item.customer_name }}</td>
                                    <td>{{ item.customer_phone || '-' }}</td>
                                    <td>{{ item.guest_count }}</td>
                                    <td>{{ formatDate(item.reservation_date) }}</td>
                                    <td>{{ item.reservation_time }}{{ item.end_time ? ' - ' + item.end_time : '' }}</td>
                                    <td>{{ item.type === 'walkin' ? 'Walk-in' : 'Booking' }}</td>
                                    <td><span class="badge" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></td>
                                    <td class="row-actions">
                                        <button class="icon-button" type="button" aria-label="Edit" @click="openEdit(item)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <button class="icon-button icon-button--danger" type="button" aria-label="Cancel" @click="confirmDestroy(item)">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="paginatedReservations.length === 0" class="empty-state">Belum ada reservasi.</div>
                    </div>
                </section>
            </section>
        </main>

        <div v-if="modalOpen" class="modal-backdrop">
            <form class="modal" @submit.prevent="submit">
                <div class="modal__header">
                    <div><h2>{{ editing ? 'Edit Reservasi' : 'Tambah Reservasi' }}</h2></div>
                    <button class="icon-button" type="button" aria-label="Close" @click="closeModal"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="form-grid">
                    <label><span>Meja</span>
                        <select v-model="form.table_number">
                            <option value="">Pilih Meja</option>
                            <option v-for="t in tables" :key="t.guid" :value="t.table_number">{{ t.table_number }} ({{ t.capacity }} org - {{ t.location }})</option>
                        </select>
                        <small v-if="form.errors.table_number">{{ form.errors.table_number }}</small>
                    </label>
                    <label><span>Jumlah Tamu</span><input v-model.number="form.guest_count" type="number" min="1" /><small v-if="form.errors.guest_count">{{ form.errors.guest_count }}</small></label>
                    <label class="form-grid__wide"><span>Nama Customer</span><input v-model="form.customer_name" type="text" placeholder="Nama pelanggan" /><small v-if="form.errors.customer_name">{{ form.errors.customer_name }}</small></label>
                    <label><span>No. Telepon</span><input v-model="form.customer_phone" type="text" placeholder="08xxxxxxxxxx" /><small v-if="form.errors.customer_phone">{{ form.errors.customer_phone }}</small></label>
                    <label><span>Tanggal</span><input v-model="form.reservation_date" type="date" /><small v-if="form.errors.reservation_date">{{ form.errors.reservation_date }}</small></label>
                    <label><span>Jam Mulai</span><input v-model="form.reservation_time" type="time" /><small v-if="form.errors.reservation_time">{{ form.errors.reservation_time }}</small></label>
                    <label><span>Jam Selesai</span><input v-model="form.end_time" type="time" :disabled="form.type === 'walkin'" /><small v-if="form.errors.end_time">{{ form.errors.end_time }}</small></label>
                    <label><span>Tipe</span>
                        <select v-model="form.type">
                            <option value="booking">Booking</option>
                            <option value="walkin">Walk-in</option>
                        </select>
                        <small v-if="form.errors.type">{{ form.errors.type }}</small>
                    </label>
                    <label><span>Status</span>
                        <select v-model="form.status">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="seated">Seated</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <small v-if="form.errors.status">{{ form.errors.status }}</small>
                    </label>
                    <label class="form-grid__wide"><span>Catatan</span><textarea v-model="form.notes" rows="2" placeholder="Opsional"></textarea><small v-if="form.errors.notes">{{ form.errors.notes }}</small></label>
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
                    <p>Yakin ingin menghapus reservasi <strong>{{ confirmDelete?.table_number || confirmDelete?.customer_name || '' }}</strong>?</p>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="confirmDelete = null">Batal</button>
                    <button class="primary-action primary-action--danger" type="button" :disabled="form?.processing" @click="executeDestroy">
                        <span class="material-symbols-outlined fill">delete</span>Hapus
                    </button>
                </div>
            </div>
        </div>

        <div v-if="releaseTarget" class="modal-backdrop">
            <div class="modal modal--confirm">
                <div class="modal__header">
                    <div><h2>Konfirmasi Kosongkan</h2></div>
                    <button class="icon-button" type="button" @click="releaseTarget = null"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="modal__body">
                    <p>Yakin ingin mengosongkan meja <strong>{{ releaseTarget?.table_number }}</strong>?</p>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="releaseTarget = null">Batal</button>
                    <button v-if="releaseTarget?.reservation_guid" class="primary-action primary-action--danger" type="button" @click="submitRelease('reservation')">
                        <span class="material-symbols-outlined fill">delete</span>Kosongkan
                    </button>
                    <button v-else-if="releaseTarget?.order_guid" class="primary-action primary-action--danger" type="button" @click="submitRelease('order')">
                        <span class="material-symbols-outlined fill">delete</span>Kosongkan
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
.page-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.table-map-section { width: min(100%, 1360px); margin: 0 auto 16px; }
.map-legend { display: flex; gap: 16px; margin-bottom: 12px; flex-wrap: wrap; }
.legend-item { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #454652; }
.dot { width: 12px; height: 12px; border-radius: 50%; }
.dot--available { background: #166534; }
.dot--occupied { background: #991b1b; }
.dot--reserved { background: #8a5a00; }
.dot--maintenance { background: #454652; }
.map-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
.map-card { display: flex; flex-direction: column; align-items: center; gap: 4px; border-radius: 10px; padding: 14px 10px; border: 2px solid; }
.map--available { background: #f0fdf4; border-color: #86efac; }
.map--occupied { background: #fef2f2; border-color: #fca5a5; }
.map--reserved { background: #fffbeb; border-color: #fcd34d; }
.map--maintenance { background: #f3f4f6; border-color: #d1d5db; }
.map-card__number { font-size: 20px; font-weight: 800; }
.map-card__capacity { font-size: 11px; color: #5d6268; }
.map-card__status { font-size: 11px; font-weight: 800; text-transform: uppercase; }
.map-card__time { font-size: 11px; color: #5d6268; margin-top: 2px; }
.map-card__actions { display: flex; gap: 4px; margin-top: 6px; }
.map-action { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 6px; border: 1px solid #c6c5d4; background: #fff; color: #000666; cursor: pointer; }
.map-action--danger { border-color: #fca5a5; color: #991b1b; }
.map--available .map-card__status { color: #166534; }
.map--occupied .map-card__status { color: #991b1b; }
.map--reserved .map-card__status { color: #8a5a00; }
.map--maintenance .map-card__status { color: #454652; }
.content { height: calc(100vh - 64px); margin-left: 80px; margin-top: 64px; overflow-y: auto; padding: 24px; }
.page-title, .inventory-layout { width: min(100%, 1360px); margin-inline: auto; }
.page-title { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.page-title h1 { margin: 0; font-size: 32px; font-weight: 700; }
.page-title p { margin: 3px 0 0; color: #454652; }
.primary-action, .secondary-action { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; padding: 0 14px; font-weight: 800; }
.primary-action { background: #1b6d24; color: #fff; }
.primary-action--danger { background: #ba1a1a; }
.secondary-action { border: 1px solid #c6c5d4; background: #fff; color: #000666; }
.icon-button { display: inline-grid; width: 40px; height: 40px; place-items: center; border-radius: 999px; background: transparent; color: #454652; text-decoration: none; }
.icon-button:hover { background: #edeeef; }
.icon-button:disabled { opacity: 0.45; cursor: not-allowed; }
.icon-button--danger { color: #ba1a1a; }
.inventory-layout { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 16px; align-items: start; }
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
table { width: 100%; min-width: 700px; border-collapse: collapse; }
th, td { border-bottom: 1px solid #edeeef; padding: 12px 14px; text-align: left; vertical-align: middle; }
th { color: #454652; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
td { color: #191c1d; }
.row-actions { text-align: right; white-space: nowrap; display: flex; justify-content: flex-end; gap: 4px; }
.badge { display: inline-flex; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 800; }
.badge--occupied { background: #fee2e2; color: #991b1b; }
.badge--pending { background: #fff7d6; color: #8a5a00; }
.badge--confirmed { background: #eef2ff; color: #1a237e; }
.badge--seated { background: #dcfce7; color: #166534; }
.badge--completed { background: #e7e8e9; color: #454652; }
.badge--cancelled { background: #fee2e2; color: #991b1b; }
.empty-state { padding: 32px; color: #5d6268; text-align: center; }
.modal-backdrop { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; background: rgb(0 0 0 / 36%); padding: 18px; }
.modal { width: min(100%, 520px); border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; box-shadow: 0 18px 48px rgb(15 23 42 / 20%); }
.modal__header, .modal__actions { display: flex; align-items: center; }
.modal__header { justify-content: space-between; gap: 14px; border-bottom: 1px solid #c6c5d4; padding: 18px; }
.modal h2 { margin: 0; color: #000666; font-size: 20px; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; padding: 18px; }
.form-grid__wide { grid-column: 1 / -1; }
label > span { display: block; margin-bottom: 6px; color: #454652; font-size: 12px; font-weight: 800; }
input, select, textarea { width: 100%; border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; color: #191c1d; outline: 0; padding: 0 12px; font: inherit; height: 42px; }
textarea { padding: 10px 12px; resize: vertical; height: auto; }
small { display: block; margin-top: 6px; color: #ba1a1a; font-weight: 700; }
.modal--confirm .modal__body { padding: 18px; }
.modal--confirm .modal__body p { margin: 0; font-size: 15px; line-height: 1.5; }
.modal__actions { justify-content: flex-end; gap: 10px; padding: 18px; }
@media (max-width: 1000px) { .inventory-layout { grid-template-columns: 1fr; } }
@media (max-width: 720px) {
    :global(body) { overflow: auto; }
    .content { height: auto; margin-left: 0; padding: 18px 14px 92px; }
    .brand { font-size: 24px; }
    .page-title, .modal__header { align-items: stretch; flex-direction: column; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
