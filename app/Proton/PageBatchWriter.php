<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton Distributor
//---------------------------------------------------------------------------------
class PageBatchWriter extends PageWriter
{
    protected array $batchkey;

    public function __construct(Page $page, \Twig\Environment $twig, Config $config)
    {
        $this->config = $config;
        $this->page   = $page;
        $this->twig   = $twig;
    }

    public function processBatch(): void
    {
        $batchkey = $this->page->data[Page::BATCHKEY];
        foreach ($this->page->data[$batchkey] as $key => $data) {
            $this->output = $this->render($data);
            $this->formatOutput();

            $this->path = $this->buildPagePath($batchkey);
            $this->savePage();
        }
    }
}
