<?php

namespace App\Commands;

use App\Proton\Config;
use App\Proton\Page;
use LaravelZero\Framework\Commands\Command;

class Data extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'data

                            {--page= : The name of the page to dump data for (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Dump the data structure used during build';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $config = app(Config::class);
        $data   = app(\App\Proton\Data::class);

        $pageOption = $this->option('page');
        if (is_string($pageOption)) {
            $pageName = $pageOption;
            $this->info("Loading data for $pageName");
            $page = new Page($pageName, $config, $data);
            $page->dumpData();
        } else {
            $this->info('Loading global data');
            $data->dump();
        }
    }
}
