<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Set counterpart_id to null for existing purchase transactions
     * related to handling fees and card fees accounting entries.
     */
    public function up(): void
    {
        // Get all handling_fees and card_fees accounting_entry_ids from settings
        $entryIds = DB::table('settings')
            ->whereNotNull('handling_fees_accounting_entry_id')
            ->orWhereNotNull('card_fees_accounting_entry_id')
            ->get(['handling_fees_accounting_entry_id', 'card_fees_accounting_entry_id'])
            ->flatMap(fn ($row) => array_filter([
                $row->handling_fees_accounting_entry_id,
                $row->card_fees_accounting_entry_id,
            ]))
            ->unique()
            ->values()
            ->all();

        if (!empty($entryIds)) {
            DB::table('accounting_transactions')
                ->where('transaction_type', 'purchase')
                ->whereIn('accounting_entry_id', $entryIds)
                ->whereNotNull('counterpart_id')
                ->update(['counterpart_id' => null]);
        }
    }

    /**
     * Reverse is not possible — we don't know the original counterpart_ids.
     */
    public function down(): void
    {
        // Cannot reverse: original counterpart_id values are unknown
    }
};
