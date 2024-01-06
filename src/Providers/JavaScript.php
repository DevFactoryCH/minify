<?php namespace Devfactory\Minify\Providers;

use Devfactory\Minify\Contracts\MinifyInterface;
use Exception;
use JShrink\Minifier;

class JavaScript extends BaseProvider implements MinifyInterface
{
    /**
     * The extension of the outputted file.
     */
    const EXTENSION = '.js';

    /**
     * @throws Exception
     */
    public function minify(): string
    {
        $minified = Minifier::minify($this->appended);

        return $this->put($minified);
    }

    public function tag(string $file, array $attributes = []): string
    {
        $attributes = ['src' => $file] + $attributes;

        return "<script {$this->attributes($attributes)}></script>" . PHP_EOL;
    }
}
