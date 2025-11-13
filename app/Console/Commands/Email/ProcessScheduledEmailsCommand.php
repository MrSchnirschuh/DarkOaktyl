<?php

namespace Everest\Console\Commands\Email;

use Everest\Jobs\ProcessScheduledEmails;
use Illuminate\Console\Command;

class ProcessScheduledEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'p:email:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled emails that are due to be sent';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing scheduled emails...');

        ProcessScheduledEmails::dispatch();

        $this->info('Scheduled emails have been queued for processing.');

        return self::SUCCESS;
    }
}
