<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    reservations: { type: Array, default: () => [] },
    cabangs: { type: Array, default: () => [] },
    tables: { type: Array, default: () => [] },
    serverTime: { type: String, default: '' },
});

const showMap = ref(false);
const modalOpen = ref(false);
const editing = ref(null);

const form = useForm({
    table_number: '',
    customer_name: '',
    customer_phone: '',
    guest_count: 1,
    reservation_date: new Date().toISOString().slice(0, 10),
    reservation_time: '12:00',
    notes: '',
    status: 'pending',
});

const statusLabel = (s) => ({ pending: 'Pending', confirmed: 'Confirmed', seated: 'Seated', completed: 'Completed', cancelled: 'Cancelled' })[s] ?? s;
const statusClass = (s) => ({ pending: 'badge--pending', confirmed: 'badge--confirmed', seated: 'badge--seated', completed: 'badge--completed', cancelled: 'badge--cancelled' })[s] ?? '';

const formatDate = (v) => { if (!v) return '-'; const d = new Date(v + 'T00:00:00'); return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); };

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.table_number = '';
    form.customer_name = '';
    form.customer_phone = '';
    form.guest_count = 1;
    form.reservation_date = new Date().toISOString().slice(0, 10);
    form.reservation_time = '12:00';
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
        form.transform((d) => ({ ...d, _method: 'put' })).post(`/reservations/${editing.value.guid}`, options);
        return;
    }
    form.post('/reservations', options);
};

const destroyItem = (item) => {
    form.delete(`/reservations/${item.guid}`, { preserveScroll: true });
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
                    <div v-for="t in tables" :key="t.guid" class="map-card" :class="`map--${t.status}`">
                        <span class="map-card__number">{{ t.table_number }}</span>
                        <span class="map-card__capacity">{{ t.capacity }} org</span>
                        <span class="map-card__status">{{ t.status }}</span>
                    </div>
                </div>
            </section>

            <section class="panel">
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
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in reservations" :key="item.guid">
                                <td><strong>{{ item.table_number }}</strong></td>
                                <td>{{ item.customer_name }}</td>
                                <td>{{ item.customer_phone || '-' }}</td>
                                <td>{{ item.guest_count }}</td>
                                <td>{{ formatDate(item.reservation_date) }}</td>
                                <td>{{ item.reservation_time }}</td>
                                <td><span class="badge" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></td>
                                <td class="row-actions">
                                    <button class="icon-button" type="button" aria-label="Edit" @click="openEdit(item)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="icon-button icon-button--danger" type="button" aria-label="Cancel" @click="destroyItem(item)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="reservations.length === 0" class="empty-state">Belum ada reservasi.</div>
                </div>
            </section>
        </main>

        <div v-if="modalOpen" class="modal-backdrop">
            <form class="modal" @submit.prevent="submit">
                <div class="modal__header">
                    <div>
                        <h2>{{ editing ? 'Edit Reservasi' : 'Tambah Reservasi' }}</h2>
                    </div>
                    <button class="icon-button" type="button" aria-label="Close" @click="closeModal">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="form-grid">
                    <label>
                        <span>Meja</span>
                        <input v-model="form.table_number" type="text" placeholder="A1" />
                        <small v-if="form.errors.table_number">{{ form.errors.table_number }}</small>
                    </label>
                    <label>
                        <span>Jumlah Tamu</span>
                        <input v-model.number="form.guest_count" type="number" min="1" />
                        <small v-if="form.errors.guest_count">{{ form.errors.guest_count }}</small>
                    </label>
                    <label class="form-grid__wide">
                        <span>Nama Customer</span>
                        <input v-model="form.customer_name" type="text" placeholder="Nama pelanggan" />
                        <small v-if="form.errors.customer_name">{{ form.errors.customer_name }}</small>
                    </label>
                    <label>
                        <span>No. Telepon</span>
                        <input v-model="form.customer_phone" type="text" placeholder="08xxxxxxxxxx" />
                        <small v-if="form.errors.customer_phone">{{ form.errors.customer_phone }}</small>
                    </label>
                    <label>
                        <span>Tanggal</span>
                        <input v-model="form.reservation_date" type="date" />
                        <small v-if="form.errors.reservation_date">{{ form.errors.reservation_date }}</small>
                    </label>
                    <label>
                        <span>Jam</span>
                        <input v-model="form.reservation_time" type="time" />
                        <small v-if="form.errors.reservation_time">{{ form.errors.reservation_time }}</small>
                    </label>
                    <label class="form-grid__wide">
                        <span>Catatan</span>
                        <textarea v-model="form.notes" rows="2" placeholder="Opsional"></textarea>
                        <small v-if="form.errors.notes">{{ form.errors.notes }}</small>
                    </label>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="closeModal">Batal</button>
                    <button class="primary-action" type="submit" :disabled="form.processing">
                        <span class="material-symbols-outlined fill">save</span>
                        Simpan
                    </button>
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
.map--available .map-card__status { color: #166534; }
.map--occupied .map-card__status { color: #991b1b; }
.map--reserved .map-card__status { color: #8a5a00; }
.map--maintenance .map-card__status { color: #454652; }
.content { height: calc(100vh - 64px); margin-left: 80px; margin-top: 64px; overflow-y: auto; padding: 24px; }
.page-title, .panel { width: min(100%, 1360px); margin-inline: auto; }
.page-title { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.page-title h1 { margin: 0; font-size: 32px; font-weight: 700; }
.page-title p { margin: 3px 0 0; color: #454652; }
.primary-action, .secondary-action { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; padding: 0 14px; font-weight: 800; }
.primary-action { background: #1b6d24; color: #fff; }
.secondary-action { border: 1px solid #c6c5d4; background: #fff; color: #000666; }
.icon-button { display: inline-grid; width: 40px; height: 40px; place-items: center; border-radius: 999px; background: transparent; color: #454652; text-decoration: none; }
.icon-button:hover { background: #edeeef; }
.icon-button--danger { color: #ba1a1a; }
.panel { border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; overflow: hidden; }
.table-wrap { overflow-x: auto; }
table { width: 100%; min-width: 700px; border-collapse: collapse; }
th, td { border-bottom: 1px solid #edeeef; padding: 12px 14px; text-align: left; vertical-align: middle; }
th { color: #454652; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
td { color: #191c1d; }
.row-actions { text-align: right; white-space: nowrap; display: flex; justify-content: flex-end; gap: 4px; }
.badge { display: inline-flex; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 800; }
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
.modal__actions { justify-content: flex-end; gap: 10px; padding: 18px; }

@media (max-width: 720px) {
    :global(body) { overflow: auto; }
    .content { height: auto; margin-left: 0; padding: 18px 14px 92px; }
    .brand { font-size: 24px; }
    .page-title, .modal__header { align-items: stretch; flex-direction: column; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
