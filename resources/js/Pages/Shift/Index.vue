<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    shifts: { type: Array, default: () => [] },
    summary: {
        type: Object,
        default: () => ({
            active_count: 0,
            today_count: 0,
            closed_today: 0,
            total_sales_today: 0,
        }),
    },
    serverTime: { type: String, default: '' },
});

const statusFilter = ref('all');
const search = ref('');

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const formatDate = (value) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const filteredShifts = computed(() => props.shifts.filter((shift) => {
    const matchStatus = statusFilter.value === 'all' || shift.status === statusFilter.value;
    const keyword = search.value.trim().toLowerCase();
    const matchSearch = !keyword
        || shift.shift_number?.toLowerCase().includes(keyword)
        || shift.user?.full_name?.toLowerCase().includes(keyword)
        || shift.user?.username?.toLowerCase().includes(keyword);

    return matchStatus && matchSearch;
}));

const cards = computed(() => [
    { label: 'Active Shift', value: props.summary.active_count, icon: 'timer' },
    { label: 'Shift Hari Ini', value: props.summary.today_count, icon: 'calendar_today' },
    { label: 'Closed Today', value: props.summary.closed_today, icon: 'task_alt' },
    { label: 'Sales Shift Hari Ini', value: formatCurrency(props.summary.total_sales_today), icon: 'payments' },
]);
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input v-model="search" type="text" placeholder="Cari shift atau cashier..." />
                </label>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Shift Monitoring</h1>
                    <p>Pantau jam kerja cashier, sales per shift, dan status kas berjalan.</p>
                </div>
                <div class="segmented" aria-label="Filter status shift">
                    <button :class="{ active: statusFilter === 'all' }" type="button" @click="statusFilter = 'all'">All</button>
                    <button :class="{ active: statusFilter === 'open' }" type="button" @click="statusFilter = 'open'">Open</button>
                    <button :class="{ active: statusFilter === 'closed' }" type="button" @click="statusFilter = 'closed'">Closed</button>
                </div>
            </section>

            <section class="kpi-grid">
                <article v-for="card in cards" :key="card.label" class="kpi-card">
                    <div class="kpi-card__icon">
                        <span class="material-symbols-outlined">{{ card.icon }}</span>
                    </div>
                    <span>{{ card.label }}</span>
                    <strong>{{ card.value }}</strong>
                </article>
            </section>

            <section class="panel">
                <div class="panel__header">
                    <div>
                        <h2>Daftar Shift</h2>
                        <p>{{ filteredShifts.length }} shift ditampilkan</p>
                    </div>
                </div>

                <div class="shift-table">
                    <div class="shift-row shift-row--head">
                        <span>Shift</span>
                        <span>Cashier</span>
                        <span>Work Hours</span>
                        <span>Sales</span>
                        <span>Cash</span>
                        <span>Status</span>
                        <span></span>
                    </div>

                    <div v-for="shift in filteredShifts" :key="shift.guid" class="shift-row">
                        <div>
                            <strong>{{ shift.shift_number }}</strong>
                            <small>{{ formatDate(shift.opened_at) }}</small>
                        </div>
                        <div>
                            <strong>{{ shift.user?.full_name ?? '-' }}</strong>
                            <small>{{ shift.user?.role ?? '-' }}</small>
                        </div>
                        <span>{{ Number(shift.work_hours ?? 0).toFixed(2) }} jam</span>
                        <span>{{ formatCurrency(shift.summary?.total_sales) }}</span>
                        <span>{{ formatCurrency(shift.summary?.cash_sales) }}</span>
                        <span class="status" :class="`status--${shift.status}`">{{ shift.status }}</span>
                        <Link class="icon-link" :href="`/shifts/${shift.guid}`" aria-label="Detail shift">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </Link>
                    </div>

                    <div v-if="filteredShifts.length === 0" class="empty-state">
                        Belum ada shift sesuai filter.
                    </div>
                </div>
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

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    line-height: 1;
}

.dashboard-shell {
    min-height: 100vh;
    background: #f8f9fa;
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.content {
    height: 100vh;
    overflow-y: auto;
    padding: 112px 32px 32px 112px;
}

.brand {
    font-size: 20px;
    font-weight: 800;
}

.search-box {
    display: flex;
    align-items: center;
    width: min(420px, 42vw);
    height: 44px;
    gap: 10px;
    border: 1px solid #d6d7de;
    border-radius: 8px;
    background: #ffffff;
    padding: 0 14px;
    color: #5d6268;
}

.search-box input {
    width: 100%;
    border: 0;
    outline: 0;
    font: inherit;
}

.page-title {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
}

.page-title h1,
.panel h2 {
    margin: 0;
    color: #191c1d;
}

.page-title h1 {
    font-size: 32px;
    line-height: 40px;
}

.page-title p,
.panel p,
.shift-row small {
    margin: 4px 0 0;
    color: #5d6268;
    font-size: 13px;
}

.segmented {
    display: flex;
    border: 1px solid #d6d7de;
    border-radius: 8px;
    background: #ffffff;
    padding: 4px;
}

.segmented button {
    min-width: 76px;
    height: 36px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #454652;
    font-weight: 700;
    cursor: pointer;
}

.segmented button.active {
    background: #d8f8d5;
    color: #217128;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.kpi-card,
.panel {
    border: 1px solid #dfe1e5;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 2px rgb(15 23 42 / 6%);
}

.kpi-card {
    min-height: 132px;
    padding: 18px;
}

.kpi-card__icon {
    display: grid;
    width: 40px;
    height: 40px;
    margin-bottom: 14px;
    place-items: center;
    border-radius: 8px;
    background: #eef2ff;
    color: #1a237e;
}

.kpi-card span {
    display: block;
    color: #5d6268;
    font-size: 13px;
    font-weight: 700;
}

.kpi-card strong {
    display: block;
    margin-top: 6px;
    font-size: 22px;
}

.panel {
    padding: 18px;
}

.panel__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.shift-table {
    display: grid;
    gap: 8px;
}

.shift-row {
    display: grid;
    grid-template-columns: 1.3fr 1.2fr 0.8fr 1fr 1fr 0.7fr 44px;
    align-items: center;
    min-height: 68px;
    gap: 12px;
    border: 1px solid #edf0f2;
    border-radius: 8px;
    padding: 12px 14px;
}

.shift-row--head {
    min-height: 40px;
    border: 0;
    color: #5d6268;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.shift-row strong {
    display: block;
}

.status {
    width: fit-content;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 800;
}

.status--open {
    background: #fff7d6;
    color: #8a5a00;
}

.status--closed {
    background: #dcfce7;
    color: #166534;
}

.icon-link {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border-radius: 8px;
    color: #1a237e;
    text-decoration: none;
}

.icon-link:hover {
    background: #eef2ff;
}

.empty-state {
    padding: 32px;
    color: #5d6268;
    text-align: center;
}

@media (max-width: 980px) {
    .content {
        padding: 96px 16px 96px;
    }

    .page-title {
        align-items: stretch;
        flex-direction: column;
    }

    .kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .shift-row,
    .shift-row--head {
        grid-template-columns: 1fr;
    }

    .shift-row--head {
        display: none;
    }

    .search-box {
        width: 100%;
    }
}
</style>
