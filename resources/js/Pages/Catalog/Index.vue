<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    serverTime: {
        type: String,
        default: () => '',
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const displayName = computed(() => authUser.value?.detail?.full_name || authUser.value?.username || 'User');
const displayRole = computed(() => authUser.value?.role || 'Staff');

// Timer untuk update waktu setiap detik
const currentTime = ref(props.serverTime);
const displayTime = computed(() => currentTime.value);

// Update time every second
onMounted(() => {
    const updateTime = () => {
        const now = new Date();
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        
        const dayName = days[now.getDay()];
        const day = String(now.getDate()).padStart(2, '0');
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        const hours = String(now.getHours() % 12 || 12).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
        
        currentTime.value = `${dayName}, ${day} ${month} ${year} at ${hours}:${minutes} ${ampm}`;
    };
    
    updateTime();
    const interval = setInterval(updateTime, 1000);
    
    onUnmounted(() => clearInterval(interval));
});

const navItems = [
    { label: 'Home', icon: 'home', href: '/' },
    { label: 'New Sale', icon: 'add_shopping_cart', href: '/orders' },
    { label: 'Orders', icon: 'receipt_long', href: '/orders' },
    { label: 'Shift', icon: 'calendar_today', href: '#' },
    { label: 'Reports', icon: 'bar_chart', href: '#' },
    { label: 'Products', icon: 'inventory_2', href: '/catalog', active: true },
    { label: 'Sync Center', icon: 'sync', href: '#' },
    { label: 'Settings', icon: 'settings', href: '/settings/profile' },
];

const tabs = [
    { key: 'products', label: 'Products', singular: 'Product', icon: 'inventory_2' },
    { key: 'categories', label: 'Categories', singular: 'Category', icon: 'category' },
    { key: 'groups', label: 'Groups', singular: 'Group', icon: 'folder' },
];

const activeTab = ref('products');
const editing = ref(null);
const modalOpen = ref(false);

const productForm = useForm({
    category_guid: '',
    group_guid: '',
    name: '',
    description: '',
    price: 0,
    is_active: true,
});

const categoryForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const groupForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const activeForm = computed(() => {
    if (activeTab.value === 'categories') return categoryForm;
    if (activeTab.value === 'groups') return groupForm;

    return productForm;
});

const panelTitle = computed(() => tabs.find((tab) => tab.key === activeTab.value)?.label ?? 'Products');
const actionTitle = computed(() => tabs.find((tab) => tab.key === activeTab.value)?.singular ?? 'Product');

const resetForms = () => {
    productForm.reset();
    categoryForm.reset();
    groupForm.reset();
    productForm.clearErrors();
    categoryForm.clearErrors();
    groupForm.clearErrors();
    editing.value = null;
};

const openCreate = (tab = activeTab.value) => {
    activeTab.value = tab;
    resetForms();
    modalOpen.value = true;
};

const openEdit = (item) => {
    resetForms();
    editing.value = item;

    if (activeTab.value === 'products') {
        productForm.category_guid = item.category_guid ?? '';
        productForm.group_guid = item.group_guid ?? '';
        productForm.name = item.name ?? '';
        productForm.description = item.description ?? '';
        productForm.price = item.price ?? 0;
        productForm.is_active = Boolean(item.is_active);
    }

    if (activeTab.value === 'categories') {
        categoryForm.name = item.name ?? '';
        categoryForm.description = item.description ?? '';
        categoryForm.is_active = Boolean(item.is_active);
    }

    if (activeTab.value === 'groups') {
        groupForm.name = item.name ?? '';
        groupForm.description = item.description ?? '';
        groupForm.is_active = Boolean(item.is_active);
    }

    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    resetForms();
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (activeTab.value === 'categories') {
        editing.value
            ? categoryForm.put(`/catalog/categories/${editing.value.guid}`, options)
            : categoryForm.post('/catalog/categories', options);
    }

    if (activeTab.value === 'groups') {
        editing.value
            ? groupForm.put(`/catalog/groups/${editing.value.guid}`, options)
            : groupForm.post('/catalog/groups', options);
    }

    if (activeTab.value === 'products') {
        editing.value
            ? productForm.put(`/catalog/products/${editing.value.guid}`, options)
            : productForm.post('/catalog/products', options);
    }
};

const destroyItem = (item) => {
    if (activeTab.value === 'categories') {
        categoryForm.delete(`/catalog/categories/${item.guid}`, { preserveScroll: true });
    }

    if (activeTab.value === 'groups') {
        groupForm.delete(`/catalog/groups/${item.guid}`, { preserveScroll: true });
    }

    if (activeTab.value === 'products') {
        productForm.delete(`/catalog/products/${item.guid}`, { preserveScroll: true });
    }
};

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));
</script>

