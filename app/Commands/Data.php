<?php

namespace App\Commands;

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
     *
     * @return mixed
     */
    public function handle()
    {
        $config = new \App\Proton\Config();
        $data = new \App\Proton\Data($config);

        // Create config file
        if ($this->option('page')) {
            $pageName = strval($this->option('page'));
            $this->info("Loading data for $pageName");
            $page = new \App\Proton\Page($pageName, $config, $data);
            $page->dumpData();
        } else {
            $this->info('Loading global data');
            $data->dump();
        }
    }
}
