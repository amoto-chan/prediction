import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;
Chart.defaults.font.family = 'DM Sans, ui-sans-serif, system-ui, sans-serif';

/**
 * Shared root component for every page (landing, login, authenticated shell).
 * The clock is rendered in the campus timezone and corrected against the
 * server timestamp so client/server views remain aligned.
 */
Alpine.data('shell', () => ({
    dark: false,
    sidebar: true,
    collapsed: false,
    now: '',
    serverOffset: 0,

    init() {
        this.dark = this.readStorage('theme') === 'dark';
        this.sidebar = window.innerWidth >= 1024;
        this.collapsed = this.readStorage('sidebar-collapsed') === '1';
        document.documentElement.classList.toggle('dark', this.dark);

        const serverTime = document.querySelector('meta[name="server-time"]')?.content;
        if (serverTime) {
            const parsed = Date.parse(serverTime);
            if (!Number.isNaN(parsed)) this.serverOffset = parsed - Date.now();
        }

        this.tick();
        this.timer = window.setInterval(() => this.tick(), 1000);

        let wasDesktop = window.innerWidth >= 1024;
        window.addEventListener('resize', () => {
            const isDesktop = window.innerWidth >= 1024;
            if (wasDesktop && !isDesktop) this.sidebar = false;
            if (!wasDesktop && isDesktop) this.sidebar = true;
            wasDesktop = isDesktop;
        });
    },

    tick() {
        const date = new Date(Date.now() + this.serverOffset);
        const formattedDate = new Intl.DateTimeFormat('en-PH', {
            timeZone: 'Asia/Manila',
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(date);
        const formattedTime = new Intl.DateTimeFormat('en-PH', {
            timeZone: 'Asia/Manila',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
        }).format(date);

        this.now = `${formattedDate} • ${formattedTime}`;
    },

    readStorage(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },

    writeStorage(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Storage can be disabled by a browser privacy policy.
        }
    },

    toggleTheme() {
        this.dark = !this.dark;
        this.writeStorage('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    },

    toggleSidebar() {
        this.sidebar = !this.sidebar;
    },

    toggleCollapsed() {
        this.collapsed = !this.collapsed;
        this.writeStorage('sidebar-collapsed', this.collapsed ? '1' : '0');
    },
}));

Alpine.start();
