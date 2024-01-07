<?php namespace Devfactory\Minify;

use Devfactory\Minify\Exceptions\CannotRemoveFileException;
use Devfactory\Minify\Exceptions\CannotSaveFileException;
use Devfactory\Minify\Exceptions\DirNotExistException;
use Devfactory\Minify\Exceptions\DirNotWritableException;
use Devfactory\Minify\Exceptions\FileNotExistException;
use Devfactory\Minify\Exceptions\InvalidArgumentException;
use Devfactory\Minify\Providers\JavaScript;
use Devfactory\Minify\Providers\StyleSheet;
use FilesystemIterator;
use Illuminate\Support\Facades\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Minify
{
    protected array $config;

    protected array $attributes = [];

    private string $environment;

    private StyleSheet|JavaScript $provider;

    private string $buildPath;

    private bool $fullUrl = false;

    private bool $onlyUrl = false;

    private string $buildExtension;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $config, string $environment)
    {
        $this->checkConfiguration($config);

        $this->config = $config;
        $this->environment = $environment;
    }

    public function initialiseJavascript(array $attributes): void
    {
        $this->provider = new JavaScript(public_path(), [
            'hash_salt' => $this->config['hash_salt'],
            'disable_mtime' => $this->config['disable_mtime']
        ]);
        $this->buildPath = $this->config['js_build_path'];
        $this->attributes = $attributes;
        $this->buildExtension = 'js';
    }

    private function initialiseStylesheet(array $attributes): void
    {
        $this->provider = new StyleSheet(public_path(), [
            'hash_salt' => $this->config['hash_salt'],
            'disable_mtime' => $this->config['disable_mtime']
        ]);
        $this->buildPath = $this->config['css_build_path'];
        $this->attributes = $attributes;
        $this->buildExtension = 'css';
    }

    /**
     * @throws DirNotExistException
     * @throws DirNotWritableException
     * @throws CannotSaveFileException
     * @throws CannotRemoveFileException
     * @throws FileNotExistException
     */
    public function javascript(array|string $file, array $attributes = []): Minify
    {
        $this->initialiseJavascript($attributes);

        $this->process($file);

        return $this;
    }

    /**
     * @throws DirNotExistException
     * @throws DirNotWritableException
     * @throws CannotSaveFileException
     * @throws CannotRemoveFileException
     * @throws FileNotExistException
     */
    public function stylesheet(array|string $file, array $attributes = []): Minify
    {
        $this->initialiseStylesheet($attributes);

        $this->process($file);

        return $this;
    }

    /**
     * @throws DirNotExistException
     * @throws DirNotWritableException
     * @throws CannotSaveFileException
     * @throws CannotRemoveFileException
     * @throws FileNotExistException
     */
    public function stylesheetDir(string $dir, array $attributes = []): string
    {
        $this->initialiseStylesheet($attributes);

        return $this->assetDirHelper('css', $dir);
    }

    /**
     * @throws DirNotExistException
     * @throws DirNotWritableException
     * @throws CannotSaveFileException
     * @throws CannotRemoveFileException
     * @throws FileNotExistException
     */
    public function javascriptDir(string $dir, array $attributes = []): string
    {
        $this->initialiseJavascript($attributes);

        return $this->assetDirHelper('js', $dir);
    }

    /**
     * @throws DirNotExistException
     * @throws DirNotWritableException
     * @throws CannotSaveFileException
     * @throws CannotRemoveFileException
     * @throws FileNotExistException
     */
    private function assetDirHelper(string $ext, string $dir): Minify
    {
        $files = [];

        $itr_obj = new RecursiveDirectoryIterator(public_path() . $dir);
        $itr_obj->setFlags(FilesystemIterator::SKIP_DOTS);
        $dir_obj = new RecursiveIteratorIterator($itr_obj);

        foreach ($dir_obj as $fileinfo) {
            if (!$fileinfo->isDir() && ($filename = $fileinfo->getFilename()) && (pathinfo($filename, PATHINFO_EXTENSION) == $ext) && (strlen($fileinfo->getFilename()) < 30)) {
                $files[] = str_replace(public_path(), '', $fileinfo);
            }
        }

        if (count($files) > 0) {
            if ($this->config['reverse_sort']) {
                rsort($files);
            } else {
                sort($files);
            }
            $this->process($files);
        }

        return $this;
    }

    /**
     * @throws DirNotExistException
     * @throws DirNotWritableException
     * @throws CannotSaveFileException
     * @throws CannotRemoveFileException
     * @throws FileNotExistException
     */
    private function process(array|string $file): void
    {
        $this->provider->add($file);

        if ($this->minifyForCurrentEnvironment() && $this->provider->make($this->buildPath)) {
            $this->provider->minify();
        }

        $this->fullUrl = false;
    }

    protected function render(): string
    {
        $baseUrl = $this->fullUrl ? $this->getBaseUrl() : '';
        if (!$this->minifyForCurrentEnvironment()) {
            return $this->provider->tags($baseUrl, $this->attributes);
        }

        if ($this->buildExtension == 'js') {
            $buildPath = $this->config['js_url_path'] ?? $this->buildPath;
        } else# if( $this->buildExtension == 'css')
        {
            $buildPath = $this->config['css_url_path'] ?? $this->buildPath;
        }

        $filename = $baseUrl . $buildPath . $this->provider->getFilename();

        if ($this->onlyUrl) {
            return $filename;
        }

        return $this->provider->tag($filename, $this->attributes);
    }

    protected function minifyForCurrentEnvironment(): bool
    {
        return !in_array($this->environment, $this->config['ignore_environments']);
    }

    public function withFullUrl(): Minify
    {
        $this->fullUrl = true;

        return $this;
    }

    public function onlyUrl(): Minify
    {
        $this->onlyUrl = true;

        return $this;
    }

    public function __toString()
    {
        return $this->render();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkConfiguration(array $config): void
    {
        if (!isset($config['css_build_path']) || !is_string($config['css_build_path']))
        {
            throw new InvalidArgumentException('Missing css_build_path field');
        }

        if (!isset($config['js_build_path']) || !is_string($config['js_build_path']))
        {
            throw new InvalidArgumentException('Missing js_build_path field');
        }

        if (!isset($config['ignore_environments']) || !is_array($config['ignore_environments']))
        {
            throw new InvalidArgumentException('Missing ignore_environments field');
        }
    }

    private function getBaseUrl(): string
    {
        if (is_null($this->config['base_url']) || (trim($this->config['base_url']) == '')) {
            return Request::root();
        } else {
            return $this->config['base_url'];
        }
    }
}
