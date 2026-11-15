<?php

namespace App\Console\Commands;

use App\Enums\OutageStatus;
use App\Models\Outage;
use Illuminate\Console\Command;

class ExpireOutagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outages:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transition overdue active outages to expired status';

    /**
     * Execute the console command.
     */
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
