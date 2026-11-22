export function fmt(minor, currency = 'EGP') {
    const v = (minor ?? 0) / 100;
    return new Intl.NumberFormat('ar-EG', { style: 'currency', currency, maximumFractionDigits: 2 }).format(v);
}
export const statusAr = {
    scheduled: 'مجدول', confirmed: 'مؤكد', checked_in: 'وصل', completed: 'تم',
    cancelled: 'ملغي', no_show: 'لم يحضر',
    draft: 'مسودة', issued: 'صادرة', partially_paid: 'مدفوعة جزئيًا', paid: 'مدفوعة', void: 'ملغاة',
    proposed: 'مقترحة', accepted: 'مقبولة', in_progress: 'قيد التنفيذ',
    rejected: 'مرفوضة',
};
