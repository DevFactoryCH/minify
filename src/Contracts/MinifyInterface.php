<?php namespace Devfactory\Minify\Contracts;

use Devfactory\Minify\Exceptions\CannotSaveFileException;

interface MinifyInterface {
    /**
     * @throws CannotSaveFileException
     */
    public function minify(): string;

    public function tag(string $file, array $attributes): string;
}
