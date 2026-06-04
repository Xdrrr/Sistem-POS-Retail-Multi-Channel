<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    orders: { type: Array, default: () => [] },
    serverTime: {
        type: String,
        default: () => '',
    },
});

const selectedOrder = ref(null);
const productSearch = ref('');

const orderForm = useForm({
    customer_name: '',
    customer_phone: '',
    table_number: '',
    order_type: 'dine_in',
    discount_amount: 0,
    tax_amount: 0,
    notes: '',
    items: [],
    payment_method: 'cash',
    payment_amount: 0,
    reference_number: '',
});

const paymentForm = useForm({
    method: 'cash',
    amount: 0,
    reference_number: '',
    notes: '',
});

const filteredProducts = computed(() => {
    const keyword = productSearch.value.toLowerCase().trim();
    if (!keyword) return props.products;

    return props.products.filter((product) => [
        product.name,
        product.category_name,
        product.group_name,
    ].some((value) => String(value ?? '').toLowerCase().includes(keyword)));
});

const subtotal = computed(() => orderForm.items.reduce((total, item) => total + item.subtotal, 0));
const total = computed(() => Math.max(0, subtotal.value - Number(orderForm.discount_amount || 0) + Number(orderForm.tax_amount || 0)));
const remainingAmount = computed(() => selectedOrder.value
    ? Math.max(0, Number(selectedOrder.value.total_amount || 0) - Number(selectedOrder.value.paid_amount || 0))
    : 0);

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));

const addProduct = (product) => {
    const existing = orderForm.items.find((item) => item.product_guid === product.guid);

    if (existing) {
        existing.quantity += 1;
        existing.subtotal = Math.max(0, (existing.quantity * existing.unit_price) - Number(existing.discount_amount || 0));
        return;
    }

    orderForm.items.push({
        product_guid: product.guid,
        name: product.name,
        quantity: 1,
        unit_price: Number(product.price || 0),
        discount_amount: 0,
        subtotal: Number(product.price || 0),
        notes: '',
    });
};

const recalculateItem = (item) => {
    item.quantity = Math.max(0.01, Number(item.quantity || 0));
    item.unit_price = Math.max(0, Number(item.unit_price || 0));
    item.discount_amount = Math.max(0, Number(item.discount_amount || 0));
    item.subtotal = Math.max(0, (item.quantity * item.unit_price) - item.discount_amount);
};

const removeItem = (index) => {
    orderForm.items.splice(index, 1);
};

const resetOrder = () => {
    orderForm.reset();
    orderForm.items = [];
    orderForm.clearErrors();
};

const submitOrder = () => {
    orderForm.payment_amount = Number(orderForm.payment_amount || 0);
    orderForm.post('/orders/create', {
        preserveScroll: true,
        onSuccess: resetOrder,
    });
};

const openPayment = (order) => {
    selectedOrder.value = order;
    paymentForm.reset();
    paymentForm.method = 'cash';
    paymentForm.amount = Math.max(0, Number(order.total_amount || 0) - Number(order.paid_amount || 0));
    paymentForm.reference_number = '';
    paymentForm.notes = '';
};

const closePayment = () => {
    selectedOrder.value = null;
    paymentForm.reset();
};

const submitPayment = () => {
    paymentForm.post(`/orders/${selectedOrder.value.guid}/payments`, {
        preserveScroll: true,
        onSuccess: closePayment,
    });
};

const completeOrder = (order) => {
    useForm({}).put(`/orders/${order.guid}/complete`, { preserveScroll: true });
};

const cancelOrder = (order) => {
    useForm({}).put(`/orders/${order.guid}/cancel`, { preserveScroll: true });
};
</script>

