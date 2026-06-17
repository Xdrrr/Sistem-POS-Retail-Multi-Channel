<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    history: { type: Array, default: () => [] },
    pagination: { type: Object, default: () => ({ total: 0, per_page: 20, current_page: 1, last_page: 1 }) },
    cabangs: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    serverTime: { type: String, default: '' },
});



const formatNumber = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
}).format(Number(value ?? 0));

const formatDate = (value) => {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const typeLabel = (type) => {
    const map = { in: 'Stok Masuk', out: 'Stok Keluar', adjustment: 'Penyesuaian' };
    return map[type] ?? type;
};

const typeIcon = (type) => {
    const map = { in: 'add_circle', out: 'remove_circle', adjustment: 'tune' };
    return map[type] ?? 'info';
};

const typeClass = (type) => {
    const map = { in: 'type--in', out: 'type--out', adjustment: 'type--adjustment' };
    return map[type] ?? '';
};

const qtyDisplay = (item) => {
    const prefix = item.type === 'in' ? '+' : item.type === 'out' ? '-' : '±';
    return `${prefix} ${formatNumber(item.qty)}`;
};

const refLabel = (type) => {
    const map = { order: 'Pesanan', manual_adjustment: 'Manual' };
    return map[type] ?? type ?? '-';
};

const summaryCards = computed(() => [
    { label: 'Total Mutasi', value: props.pagination.total, icon: 'swap_vert' },
    { label: 'Stok Masuk', value: props.history.filter((h) => h.type === 'in').length, icon: 'add_circle' },
    { label: 'Stok Keluar', value: props.history.filter((h) => h.type === 'out').length, icon: 'remove_circle' },
    { label: 'Penyesuaian', value: props.history.filter((h) => h.type === 'adjustment').length, icon: 'tune' },
]);

const applyFilters = () => {
    const params = new URLSearchParams();
    Object.entries(localFilters).forEach(([key, value]) => {
        if (value) params.set(`filter[${key}]`, value);
    });
    params.set('limit', localFilters.limit || 20);
    params.set('sort', localFilters.sort || 'DESC');
    router.get('/inventory/history', Object.fromEntries(params), { preserveState: true, replace: true });
};

const localFilters = reactive({
    search: props.filters.search || '',
    guid_cabang: props.filters.guid_cabang || '',
    product_guid: props.filters.product_guid || '',
    type: props.filters.type || '',
    reference_type: props.filters.reference_type || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || '',
    limit: props.filters.limit || 20,
    sort: props.filters.sort || 'DESC',
});

const resetFilters = () => {
    localFilters.search = '';
    localFilters.guid_cabang = '';
    localFilters.product_guid = '';
    localFilters.type = '';
    localFilters.reference_type = '';
    localFilters.from_date = '';
    localFilters.to_date = '';
    localFilters.limit = 20;
    localFilters.sort = 'DESC';
    applyFilters();
};

const changePage = (page) => {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    router.get('/inventory/history', Object.fromEntries(params), { preserveState: true, replace: true });
};

const adjustModal = ref(false);
const adjustForm = useForm({
    inventory_guid: '',
    type: 'in',
    qty: 1,
    notes: '',
});

const openAdjust = (inventoryGuid = '') => {
    adjustForm.reset();
    adjustForm.inventory_guid = inventoryGuid;
    adjustModal.value = true;
};

const closeAdjust = () => {
    adjustModal.value = false;
    adjustForm.reset();
};

const submitAdjust = () => {
    adjustForm.post('/inventory/items/adjust', {
        preserveScroll: true,
        onSuccess: closeAdjust,
    });
};
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <Link class="back-link" href="/inventory">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Inventory
                </Link>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Riwayat Stok</h1>
                    <p>Seluruh mutasi stok masuk, keluar, dan penyesuaian.</p>
                </div>
                <div class="page-actions">
                    <Link class="primary-action" href="/inventory">
                        <span class="material-symbols-outlined">add_circle</span>
                        Adjust Stok
                    </Link>
                </div>
            </section>

            <section class="summary-grid">
                <article class="summary-card summary-card--hero">
                    <span class="material-symbols-outlined fill">swap_vert</span>
                    <div>
                        <small>Total Mutasi</small>
                        <strong>{{ pagination.total }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">add_circle</span>
                    <div>
                        <small>Stok Masuk</small>
                        <strong>{{ history.filter((h) => h.type === 'in').length }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">remove_circle</span>
                    <div>
                        <small>Stok Keluar</small>
                        <strong>{{ history.filter((h) => h.type === 'out').length }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">tune</span>
                    <div>
                        <small>Penyesuaian</small>
                        <strong>{{ history.filter((h) => h.type === 'adjustment').length }}</strong>
                    </div>
                </article>
            </section>

            <section class="history-layout">
                <aside class="filter-panel">
                    <div class="filter-panel__head">
                        <strong>Filter</strong>
                    </div>

                    <label>
                        <span>Cari Produk</span>
                        <input v-model="localFilters.search" type="text" placeholder="Nama produk..." @keyup.enter="applyFilters" />
                    </label>

                    <label>
                        <span>Cabang</span>
                        <select v-model="localFilters.guid_cabang" @change="applyFilters">
                            <option value="">Semua</option>
                            <option v-for="c in cabangs" :key="c.guid" :value="c.guid">{{ c.kode }} - {{ c.nama }}</option>
                        </select>
                    </label>

                    <label>
                        <span>Produk</span>
                        <select v-model="localFilters.product_guid" @change="applyFilters">
                            <option value="">Semua</option>
                            <option v-for="p in products" :key="p.guid" :value="p.guid">{{ p.name }}</option>
                        </select>
                    </label>

                    <label>
                        <span>Tipe Mutasi</span>
                        <select v-model="localFilters.type" @change="applyFilters">
                            <option value="">Semua</option>
                            <option value="in">Stok Masuk</option>
                            <option value="out">Stok Keluar</option>
                            <option value="adjustment">Penyesuaian</option>
                        </select>
                    </label>

                    <label>
                        <span>Referensi</span>
                        <select v-model="localFilters.reference_type" @change="applyFilters">
                            <option value="">Semua</option>
                            <option value="order">Pesanan</option>
                            <option value="manual_adjustment">Manual</option>
                        </select>
                    </label>

                    <label>
                        <span>Dari Tanggal</span>
                        <input v-model="localFilters.from_date" type="date" @change="applyFilters" />
                    </label>

                    <label>
                        <span>Sampai Tanggal</span>
                        <input v-model="localFilters.to_date" type="date" @change="applyFilters" />
                    </label>

                    <label>
                        <span>Per Halaman</span>
                        <input v-model.number="localFilters.limit" type="number" min="1" max="100" @change="applyFilters" />
                    </label>

                    <label>
                        <span>Urutan</span>
                        <select v-model="localFilters.sort" @change="applyFilters">
                            <option value="DESC">Terbaru</option>
                            <option value="ASC">Terlama</option>
                        </select>
                    </label>

                    <button class="secondary-action" type="button" @click="resetFilters">
                        <span class="material-symbols-outlined">restart_alt</span>
                        Reset
                    </button>
                </aside>

                <section class="history-panel">
                    <div class="history-panel__header">
                        <div>
                            <h2>Data Mutasi</h2>
                            <p>{{ pagination.total }} item</p>
                        </div>
                        <div class="panel-actions">
                            <div class="pager">
                                <button class="icon-button" type="button" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </button>
                                <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
                                <button class="icon-button" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Cabang</th>
                                    <th>Tipe</th>
                                    <th>Qty</th>
                                    <th>Stok Sebelum</th>
                                    <th>Stok Sesudah</th>
                                    <th>Referensi</th>
                                    <th>Catatan</th>
                                    <th>Oleh</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in history" :key="item.guid">
                                    <td>
                                        <Link :href="`/inventory/items/${item.inventory_guid}/history`" class="product-link">
                                            <strong>{{ item.product_name }}</strong>
                                            <span>{{ item.category_name || '-' }} / {{ item.group_name || '-' }}</span>
                                        </Link>
                                    </td>
                                    <td>{{ item.cabang_kode || '-' }}</td>
                                    <td>
                                        <span class="type-badge" :class="typeClass(item.type)">
                                            <span class="material-symbols-outlined">{{ typeIcon(item.type) }}</span>
                                            {{ typeLabel(item.type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong :class="typeClass(item.type)">{{ qtyDisplay(item) }}</strong>
                                    </td>
                                    <td>{{ formatNumber(item.stock_before) }}</td>
                                    <td>{{ formatNumber(item.stock_after) }}</td>
                                    <td>
                                        <span v-if="item.reference_type" class="ref-badge">{{ refLabel(item.reference_type) }}</span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td><span class="text-muted">{{ item.notes || '-' }}</span></td>
                                    <td>{{ item.created_by }}</td>
                                    <td class="text-nowrap">{{ formatDate(item.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="history.length === 0" class="empty-state">
                            Belum ada riwayat mutasi stok.
                        </div>
                    </div>
                </section>
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

button,
a {
    font: inherit;
}

button {
    border: 0;
    cursor: pointer;
}

.dashboard-shell {
    min-height: 100vh;
    background: #f8f9fa;
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.brand {
    color: #1a237e;
    font-size: 32px;
    font-weight: 800;
    line-height: 40px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 40px;
    border: 1px solid #d6d7de;
    border-radius: 8px;
    padding: 0 12px;
    color: #191c1d;
    font-weight: 800;
    text-decoration: none;
}

.content {
    height: calc(100vh - 64px);
    margin-left: 80px;
    margin-top: 64px;
    overflow-y: auto;
    padding: 24px;
}

.page-title,
.summary-grid,
.history-layout {
    width: min(100%, 1920px);
    margin-inline: auto;
}

.page-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.page-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.page-title h1,
.history-panel h2 {
    margin: 0;
    color: #191c1d;
}

.page-title h1 {
    font-size: 32px;
    font-weight: 700;
    line-height: 40px;
}

.page-title p {
    margin: 3px 0 0;
    color: #454652;
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
}

.primary-action {
    background: #1b6d24;
    color: #ffffff;
}

.secondary-action {
    border: 1px solid #c6c5d4;
    background: #ffffff;
    color: #000666;
}

.icon-button {
    display: inline-grid;
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

.icon-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.summary-grid {
    display: grid;
    grid-template-columns: 1.4fr repeat(3, minmax(160px, 0.7fr));
    gap: 12px;
    margin-bottom: 16px;
}

.summary-card {
    display: flex;
    align-items: center;
    min-height: 112px;
    gap: 14px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    padding: 18px;
}

.summary-card > span {
    display: grid;
    width: 48px;
    height: 48px;
    place-items: center;
    border-radius: 8px;
    background: #eef2ff;
    color: #1a237e;
}

.summary-card--hero {
    border-color: transparent;
    background: #000666;
    color: #ffffff;
}

.summary-card--hero > span {
    background: #a0f399;
    color: #217128;
}

.summary-card small {
    display: block;
    color: #454652;
    font-weight: 700;
}

.summary-card--hero small {
    color: #bdc2ff;
}

.summary-card strong {
    display: block;
    margin-top: 2px;
    font-size: 30px;
}

.history-layout {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.filter-panel,
.history-panel {
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
}

.filter-panel {
    display: grid;
    gap: 12px;
    padding: 14px;
}

.filter-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.filter-panel label > span {
    display: block;
    margin-bottom: 6px;
    color: #454652;
    font-size: 12px;
    font-weight: 800;
}

.filter-panel input,
.filter-panel select {
    width: 100%;
    height: 42px;
    padding: 0 12px;
    font: inherit;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    color: #191c1d;
    outline: 0;
}

.filter-panel .secondary-action {
    width: 100%;
}

.history-panel {
    overflow: hidden;
}

.history-panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #c6c5d4;
    padding: 14px;
}

.history-panel__header p {
    margin: 3px 0 0;
    color: #454652;
}

.panel-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

.pager {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #5c5f66;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

th,
td {
    border-bottom: 1px solid #edeeef;
    padding: 14px;
    text-align: left;
    vertical-align: middle;
}

th {
    color: #454652;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

td {
    color: #191c1d;
}

td strong {
    display: block;
}

td > span {
    margin-top: 3px;
    color: #454652;
    font-size: 12px;
}

.product-link {
    color: #191c1d;
    text-decoration: none;
}

.product-link:hover strong {
    color: #1a237e;
}

.product-link span {
    display: block;
    margin-top: 3px;
    color: #454652;
    font-size: 12px;
}

.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.type-badge .material-symbols-outlined {
    font-size: 16px;
}

.type--in {
    background: #dcfce7;
    color: #166534;
}

.type--out {
    background: #fee2e2;
    color: #991b1b;
}

.type--adjustment {
    background: #fff7d6;
    color: #8a5a00;
}

.ref-badge {
    display: inline-flex;
    border-radius: 999px;
    background: #e7e8e9;
    color: #454652;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 800;
}

.text-muted {
    color: #5d6268;
    font-size: 12px;
}

.text-nowrap {
    white-space: nowrap;
}

.empty-state {
    padding: 32px;
    color: #5d6268;
    text-align: center;
}

@media (max-width: 1000px) {
    .summary-grid,
    .history-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    :global(body) {
        overflow: auto;
    }

    .content {
        height: auto;
        min-height: calc(100vh - 64px);
        margin-left: 0;
        padding: 18px 14px 92px;
    }

    .brand {
        font-size: 24px;
    }

    .page-title,
    .history-panel__header {
        align-items: stretch;
        flex-direction: column;
    }

    .page-actions {
        align-self: flex-start;
    }

    .panel-actions {
        justify-content: space-between;
    }
}
</style>
