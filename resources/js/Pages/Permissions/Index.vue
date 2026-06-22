<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppNavbar from '../../Components/AppNavbar.vue';
import AppSidebar from '../../Components/AppSidebar.vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
    role_permissions: { type: Object, default: () => ({}) },
    serverTime: { type: String, default: '' },
});

const activeRole = ref(null);
const saving = ref(false);

const selectedPerms = reactive({});

const selectRole = (role) => {
    activeRole.value = role;
    Object.keys(selectedPerms).forEach((key) => { delete selectedPerms[key]; });
    const current = props.role_permissions[role.guid] || [];
    current.forEach((name) => { selectedPerms[name] = true; });
};

const grouped = computed(() => {
    const groups = {};
    props.permissions.forEach((g) => {
        if (!groups[g.group]) groups[g.group] = { group: g.group, type: g.type, items: [] };
        groups[g.group].type = g.type;
        groups[g.group].items.push(...g.items);
    });
    return Object.values(groups);
});

const selectedCount = computed(() => Object.keys(selectedPerms).length);

const toggleGroup = (group, value) => {
    group.items.forEach((p) => {
        if (value) { selectedPerms[p.name] = true; }
        else { delete selectedPerms[p.name]; }
    });
};

const isGroupAllSelected = (group) => group.items.every((p) => selectedPerms[p.name]);

const savePermissions = async () => {
    if (!activeRole.value) return;
    saving.value = true;

    const names = Object.keys(selectedPerms).filter((k) => selectedPerms[k]);

    router.put(`/permissions/role/${activeRole.value.guid}`, { permissions: names }, {
        preserveScroll: true,
        preserveState: false,
        onFinish: () => { saving.value = false; },
    });
};

const form = useForm({});
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
                    <h1>Permission Roles</h1>
                    <p>Atur hak akses setiap role ke menu dan API.</p>
                </div>
                <div class="page-actions">
                    <Link class="secondary-action" href="/roles">
                        <span class="material-symbols-outlined">security</span>
                        Kelola Role
                    </Link>
                    <Link class="secondary-action" href="/users">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Kembali ke Users
                    </Link>
                </div>
            </section>

            <section class="perm-layout">
                <aside class="role-list">
                    <div class="role-list__head"><strong>Pilih Role</strong></div>
                    <button
                        v-for="role in props.roles"
                        :key="role.guid"
                        class="role-item"
                        :class="{ 'role-item--active': activeRole?.guid === role.guid }"
                        @click="selectRole(role)"
                    >
                        <span class="material-symbols-outlined">badge</span>
                        {{ role.name }}
                    </button>
                </aside>

                <section class="perm-panel">
                    <template v-if="!activeRole">
                        <div class="empty-state">Pilih role di samping untuk mengatur permission.</div>
                    </template>
                    <template v-else>
                        <div class="perm-panel__head">
                            <div>
                                <h2>{{ activeRole.name }}</h2>
                                <p>{{ selectedCount }} permission dipilih</p>
                            </div>
                            <button class="primary-action" type="button" :disabled="saving" @click="savePermissions">
                                <span class="material-symbols-outlined fill">save</span>
                                {{ saving ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>

                        <div class="perm-groups">
                            <div v-for="group in grouped" :key="group.group" class="perm-group">
                                <div class="perm-group__head">
                                    <strong>{{ group.group }}</strong>
                                    <span class="perm-type">{{ group.type }}</span>
                                    <button class="toggle-btn" type="button" @click="toggleGroup(group, !isGroupAllSelected(group))">
                                        {{ isGroupAllSelected(group) ? 'Uncheck All' : 'Check All' }}
                                    </button>
                                </div>
                                <div class="perm-items">
                                    <label
                                        v-for="item in group.items"
                                        :key="item.guid"
                                        class="perm-item"
                                        :class="{ 'perm-item--checked': selectedPerms[item.name] }"
                                    >
                                        <input v-model="selectedPerms[item.name]" type="checkbox" :true-value="true" :false-value="undefined" />
                                        <span class="perm-item__name">{{ item.display_name }}</span>
                                        <code class="perm-item__code">{{ item.name }}</code>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </template>
                </section>
            </section>
        </main>
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
.content { height: calc(100vh - 64px); margin-left: 80px; margin-top: 64px; overflow-y: auto; padding: 24px; }
.page-title { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; width: min(100%, 1360px); margin-inline: auto; }
.page-title h1 { margin: 0; font-size: 32px; font-weight: 700; }
.page-title p { margin: 3px 0 0; color: #454652; }
.page-actions { display: flex; gap: 8px; flex-shrink: 0; }
.primary-action, .secondary-action { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; border-radius: 8px; padding: 0 14px; font-weight: 800; text-decoration: none; }
.primary-action { background: #1b6d24; color: #fff; }
.secondary-action { border: 1px solid #c6c5d4; background: #fff; color: #000666; }
.perm-layout { display: grid; grid-template-columns: 200px minmax(0, 1fr); gap: 16px; align-items: start; width: min(100%, 1360px); margin-inline: auto; }
.role-list { border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; overflow: hidden; }
.role-list__head { border-bottom: 1px solid #c6c5d4; padding: 14px; font-size: 12px; color: #454652; text-transform: uppercase; letter-spacing: .04em; }
.role-item { display: flex; width: 100%; align-items: center; gap: 8px; padding: 12px 14px; background: #fff; color: #191c1d; font-weight: 600; font-size: 13px; transition: background 120ms; }
.role-item:hover { background: #f3f4f6; }
.role-item--active { background: #eef2ff; color: #1a237e; }
.role-item .material-symbols-outlined { font-size: 18px; }
.perm-panel { border: 1px solid #c6c5d4; border-radius: 8px; background: #fff; overflow: hidden; }
.perm-panel__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid #c6c5d4; padding: 14px; }
.perm-panel h2 { margin: 0; font-size: 18px; }
.perm-panel p { margin: 3px 0 0; color: #454652; font-size: 13px; }
.perm-groups { padding: 14px; display: grid; gap: 16px; }
.perm-group { border: 1px solid #edeeef; border-radius: 8px; overflow: hidden; }
.perm-group__head { display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f9fafb; border-bottom: 1px solid #edeeef; font-size: 13px; text-transform: capitalize; }
.perm-group__head strong { flex: 1; }
.perm-type { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: #e7e8e9; color: #454652; text-transform: uppercase; }
.toggle-btn { font-size: 11px; font-weight: 700; color: #1a237e; background: none; padding: 2px 8px; border-radius: 4px; }
.toggle-btn:hover { background: #eef2ff; }
.perm-items { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2px; padding: 6px; }
.perm-item { display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 13px; transition: background 120ms; }
.perm-item:hover { background: #f3f4f6; }
.perm-item--checked { background: #f0fdf4; }
.perm-item input { width: 16px; height: 16px; accent-color: #1b6d24; cursor: pointer; flex-shrink: 0; }
.perm-item__name { font-weight: 600; }
.perm-item__code { font-size: 11px; color: #5d6268; margin-left: auto; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
.empty-state { padding: 48px; color: #5d6268; text-align: center; }
@media (max-width: 1000px) { .perm-layout { grid-template-columns: 1fr; } .perm-items { grid-template-columns: 1fr; } }
@media (max-width: 720px) { :global(body) { overflow: auto; } .content { height: auto; margin-left: 0; padding: 18px 14px 92px; } .brand { font-size: 24px; } .page-title { align-items: stretch; flex-direction: column; } .page-actions { align-self: flex-start; } }
</style>
