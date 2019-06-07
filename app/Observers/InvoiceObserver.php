<?php

namespace FI\Observers;

use FI\Modules\Currencies\Support\CurrencyConverterFactory;
use FI\Modules\CustomFields\Models\InvoiceCustom;
use FI\Modules\Expenses\Models\Expense;
use FI\Modules\Groups\Models\Group;
use FI\Modules\Invoices\Models\Invoice;
use FI\Modules\Invoices\Support\InvoiceCalculate;
use FI\Modules\Quotes\Models\Quote;
use FI\Support\DateFormatter;
use FI\Support\Statuses\InvoiceStatuses;

class InvoiceObserver
{
    private $invoiceCalculate;

    public function __construct(InvoiceCalculate $invoiceCalculate)
    {
        $this->invoiceCalculate = $invoiceCalculate;
    }
    /**
     * Handle the invoice "created" event.
     *
     * @param  \FI\Modules\Invoices\Models\Invoice  $invoice
     * @return void
     */
    public function created(Invoice $invoice): void
    {
        // Create the empty invoice amount record.
        $this->invoiceCalculate->calculate($invoice);

        // Increment the next id.
        Group::incrementNextId($invoice);

        // Create the custom invoice record.
        $invoice->custom()->save(new InvoiceCustom());
    }

    /**
     * Handle the invoice "creating" event.
     *
     * @param  \FI\Modules\Invoices\Models\Invoice  $invoice
     * @return void
     */
    public function creating(Invoice $invoice): void
    {
        if (!$invoice->client_id)
        {
            // This needs to throw an exception since this is required.
        }

        if (!$invoice->user_id)
        {
            $invoice->user_id = auth()->user()->id;
        }

        if (!$invoice->invoice_date)
        {
            $invoice->invoice_date = date('Y-m-d');
        }

        if (!$invoice->due_at)
        {
            $invoice->due_at = DateFormatter::incrementDateByDays($invoice->invoice_date->format('Y-m-d'), $invoice->client->client_terms);
        }

        if (!$invoice->company_profile_id)
        {
            $invoice->company_profile_id = config('fi.defaultCompanyProfile');
        }

        if (!$invoice->group_id)
        {
            $invoice->group_id = config('fi.invoiceGroup');
        }

        if (!$invoice->number)
        {
            $invoice->number = Group::generateNumber($invoice->group_id);
        }

        if (!isset($invoice->terms))
        {
            $invoice->terms = config('fi.invoiceTerms');
        }

        if (!isset($invoice->footer))
        {
            $invoice->footer = config('fi.invoiceFooter');
        }

        if (!$invoice->invoice_status_id)
        {
            $invoice->invoice_status_id = InvoiceStatuses::getStatusId('draft');
        }

        if (!$invoice->currency_code)
        {
            $invoice->currency_code = $invoice->client->currency_code;
        }

        if (!$invoice->template)
        {
            $invoice->template = $invoice->companyProfile->invoice_template;
        }

        if ($invoice->currency_code == config('fi.baseCurrency'))
        {
            $invoice->exchange_rate = 1;
        }
        elseif (!$invoice->exchange_rate)
        {
            $currencyConverter      = CurrencyConverterFactory::create();
            $invoice->exchange_rate = $currencyConverter->convert(config('fi.baseCurrency'), $invoice->currency_code);
        }

        $invoice->url_key = str_random(32);
    }

    /**
     * Handle the invoice "deleted" event.
     *
     * @param  \FI\Modules\Invoices\Models\Invoice  $invoice
     * @return void
     */
    public function deleteing(Invoice $invoice): void
    {
        foreach ($invoice->activities as $activity)
        {
            ($invoice->isForceDeleting()) ? $activity->onlyTrashed()->forceDelete() : $activity->delete();
        }

        foreach ($invoice->attachments as $attachment)
        {
            ($invoice->isForceDeleting()) ? $attachment->onlyTrashed()->forceDelete() : $attachment->delete();
        }

        foreach ($invoice->mailQueue as $mailQueue)
        {
            ($invoice->isForceDeleting()) ? $mailQueue->onlyTrashed()->forceDelete() : $mailQueue->delete();
        }

        foreach ($invoice->notes as $note)
        {
            ($invoice->isForceDeleting()) ? $note->onlyTrashed()->forceDelete() : $note->delete();
        }

        Quote::where('invoice_id', $invoice->id)->update(['invoice_id' => 0]);

        Expense::where('invoice_id', $invoice->id)->update(['invoice_id' => 0]);

        $group = Group::where('id', $invoice->group_id)
            ->where('last_number', $invoice->number)
            ->first();

        if ($group)
        {
            $group->next_id = $group->next_id - 1;
            $group->save();
        }
    }

}
