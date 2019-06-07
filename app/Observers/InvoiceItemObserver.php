<?php

namespace FI\Observers;

use FI\Events\InvoiceModified;
use FI\Modules\Invoices\Models\InvoiceItem;

class InvoiceItemObserver
{
    /**
     * Handle the invoice item "saving" event.
     *
     * @param  \FI\Modules\Invoices\Models\InvoiceItem  $invoiceItem
     * @return void
     */
    public function saving(InvoiceItem $invoiceItem): void
    {
        $item = $invoiceItem;

        $applyExchangeRate = $item->apply_exchange_rate;
        unset($item->apply_exchange_rate);

        if ($applyExchangeRate == true)
        {
            $item->price = $item->price * $item->invoice->exchange_rate;
        }

        if (!$item->display_order)
        {
            $displayOrder = InvoiceItem::where('invoice_id', $item->invoice_id)->max('display_order');

            $displayOrder++;

            $item->display_order = $displayOrder;
        }

        if (is_null($item->tax_rate_id))
        {
            $item->tax_rate_id = 0;
        }

        if (is_null($item->tax_rate_2_id))
        {
            $item->tax_rate_2_id = 0;
        }

        if (!$item->resource_id){
            $item->resource_id = 0;
        }

    }

    /**
     * Handle the invoice item "saved" event.
     *
     * @param  \FI\Modules\Invoices\Models\InvoiceItem  $invoiceItem
     * @return void
     */
    public function saved(InvoiceItem $invoiceItem): void
    {
        event(new InvoiceModified($invoiceItem->invoice));

    }

    /**
     * Handle the invoice item "deleted" event.
     *
     * @param  \FI\Modules\Invoices\Models\InvoiceItem  $invoiceItem
     * @return void
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        if ($invoiceItem->invoice)
        {
            event(new InvoiceModified($invoiceItem->invoice));
        }
    }

}
