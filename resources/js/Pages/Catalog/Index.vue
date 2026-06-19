<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    cabangs: { type: Array, default: () => [] },
    serverTime: {
        type: String,
        default: () => '',
    },
});

const filters = reactive({
    search: '',
    guid_cabang: '',
    category_guids: [],
    group_guids: [],
    is_active: '',
    limit: 20,
    sort: 'ASC',
});

const sortByName = (items) => {
    const sorted = [...items].sort((a, b) => a.name.localeCompare(b.name));
    return filters.sort === 'DESC' ? sorted.reverse() : sorted;
};

const filteredProducts = computed(() => {
    let items = props.products;
    if (filters.search) {
        const q = filters.search.toLowerCase();
        items = items.filter((p) => p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q));
    }
    if (filters.category_guids.length > 0) {
        items = items.filter((p) => filters.category_guids.includes(p.category_guid));
    }
    if (filters.group_guids.length > 0) {
        items = items.filter((p) => filters.group_guids.includes(p.group_guid));
    }
    if (filters.is_active !== '') {
        items = items.filter((p) => String(p.is_active) === filters.is_active);
    }
    if (filters.guid_cabang) {
        items = items.filter((p) => p.guid_cabang === filters.guid_cabang);
    }
    return sortByName(items);
});

const filteredCategories = computed(() => {
    let items = props.categories;
    if (filters.search) {
        const q = filters.search.toLowerCase();
        items = items.filter((c) => c.name.toLowerCase().includes(q));
    }
    if (filters.is_active !== '') {
        items = items.filter((c) => String(c.is_active) === filters.is_active);
    }
    return sortByName(items);
});

const filteredGroups = computed(() => {
    let items = props.groups;
    if (filters.search) {
        const q = filters.search.toLowerCase();
        items = items.filter((g) => g.name.toLowerCase().includes(q));
    }
    if (filters.is_active !== '') {
        items = items.filter((g) => String(g.is_active) === filters.is_active);
    }
    return sortByName(items);
});

const resetFilters = () => {
    filters.search = '';
    filters.guid_cabang = '';
    filters.category_guids = [];
    filters.group_guids = [];
    filters.is_active = '';
    filters.limit = 20;
    filters.sort = 'ASC';
    currentPage.value = 1;
};

const tabs = [
    { key: 'products', label: 'Products', singular: 'Product', icon: 'inventory_2' },
    { key: 'categories', label: 'Categories', singular: 'Category', icon: 'category' },
    { key: 'groups', label: 'Groups', singular: 'Group', icon: 'folder' },
];

const activeTab = ref('products');
const editing = ref(null);
const modalOpen = ref(false);
const currentImageUrl = ref(null);
const currentPage = ref(1);
const loading = ref(false);

const productForm = useForm({
    category_guid: '',
    group_guid: '',
    guid_cabang: '',
    name: '',
    description: '',
    image: null,
    price: 0,
    is_active: true,
});

const categoryForm = useForm({
    name: '',
    description: '',
    image: null,
    is_active: true,
});

const groupForm = useForm({
    name: '',
    description: '',
    image: null,
    is_active: true,
});

const activeForm = computed(() => {
    if (activeTab.value === 'categories') return categoryForm;
    if (activeTab.value === 'groups') return groupForm;

    return productForm;
});

const panelTitle = computed(() => tabs.find((tab) => tab.key === activeTab.value)?.label ?? 'Products');
const actionTitle = computed(() => tabs.find((tab) => tab.key === activeTab.value)?.singular ?? 'Product');

