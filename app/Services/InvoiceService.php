<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    /** Recompute subtotal/discount/tax/total from items. */
    public function recalcTotals(Invoice $invoice): void
    {
        $subtotal = 0;
        $discount = 0;
        foreach ($invoice->items as $item) {
            $subtotal += $item->unit_price_minor * $item->qty;
            $discount += $item->discount_minor;
        }
        $taxable = $subtotal - $discount;
        $tax = (int) intdiv($taxable * $invoice->tax_rate_bp + 5000, 10000); // round half up

        $invoice->forceFill([
            'subtotal_minor' => $subtotal,
            'discount_minor' => $discount,
            'tax_minor' => $tax,
            'total_minor' => $taxable + $tax,
        ])->save();
    }

    /** Issue a draft invoice: allocate number, stamp time. */
    public function issue(Invoice $invoice): void
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => ['لا يمكن إصدار فاتورة ليست مسودة.']]);
        }
        $invoice->number = $invoice->clinic->allocateInvoiceNumber();
        $invoice->status = 'issued';
        $invoice->issued_at = now();
        $invoice->save();

        AuditLog::create([
            'clinic_id' => $invoice->clinic_id,
            'user_id' => auth()->id(),
            'action' => 'invoice_issued',
            'auditable_type' => $invoice->getMorphClass(),
            'auditable_id' => $invoice->id,
            'changes' => ['number' => $invoice->number, 'total_minor' => $invoice->total_minor],
            'ip' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
        ]);
    }

    /** Record a payment (installment) and update cached paid/status. */
    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        if (! in_array($invoice->status, ['issued', 'partially_paid'], true)) {
            throw ValidationException::withMessages(['invoice' => ['لا يمكن الدفع على فاتورة بحالة '.$invoice->status]]);
        }
        $amount = (int) $data['amount_minor'];
        if ($amount <= 0 || $amount > $invoice->balanceMinor()) {
            throw ValidationException::withMessages(['amount_minor' => ['مبلغ الدفعة غير صالح أو أكبر من المتبقي.']]);
        }

        return DB::transaction(function () use ($invoice, $data, $amount) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'amount_minor' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'received_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->paid_minor = $invoice->payments()->sum('amount_minor');
            $invoice->status = $invoice->paid_minor >= $invoice->total_minor ? 'paid' : 'partially_paid';
            $invoice->save();

            return $payment;
        });
    }
}