<template>
    <div class="dashboard-shell">
        <AppSidebar />

        <AppNavbar :server-time="serverTime" :show-shift="false">
            <template #left>
                <div class="brand">RetailPOS</div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input v-model="productSearch" type="text" placeholder="Search products..." />
                </label>
            </template>

            <template #actions>
                <Link class="icon-button" href="/" aria-label="Home">
                    <span class="material-symbols-outlined">dashboard</span>
                </Link>
                <Link class="icon-button" href="/catalog" aria-label="Catalog">
                    <span class="material-symbols-outlined">inventory_2</span>
                </Link>
            </template>
        </AppNavbar>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Orders</h1>
                    <p>Kelola order, keranjang transaksi, dan pembayaran dari satu layar kasir.</p>
                </div>
                <div class="title-metric">
                    <span>Open Orders</span>
                    <strong>{{ orders.filter((order) => order.status === 'open').length }}</strong>
                </div>
            </section>

            <section class="order-workspace">
                <section class="product-panel">
                    <div class="panel-header">
                        <h2>Products</h2>
                        <span>{{ filteredProducts.length }} items</span>
                    </div>
                    <div class="product-grid">
                        <button
                            v-for="product in filteredProducts"
                            :key="product.guid"
                            class="product-tile"
                            type="button"
                            @click="addProduct(product)"
                        >
                            <img v-if="product.image_url" class="product-tile__image" :src="product.image_url" :alt="product.name" />
                            <span v-else class="material-symbols-outlined">fastfood</span>
                            <strong>{{ product.name }}</strong>
                            <small>{{ product.category_name || 'Uncategorized' }} - {{ product.group_name || 'No group' }}</small>
                            <em>{{ formatCurrency(product.price) }}</em>
                        </button>
                    </div>
                </section>

                <form class="cart-panel" @submit.prevent="submitOrder">
                    <div class="panel-header">
                        <h2>Current Order</h2>
                        <button class="ghost-button" type="button" @click="resetOrder">
                            <span class="material-symbols-outlined">refresh</span>
                            Reset
                        </button>
                    </div>

                    <div class="form-grid">
                        <label>
                            <span>Customer</span>
                            <input v-model="orderForm.customer_name" type="text" placeholder="Nama customer" />
                        </label>
                        <label>
                            <span>Phone</span>
                            <input v-model="orderForm.customer_phone" type="text" placeholder="08xxxxxxxxxx" />
                        </label>
                        <label>
                            <span>Table</span>
                            <input v-model="orderForm.table_number" type="text" placeholder="A1" />
                        </label>
                        <label>
                            <span>Order Type</span>
                            <select v-model="orderForm.order_type">
                                <option value="dine_in">Dine In</option>
                                <option value="takeaway">Takeaway</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </label>
                    </div>

                    <div class="cart-list">
                        <div v-if="orderForm.items.length === 0" class="empty-state">
                            <span class="material-symbols-outlined">shopping_cart</span>
                            <strong>Keranjang masih kosong</strong>
                        </div>
                        <div v-for="(item, index) in orderForm.items" :key="item.product_guid" class="cart-row">
                            <div class="cart-row__name">
                                <strong>{{ item.name }}</strong>
                                <span>{{ formatCurrency(item.subtotal) }}</span>
                            </div>
                            <input v-model.number="item.quantity" type="number" min="0.01" step="0.01" @input="recalculateItem(item)" />
                            <input v-model.number="item.unit_price" type="number" min="0" @input="recalculateItem(item)" />
                            <button class="icon-button icon-button--danger" type="button" @click="removeItem(index)">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </div>

                    <div class="totals">
                        <label>
                            <span>Discount</span>
                            <input v-model.number="orderForm.discount_amount" type="number" min="0" />
                        </label>
                        <label>
                            <span>Tax</span>
                            <input v-model.number="orderForm.tax_amount" type="number" min="0" />
                        </label>
                        <label>
                            <span>Pay Now</span>
                            <input v-model.number="orderForm.payment_amount" type="number" min="0" />
                        </label>
                        <label>
                            <span>Method</span>
                            <select v-model="orderForm.payment_method">
                                <option value="cash">Cash</option>
                                <option value="qris">QRIS</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="transfer">Transfer</option>
                                <option value="e_wallet">E-Wallet</option>
                            </select>
                        </label>
                    </div>

                    <div class="checkout-bar">
                        <div>
                            <span>Subtotal</span>
                            <strong>{{ formatCurrency(subtotal) }}</strong>
                        </div>
                        <div>
                            <span>Total</span>
                            <strong>{{ formatCurrency(total) }}</strong>
                        </div>
                        <button class="primary-action" type="submit" :disabled="orderForm.processing || orderForm.items.length === 0">
                            <span class="material-symbols-outlined fill">save</span>
                            Simpan Order
                        </button>
                    </div>
                    <small v-if="orderForm.errors.items">{{ orderForm.errors.items }}</small>
                </form>
            </section>

            <section class="orders-panel">
                <div class="panel-header">
                    <h2>Order & Payment</h2>
                    <span>{{ orders.length }} latest orders</span>
                </div>
                <div class="order-list">
                    <article v-for="order in orders" :key="order.guid" class="order-card">
                        <div class="order-card__main">
                            <div>
                                <strong>{{ order.order_number }}</strong>
                                <span>{{ order.customer_name || 'Walk-in' }} - {{ order.ordered_at }}</span>
                            </div>
                            <div class="badge-row">
                                <span class="status-badge">{{ order.status }}</span>
                                <span class="status-badge" :class="{ 'status-badge--positive': order.payment_status === 'paid' }">
                                    {{ order.payment_status }}
                                </span>
                            </div>
                        </div>

                        <div class="order-card__items">
                            <span v-for="item in order.items" :key="item.name">{{ item.quantity }}x {{ item.name }}</span>
                        </div>

                        <div class="order-card__footer">
                            <div>
                                <span>Paid {{ formatCurrency(order.paid_amount) }}</span>
                                <strong>{{ formatCurrency(order.total_amount) }}</strong>
                            </div>
                            <div class="row-actions">
                                <button class="secondary-action" type="button" @click="openPayment(order)">
                                    <span class="material-symbols-outlined">payments</span>
                                    Payment
                                </button>
                                <button v-if="order.status === 'open'" class="icon-button" type="button" aria-label="Complete order" @click="completeOrder(order)">
                                    <span class="material-symbols-outlined">check_circle</span>
                                </button>
                                <button v-if="order.status !== 'cancelled'" class="icon-button icon-button--danger" type="button" aria-label="Cancel order" @click="cancelOrder(order)">
                                    <span class="material-symbols-outlined">cancel</span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <div v-if="selectedOrder" class="modal-backdrop">
            <form class="modal" @submit.prevent="submitPayment">
                <div class="modal__header">
                    <div>
                        <h2>Tambah Payment</h2>
                        <p>{{ selectedOrder.order_number }} - sisa {{ formatCurrency(remainingAmount) }}</p>
                    </div>
                    <button class="icon-button" type="button" aria-label="Close" @click="closePayment">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="form-grid">
                    <label>
                        <span>Method</span>
                        <select v-model="paymentForm.method">
                            <option value="cash">Cash</option>
                            <option value="qris">QRIS</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="transfer">Transfer</option>
                            <option value="e_wallet">E-Wallet</option>
                        </select>
                    </label>
                    <label>
                        <span>Amount</span>
                        <input v-model.number="paymentForm.amount" type="number" />
                    </label>
                    <label class="form-grid__wide">
                        <span>Reference</span>
                        <input v-model="paymentForm.reference_number" type="text" placeholder="Nomor referensi" />
                    </label>
                    <label class="form-grid__wide">
                        <span>Notes</span>
                        <textarea v-model="paymentForm.notes" rows="3" placeholder="Catatan payment"></textarea>
                    </label>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="closePayment">Batal</button>
                    <button class="primary-action" type="submit" :disabled="paymentForm.processing">
                        <span class="material-symbols-outlined fill">save</span>
                        Simpan Payment
                    </button>
                </div>
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

