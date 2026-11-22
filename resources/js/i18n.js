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
        'nav.newPatient': 'مريض جديد',
        'pat.title': 'المرضى',
        'pat.files': '{total} ملف مسجل',
        'pat.search': 'بحث بالاسم أو الهاتف أو رقم الملف…',
        'pat.empty': 'لا يوجد مرضى مطابقون',
        'pat.emptyHint': 'جرّب بحثًا مختلفًا أو أضف مريضًا جديدًا.',
        'pat.col.file': 'ملف',
        'pat.col.name': 'الاسم',
        'pat.col.phone': 'الهاتف',
        'pat.col.birth': 'تاريخ الميلاد',
        'pat.open': 'فتح الملف',
        'pat.form.new': 'مريض جديد',
        'pat.form.edit': 'تعديل بيانات المريض',
        'pat.form.fullName': 'الاسم الكامل *',
        'pat.form.phone': 'الهاتف *',
        'pat.form.whatsapp': 'واتساب',
        'pat.form.birth': 'تاريخ الميلاد',
        'pat.form.gender': 'النوع',
        'pat.form.male': 'ذكر',
        'pat.form.female': 'أنثى',
        'pat.form.nationalId': 'الرقم القومي',
        'pat.form.alerts': 'تنبيهات طبية (حساسيات، أمراض مزمنة) — افصل بفاصلة',
        'pat.form.notes': 'ملاحظات',
        'pat.form.save': 'حفظ',
        'dash.title': 'لوحة المتابعة',
        'dash.allToday': 'كل مواعيد اليوم',
        'dash.kpi.today': 'مواعيد اليوم',
        'dash.kpi.todayHint': '{active} نشطة · {done} تمت · {week} خلال ٧ أيام',
        'dash.kpi.collected': 'محصل هذا الشهر',
        'dash.kpi.outstanding': 'مستحقات متبقية',
        'dash.kpi.outstandingHint': 'فواتير صادرة غير مسددة',
        'dash.kpi.newPatients': 'مرضى جدد هذا الشهر',
        'dash.kpi.totalPatients': 'إجمالي المرضى',
        'dash.chart.title': 'المحصل مقابل المصدر — آخر ٦ شهور',
        'dash.chart.a': 'محصل',
        'dash.chart.b': 'مصدر',
        'dash.unpaid.title': 'أكبر مستحقات مفتوحة',
        'dash.unpaid.all': 'كل الفواتير',
        'dash.unpaid.empty': 'لا توجد مستحقات مفتوحة',
        'dash.unpaid.emptyHint': 'كل الفواتير الصادرة مسددة بالكامل.',
        'dash.schedule.title': 'جدول اليوم',
        'dash.schedule.count': '{active} نشط من أصل {total}',
        'dash.schedule.empty': 'لا توجد مواعيد اليوم',
        'dash.schedule.emptyHint': 'احجز موعدًا من صفحة المواعيد أو زر مريض جديد بالأعلى.',
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
        'nav.newPatient': 'New patient',
        'pat.title': 'Patients',
        'pat.files': '{total} files on record',
        'pat.search': 'Search by name, phone or file number…',
        'pat.empty': 'No matching patients',
        'pat.emptyHint': 'Try a different search or add a new patient.',
        'pat.col.file': 'File',
        'pat.col.name': 'Name',
        'pat.col.phone': 'Phone',
        'pat.col.birth': 'Birth date',
        'pat.open': 'Open file',
        'pat.form.new': 'New patient',
        'pat.form.edit': 'Edit patient details',
        'pat.form.fullName': 'Full name *',
        'pat.form.phone': 'Phone *',
        'pat.form.whatsapp': 'WhatsApp',
        'pat.form.birth': 'Birth date',
        'pat.form.gender': 'Gender',
        'pat.form.male': 'Male',
        'pat.form.female': 'Female',
        'pat.form.nationalId': 'National ID',
        'pat.form.alerts': 'Medical alerts (allergies, chronic conditions) — comma separated',
        'pat.form.notes': 'Notes',
        'pat.form.save': 'Save',
        'dash.title': 'Dashboard',
        'dash.allToday': "All of today's appointments",
        'dash.kpi.today': "Today's appointments",
        'dash.kpi.todayHint': '{active} active · {done} done · {week} in 7 days',
        'dash.kpi.collected': 'Collected this month',
        'dash.kpi.outstanding': 'Outstanding balances',
        'dash.kpi.outstandingHint': 'Issued unpaid invoices',
        'dash.kpi.newPatients': 'New patients this month',
        'dash.kpi.totalPatients': 'total patients',
        'dash.chart.title': 'Collected vs issued — last 6 months',
        'dash.chart.a': 'Collected',
        'dash.chart.b': 'Issued',
        'dash.unpaid.title': 'Largest open balances',
        'dash.unpaid.all': 'All invoices',
        'dash.unpaid.empty': 'No open balances',
        'dash.unpaid.emptyHint': 'All issued invoices are fully paid.',
        'dash.schedule.title': "Today's schedule",
        'dash.schedule.count': '{active} active of {total}',
        'dash.schedule.empty': 'No appointments today',
        'dash.schedule.emptyHint': 'Book one from the appointments page or the new patient button above.',
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

export function t(key, params) {
    let s = messages[i18n.locale][key] ?? messages.ar[key] ?? key;
    if (params) {
        for (const k of Object.keys(params)) s = s.replaceAll(`{${k}}`, params[k]);
    }
    return s;
}

applyDir();
