<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    reportTypes: { type: Array, default: () => [] },
    cabangs: { type: Array, default: () => [] },
    serverTime: { type: String, default: () => '' },
});

const typeLabelMap = computed(() => {
    const map = {};
    props.reportTypes.forEach((r) => { map[r.key] = r.title; });
    return map;
});
const typeLabel = (key) => typeLabelMap.value[key] || key;
const fmtDate = (d) => {
    if (!d) return '';
    const date = new Date(d);
    if (isNaN(date)) return d;
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${y}-${m}-${day} ${h}:${min}`;
};
const dateRange = (filters) => {
    if (!filters) return '';
    const from = filters.set_from_date && filters.from_date ? fmtDate(filters.from_date) : '';
    const to = filters.set_to_date && filters.to_date ? fmtDate(filters.to_date) : '';
    if (!from && !to) return '';
    return ` ${from || 'start'} to ${to || 'end'}`;
};

const exportsList = ref([]);
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const loading = ref(false);
const error = ref('');
const selectedGuid = ref(new URLSearchParams(window.location.search).get('export') ?? '');

const filters = reactive({
    type: '',
    status: '',
    from_datetime: '',
    to_datetime: '',
    limit: 10,
    page: 1,
});

const exportStatusOptions = ['queued', 'processing', 'done', 'failed'];

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const buildFilter = () => {
    const filter = {};
    const put = (key, value) => {
        const active = value !== '' && value !== null && value !== undefined;
        filter[`set_${key}`] = active;
        filter[key] = value;
    };

    put('type', filters.type);
    put('status', filters.status);
    put('from_date', filters.from_datetime);
    put('to_date', filters.to_datetime);

    return filter;
};

const loadHistory = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await fetch('/reports/exports/history', {
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
            }),
        });

        const payload = await response.json();
        if (!response.ok || payload.response?.status === 'failed') {
            throw new Error(payload.response?.message_id ?? 'Gagal memuat history export.');
        }

        exportsList.value = payload.response.data?.data ?? [];
        meta.value = payload.response.data?.meta ?? meta.value;
    } catch (exception) {
        error.value = exception.message;
    } finally {
        loading.value = false;
    }
};

const refreshExport = async (item) => {
    const response = await fetch(`/reports/exports/${item.guid}`, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    const payload = await response.json();

    if (response.ok && payload.response?.data) {
        const index = exportsList.value.findIndex((exportItem) => exportItem.guid === item.guid);
        if (index >= 0) exportsList.value[index] = payload.response.data;
    }
};

const resetFilters = () => {
    filters.type = '';
    filters.status = '';
    filters.from_datetime = '';
    filters.to_datetime = '';
    filters.limit = 10;
    filters.page = 1;
    loadHistory();
};

const changePage = (page) => {
    filters.page = page;
    loadHistory();
};

onMounted(loadHistory);
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input v-model="filters.type" type="text" placeholder="Search report type..." @keyup.enter="loadHistory" />
                </label>
            </template>

            <template #actions>
                <Link class="icon-button" href="/reports" aria-label="Reports">
                    <span class="material-symbols-outlined">bar_chart</span>
                </Link>
                <Link class="icon-button" href="/" aria-label="Home">
                    <span class="material-symbols-outlined">dashboard</span>
                </Link>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Download History</h1>
                    <p>Riwayat export report dan file CSV yang siap diunduh.</p>
                </div>
                <Link class="primary-action" href="/reports">
                    <span class="material-symbols-outlined">add_chart</span>
                    Create Export
                </Link>
            </section>

            <section class="history-panel">
                <div class="history-filter">
                    <label>
                        <span>Report</span>
                        <select v-model="filters.type" @change="loadHistory">
                            <option value="">All reports</option>
                            <option v-for="report in reportTypes" :key="report.key" :value="report.key">{{ report.title }}</option>
                        </select>
                    </label>

                    <label>
                        <span>Status</span>
                        <select v-model="filters.status" @change="loadHistory">
                            <option value="">All status</option>
                            <option v-for="status in exportStatusOptions" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </label>

                    <label>
                        <span>From</span>
                        <input v-model="filters.from_datetime" type="datetime-local" @change="loadHistory" />
                    </label>

                    <label>
                        <span>To</span>
                        <input v-model="filters.to_datetime" type="datetime-local" @change="loadHistory" />
                    </label>

                    <button class="secondary-action" type="button" @click="resetFilters">
                        <span class="material-symbols-outlined">restart_alt</span>
                        Reset
                    </button>

                    <!-- <button class="primary-action" type="button" :disabled="loading" @click="loadHistory">
                        {{ loading ? 'Loading...' : 'Filter' }}
                    </button> -->
                </div>
            </section>

            <section class="history-panel">
                <div class="table-panel__head">
                    <strong>{{ meta.total }} exports</strong>
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

                <div v-if="error" class="error-banner">{{ error }}</div>

                <div class="export-list">
                    <article v-if="loading" class="export-row">
                        <div>
                            <strong>Loading history...</strong>
                            <span>Please wait</span>
                        </div>
                    </article>

                    <article v-else-if="exportsList.length === 0" class="export-row">
                        <div>
                            <strong>No export history</strong>
                            <span>Belum ada export sesuai filter.</span>
                        </div>
                    </article>

                    <article
                        v-for="item in exportsList"
                        v-else
                        :key="item.guid"
                        class="export-row"
                        :class="{ 'export-row--selected': item.guid === selectedGuid }"
                    >
                        <div>
                            <strong>{{ typeLabel(item.type) }}{{ dateRange(item.filters) }}.{{ item.format }}</strong>
                            <span>{{ item.status }} - {{ item.row_count }} rows - {{ item.created_at }}</span>
                            <small v-if="item.error_message">{{ item.error_message }}</small>
                        </div>
                        <div class="export-row__actions">
                            <button v-if="item.status !== 'done'" class="icon-button" type="button" aria-label="Refresh export" @click="refreshExport(item)">
                                <span class="material-symbols-outlined">refresh</span>
                            </button>
                            <a v-if="item.download_url" class="icon-button" :href="item.download_url" aria-label="Download export">
                                <span class="material-symbols-outlined">download</span>
                            </a>
                        </div>
                    </article>
                </div>
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
    height: 100vh;
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

.content {
    scrollbar-width: thin;
    scrollbar-color: #c6c5d4 transparent;
}

.brand {
    color: #1a237e;
    font-size: 32px;
    font-weight: 800;
    line-height: 40px;
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

.page-title,
.table-panel__head,
.export-row {
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

.primary-action,
.secondary-action,
.icon-button {
    font: inherit;
    cursor: pointer;
}

.primary-action,
.secondary-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    border-radius: 8px;
    padding: 0 18px;
    font-weight: 800;
    text-decoration: none;
}

.primary-action {
    border: 0;
    background: #1a237e;
    color: #fff;
}

.secondary-action {
    border: 1px solid #c6c5d4;
    background: #fff;
    color: #1a237e;
}

.primary-action:disabled,
.icon-button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.icon-button {
    display: inline-grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border: 0;
    border-radius: 8px;
    background: #ffffff;
    color: #1f2937;
    text-decoration: none;
}

.history-panel {
    margin-bottom: 16px;
    border: 1px solid #d9dadd;
    border-radius: 8px;
    background: #ffffff;
    padding: 16px;
}

.history-filter {
    display: grid;
    grid-template-columns: repeat(4, minmax(150px, 1fr)) auto auto;
    gap: 10px;
    align-items: end;
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

.table-panel__head {
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 14px;
}

.pager {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #5c5f66;
    font-size: 13px;
    font-weight: 800;
}

.error-banner {
    margin-bottom: 12px;
    border-radius: 8px;
    background: #ffe7e7;
    color: #8a1f1f;
    padding: 12px 14px;
    font-weight: 700;
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

.export-row--selected {
    border-color: #1a237e;
    background: #f0f2ff;
}

.export-row strong,
.export-row span,
.export-row small {
    display: block;
}

.export-row span {
    color: #5c5f66;
    font-size: 12px;
}

.export-row small {
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
        height: auto;
        margin-left: 0;
        padding: 92px 16px 96px;
        overflow: visible;
    }

    .search-box {
        display: none;
    }

    .page-title {
        flex-direction: column;
        align-items: stretch;
    }

    .history-filter {
        grid-template-columns: 1fr;
    }

    .primary-action,
    .secondary-action {
        width: 100%;
    }
}
</style>
