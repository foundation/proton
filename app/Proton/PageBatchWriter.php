<?php

namespace App\Proton;

// ---------------------------------------------------------------------------------
// Proton Distributor
// ---------------------------------------------------------------------------------
class PageBatchWriter extends PageWriter
{
    protected string $batchkey;

    public function __construct(Page $page, \Twig\Environment $twig, Config $config)
    {
        $this->config = $config;
        $this->page   = $page;
        $this->twig   = $twig;
    }

    /** @var string[] Fields computed by Page::computeMetadata that must be rebuilt per batch item */
    private const COMPUTED_FIELDS = [
        'url', 'canonical', 'title', 'filename', 'dirname', 'ext',
        'path', 'outputPath', 'depth', 'isIndex', 'parent', 'slug',
    ];

    public function processBatch(): void
    {
        $batchkey  = $this->page->getPageData(Page::BATCHKEY);
        $batchData = $this->page->getData($batchkey);
        foreach ($batchData as $key => $props) {
            $this->batchkey = (string) $key;

            // merge batch data into the global data batch key
            $data          = $this->page->data;
            $data['batch'] = $props;

            // Strip all computed fields so they get rebuilt for this batch key
            $frontmatter = $data['page'];
            foreach (self::COMPUTED_FIELDS as $field) {
                unset($frontmatter[$field]);
            }

            // Recompute metadata using batch key as the filename
            $computed = Page::computeMetadata(
                $this->page->name,
                $this->batchkey,
                $this->page->dirname,
                $this->page->ext,
                $frontmatter,
                $this->config,
            );

            // Merge: computed values first, then frontmatter overrides
            $data['page'] = array_merge($computed, $frontmatter);

            $this->output = $this->render($data);
            $this->formatOutput();

            $this->path = $this->buildPagePath();
            $this->savePage();
        }
    }

    #[\Override]
    protected function buildPagePath(): string
    {
        $filePath = [];

        // Directory
        if ($this->page->dirname) {
            $filePath[] = $this->page->dirname;
        }

        // Filename
        $filePath[] = $this->batchkey;

        // Auto Index (skip if batch key is already "index")
        if ($this->config->settings->autoindex && $this->batchkey !== 'index') {
            $filePath[] = 'index';
        }

        // Extension
        $ext = $this->findExtension();

        return implode(DIRECTORY_SEPARATOR, $filePath) . ".$ext";
    }
}
