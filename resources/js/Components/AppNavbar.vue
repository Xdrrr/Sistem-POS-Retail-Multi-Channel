<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    serverTime: {
        type: String,
        default: '',
    },
    showShift: {
        type: Boolean,
        default: true,
    },
    showTime: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);
const displayName = computed(() => authUser.value?.detail?.full_name || authUser.value?.username || 'User');
const displayRole = computed(() => authUser.value?.role || 'Staff');

const currentTime = ref(props.serverTime);
const displayTime = computed(() => currentTime.value);

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
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const ampm = now.getHours() >= 12 ? 'PM' : 'AM';

    currentTime.value = `${dayName}, ${day} ${month} ${year} at ${hours}:${minutes}:${seconds} ${ampm}`;
};

onMounted(() => {
    updateTime();
    const interval = setInterval(updateTime, 1000);

    onUnmounted(() => clearInterval(interval));
});
</script>

<template>
    <header class="top-bar">
        <div class="top-bar__left">
            <slot name="left">
                <div class="brand">RetailPOS</div>
            </slot>
        </div>

        <div class="top-bar__right">
            <slot name="actions"></slot>

            <div v-if="showTime" class="current-time">
                <span class="material-symbols-outlined">schedule</span>
                <span>{{ displayTime }}</span>
            </div>

            <div class="top-bar__divider"></div>

            <div v-if="showShift" class="shift-pill">
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

            <Link class="icon-button" aria-label="Logout" href="/logout" method="post" as="button">
                <span class="material-symbols-outlined">logout</span>
            </Link>
        </div>
    </header>
</template>

<style scoped>
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
.current-time,
.shift-pill,
.profile {
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
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #ffffff;
    color: #454652;
    font-size: 14px;
    font-weight: 600;
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

.shift-pill span:first-child {
    font-size: 18px;
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

.icon-button {
    display: grid;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    place-items: center;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #454652;
    font: inherit;
    text-decoration: none;
    transition: background-color 160ms ease;
    cursor: pointer;
}

.icon-button:hover {
    background: #edeeef;
}

@media (max-width: 900px) {
    .current-time,
    .top-bar__divider,
    .profile > div:first-child {
        display: none;
    }

    .brand {
        font-size: 24px;
    }
}

@media (max-width: 720px) {
    .top-bar {
        left: 0;
        width: 100%;
    }

    .top-bar__right {
        gap: 2px;
    }

    .shift-pill {
        display: none;
    }
}
</style>
