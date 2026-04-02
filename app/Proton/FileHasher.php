<?php

namespace App\Proton;

class FileHasher
{
    public function __construct(protected string $assetsDir)
    {
    }

    public function hash(string $path): string
    {
        $fullPath = $this->resolve($path);
        if ($fullPath === null) {
            return '';
        }

        return substr(md5_file($fullPath) ?: '', 0, 8);
    }

    public function integrity(string $path, string $algo = 'sha384'): string
    {
        $fullPath = $this->resolve($path);
        if ($fullPath === null) {
            return '';
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            return '';
        }

        $digest = hash($algo, $content, true);

        return $algo . '-' . base64_encode($digest);
    }

    public function stylesheet(string $url, bool $integrity = false): string
    {
        $attrs = $this->buildAttrs($url, $integrity);
        $href  = $attrs['src'];

        $tag = "<link href=\"$href\" rel=\"stylesheet\"";
        if ($attrs['integrity'] !== '') {
            $tag .= " integrity=\"{$attrs['integrity']}\"";
        }

        return $tag . '>';
    }

    public function stylesheetAsync(string $url, bool $integrity = false): string
    {
        $attrs         = $this->buildAttrs($url, $integrity);
        $href          = $attrs['src'];
        $integrityVal  = $attrs['integrity'];

        $integrityAttr = $integrityVal !== '' ? " integrity=\"$integrityVal\"" : '';

        return "<link rel=\"stylesheet\" href=\"$href\" media=\"print\" onload=\"this.media='all';\"$integrityAttr>"
            . "\n<noscript><link rel=\"stylesheet\" href=\"$href\"$integrityAttr></noscript>";
    }

    public function script(string $url, bool $integrity = false): string
    {
        $attrs = $this->buildAttrs($url, $integrity);

        $tag = "<script src=\"{$attrs['src']}\"";
        if ($attrs['integrity'] !== '') {
            $tag .= " integrity=\"{$attrs['integrity']}\"";
        }

        return $tag . '></script>';
    }

    /**
     * @return array{src: string, integrity: string}
     */
    private function buildAttrs(string $url, bool $integrity = false): array
    {
        $hash = $this->hash($url);
        $sri  = $integrity ? $this->integrity($url) : '';
        $src  = $hash !== '' ? "$url?cache=$hash" : $url;

        return ['src' => $src, 'integrity' => $sri];
    }

    private function resolve(string $path): ?string
    {
        $fullPath = $this->assetsDir . DIRECTORY_SEPARATOR . ltrim($path, '/\\');

        return file_exists($fullPath) ? $fullPath : null;
    }
}
