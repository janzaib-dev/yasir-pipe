<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Model;

class JournalEntryService
{
    /**
     * Create a Journal Entry and update Account Balance.
     *
     * @param  Model  $source  The source model (VoucherMaster, Sale, etc.)
     * @param  string  $date  (Y-m-d)
     * @param  Model|null  $party  (Optional Customer/Vendor model)
     */
    public function recordEntry(Model $source, int $accountId, float $debit, float $credit, ?string $description, string $date, ?Model $party = null)
    {
        // 1. Create Journal Entry
        $data = [
            'source_type' => get_class($source),
            'source_id' => $source->id,
            'account_id' => $accountId,
            'entry_date' => $date,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description,
        ];

        if ($party) {
            $data['party_type'] = get_class($party);
            $data['party_id'] = $party->id;
        }

        $entry = JournalEntry::create($data);

        // 2. Update Account Balance
        $this->updateAccountBalance($accountId, $debit, $credit);

        return $entry;
    }

    /**
     * Update the real-time balance on the Account model.
     * Assets/Expenses: Dr increases, Cr decreases.
     * Liabilities/Equity/Income: Cr increases, Dr decreases.
     *
     * @param  int  $accountId
     * @param  float  $debit
     * @param  float  $credit
     */
    private function updateAccountBalance(/** @var int */ $accountId, /** @var float */ $debit, /** @var float */ $credit)
    {
        $account = Account::find($accountId);
        if (! $account) {
            return;
        }

        // Determine normal balance side based on Head Type
        // If Head Type is not yet migrated, assume default based on name or similar logic.
        // For now, let's use a simplified approach:
        // current_balance represents the "net" (Debit - Credit) or similar.
        // Actually, standards usually keep a signed balance or a Dr/Cr indicator.
        // Let's adopt: Positive = Debit Balance, Negative = Credit Balance.

        $netChange = $debit - $credit;
        $currentBalance = $account->current_balance ?? 0;
        $account->current_balance = $currentBalance + $netChange;
        $account->save();
    }
}
