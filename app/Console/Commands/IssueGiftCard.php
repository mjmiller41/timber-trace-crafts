<?php

namespace App\Console\Commands;

use App\Services\GiftCardService;
use Illuminate\Console\Command;

class IssueGiftCard extends Command
{
    protected $signature = 'giftcard:issue
        {amount : Starting balance in dollars (e.g. 50 or 25.00)}
        {--recipient-email= : Recipient email address}
        {--recipient-name= : Recipient name}
        {--message= : Gift message}
        {--expires= : Expiry date (Y-m-d), optional}';

    protected $description = 'Issue a new store gift card and print its code.';

    public function handle(GiftCardService $service): int
    {
        $amount = (float) $this->argument('amount');

        if ($amount <= 0) {
            $this->error('Amount must be greater than zero.');

            return self::FAILURE;
        }

        $attributes = array_filter([
            'recipient_email' => $this->option('recipient-email'),
            'recipient_name' => $this->option('recipient-name'),
            'message' => $this->option('message'),
            'expires_at' => $this->option('expires'),
        ], fn ($v) => $v !== null && $v !== '');

        $card = $service->issue($amount, $attributes);

        $this->info('Gift card issued.');
        $this->line("  Code:    {$card->code}");
        $this->line('  Balance: $'.number_format((float) $card->balance, 2));

        if ($card->expires_at) {
            $this->line('  Expires: '.$card->expires_at->toDateString());
        }

        return self::SUCCESS;
    }
}
