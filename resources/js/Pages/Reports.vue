<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Kpi from '@/Components/Kpi.vue';
import Badge from '@/Components/Badge.vue';
import BarChart from '@/Components/BarChart.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { fmt, statusAr } from '@/money';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ range: Object, currency: String, cards: Object, byMethod: Object, byStatus: Object, perDentist: Array, topServices: Array, daily: Array });
const money = (v) => fmt(v, props.currency);
const methodAr = { cash: 'نقدي', card: 'بطاقة', instapay: 'إنستاباي', bank_transfer: 'تحويل بنكي', wallet: 'محفظة إلكترونية' };
const from = ref(props.range.from);
const to = ref(props.range.to);
const apply = () => router.get('/reports', { from: from.value, to: to.value }, { preserveState: true });
const preset = (days) => {
    const t = new Date(); const f = new Date(); f.setDate(f.getDate() - (days - 1));
    from.value = f.toISOString().slice(0, 10); to.value = t.toISOString().slice(0, 10); apply();
};
const methodMax = Math.max(1, ...Object.values(props.byMethod));
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">التقارير</h1>
                <p class="text-sm text-slate-500 tabular-nums" dir="ltr">{{ range.from }} → {{ range.to }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <button @click="preset(30)" class="btn-secondary btn-sm">٣٠ يوم</button>
                <button @click="preset(90)" class="btn-secondary btn-sm">٩٠ يوم</button>
                <input v-model="from" type="date" class="input !w-auto !py-1.5" aria-label="من">
                <input v-model="to" type="date" class="input !w-auto !py-1.5" aria-label="إلى">
                <button @click="apply" class="btn-primary btn-sm !py-2">تطبيق</button>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <Kpi label="المحصل في الفترة" :value="money(cards.collected_minor)" icon="cash" tone="good" />
            <Kpi label="المصدر في الفترة" :value="money(cards.invoiced_minor)" icon="invoices" />
            <Kpi label="مستحقات متبقية (حالية)" :value="money(cards.outstanding_minor)" icon="invoices" :tone="cards.outstanding_minor > 0 ? 'bad' : 'slate'" />
            <Kpi label="مرضى جدد" :value="cards.new_patients" icon="patients" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="card lg:col-span-2">
                <h2 class="mb-4 font-bold text-ink-900">المحصل مقابل المصدر خلال الفترة</h2>
                <EmptyState v-if="!cards.collected_minor" icon="reports" title="لا توجد تحصيلات في الفترة" />
                <BarChart v-else :rows="daily" label-a="محصل" label-b="مصدر" :format="money" />
            </div>

            <div class="card">
                <h2 class="mb-4 font-bold text-ink-900">التحصيل حسب طريقة الدفع</h2>
                <EmptyState v-if="!cards.collected_minor" icon="cash" title="لا توجد دفعات" />
                <div v-else class="space-y-2.5 text-sm">
                    <div v-for="(v, m) in byMethod" :key="m" class="flex items-center gap-3">
                        <span class="w-28 shrink-0 text-slate-600">{{ methodAr[m] ?? m }}</span>
                        <div class="h-4 flex-1 overflow-hidden rounded bg-slate-100">
                            <div class="h-full rounded bg-brand-600" :style="{ width: (v / methodMax * 100) + '%' }" />
                        </div>
                        <span class="w-24 shrink-0 text-end tabular-nums font-semibold">{{ money(v) }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 font-bold text-ink-900">المواعيد حسب الحالة</h2>
                <div class="flex flex-wrap gap-2">
                    <Badge v-for="(v, s) in byStatus" :key="s" :tone="{ scheduled: 'blue', confirmed: 'violet', checked_in: 'amber', completed: 'green', cancelled: 'slate', no_show: 'rose' }[s]">
                        {{ statusAr[s] }} · {{ v }}
                    </Badge>
                </div>
                <p class="mt-4 text-sm text-slate-500">نسبة الإتمام <span class="font-bold text-ink-900">{{ cards.completion_pct }}%</span> · عدم الحضور <span class="font-bold text-rose-700">{{ cards.no_show_pct }}%</span></p>
            </div>

            <div class="card">
                <h2 class="mb-3 font-bold text-ink-900">نشاط الأطباء</h2>
                <EmptyState v-if="!perDentist.length" icon="patients" title="لا توجد مواعيد في الفترة" />
                <table v-else class="w-full text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">الطبيب</th><th class="th">مواعيد</th><th class="th">تمت</th><th class="th">لم يحضر</th><th class="th !px-0">ملغي</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="d in perDentist" :key="d.dentist">
                            <td class="td !px-0 font-medium">{{ d.dentist }}</td>
                            <td class="td tabular-nums">{{ d.total }}</td>
                            <td class="td tabular-nums text-emerald-700">{{ d.completed }}</td>
                            <td class="td tabular-nums text-rose-700">{{ d.no_show }}</td>
                            <td class="td !px-0 tabular-nums text-slate-500">{{ d.cancelled }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="mb-3 font-bold text-ink-900">أكثر الخدمات في الخطط المقبولة</h2>
                <EmptyState v-if="!topServices.length" icon="services" title="لا توجد بنود خطط في الفترة" />
                <table v-else class="w-full text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">الخدمة</th><th class="th">عدد</th><th class="th !px-0 text-end">القيمة</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in topServices" :key="s.name">
                            <td class="td !px-0 font-medium">{{ s.name }}</td>
                            <td class="td tabular-nums">{{ s.count }}</td>
                            <td class="td !px-0 text-end tabular-nums font-semibold">{{ money(s.total_minor) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
