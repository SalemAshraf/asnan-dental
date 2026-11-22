import { intlLocale } from '@/i18n';

export function fmt(minor, currency = 'EGP') {
    const v = (minor ?? 0) / 100;
    return new Intl.NumberFormat(intlLocale(), { style: 'currency', currency, maximumFractionDigits: 2 }).format(v);
}
