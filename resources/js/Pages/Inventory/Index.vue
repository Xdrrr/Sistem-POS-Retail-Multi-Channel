<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    inventories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    serverTime: { type: String, default: '' },
});

const filters = reactive({
    search: '',
    id_cabang: '',
    status: '',
    stock: 'all',
    limit: 20,
    sort: 'ASC',
});

const modalOpen = ref(false);
const editing = ref(null);
const adjustMode = ref(false);
const currentPage = ref(1);

const form = useForm({
    product_guid: '',
    id_cabang: 'PUSAT',
    unit: 'pcs',
    current_stock: 0,
    minimum_stock: 0,
    is_active: true,
});

const adjustForm = useForm({
    product_guid: '',
    type: 'in',
    qty: 1,
    notes: '',
});

const branches = computed(() => [...new Set(props.inventories.map((item) => item.id_cabang).filter(Boolean))].sort());

const filteredInventories = computed(() => {
    const keyword = filters.search.trim().toLowerCase();
    let items = props.inventories.filter((item) => {
        const matchesSearch = !keyword
            || item.product_name?.toLowerCase().includes(keyword)
            || item.category_name?.toLowerCase().includes(keyword)
            || item.group_name?.toLowerCase().includes(keyword);
        const matchesBranch = !filters.id_cabang || item.id_cabang === filters.id_cabang;
        const matchesStatus = filters.status === '' || String(item.is_active) === filters.status;
        const matchesStock = filters.stock === 'all'
            || (filters.stock === 'low' && item.is_low_stock)
            || (filters.stock === 'available' && Number(item.current_stock ?? 0) > Number(item.minimum_stock ?? 0));

        return matchesSearch && matchesBranch && matchesStatus && matchesStock;
    });

    items = [...items].sort((a, b) => a.product_name.localeCompare(b.product_name));
    return filters.sort === 'DESC' ? items.reverse() : items;
});

const summary = computed(() => {
    const total = props.inventories.length;
    const active = props.inventories.filter((item) => item.is_active).length;
    const lowStock = props.inventories.filter((item) => item.is_low_stock).length;
    const totalStock = props.inventories.reduce((sum, item) => sum + Number(item.current_stock ?? 0), 0);

    return { total, active, lowStock, totalStock };
});

const pageSize = computed(() => Math.max(1, Math.min(100, Number(filters.limit || 20))));
const meta = computed(() => {
    const lastPage = Math.max(1, Math.ceil(filteredInventories.value.length / pageSize.value));

    return {
        current_page: Math.min(currentPage.value, lastPage),
        last_page: lastPage,
        total: filteredInventories.value.length,
    };
});

const paginatedInventories = computed(() => {
    const start = (meta.value.current_page - 1) * pageSize.value;
    return filteredInventories.value.slice(start, start + pageSize.value);
});

watch(
    () => [filters.search, filters.id_cabang, filters.status, filters.stock, filters.limit, filters.sort],
    () => {
        currentPage.value = 1;
    },
);

const formatNumber = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
}).format(Number(value ?? 0));

const resetFilters = () => {
    filters.search = '';
    filters.id_cabang = '';
    filters.status = '';
    filters.stock = 'all';
    filters.limit = 20;
    filters.sort = 'ASC';
};

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.product_guid = '';
    form.id_cabang = 'PUSAT';
    form.unit = 'pcs';
    form.current_stock = 0;
    form.minimum_stock = 0;
    form.is_active = true;
    editing.value = null;
};

const openCreate = () => {
    resetForm();
    adjustMode.value = true;
    editing.value = null;
    adjustForm.reset();
    adjustForm.type = 'in';
    adjustForm.qty = 1;
    adjustForm.notes = '';
    modalOpen.value = true;
};

