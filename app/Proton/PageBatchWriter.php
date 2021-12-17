<?php

namespace App\Proton;

//---------------------------------------------------------------------------------
// Proton Distributor
//---------------------------------------------------------------------------------
class PageBatchWriter extends PageWriter
{
    protected string $batchkey;

    public function __construct(Page $page, \Twig\Environment $twig, Config $config)
    {
        $this->config = $config;
        $this->page   = $page;
        $this->twig   = $twig;
    }

    public function processBatch(): void
    {
        $batchkey = $this->page->data[Page::BATCHKEY];
        $batchData = $this->page->data[$batchkey];
        foreach ($batchData as $key => $props) {
            // merge batch data into the global data batch key
            $data = $this->page->data;
            $data['batch'] = $props;

            $this->output = $this->render($data);
            $this->formatOutput();

            $this->batchkey = $key;
            $this->path = $this->buildPagePath();
            $this->savePage();
        }
    }

    protected function buildPagePath(): string
    {
        $filePath = [];

        // Directory
        if ($this->page->dirname) {
            array_push($filePath, $this->page->dirname);
        }

        // Filename
        array_push($filePath, $this->batchkey);

        // Auto Index
        if ($this->config->settings->autoindex) {
            array_push($filePath, "index");
        }

        // Extension
        $ext = $this->findExtension();

        return implode(DIRECTORY_SEPARATOR, $filePath).".$ext";
    }
}