<template>
    <div class="dashboard-shell">
        <nav class="side-nav" aria-label="Main navigation">
            <div class="side-nav__main">
                <div class="branch-card">
                    <div class="branch-card__icon">
                        <span class="material-symbols-outlined">storefront</span>
                    </div>
                    <div class="branch-card__name">Main Branch</div>
                    <div class="branch-card__terminal">Terminal 01</div>
                </div>

                <div class="nav-list">
                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        class="nav-item"
                        :class="{ 'nav-item--active': item.active }"
                        :href="item.href"
                    >
                        <span class="material-symbols-outlined" :class="{ fill: item.active }">{{ item.icon }}</span>
                        <span>{{ item.label }}</span>
                    </Link>
                </div>
            </div>

            <button class="nav-item nav-item--footer" type="button">
                <span class="material-symbols-outlined">help</span>
                <span>Help</span>
            </button>
        </nav>

        <header class="top-bar">
            <div class="top-bar__left">
                <div class="brand">RetailPOS</div>
                <div class="current-time">
                    <span class="material-symbols-outlined">schedule</span>
                    <span>{{ displayTime }}</span>
                </div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" placeholder="Search catalog..." />
                </label>
            </div>

            <div class="top-bar__right">
                <Link class="icon-button" href="/" aria-label="Home">
                    <span class="material-symbols-outlined">dashboard</span>
                </Link>
                <!-- <button class="icon-button" aria-label="Sync status" type="button">
                    <span class="material-symbols-outlined">sync</span>
                </button> -->
                <div class="top-bar__divider"></div>
                <div class="shift-pill">
                    <span class="material-symbols-outlined">play_circle</span>
                    <span>Shift: Open</span>
                </div>
                <div class="profile">
                    <div>
                        <strong>{{ displayName }}</strong>
                        <span>{{ displayRole }}</span>
                    </div>
                    <div class="profile__avatar">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                </div>
                <Link class="icon-button" href="/logout" method="post" as="button" aria-label="Logout">
                    <span class="material-symbols-outlined">logout</span>
                </Link>
            </div>
        </header>

        <main class="content">
            <section class="page-title">
                <div>
                    <h1>Product Catalog</h1>
                    <p>Kelola produk, kategori, dan group untuk operasional POS.</p>
                </div>
                <button class="primary-action" type="button" @click="openCreate()">
                    <span class="material-symbols-outlined fill">add_circle</span>
                    Tambah {{ actionTitle }}
                </button>
            </section>

            <section class="summary-grid" aria-label="Catalog summary">
                <article class="summary-card summary-card--hero">
                    <span class="material-symbols-outlined fill">inventory_2</span>
                    <div>
                        <small>Total Products</small>
                        <strong>{{ products.length }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">category</span>
                    <div>
                        <small>Categories</small>
                        <strong>{{ categories.length }}</strong>
                    </div>
                </article>
                <article class="summary-card">
                    <span class="material-symbols-outlined">folder</span>
                    <div>
                        <small>Groups</small>
                        <strong>{{ groups.length }}</strong>
                    </div>
                </article>
            </section>

            <section class="catalog-panel">
                <div class="catalog-panel__header">
                    <div class="tabs" role="tablist">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            class="tab-button"
                            :class="{ 'tab-button--active': activeTab === tab.key }"
                            type="button"
                            @click="activeTab = tab.key"
                        >
                            <span class="material-symbols-outlined">{{ tab.icon }}</span>
                            {{ tab.label }}
                        </button>
                    </div>
                    <button class="secondary-action" type="button" @click="openCreate()">
                        <span class="material-symbols-outlined">add</span>
                        Baru
                    </button>
                </div>

                <div v-if="activeTab === 'products'" class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Group</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products" :key="product.guid">
                                <td>
                                    <strong>{{ product.name }}</strong>
                                    <span>{{ product.description || 'No description' }}</span>
                                </td>
                                <td>{{ product.category_name || '-' }}</td>
                                <td>{{ product.group_name || '-' }}</td>
                                <td>{{ formatCurrency(product.price) }}</td>
                                <td>
                                    <span class="status-badge" :class="{ 'status-badge--positive': product.is_active }">
                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="row-actions">
                                    <button class="icon-button" type="button" aria-label="Edit product" @click="openEdit(product)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="icon-button icon-button--danger" type="button" aria-label="Delete product" @click="destroyItem(product)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="activeTab === 'categories'" class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in categories" :key="category.guid">
                                <td><strong>{{ category.name }}</strong></td>
                                <td>{{ category.description || '-' }}</td>
                                <td>
                                    <span class="status-badge" :class="{ 'status-badge--positive': category.is_active }">
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="row-actions">
                                    <button class="icon-button" type="button" aria-label="Edit category" @click="openEdit(category)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="icon-button icon-button--danger" type="button" aria-label="Delete category" @click="destroyItem(category)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="activeTab === 'groups'" class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Group</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="group in groups" :key="group.guid">
                                <td><strong>{{ group.name }}</strong></td>
                                <td>{{ group.description || '-' }}</td>
                                <td>
                                    <span class="status-badge" :class="{ 'status-badge--positive': group.is_active }">
                                        {{ group.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="row-actions">
                                    <button class="icon-button" type="button" aria-label="Edit group" @click="openEdit(group)">
                                        <span class="material-symbols-outlined">edit</span>
                                    </button>
                                    <button class="icon-button icon-button--danger" type="button" aria-label="Delete group" @click="destroyItem(group)">
                                        <span class="material-symbols-outlined">delete</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <div v-if="modalOpen" class="modal-backdrop">
            <form class="modal" @submit.prevent="submit">
                <div class="modal__header">
                    <div>
                        <h2>{{ editing ? 'Edit' : 'Tambah' }} {{ actionTitle }}</h2>
                        <p>{{ activeTab === 'products' ? 'Atur relasi category dan group produk.' : 'Lengkapi data master katalog.' }}</p>
                    </div>
                    <button class="icon-button" type="button" aria-label="Close modal" @click="closeModal">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div v-if="activeTab === 'products'" class="form-grid">
                    <label>
                        <span>Nama Product</span>
                        <input v-model="productForm.name" type="text" placeholder="Nasi Goreng Special" />
                        <small v-if="productForm.errors.name">{{ productForm.errors.name }}</small>
                    </label>
                    <label>
                        <span>Harga</span>
                        <input v-model="productForm.price" type="number" min="0" placeholder="0" />
                        <small v-if="productForm.errors.price">{{ productForm.errors.price }}</small>
                    </label>
                    <label>
                        <span>Category</span>
                        <select v-model="productForm.category_guid">
                            <option value="">Pilih category</option>
                            <option v-for="category in categories" :key="category.guid" :value="category.guid">
                                {{ category.name }}
                            </option>
                        </select>
                        <small v-if="productForm.errors.category_guid">{{ productForm.errors.category_guid }}</small>
                    </label>
                    <label>
                        <span>Group</span>
                        <select v-model="productForm.group_guid">
                            <option value="">Pilih group</option>
                            <option v-for="group in groups" :key="group.guid" :value="group.guid">
                                {{ group.name }}
                            </option>
                        </select>
                        <small v-if="productForm.errors.group_guid">{{ productForm.errors.group_guid }}</small>
                    </label>
                    <label class="form-grid__wide">
                        <span>Deskripsi</span>
                        <textarea v-model="productForm.description" rows="3" placeholder="Catatan produk"></textarea>
                    </label>
                </div>

                <div v-else class="form-grid">
                    <label class="form-grid__wide">
                        <span>Nama</span>
                        <input v-model="activeForm.name" type="text" placeholder="makanan" />
                        <small v-if="activeForm.errors.name">{{ activeForm.errors.name }}</small>
                    </label>
                    <label class="form-grid__wide">
                        <span>Deskripsi</span>
                        <textarea v-model="activeForm.description" rows="3" placeholder="Catatan master data"></textarea>
                    </label>
                </div>

                <label class="toggle-row">
                    <input v-model="activeForm.is_active" type="checkbox" />
                    <span>Active</span>
                </label>

                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="closeModal">Batal</button>
                    <button class="primary-action" type="submit" :disabled="activeForm.processing">
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

.nav-item--footer {
    width: 100%;
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
.shift-pill,
.profile,
.page-title,
.summary-card,
.catalog-panel__header,
.tabs,
.tab-button,
.row-actions,
.modal__header,
.modal__actions,
.toggle-row {
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

.search-box {
    position: relative;
    width: 264px;
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

.search-box input {
    width: 100%;
    height: 40px;
    border-radius: 999px;
    padding: 0 16px 0 40px;
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

.shift-pill {
    gap: 8px;
    border: 1px solid rgb(27 109 36 / 20%);
    border-radius: 999px;
    background: #a0f399;
    color: #217128;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
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

.content::-webkit-scrollbar {
    width: 6px;
}

.content::-webkit-scrollbar-thumb {
    border-radius: 4px;
    background: #e1e3e4;
}

.page-title,
.summary-grid,
.catalog-panel {
    width: min(100%, 1280px);
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
    font-weight: 700;
    line-height: 40px;
}

.page-title p {
    margin: 2px 0 0;
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

.summary-grid {
    display: grid;
    grid-template-columns: 1.4fr repeat(2, minmax(180px, 0.7fr));
    gap: 12px;
    margin-bottom: 12px;
}

.summary-card {
    min-height: 116px;
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
    background: #e0e0ff;
    color: #343d96;
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

.catalog-panel {
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    overflow: hidden;
}

.catalog-panel__header {
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid #c6c5d4;
    padding: 14px;
}

.tabs {
    gap: 8px;
}

.tab-button {
    gap: 8px;
    min-height: 38px;
    border-radius: 8px;
    background: transparent;
    color: #454652;
    padding: 0 12px;
    font-weight: 800;
}

.tab-button--active {
    background: #a0f399;
    color: #217128;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 760px;
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

.row-actions {
    justify-content: flex-end;
    gap: 4px;
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

.form-grid label > span {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 800;
}

input,
select {
    width: 100%;
    height: 42px;
    padding: 0 12px;
}

textarea {
    width: 100%;
    resize: vertical;
    padding: 10px 12px;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #000666;
    box-shadow: 0 0 0 3px rgb(0 6 102 / 12%);
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

.toggle-row input {
    width: 16px;
    height: 16px;
}

.modal__actions {
    justify-content: flex-end;
    gap: 10px;
    padding: 18px;
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

    .summary-grid {
        grid-template-columns: 1fr;
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
    .nav-item:nth-child(n + 6),
    .nav-item--footer {
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

    .shift-pill {
        display: none;
    }

    .content {
        height: auto;
        min-height: calc(100vh - 64px);
        margin-left: 0;
        padding: 18px 14px 92px;
    }

    .page-title,
    .catalog-panel__header {
        align-items: flex-start;
        flex-direction: column;
    }

    .tabs {
        width: 100%;
        overflow-x: auto;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
