<?php

namespace App\Proton;

use Michelf\MarkdownExtra;
use Twig\Extra\Markdown\MarkdownInterface;

class MarkdownConverter implements MarkdownInterface
{
    private MarkdownExtra $converter;

    /** @var array<int, array{id: string, text: string, level: int}> */
    private array $toc = [];

    public function __construct()
    {
        $this->converter            = new MarkdownExtra();
        $this->converter->hard_wrap = true;

        $this->converter->header_id_func = function (string $heading): string {
            $slug = strtolower(trim($heading));
            $slug = str_replace(' ', '-', $slug);
            $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);

            // Ensure IDs don't start with a number
            if ($slug !== '' && ctype_digit($slug[0])) {
                $slug = 'h-' . $slug;
            }

            return $slug;
        };
    }

    public function convert(string $body): string
    {
        $this->toc = [];
        $html       = $this->converter->transform($body);

        // Extract h2 headings with their IDs for the table of contents
        preg_match_all('/<h2[^>]*\bid="([^"]*)"[^>]*>(.*?)<\/h2>/si', $html, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->toc[] = [
                'id'    => $match[1],
                'text'  => strip_tags($match[2]),
                'level' => 2,
            ];
        }

        return $html;
    }

    /**
     * @return array<int, array{id: string, text: string, level: int}>
     */
    public function getToc(): array
    {
        return $this->toc;
    }

    public function resetToc(): void
    {
        $this->toc = [];
    }
}