const pageSize = computed(() => Math.max(1, Math.min(100, Number(filters.limit || 20))));
const activeTotal = computed(() => {
    if (activeTab.value === 'categories') return filteredCategories.value.length;
    if (activeTab.value === 'groups') return filteredGroups.value.length;

    return filteredProducts.value.length;
});
const meta = computed(() => {
    const lastPage = Math.max(1, Math.ceil(activeTotal.value / pageSize.value));

    return {
        current_page: Math.min(currentPage.value, lastPage),
        last_page: lastPage,
        per_page: pageSize.value,
        total: activeTotal.value,
    };
});
const paginate = (items) => {
    const start = (meta.value.current_page - 1) * pageSize.value;

    return items.slice(start, start + pageSize.value);
};
const paginatedProducts = computed(() => paginate(filteredProducts.value));
const paginatedCategories = computed(() => paginate(filteredCategories.value));
const paginatedGroups = computed(() => paginate(filteredGroups.value));
const changePage = (page) => {
    currentPage.value = Math.max(1, Math.min(meta.value.last_page, page));
};

watch(
    () => [
        activeTab.value,
        filters.search,
        filters.category_guids.join('|'),
        filters.group_guids.join('|'),
        filters.is_active,
        filters.limit,
        filters.sort,
    ],
    () => {
        currentPage.value = 1;
    },
);

const resetForms = () => {
    productForm.reset();
    categoryForm.reset();
    groupForm.reset();
    productForm.clearErrors();
    categoryForm.clearErrors();
    groupForm.clearErrors();
    editing.value = null;
    currentImageUrl.value = null;
};

const openCreate = (tab = activeTab.value) => {
    activeTab.value = tab;
    resetForms();
    modalOpen.value = true;
};

const openEdit = (item) => {
    resetForms();
    editing.value = item;
    currentImageUrl.value = item.image_url ?? null;

    if (activeTab.value === 'products') {
        productForm.category_guid = item.category_guid ?? '';
        productForm.group_guid = item.group_guid ?? '';
        productForm.guid_cabang = item.guid_cabang ?? '';
        productForm.name = item.name ?? '';
        productForm.description = item.description ?? '';
        productForm.image = null;
        productForm.price = item.price ?? 0;
        productForm.is_active = Boolean(item.is_active);
    }

    if (activeTab.value === 'categories') {
        categoryForm.name = item.name ?? '';
        categoryForm.description = item.description ?? '';
        categoryForm.image = null;
        categoryForm.is_active = Boolean(item.is_active);
    }

    if (activeTab.value === 'groups') {
        groupForm.name = item.name ?? '';
        groupForm.description = item.description ?? '';
        groupForm.image = null;
        groupForm.is_active = Boolean(item.is_active);
    }

    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    resetForms();
};

const setImage = (event) => {
    const [file] = event.target.files ?? [];
    activeForm.value.image = file ?? null;
    currentImageUrl.value = file ? URL.createObjectURL(file) : editing.value?.image_url ?? null;
};

const submitForm = (form, url, method = 'post') => {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: closeModal,
    };

    if (method === 'put') {
        form.transform((data) => ({ ...data, _method: 'put' })).post(url, options);
        return;
    }

    form.transform((data) => data).post(url, options);
};

