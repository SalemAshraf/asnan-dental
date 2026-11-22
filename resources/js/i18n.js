import { reactive } from 'vue';

// Lightweight UI-locale layer. Arabic stays the default; English is opt-in per browser.
// Clinical content and user-entered data are never translated.
export const messages = {
    ar: {
        'nav.groups.clinic': 'العيادة',
        'nav.groups.finance': 'المالية',
        'nav.groups.admin': 'الإدارة',
        'nav.dashboard': 'الرئيسية',
        'nav.patients': 'المرضى',
        'nav.appointments': 'المواعيد',
        'nav.recalls': 'المتابعات',
        'nav.services': 'الخدمات والأسعار',
        'nav.invoices': 'الفواتير',
        'nav.reports': 'التقارير',
        'nav.settings': 'الإعدادات',
        'nav.audit': 'سجل التدقيق',
        'nav.search': 'بحث سريع عن مريض بالاسم أو الهاتف…',
        'nav.collapse': 'طي القائمة',
        'nav.logout': 'تسجيل الخروج',
        'nav.menu': 'القائمة',
        'nav.close': 'إغلاق القائمة',
    },
    en: {
        'nav.groups.clinic': 'Clinic',
        'nav.groups.finance': 'Finance',
        'nav.groups.admin': 'Administration',
        'nav.dashboard': 'Dashboard',
        'nav.patients': 'Patients',
        'nav.appointments': 'Appointments',
        'nav.recalls': 'Recalls',
        'nav.services': 'Services & Prices',
        'nav.invoices': 'Invoices',
        'nav.reports': 'Reports',
        'nav.settings': 'Settings',
        'nav.audit': 'Audit Log',
        'nav.search': 'Quick patient search by name or phone…',
        'nav.collapse': 'Collapse sidebar',
        'nav.logout': 'Log out',
        'nav.menu': 'Menu',
        'nav.close': 'Close menu',
    },
};

export const i18n = reactive({ locale: (typeof localStorage !== 'undefined' && localStorage.getItem('locale')) || 'ar' });

function applyDir() {
    if (typeof document === 'undefined') return;
    document.documentElement.lang = i18n.locale;
    document.documentElement.dir = i18n.locale === 'ar' ? 'rtl' : 'ltr';
}

export function setLocale(locale) {
    if (!messages[locale]) return;
    i18n.locale = locale;
    localStorage.setItem('locale', locale);
    applyDir();
}

export function toggleLocale() {
    setLocale(i18n.locale === 'ar' ? 'en' : 'ar');
}

export function t(key) {
    return messages[i18n.locale][key] ?? messages.ar[key] ?? key;
}

applyDir();
