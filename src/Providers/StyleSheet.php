<?php namespace Devfactory\Minify\Providers;

use CssMinifier;
use Devfactory\Minify\Contracts\MinifyInterface;
use Devfactory\Minify\Exceptions\FileNotExistException;

class StyleSheet extends BaseProvider implements MinifyInterface
{
    /**
     * The extension of the outputted file.
     */
    const EXTENSION = '.css';

    public function minify(): string
    {
        $minified = new CssMinifier($this->appended);

        return $this->put($minified->getMinified());
    }

    public function tag(string $file, array $attributes = []): string
    {
        $attributes = ['href' => $file, 'rel' => 'stylesheet'] + $attributes;

        return "<link {$this->attributes($attributes)}>" . PHP_EOL;
    }

    /**
     * Override appendFiles to solve CSS URL path issue.
     *
     * @throws FileNotExistException
     */
    protected function appendFiles(): void
    {
        foreach ($this->files as $file) {
            $fileContent = $this->getFileContentWithCorrectedUrls($file);
            $this->appended .= $fileContent . "\n";
        }
    }

    /**
     * CSS URL path correction.
     *
     * @throws FileNotExistException
     */
    public function getFileContentWithCorrectedUrls(string $file): string
    {
        $fileDirectory = $this->getPublicFileDirectory($file);
        $fileContent = $this->getFileContents($file);

        return $this->contentUrlCorrection($fileDirectory, $fileContent);
    }

    /**
     * CSS content URL path correction.
     */
    private function contentUrlCorrection(string $fileDirectory, string $fileContent): string
    {
        $contentReplace = [];
        $contentReplaceWith = [];
        preg_match_all('/url\((\s)?([\"|\'])?(.*?)([\"|\'])?(\s)?\)/i', $fileContent, $matches, PREG_PATTERN_ORDER);
        if (!count($matches)) {
            return $fileContent;
        }
        foreach ($matches[0] as $match) {
            $contentReplace[] = $match;
            if (strpos($match, "'")) {
                $contentReplaceWith[] = str_replace('url(\'', 'url(\''.$fileDirectory, $match);
            } elseif (str_contains($match, '"')) {
                $contentReplaceWith[] = str_replace('url("', 'url("'.$fileDirectory, $match);
            } else {
                $contentReplaceWith[] = str_replace('url(', 'url('.$fileDirectory, $match);
            }
        }

        return str_replace($contentReplace, $contentReplaceWith, $fileContent);
    }
}