const submit = () => {
    if (activeTab.value === 'categories') {
        editing.value
            ? submitForm(categoryForm, `/catalog/categories/${editing.value.guid}`, 'put')
            : submitForm(categoryForm, '/catalog/categories');
    }

    if (activeTab.value === 'groups') {
        editing.value
            ? submitForm(groupForm, `/catalog/groups/${editing.value.guid}`, 'put')
            : submitForm(groupForm, '/catalog/groups');
    }

    if (activeTab.value === 'products') {
        editing.value
            ? submitForm(productForm, `/catalog/products/${editing.value.guid}`, 'put')
            : submitForm(productForm, '/catalog/products');
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

const confirmDelete = ref(null);
const confirmDestroy = (item) => { confirmDelete.value = item; };
const executeDestroy = () => {
    if (confirmDelete.value) {
        destroyItem(confirmDelete.value);
        confirmDelete.value = null;
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
        <AppSidebar />

        <AppNavbar :server-time="serverTime">
            <template #left>
                <div class="brand">RetailPOS</div>
                <label class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input v-model="filters.search" type="text" placeholder="Search catalog..." />
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

            <section class="catalog-layout">
                <aside class="filter-panel">
                    <div class="filter-panel__head">
                        <strong>Filter</strong>
                    </div>

                    <label>
                        <span>Search</span>
                        <input v-model="filters.search" type="text" placeholder="Cari nama..." />
                    </label>

                    <label v-if="activeTab === 'products'">
                        <span>Category</span>
                        <select v-model="filters.category_guids" multiple>
                            <option v-for="category in categories" :key="category.guid" :value="category.guid">{{ category.name }}</option>
                        </select>
                    </label>

                    <label v-if="activeTab === 'products'">
                        <span>Group</span>
                        <select v-model="filters.group_guids" multiple>
                            <option v-for="group in groups" :key="group.guid" :value="group.guid">{{ group.name }}</option>
                        </select>
                    </label>

                    <label>
                        <span>Cabang</span>
                        <select v-model="filters.guid_cabang">
                            <option value="">Semua</option>
                            <option v-for="c in cabangs" :key="c.guid" :value="c.guid">{{ c.kode }} - {{ c.nama }}</option>
                        </select>
                    </label>

                    <label>
                        <span>Status</span>
                        <select v-model="filters.is_active">
                            <option value="">All</option>
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

                    <button class="secondary-action" type="button" @click="resetFilters" style="width: 100%; margin-top: 8px;">
                        <span class="material-symbols-outlined">restart_alt</span>
                        Reset
                    </button>
                </aside>

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
                        <div class="catalog-panel__actions">
                            <button class="secondary-action" type="button" @click="openCreate()">
                                <span class="material-symbols-outlined">add</span>
                                Baru
                            </button>
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
                    </div>

                    <div class="filter-info">
                        <span>{{ meta.total }} {{ panelTitle.toLowerCase() }} found</span>
                    </div>

                    <div v-if="activeTab === 'products'" class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Image</th>
                                    <th>Category</th>
                                    <th>Group</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="product in paginatedProducts" :key="product.guid">
                                    <td>
                                        <strong>{{ product.name }}</strong>
                                        <span>{{ product.description || 'No description' }}</span>
                                    </td>
                                    <td>
                                        <img v-if="product.image_url" class="catalog-thumb" :src="product.image_url" :alt="product.name" />
                                        <span v-else class="image-placeholder">
                                            <span class="material-symbols-outlined">image</span>
                                        </span>
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
                                        <button class="icon-button icon-button--danger" type="button" aria-label="Delete product" @click="confirmDestroy(product)">
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
                                    <th>Image</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in paginatedCategories" :key="category.guid">
                                    <td><strong>{{ category.name }}</strong></td>
                                    <td>
                                        <img v-if="category.image_url" class="catalog-thumb" :src="category.image_url" :alt="category.name" />
                                        <span v-else class="image-placeholder">
                                            <span class="material-symbols-outlined">image</span>
                                        </span>
                                    </td>
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
                                        <button class="icon-button icon-button--danger" type="button" aria-label="Delete category" @click="confirmDestroy(category)">
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
                                    <th>Image</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="group in paginatedGroups" :key="group.guid">
                                    <td><strong>{{ group.name }}</strong></td>
                                    <td>
                                        <img v-if="group.image_url" class="catalog-thumb" :src="group.image_url" :alt="group.name" />
                                        <span v-else class="image-placeholder">
                                            <span class="material-symbols-outlined">image</span>
                                        </span>
                                    </td>
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
                                        <button class="icon-button icon-button--danger" type="button" aria-label="Delete group" @click="confirmDestroy(group)">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
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
                    <label>
                        <span>Cabang</span>
                        <select v-model="productForm.guid_cabang">
                            <option value="">Pilih cabang</option>
                            <option v-for="c in cabangs" :key="c.guid" :value="c.guid">{{ c.kode }} - {{ c.nama }}</option>
                        </select>
                        <small v-if="productForm.errors.guid_cabang">{{ productForm.errors.guid_cabang }}</small>
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

                <div class="image-field">
                    <div>
                        <span>Gambar</span>
                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="setImage" />
                        <small v-if="activeForm.errors.image">{{ activeForm.errors.image }}</small>
                    </div>
                    <img v-if="currentImageUrl" class="image-preview" :src="currentImageUrl" alt="Preview gambar" />
                    <span v-else class="image-preview image-preview--empty">
                        <span class="material-symbols-outlined">image</span>
                    </span>
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
        <div v-if="confirmDelete" class="modal-backdrop">
            <div class="modal modal--confirm">
                <div class="modal__header">
                    <div><h2>Konfirmasi Hapus</h2></div>
                    <button class="icon-button" type="button" @click="confirmDelete = null"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div class="modal__body">
                    <p>Yakin ingin menghapus <strong>{{ confirmDelete?.name || '' }}</strong>?</p>
                </div>
                <div class="modal__actions">
                    <button class="secondary-action" type="button" @click="confirmDelete = null">Batal</button>
                    <button class="primary-action primary-action--danger" type="button" :disabled="categoryForm?.processing || groupForm?.processing || productForm?.processing" @click="executeDestroy">
                        <span class="material-symbols-outlined fill">delete</span>Hapus
                    </button>
                </div>
            </div>
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
    width: min(100%, 1920px);
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

.catalog-panel__actions {
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

.pager .icon-button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
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

td.row-actions {
    display: table-cell;
    text-align: right;
    white-space: nowrap;
}

td.row-actions .icon-button {
    display: inline-grid;
    vertical-align: middle;
}

td.row-actions .icon-button + .icon-button {
    margin-left: 4px;
}

.catalog-thumb,
.image-placeholder {
    width: 52px;
    height: 52px;
    border-radius: 8px;
}

.catalog-thumb {
    display: block;
    object-fit: cover;
}

.image-placeholder {
    display: grid;
    place-items: center;
    border: 1px solid #edeeef;
    background: #f8f9fa;
    color: #454652;
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

.image-field {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 88px;
    gap: 14px;
    align-items: end;
    padding: 0 18px 18px;
}

.image-field > div > span {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 800;
}

.image-field input[type='file'] {
    padding: 8px 12px;
}

.image-preview {
    width: 88px;
    height: 88px;
    border-radius: 8px;
    object-fit: cover;
}

.image-preview--empty {
    display: grid;
    place-items: center;
    border: 1px solid #edeeef;
    background: #f8f9fa;
    color: #454652;
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

    .catalog-panel__actions {
        width: 100%;
        justify-content: space-between;
    }

    .tabs {
        width: 100%;
        overflow-x: auto;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}

.catalog-layout {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.filter-panel {
    display: grid;
    gap: 12px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    background: #ffffff;
    padding: 14px;
}

.filter-panel__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.filter-panel__head strong {
    font-size: 14px;
}

.filter-panel label > span {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 800;
    color: #454652;
}

.filter-panel input,
.filter-panel select {
    width: 100%;
    min-height: 38px;
    border: 1px solid #c6c5d4;
    border-radius: 8px;
    padding: 8px 10px;
    font: inherit;
    background: #fff;
}

.filter-panel select[multiple] {
    min-height: 100px;
}

.filter-panel .secondary-action {
    width: 100%;
    justify-content: center;
}

.filter-info {
    padding: 10px 14px;
    font-size: 13px;
    color: #5c5f66;
    border-bottom: 1px solid #edeeef;
}

.modal--confirm .modal__body { padding: 18px; }
.modal--confirm .modal__body p { margin: 0; font-size: 15px; line-height: 1.5; }
.primary-action--danger { background: #ba1a1a; }

@media (max-width: 900px) {
    .catalog-layout {
        grid-template-columns: 1fr;
    }
}
</style>
