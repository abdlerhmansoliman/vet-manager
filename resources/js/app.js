import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { i18nVue, getActiveLanguage, loadLanguageAsync } from 'laravel-vue-i18n';

createInertiaApp({
    title: (title) => (title ? `${title} — VetManager Clinic` : 'VetManager Clinic'),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        const initialLocale = props.initialPage.props.locale || 'en';
        const initialDir = props.initialPage.props.dir || (initialLocale === 'ar' ? 'rtl' : 'ltr');
        if (typeof document !== 'undefined') {
            document.documentElement.setAttribute('dir', initialDir);
            document.documentElement.setAttribute('lang', initialLocale);
        }

        router.on('navigate', (event) => {
            const locale = event.detail.page.props.locale || 'en';
            const dir = event.detail.page.props.dir || (locale === 'ar' ? 'rtl' : 'ltr');
            if (typeof document !== 'undefined') {
                document.documentElement.setAttribute('dir', dir);
                document.documentElement.setAttribute('lang', locale);
            }
            if (getActiveLanguage() !== locale) {
                loadLanguageAsync(locale).catch((err) => console.error('Failed to load language chunk:', err));
            }
        });

        let mounted = false;
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nVue, {
                lang: initialLocale,
                resolve: async (lang) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    if (langs[`../../lang/${lang}.json`]) {
                        return await langs[`../../lang/${lang}.json`]();
                    }
                    return {};
                },
                onLoad: () => {
                    if (!mounted) {
                        vueApp.mount(el);
                        mounted = true;
                    }
                },
            });

        setTimeout(() => {
            if (!mounted) {
                vueApp.mount(el);
                mounted = true;
            }
        }, 100);
    },
    progress: {
        color: '#0D9488',
        showSpinner: false,
    },
});