.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    line-height: 1;
}

.material-symbols-outlined.fill {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.dashboard-shell {
    min-height: 100vh;
    width: 100vw;
    overflow: hidden;
    background: #f8f9fa;
    color: #191c1d;
    font-family: Inter, ui-sans-serif, system-ui, sans-serif;
}

.side-nav {
    position: fixed;
    inset: 0 auto 0 0;
    z-index: 40;
    display: flex;
    width: 80px;
    flex-direction: column;
    justify-content: space-between;
    border-right: 1px solid #c6c5d4;
    background: #ffffff;
    padding: 24px 8px;
}

.side-nav__main,
.nav-list {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.branch-card {
    margin-bottom: 24px;
    width: 100%;
    text-align: center;
}

.branch-card__icon {
    display: grid;
    width: 48px;
    height: 48px;
    margin: 0 auto 8px;
    place-items: center;
    border-radius: 8px;
    background: #1a237e;
    color: #ffffff;
}

.branch-card__name,
.branch-card__terminal,
.nav-item span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 10px;
    font-weight: 700;
}

.branch-card__name {
    color: #191c1d;
    font-size: 11px;
}

.branch-card__terminal {
    color: #454652;
}

.nav-list {
    gap: 8px;
    width: 100%;
}

button,
a {
    font: inherit;
}

button {
    border: 0;
    cursor: pointer;
}

.nav-item {
    display: flex;
    min-height: 54px;
    width: 56px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    border-radius: 8px;
    background: transparent;
    color: #454652;
    text-decoration: none;
}

.nav-item:hover {
    background: #e7e8e9;
}

.nav-item--active {
    background: #a0f399;
    color: #217128;
}

.top-bar {
    position: fixed;
    top: 0;
    left: 80px;
    z-index: 50;
    display: flex;
    width: calc(100vw - 80px);
    height: 64px;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #c6c5d4;
    background: #f8f9fa;
    padding: 0 16px;
}

.top-bar__left,
.top-bar__right,
.search-box,
.profile,
.page-title,
.panel-header,
.checkout-bar,
.order-card__main,
.order-card__footer,
.row-actions,
.badge-row,
.modal__header,
.modal__actions {
    display: flex;
    align-items: center;
}

.top-bar__left {
    gap: 24px;
    min-width: 0;
}

.top-bar__right {
    gap: 8px;
}

.brand {
    color: #1a237e;
    font-size: 32px;
    font-weight: 800;
    line-height: 40px;
}

.search-box {
    position: relative;
    width: 300px;
}

.search-box span {
    position: absolute;
    left: 12px;
    color: #454652;
}

.current-time {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #ffffff;
    color: #454652;
    font-size: 14px;
    font-weight: 600;
}

input,
select,
textarea {
    width: 100%;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    color: #191c1d;
    outline: 0;
}

input,
select {
    height: 42px;
    padding: 0 12px;
}

textarea {
    resize: vertical;
    padding: 10px 12px;
}

.search-box input {
    height: 40px;
    border-radius: 999px;
    padding-left: 40px;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #000666;
    box-shadow: 0 0 0 3px rgb(0 6 102 / 12%);
}

.icon-button {
    display: grid;
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

.icon-button--danger {
    color: #ba1a1a;
}

.top-bar__divider {
    width: 1px;
    height: 32px;
    margin: 0 8px;
    background: #c6c5d4;
}

.profile {
    gap: 12px;
    padding-left: 8px;
}

.profile > div:first-child {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.profile strong {
    color: #000666;
    font-size: 14px;
    font-weight: 600;
}

.profile span {
    color: #454652;
    font-size: 10px;
    font-weight: 700;
}

.profile__avatar {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 1px solid #c6c5d4;
    border-radius: 999px;
    background: #1a237e;
    color: #ffffff;
}

.content {
    height: calc(100vh - 64px);
    margin-left: 80px;
    margin-top: 64px;
    overflow-y: auto;
    padding: 24px;
}

.page-title,
.order-workspace,
.orders-panel {
    width: min(100%, 1360px);
    margin-inline: auto;
}

.page-title {
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}

.page-title h1 {
    margin: 0;
    font-size: 32px;
    line-height: 40px;
}

.page-title p {
    margin: 2px 0 0;
    color: #454652;
}

.title-metric {
    min-width: 150px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    padding: 12px 14px;
    text-align: right;
}

.title-metric span,
.panel-header span,
.checkout-bar span,
.order-card span {
    color: #454652;
    font-size: 12px;
    font-weight: 700;
}

.title-metric strong {
    display: block;
    color: #000666;
    font-size: 24px;
}

.order-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(420px, 0.9fr);
    gap: 12px;
    margin-bottom: 12px;
}

.product-panel,
.cart-panel,
.orders-panel {
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
}

.panel-header {
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #edeeef;
    padding: 14px;
}

.panel-header h2 {
    margin: 0;
    font-size: 20px;
}

.product-grid {
    display: grid;
    max-height: 590px;
    overflow-y: auto;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 10px;
    padding: 14px;
}

.product-tile {
    display: flex;
    min-height: 152px;
    flex-direction: column;
    align-items: flex-start;
    gap: 7px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    padding: 12px;
    text-align: left;
}

.product-tile:hover {
    border-color: #1b6d24;
    background: #f7fff5;
}

.product-tile > span {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border-radius: 8px;
    background: #e0e0ff;
    color: #343d96;
}

.product-tile__image {
    width: 100%;
    height: 86px;
    border-radius: 8px;
    object-fit: cover;
}

.product-tile strong {
    line-height: 20px;
}

.product-tile small {
    color: #454652;
    line-height: 17px;
}

.product-tile em {
    margin-top: auto;
    color: #1b6d24;
    font-style: normal;
    font-weight: 800;
}

.form-grid,
.totals {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 14px;
}

label > span {
    display: block;
    margin-bottom: 7px;
    font-size: 12px;
    font-weight: 800;
}

.cart-list {
    display: flex;
    min-height: 168px;
    flex-direction: column;
    gap: 8px;
    padding: 0 14px 14px;
}

.empty-state {
    display: grid;
    min-height: 168px;
    place-items: center;
    border: 1px dashed #c6c5d4;
    border-radius: 8px;
    color: #454652;
    text-align: center;
}

.empty-state span {
    font-size: 38px;
}

.cart-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 82px 112px 40px;
    align-items: center;
    gap: 8px;
    border: 1px solid #edeeef;
    border-radius: 8px;
    padding: 8px;
}

.cart-row__name {
    min-width: 0;
}

.cart-row__name strong,
.cart-row__name span {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.checkout-bar {
    justify-content: space-between;
    gap: 12px;
    border-top: 1px solid #edeeef;
    padding: 14px;
}

.checkout-bar strong {
    display: block;
    font-size: 20px;
}

.primary-action,
.secondary-action,
.ghost-button {
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

.primary-action:disabled {
    opacity: 0.5;
}

.secondary-action,
.ghost-button {
    border: 1px solid #c6c5d4;
    background: #ffffff;
    color: #000666;
}

.ghost-button {
    min-height: 36px;
}

.orders-panel {
    overflow: hidden;
}

.order-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 12px;
    padding: 14px;
}

.order-card {
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    padding: 12px;
}

.order-card__main,
.order-card__footer {
    justify-content: space-between;
    gap: 12px;
}

.order-card__main strong {
    display: block;
    color: #000666;
    font-size: 16px;
}

.badge-row,
.row-actions {
    gap: 6px;
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
    text-transform: capitalize;
}

.status-badge--positive {
    background: #a0f399;
    color: #217128;
}

.order-card__items {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 12px 0;
}

.order-card__items span {
    border-radius: 999px;
    background: #f1f2f3;
    padding: 5px 8px;
}

.order-card__footer strong {
    display: block;
    font-size: 18px;
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
    width: min(100%, 560px);
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
}

.modal p {
    margin: 4px 0 0;
    color: #454652;
}

.form-grid__wide {
    grid-column: 1 / -1;
}

.modal__actions {
    justify-content: flex-end;
    gap: 10px;
    padding: 18px;
}

small {
    display: block;
    color: #ba1a1a;
    font-weight: 700;
    padding: 0 14px 14px;
}

@media (max-width: 1180px) {
    .order-workspace {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .search-box,
    .top-bar__divider,
    .profile > div:first-child {
        display: none;
    }

    .brand {
        font-size: 24px;
    }

    .page-title {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 720px) {
    :global(body) {
        overflow: auto;
    }

    .dashboard-shell {
        overflow: visible;
    }

    .side-nav {
        inset: auto 0 0 0;
        width: 100%;
        height: 72px;
        flex-direction: row;
        padding: 8px;
        border-top: 1px solid #c6c5d4;
        border-right: 0;
    }

    .branch-card,
    .nav-item:nth-child(n + 6) {
        display: none;
    }

    .nav-list,
    .side-nav__main {
        flex: 1;
        flex-direction: row;
        justify-content: space-around;
    }

    .top-bar {
        left: 0;
        width: 100%;
    }

    .content {
        height: auto;
        min-height: calc(100vh - 64px);
        margin-left: 0;
        padding: 18px 14px 92px;
    }

    .form-grid,
    .totals,
    .order-list {
        grid-template-columns: 1fr;
    }

    .cart-row {
        grid-template-columns: 1fr 70px 94px 40px;
    }

    .checkout-bar,
    .order-card__footer {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