const openEdit = (item) => {
    resetForm();
    adjustMode.value = false;
    editing.value = item;
    form.product_guid = item.product_guid ?? '';
    form.id_cabang = item.id_cabang ?? 'PUSAT';
    form.unit = item.unit ?? 'pcs';
    form.minimum_stock = item.minimum_stock ?? 0;
    form.is_active = Boolean(item.is_active);
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    resetForm();
    adjustForm.reset();
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (adjustMode.value) {
        adjustForm.post('/inventory/items/adjust', options);
        return;
    }

    if (editing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/inventory/items/${editing.value.guid}`, options);
        return;
    }

    form.post('/inventory/items', options);
};

const destroyItem = (item) => {
    form.delete(`/inventory/items/${item.guid}`, { preserveScroll: true });
};

const changePage = (page) => {
    currentPage.value = Math.max(1, Math.min(meta.value.last_page, page));
};
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input v-model="filters.search" type="text" placeholder="Cari stok produk..." />
                </label>
            </template>

            <template #actions>
                <Link class="icon-button" href="/catalog" aria-label="Catalog">
                    <span class="material-symbols-outlined">inventory_2</span>
                </Link>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Inventory</h1>
                    <p>Kelola stok produk restoran per cabang dan pantau item yang mulai menipis.</p>
                </div>
                <div class="page-actions">
                    <Link class="secondary-action" href="/inventory/history">
                        <span class="material-symbols-outlined">history</span>
                        Riwayat Stok
                    </Link>
                    <button class="primary-action" type="button" @click="openCreate">
                        <span class="material-symbols-outlined fill">add_circle</span>
                        Adjust Stok
                    </button>
                </div>
            </section>

            <section class="summary-grid" aria-label="Inventory summary">
                <article class="summary-card summary-card--hero">
                    <span class="material-symbols-outlined fill">warehouse</span>
                    <div>
                        <small>Total Item</small>
                        <strong>{{ summary.total }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">task_alt</span>
                    <div>
                        <small>Active</small>
                        <strong>{{ summary.active }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">production_quantity_limits</span>
                    <div>
                        <small>Low Stock</small>
                        <strong>{{ summary.lowStock }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">pin</span>
                    <div>
                        <small>Total Qty</small>
                        <strong>{{ formatNumber(summary.totalStock) }}</strong>
                    </div>
                </article>
            </section>

            <section class="inventory-layout">
                <aside class="filter-panel">
                    <div class="filter-panel__head">
                        <strong>Filter</strong>
                    </div>

                    <label>
                        <span>Search</span>
                        <input v-model="filters.search" type="text" placeholder="Nama produk..." />
                    </label>

                    <label>
                        <span>Cabang</span>
                        <select v-model="filters.id_cabang">
                            <option value="">Semua</option>
                            <option v-for="branch in branches" :key="branch" :value="branch">{{ branch }}</option>
                        </select>
                    </label>

                    <label>
                        <span>Stock</span>
                        <select v-model="filters.stock">
                            <option value="all">Semua</option>
                            <option value="available">Available</option>
                            <option value="low">Low Stock</option>
                        </select>
                    </label>

                    <label>
                        <span>Status</span>
                        <select v-model="filters.status">
                            <option value="">Semua</option>
                            <option value="true">Active</option>
                            <option value="false">Inactive</option>
                        </select>
                    </label>

                    <label>
                        <span>Pagination</span>
                        <input v-model.number="filters.limit" type="number" min="1" max="100" />
                    </label>

                    <label>
                        <span>Sort</span>
                        <select v-model="filters.sort">
                            <option value="ASC">A-Z</option>
                            <option value="DESC">Z-A</option>
                        </select>
                    </label>

                    <button class="secondary-action" type="button" @click="resetFilters">
                        <span class="material-symbols-outlined">restart_alt</span>
                        Reset
                    </button>
                </aside>

                <section class="inventory-panel">
                    <div class="inventory-panel__header">
                        <div>
                            <h2>Daftar Stok</h2>
                            <p>{{ meta.total }} item ditampilkan</p>
                        </div>
                        <div class="panel-actions">
                            <!-- <button class="secondary-action" type="button" @click="openCreate">
                                <span class="material-symbols-outlined">add</span>
                                Baru
                            </button> -->
                            <div class="pager">
                                <button class="icon-button" type="button" :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </button>
                                <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
                                <button class="icon-button" type="button" :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Cabang</th>
                                    <th>Stock</th>
                                    <th>Minimum</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in paginatedInventories" :key="item.guid">
                                    <td>
                                        <strong>{{ item.product_name }}</strong>
                                        <span>{{ item.category_name || '-' }} / {{ item.group_name || '-' }}</span>
                                    </td>
                                    <td>{{ item.id_cabang }}</td>
                                    <td>
                                        <strong>{{ formatNumber(item.current_stock) }}</strong>
                                        <span v-if="item.is_low_stock" class="warning-text">Butuh restock</span>
                                    </td>
                                    <td>{{ formatNumber(item.minimum_stock) }}</td>
                                    <td>{{ item.unit }}</td>
                                    <td>
                                        <span class="status-badge" :class="{ 'status-badge--positive': item.is_active, 'status-badge--warning': item.is_low_stock }">
                                            {{ item.is_low_stock ? 'Low Stock' : (item.is_active ? 'Active' : 'Inactive') }}
                                        </span>
                                    </td>
                                    <td class="row-actions">
                                        <Link class="icon-button" :href="`/inventory/items/${item.guid}/history`" aria-label="History stok">
                                            <span class="material-symbols-outlined">history</span>
                                        </Link>
                                        <button class="icon-button" type="button" aria-label="Edit inventory" @click="openEdit(item)">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                        <button class="icon-button icon-button--danger" type="button" aria-label="Delete inventory" @click="destroyItem(item)">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="paginatedInventories.length === 0" class="empty-state">
                            Belum ada inventory sesuai filter.
                        </div>
                    </div>
                </section>
            </section>
        </main>

        <div v-if="modalOpen" class="modal-backdrop">
            <form class="modal" @submit.prevent="submit">
                <div class="modal__header">
                    <div>
                        <h2>{{ adjustMode ? 'Adjust Stok' : 'Edit Stok' }}</h2>
                        <p>{{ adjustMode ? 'Tambah atau kurangi stok barang.' : 'Ubah data inventory selain stok.' }}</p>
                    </div>
                    <button class="icon-button" type="button" aria-label="Close modal" @click="closeModal">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <template v-if="adjustMode">
                    <div class="form-grid">
                        <label class="form-grid__wide">
                            <span>Product</span>
                            <select v-model="adjustForm.product_guid">
                                <option value="">Pilih produk</option>
                                <option v-for="item in inventories" :key="item.guid" :value="item.product_guid">
                                    {{ item.product_name }} (stok: {{ formatNumber(item.current_stock) }})
                                </option>
                            </select>
                            <small v-if="adjustForm.errors.product_guid">{{ adjustForm.errors.product_guid }}</small>
                        </label>

                        <label>
                            <span>Tipe</span>
                            <select v-model="adjustForm.type">
                                <option value="in">Tambah Stok (+)</option>
                                <option value="out">Kurangi Stok (-)</option>
                            </select>
                        </label>

                        <label>
                            <span>Jumlah</span>
                            <input v-model.number="adjustForm.qty" type="number" min="0.01" step="0.01" />
                            <small v-if="adjustForm.errors.qty">{{ adjustForm.errors.qty }}</small>
                        </label>

                        <label class="form-grid__wide">
                            <span>Catatan</span>
                            <textarea v-model="adjustForm.notes" rows="2" placeholder="Opsional"></textarea>
                            <small v-if="adjustForm.errors.notes">{{ adjustForm.errors.notes }}</small>
                        </label>
                    </div>

                    <div class="modal__actions">
                        <button class="secondary-action" type="button" @click="closeModal">Batal</button>
                        <button class="primary-action" type="submit" :disabled="adjustForm.processing">
                            <span class="material-symbols-outlined fill">save</span>
                            Simpan
                        </button>
                    </div>
                </template>

                <template v-else>
                    <div class="form-grid">
                        <label class="form-grid__wide">
                            <span>Product</span>
                            <select v-model="form.product_guid">
                                <option value="">Pilih produk</option>
                                <option v-for="product in products" :key="product.guid" :value="product.guid">
                                    {{ product.name }}
                                </option>
                            </select>
                            <small v-if="form.errors.product_guid">{{ form.errors.product_guid }}</small>
                        </label>

                        <label>
                            <span>Cabang</span>
                            <input v-model="form.id_cabang" type="text" placeholder="PUSAT" />
                            <small v-if="form.errors.id_cabang">{{ form.errors.id_cabang }}</small>
                        </label>

                        <label>
                            <span>Unit</span>
                            <input v-model="form.unit" type="text" placeholder="pcs" />
                            <small v-if="form.errors.unit">{{ form.errors.unit }}</small>
                        </label>

                        <label>
                            <span>Current Stock</span>
                            <div class="stock-readonly">
                                <strong>{{ formatNumber(editing?.current_stock ?? 0) }}</strong>
                                <span class="text-muted">Gunakan Adjust Stok untuk ubah stok</span>
                            </div>
                        </label>

                        <label>
                            <span>Minimum Stock Alert</span>
                            <input v-model="form.minimum_stock" type="number" min="0" step="0.01" />
                            <small v-if="form.errors.minimum_stock">{{ form.errors.minimum_stock }}</small>
                        </label>
                    </div>

                    <label class="toggle-row">
                        <input v-model="form.is_active" type="checkbox" />
                        <span>Active</span>
                    </label>

                    <div class="modal__actions">
                        <button class="secondary-action" type="button" @click="closeModal">Batal</button>
                        <button class="primary-action" type="submit" :disabled="form.processing">
                            <span class="material-symbols-outlined fill">save</span>
                            Simpan
                        </button>
                    </div>
                </template>
            </form>
        </div>
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

.search-box {
    position: relative;
    display: flex;
    align-items: center;
    width: 280px;
}

.search-box span {
    position: absolute;
    left: 12px;
    color: #454652;
}

.search-box input,
input,
select,
textarea {
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    color: #191c1d;
    outline: 0;
}

textarea {
    resize: vertical;
    padding: 10px 12px;
    font: inherit;
}

.search-box input {
    width: 100%;
    height: 40px;
    border-radius: 999px;
    padding: 0 16px 0 40px;
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
.inventory-layout {
    width: min(100%, 1920px);
    margin-inline: auto;
}

.page-title,
.summary-card,
.inventory-panel__header,
.panel-actions,
.row-actions,
.modal__header,
.modal__actions,
.toggle-row {
    display: flex;
    align-items: center;
}

.page-title {
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
.inventory-panel h2 {
    margin: 0;
    color: #191c1d;
}

.page-title h1 {
    font-size: 32px;
    font-weight: 700;
    line-height: 40px;
}

.page-title p,
.inventory-panel p {
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

.icon-button--danger {
    color: #ba1a1a;
}

.summary-grid {
    display: grid;
    grid-template-columns: 1.4fr repeat(3, minmax(160px, 0.7fr));
    gap: 12px;
    margin-bottom: 16px;
}

.summary-card {
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

.inventory-layout {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.filter-panel,
.inventory-panel {
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

.filter-panel label > span,
.form-grid label > span {
    display: block;
    margin-bottom: 6px;
    color: #454652;
    font-size: 12px;
    font-weight: 800;
}

.filter-panel input,
.filter-panel select,
.form-grid input,
.form-grid select {
    width: 100%;
    height: 42px;
    padding: 0 12px;
    font: inherit;
}

.form-grid textarea {
    width: 100%;
    padding: 10px 12px;
    font: inherit;
}

.form-grid textarea {
    padding: 10px 12px;
}

.filter-panel .secondary-action {
    width: 100%;
}

.inventory-panel {
    overflow: hidden;
}

.inventory-panel__header {
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #c6c5d4;
    padding: 14px;
}

.panel-actions {
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
    min-width: 860px;
    border-collapse: collapse;
}

th,
td {
    border-bottom: 1px solid #edeeef;
    padding: 14px;
    text-align: left;
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
    vertical-align: middle;
}

td strong,
td span {
    display: block;
}

td > span {
    margin-top: 3px;
    color: #454652;
    font-size: 12px;
}

.warning-text {
    color: #8a5a00;
    font-weight: 800;
}

.status-badge {
    display: inline-flex;
    width: max-content;
    border-radius: 999px;
    background: #e7e8e9;
    color: #454652;
    padding: 5px 8px;
    font-size: 12px;
    font-weight: 800;
}

.status-badge--positive {
    background: #a0f399;
    color: #217128;
}

.status-badge--warning {
    background: #fff7d6;
    color: #8a5a00;
}

td.row-actions {
    text-align: right;
    white-space: nowrap;
}

.row-actions {
    justify-content: flex-end;
    gap: 4px;
}

.empty-state {
    padding: 32px;
    color: #5d6268;
    text-align: center;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: grid;
    place-items: center;
    background: rgb(0 0 0 / 36%);
    padding: 18px;
}

.modal {
    width: min(100%, 620px);
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 18px 48px rgb(15 23 42 / 20%);
}

.modal__header {
    justify-content: space-between;
    gap: 14px;
    border-bottom: 1px solid #c6c5d4;
    padding: 18px;
}

.modal h2 {
    margin: 0;
    color: #000666;
    font-size: 22px;
}

.modal p {
    margin: 4px 0 0;
    color: #454652;
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

small {
    display: block;
    margin-top: 6px;
    color: #ba1a1a;
    font-weight: 700;
}

.toggle-row {
    gap: 8px;
    margin: 0 18px;
    color: #454652;
    font-weight: 800;
}

.stock-readonly {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-height: 42px;
    justify-content: center;
}

.stock-readonly strong {
    font-size: 20px;
    color: #1a237e;
}

.stock-readonly .text-muted {
    font-size: 11px;
    color: #5d6268;
    font-weight: 400;
}

.toggle-row input {
    width: 16px;
    height: 16px;
}

.modal__actions {
    justify-content: flex-end;
    gap: 10px;
    padding: 18px;
}

@media (max-width: 1000px) {
    .summary-grid,
    .inventory-layout {
        grid-template-columns: 1fr;
    }

    .search-box {
        display: none;
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
    .inventory-panel__header,
    .modal__header,
    .modal__actions {
        align-items: stretch;
        flex-direction: column;
    }

    .page-actions {
        align-self: flex-start;
    }

    .panel-actions {
        justify-content: space-between;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
