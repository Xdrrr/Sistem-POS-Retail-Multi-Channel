<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    reportTypes: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    serverTime: { type: String, default: () => '' },
    appTimezone: { type: String, default: 'UTC' },
    serverDatetime: { type: String, default: () => '' },
});

const initialType = new URLSearchParams(window.location.search).get('type');
const activeType = ref(initialType && props.reportTypes.find((r) => r.key === initialType) ? initialType : props.reportTypes[0]?.key ?? 'sales');
const rows = ref([]);
const columns = ref([]);
const summary = ref({});
const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const loading = ref(false);
const exporting = ref(false);
const error = ref('');

const serverDate = props.serverDatetime ? props.serverDatetime.slice(0, 10) : new Date().toISOString().slice(0, 10);

const filters = reactive({
    from_datetime: serverDate + 'T00:00',
    to_datetime: props.serverDatetime || new Date().toISOString().slice(0, 16),
    statuses: [],
    order_types: [],
    payment_statuses: [],
    methods: [],
    category_guids: [],
    group_guids: [],
    customer_search: '',
    customer_phone: '',
    product_search: '',
    min_transactions: '',
    min_total_spent: '',
    is_active: '',
    limit: 20,
    page: 1,
    order: '',
    sort: 'DESC',
});

const statusOptions = ['draft', 'open', 'completed', 'cancelled'];
const orderTypeOptions = ['dine_in', 'takeaway', 'delivery'];
const paymentStatusOptions = ['unpaid', 'partial', 'paid', 'refunded'];
const methodOptions = ['cash', 'debit_card', 'credit_card', 'qris', 'transfer', 'e_wallet'];
const activeReport = computed(() => props.reportTypes.find((report) => report.key === activeType.value) ?? props.reportTypes[0]);
const currentStatusOptions = computed(() => (activeType.value === 'payments' ? ['pending', 'paid', 'failed', 'refunded'] : statusOptions));
const hasDateFilter = computed(() => activeType.value !== 'catalog');
const hasStatusFilter = computed(() => ['sales', 'products', 'financial', 'status'].includes(activeType.value));
const hasPaymentStatusFilter = computed(() => ['sales', 'financial', 'status'].includes(activeType.value));
const hasOrderTypeFilter = computed(() => activeType.value === 'sales');
const hasMethodFilter = computed(() => activeType.value === 'payments');
const hasCatalogFilter = computed(() => ['products', 'catalog'].includes(activeType.value));
const hasCustomerFilter = computed(() => ['sales', 'customers'].includes(activeType.value));
const hasProductSearch = computed(() => ['products', 'catalog'].includes(activeType.value));
const hasCustomerAdvanced = computed(() => activeType.value === 'customers');
const hasActiveFilter = computed(() => activeType.value === 'catalog');

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const formatValue = (key, value) => {
    if (value === null || value === undefined || value === '') return '-';
    if (['subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'amount', 'price', 'total_spent', 'paid_amount', 'cash_amount', 'digital_amount', 'average_price'].includes(key)) {
        return formatCurrency(value);
    }
    if (typeof value === 'boolean') return value ? 'active' : 'inactive';

    return value;
};

const buildFilter = () => {
    const filter = {};
    const put = (key, value) => {
        const active = Array.isArray(value) ? value.length > 0 : value !== '' && value !== null && value !== undefined;
        filter[`set_${key}`] = active;
        filter[key] = value;
    };

    if (hasDateFilter.value) {
        put('from_date', filters.from_datetime || '');
        put('to_date', filters.to_datetime || '');
    }

    if (hasStatusFilter.value) put('statuses', filters.statuses);
    if (hasPaymentStatusFilter.value) put('payment_statuses', filters.payment_statuses);
    if (hasOrderTypeFilter.value) put('order_types', filters.order_types);
    if (hasMethodFilter.value) {
        put('methods', filters.methods);
        put('statuses', filters.statuses);
    }
    if (hasCatalogFilter.value) {
        put('category_guids', filters.category_guids);
        put('group_guids', filters.group_guids);
    }
    if (hasCustomerFilter.value) put('customer_search', filters.customer_search);
    if (hasProductSearch.value) put('product_search', filters.product_search);
    if (hasCustomerAdvanced.value) {
        put('customer_phone', filters.customer_phone);
        put('min_transactions', filters.min_transactions);
        put('min_total_spent', filters.min_total_spent);
    }
    if (hasActiveFilter.value) put('is_active', filters.is_active);

    return filter;
};

const postReport = async (action, extra = {}) => {
    const response = await fetch(`/reports/${activeType.value}/${action}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            filter: buildFilter(),
            limit: filters.limit,
            page: filters.page,
            order: filters.order,
            sort: filters.sort,
            ...extra,
        }),
    });

    const payload = await response.json();
    if (!response.ok || payload.response?.status === 'failed') {
        throw new Error(payload.response?.message_id ?? 'Request gagal.');
    }

    return payload.response.data;
};

const loadReport = async () => {
    loading.value = true;
    error.value = '';

    try {
        const [preview, summaryData] = await Promise.all([
            postReport('preview'),
            postReport('summary'),
        ]);

        columns.value = preview.columns ?? [];
        rows.value = preview.data ?? [];
        meta.value = preview.meta ?? meta.value;
        summary.value = summaryData ?? {};
    } catch (exception) {
        error.value = exception.message;
    } finally {
        loading.value = false;
    }
};

const exportReport = async () => {
    exporting.value = true;
    error.value = '';

    try {
        const data = await postReport('export');
        window.location.href = `/reports/exports?export=${data.guid}`;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        exporting.value = false;
    }
};

const resetFilters = () => {
    const sd = props.serverDatetime ? props.serverDatetime.slice(0, 10) : new Date().toISOString().slice(0, 10);
    filters.from_datetime = sd + 'T00:00';
    filters.to_datetime = props.serverDatetime || new Date().toISOString().slice(0, 16);
    filters.statuses = [];
    filters.order_types = [];
    filters.payment_statuses = [];
    filters.methods = [];
    filters.category_guids = [];
    filters.group_guids = [];
    filters.customer_search = '';
    filters.customer_phone = '';
    filters.product_search = '';
    filters.min_transactions = '';
    filters.min_total_spent = '';
    filters.is_active = '';
    filters.limit = 20;
    filters.page = 1;
    filters.order = '';
    filters.sort = 'DESC';
    filters.page = 1;
};

const changeType = (type) => {
    activeType.value = type;
    filters.page = 1;
    filters.order = '';
};

const changePage = (page) => {
    filters.page = page;
};

let filterTimeout = null;
watch(filters, () => {
    filters.page = 1;
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(loadReport, 300);
}, { deep: true });

onMounted(loadReport);
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input v-model="filters.customer_search" type="text" placeholder="Search report..." />
                </label>
            </template>

            <template #actions>
                <Link class="icon-button" href="/" aria-label="Home">
                    <span class="material-symbols-outlined">dashboard</span>
                </Link>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Reports</h1>
                    <p>{{ activeReport?.title ?? 'Laporan' }}</p>
                </div>
                <button class="primary-action" type="button" :disabled="exporting" @click="exportReport">
                    <span class="material-symbols-outlined">download</span>
                    {{ exporting ? 'Queueing...' : 'Export CSV' }}
                </button>
            </section>

            <section class="report-tabs" aria-label="Report type">
                <button
                    v-for="report in reportTypes"
                    :key="report.key"
                    type="button"
                    :class="{ active: report.key === activeType }"
                    @click="changeType(report.key)"
                >
                    {{ report.title }}
                </button>
            </section>

            <section class="report-layout">
                <aside class="filter-panel">
                    <div class="filter-panel__head">
                        <strong>Filter</strong>
                    </div>

                    <div v-if="hasDateFilter">
                        <div>
                            <label>
                                <span>From</span>
                                <input v-model="filters.from_datetime" type="datetime-local" />
                            </label>
                        </div>

                        <div style="margin-top: 10px;">
                            <label>
                                <span>To</span>
                                <input v-model="filters.to_datetime" type="datetime-local" />
                            </label>
                        </div>
                    </div>

                    <label v-if="hasStatusFilter || hasMethodFilter">
                        <span>Status</span>
                        <select v-model="filters.statuses" multiple>
                            <option v-for="status in currentStatusOptions" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </label>

                    <label v-if="hasPaymentStatusFilter">
                        <span>Payment Status</span>
                        <select v-model="filters.payment_statuses" multiple>
                            <option v-for="status in paymentStatusOptions" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </label>

                    <label v-if="hasOrderTypeFilter">
                        <span>Order Type</span>
                        <select v-model="filters.order_types" multiple>
                            <option v-for="type in orderTypeOptions" :key="type" :value="type">{{ type }}</option>
                        </select>
                    </label>

                    <label v-if="hasMethodFilter">
                        <span>Method</span>
                        <select v-model="filters.methods" multiple>
                            <option v-for="method in methodOptions" :key="method" :value="method">{{ method }}</option>
                        </select>
                    </label>

                    <label v-if="hasCatalogFilter">
                        <span>Category</span>
                        <select v-model="filters.category_guids" multiple>
                            <option v-for="category in categories" :key="category.guid" :value="category.guid">{{ category.name }}</option>
                        </select>
                    </label>

                    <label v-if="hasCatalogFilter">
                        <span>Group</span>
                        <select v-model="filters.group_guids" multiple>
                            <option v-for="group in groups" :key="group.guid" :value="group.guid">{{ group.name }}</option>
                        </select>
                    </label>

                    <label v-if="hasProductSearch">
                        <span>Product</span>
                        <input v-model="filters.product_search" type="text" />
                    </label>

                    <label v-if="hasCustomerFilter">
                        <span>Customer</span>
                        <input v-model="filters.customer_search" type="text" />
                    </label>

                    <label v-if="hasCustomerAdvanced">
                        <span>Phone</span>
                        <input v-model="filters.customer_phone" type="text" />
                    </label>

                    <div v-if="hasCustomerAdvanced" class="field-grid">
                        <label>
                            <span>Min Trx</span>
                            <input v-model="filters.min_transactions" type="number" min="0" />
                        </label>
                        <label>
                            <span>Min Spend</span>
                            <input v-model="filters.min_total_spent" type="number" min="0" />
                        </label>
                    </div>

                    <label v-if="hasActiveFilter">
                        <span>Status Product</span>
                        <select v-model="filters.is_active">
                            <option value="">All</option>
                            <option value="true">Active</option>
                            <option value="false">Inactive</option>
                        </select>
                    </label>

                    <div class="field-grid">
                        <label>
                            <span>Pagination</span>
                            <input v-model.number="filters.limit" type="number" min="1" max="100" />
                        </label>
                        <label>
                            <span>Sort</span>
                            <select v-model="filters.sort">
                                <option value="DESC">Terlama</option>
                                <option value="ASC">Terbaru</option>
                            </select>
                        </label>
                    </div>

                    <button class="secondary-action" type="button" @click="resetFilters" style="width: 100%; margin-top: 8px;">
                        <span class="material-symbols-outlined">restart_alt</span>
                        Reset Filter
                    </button>

                </aside>

                <section class="report-main">
                    <div class="summary-row">
                        <article v-for="(value, key) in summary" :key="key" class="summary-tile">
                            <span>{{ key.replaceAll('_', ' ') }}</span>
                            <strong>{{ formatValue(key, value) }}</strong>
                        </article>
                    </div>

                    <div v-if="error" class="error-banner">{{ error }}</div>

                    <section class="table-panel">
                        <div class="table-panel__head">
                            <strong>{{ meta.total }} rows</strong>
                            <div class="pager">
                                <button class="icon-button" type="button" :disabled="meta.current_page <= 1 || loading" @click="changePage(meta.current_page - 1)">
                                    <span class="material-symbols-outlined">chevron_left</span>
                                </button>
                                <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
                                <button class="icon-button" type="button" :disabled="meta.current_page >= meta.last_page || loading" @click="changePage(meta.current_page + 1)">
                                    <span class="material-symbols-outlined">chevron_right</span>
                                </button>
                            </div>
                        </div>

                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th v-for="column in columns" :key="column">{{ column.replaceAll('_', ' ') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loading">
                                        <td :colspan="Math.max(columns.length, 1)">Loading...</td>
                                    </tr>
                                    <tr v-else-if="rows.length === 0">
                                        <td :colspan="Math.max(columns.length, 1)">No data</td>
                                    </tr>
                                    <tr v-for="(row, index) in rows" v-else :key="index">
                                        <td v-for="column in columns" :key="column">{{ formatValue(column, row[column]) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                </section>
            </section>
        </main>
    </div>
</template>

<style scoped>
.dashboard-shell {
    min-height: 100vh;
    background: #f6f7f8;
    color: #191c1d;
}

.content {
    margin-left: 80px;
    height: calc(100vh);
    padding: 104px 28px 40px;
    overflow-y: auto;
}

.content::-webkit-scrollbar {
    width: 6px;
}

.content::-webkit-scrollbar-track {
    background: transparent;
}

.content::-webkit-scrollbar-thumb {
    background: #c6c5d4;
    border-radius: 3px;
}

.content::-webkit-scrollbar-thumb:hover {
    background: #a0a1a8;
}

/* Firefox scrollbar */
.content {
    scrollbar-width: thin;
    scrollbar-color: #c6c5d4 transparent;
}

.page-title,
.report-layout,
.summary-row,
.table-panel__head,
.export-row,
.field-grid {
    display: flex;
}

.page-title {
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}

.page-title h1 {
    margin: 0;
    font-size: 28px;
}

.page-title p {
    margin: 6px 0 0;
    color: #5c5f66;
}

.brand {
    color: #1a237e;
    font-size: 32px;
    font-weight: 800;
    line-height: 40px;
}

.primary-action,
.icon-button,
.report-tabs button {
    border: 0;
    font: inherit;
    cursor: pointer;
}

.primary-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    border-radius: 8px;
    background: #1a237e;
    color: #fff;
    padding: 0 18px;
    font-weight: 800;
}

.secondary-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    border-radius: 8px;
    border: 1px solid #c6c5d4;
    background: #fff;
    color: #1a237e;
    padding: 0 18px;
    font-weight: 800;
    cursor: pointer;
    font: inherit;
}

.secondary-action:hover {
    background: #f8f9fa;
}

.primary-action:disabled,
.icon-button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.search-box {
    display: flex;
    align-items: center;
    position: relative;
    width: 264px;
}

.search-box span {
    position: absolute;
    left: 12px;
    color: #454652;
}

.search-box input {
    width: 100%;
    height: 40px;
    border: 1px solid #c6c5d4;
    border-radius: 999px;
    background: #ffffff;
    padding: 0 16px 0 40px;
    color: #191c1d;
    outline: 0;
}

.icon-button {
    display: inline-grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border-radius: 8px;
    background: #ffffff;
    color: #1f2937;
    text-decoration: none;
}

.report-tabs {
    display: flex;
    gap: 8px;
    padding-bottom: 12px;
}

.report-tabs button {
    min-height: 38px;
    white-space: nowrap;
    border-radius: 8px;
    background: #ffffff;
    color: #454652;
    padding: 0 14px;
    font-weight: 800;
}

.report-tabs button.active {
    background: #a0f399;
    color: #217128;
}

.report-layout {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    height: 100%;
}

.filter-panel,
.table-panel,
.exports-panel {
    border: 1px solid #d9dadd;
    border-radius: 8px;
    background: #ffffff;
}

.filter-panel {
    position: sticky;
    top: 96px;
    width: 300px;
    flex: 0 0 300px;
    padding: 16px;
}

.filter-panel__head,
.table-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}

.table-panel__head p {
    margin: 4px 0 0;
    color: #5c5f66;
    font-size: 12px;
}

.filter-panel label {
    display: grid;
    gap: 6px;
    margin-bottom: 12px;
}

.filter-panel .secondary-action,
.filter-panel .primary-action {
    width: 100%;
}

.filter-panel span {
    color: #5c5f66;
    font-size: 12px;
    font-weight: 800;
}

.filter-panel input,
.filter-panel select {
    width: 100%;
    min-height: 38px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #fff;
    padding: 8px 10px;
}

.filter-panel select[multiple] {
    min-height: 92px;
}

.field-grid {
    gap: 10px;
}

.field-grid label {
    flex: 1;
}

.report-main {
    min-width: 0;
    flex: 1;
    padding-bottom: 40px;
}

.summary-row {
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 14px;
}

.summary-tile {
    min-width: 170px;
    flex: 1;
    border: 1px solid #d9dadd;
    border-radius: 8px;
    background: #ffffff;
    padding: 14px;
}

.summary-tile span {
    display: block;
    color: #5c5f66;
    font-size: 12px;
    font-weight: 800;
    text-transform: capitalize;
}

.summary-tile strong {
    display: block;
    margin-top: 6px;
    font-size: 20px;
}

.error-banner {
    margin-bottom: 12px;
    border-radius: 8px;
    background: #ffe7e7;
    color: #8a1f1f;
    padding: 12px 14px;
    font-weight: 700;
}

.table-panel,
.exports-panel {
    margin-bottom: 16px;
    padding: 16px;
    padding-bottom: 24px;
}

.exports-panel--separate {
    margin-top: 20px;
}

.history-filter {
    display: grid;
    grid-template-columns: repeat(4, minmax(150px, 1fr)) auto auto;
    gap: 10px;
    align-items: end;
    margin-bottom: 14px;
}

.history-filter label {
    display: grid;
    gap: 6px;
}

.history-filter span {
    color: #5c5f66;
    font-size: 12px;
    font-weight: 800;
}

.history-filter input,
.history-filter select {
    width: 100%;
    min-height: 38px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #fff;
    padding: 8px 10px;
}

.pager {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #5c5f66;
    font-size: 13px;
    font-weight: 800;
}

.table-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
}

th,
td {
    border-bottom: 1px solid #edeeef;
    padding: 12px 10px;
    text-align: left;
    vertical-align: top;
}

th {
    color: #5c5f66;
    font-size: 12px;
    text-transform: capitalize;
}

td {
    font-size: 13px;
}

.export-list {
    display: grid;
    gap: 8px;
}

.export-row {
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid #edeeef;
    border-radius: 8px;
    padding: 10px 12px;
}

.export-row strong,
.export-row span {
    display: block;
}

.export-row span {
    color: #5c5f66;
    font-size: 12px;
}

.export-row small {
    display: block;
    margin-top: 4px;
    color: #8a1f1f;
    font-size: 12px;
}

.export-row__actions {
    display: flex;
    gap: 8px;
}

@media (max-width: 900px) {
    .content {
        margin-left: 0;
        padding: 92px 16px 96px;
    }

    .search-box {
        display: none;
    }

    .page-title,
    .report-layout {
        flex-direction: column;
    }

    .filter-panel {
        position: static;
        width: 100%;
        flex-basis: auto;
    }

    .history-filter {
        grid-template-columns: 1fr;
    }

    .primary-action {
        width: 100%;
        justify-content: center;
    }
}
</style>
