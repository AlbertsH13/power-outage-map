<?php

namespace App\Console\Commands;

use App\Enums\OutageStatus;
use App\Models\Outage;
use Illuminate\Console\Command;

class ExpireOutagesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'outages:expire';

    /**
     * @var string
     */
    protected $description = 'Transition overdue active outages to expired status';

    public function handle(): int
    {
        $count = Outage::query()
            ->where('status', OutageStatus::Active)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => OutageStatus::Expired,
            ]);

        $this->info("Expired {$count} overdue outage reports.");

        return self::SUCCESS;
    }
}
