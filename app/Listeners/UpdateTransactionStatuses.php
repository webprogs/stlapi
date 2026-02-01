<?php

namespace App\Listeners;

use App\Events\DrawResultCreated;
use App\Models\Transaction;

class UpdateTransactionStatuses
{
    public function handle(DrawResultCreated $event): void
    {
        $drawResult = $event->drawResult;

        // Get all pending transactions for this draw
        $transactions = Transaction::where('draw_date', $drawResult->draw_date)
            ->where('draw_time', $drawResult->draw_time)
            ->where('game_type', $drawResult->game_type)
            ->where('status', 'pending')
            ->get();

        foreach ($transactions as $transaction) {
            if ($this->isWinningTransaction($transaction, $drawResult->winning_numbers)) {
                $transaction->markAsWon();
            } else {
                $transaction->markAsLost();
            }
        }
    }

    private function isWinningTransaction(Transaction $transaction, array $winningNumbers): bool
    {
        $betNumbers = $transaction->numbers;

        // Sort both arrays for comparison
        sort($betNumbers);
        sort($winningNumbers);

        return $betNumbers === $winningNumbers;
    }
}
