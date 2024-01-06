<?php namespace Devfactory\Minify\Providers;

use Devfactory\Minify\Exceptions\CannotRemoveFileException;
use Devfactory\Minify\Exceptions\CannotSaveFileException;
use Devfactory\Minify\Exceptions\DirNotExistException;
use Devfactory\Minify\Exceptions\DirNotWritableException;
use Devfactory\Minify\Exceptions\FileNotExistException;
use Illuminate\Filesystem\Filesystem;
use Countable;

abstract class BaseProvider implements Countable
{
    protected string $outputDir;

    protected string $appended = '';

    protected string $filename = '';

    protected array $files = [];

    protected array $headers = [];

    private string $publicPath;

    protected Filesystem $file;

    private bool $disable_mtime;

    private string $hash_salt;

    public function __construct(?string $publicPath = null, ?array $config = null, ?Filesystem $file = null)
    {
        $this->file = $file ?: new Filesystem;

        $this->publicPath = $publicPath ?: $_SERVER['DOCUMENT_ROOT'];

        if (!is_array($config))
        {
            $this->disable_mtime = false;
            $this->hash_salt = '';
        }
        else
        {
            $this->disable_mtime = $config['disable_mtime'] ?: false;
            $this->hash_salt = $config['hash_salt'] ?: '';
        }

        $value = function($key)
        {
            return $_SERVER[$key] ?? '';
        };

        $this->headers = [
            'User-Agent'      => $value('HTTP_USER_AGENT'),
            'Accept'          => $value('HTTP_ACCEPT'),
            'Accept-Language' => $value('HTTP_ACCEPT_LANGUAGE'),
            'Accept-Encoding' => 'identity',
            'Connection'      => 'close',
        ];
    }

    /**
     * @throws DirNotWritableException
     * @throws DirNotExistException
     * @throws CannotRemoveFileException
     * @throws FileNotExistException
     */
    public function make(string $outputDir): bool
    {
        $this->outputDir = $this->publicPath . $outputDir;

        $this->checkDirectory();

        if ($this->checkExistingFiles())
        {
            return false;
        }

        $this->removeOldFiles();
        $this->appendFiles();

        return true;
    }

    /**
     * @throws FileNotExistException
     */
    public function add(array|string $file): void
    {
        if (is_array($file))
        {
            foreach ($file as $value) $this->add($value);
        }
        else if ($this->checkExternalFile($file))
        {
            $this->files[] = $file;
        }
        else {
            $file = $this->publicPath . $file;
            if (!file_exists($file))
            {
                throw new FileNotExistException("File '{$file}' does not exist");
            }

            $this->files[] = $file;
        }
    }

    public function tags(string $baseUrl, array $attributes): string
    {
        $html = '';
        foreach($this->files as $file)
        {
            $file = $baseUrl . str_replace($this->publicPath, '', $file);
            $html .= $this->tag($file, $attributes);
        }

        return $html;
    }

    public function count(): int
    {
        return count($this->files);
    }

    /**
     * @throws FileNotExistException
     */
    protected function appendFiles(): void
    {
        foreach ($this->files as $file) {
            $contents = $this->getFileContents($file);
            $this->appended .= $contents . "\n";
        }
    }

    /**
     * @throws FileNotExistException
     */
    protected function getFileContents(string $file): string
    {
        if ($this->checkExternalFile($file))
        {
            if (str_starts_with($file, '//')) $file = 'http:' . $file;

            $headers = $this->headers;
            foreach ($headers as $key => $value)
            {
                $headers[$key] = $key . ': ' . $value;
            }
            $context = stream_context_create(['http' => [
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
            ]]);

            $http_response_header = [''];
            $contents = file_get_contents($file, false, $context);

            if (!str_contains($http_response_header[0], '200'))
            {
                throw new FileNotExistException("File '{$file}' does not exist");
            }
        }
        else
        {
            $contents = file_get_contents($file);
        }

        return $contents;
    }

    protected function getPublicFileDirectory(string $file): string
    {
        $publicPath = function_exists('public_path') ? public_path() : '';
        $fileWithoutPublicPath = str_replace($publicPath, '', $file);

        return str_replace(basename($fileWithoutPublicPath), '', $fileWithoutPublicPath);
    }

    protected function checkExistingFiles(): bool
    {
        $this->buildMinifiedFilename();

        return file_exists($this->outputDir . $this->filename);
    }

    /**
     * @throws DirNotWritableException
     * @throws DirNotExistException
     */
    protected function checkDirectory(): void
    {
        if (!file_exists($this->outputDir))
        {
          // Try to create the directory
          if (!$this->file->makeDirectory($this->outputDir, 0775, true)) {
            throw new DirNotExistException("Buildpath '{$this->outputDir}' does not exist");
          }
        }

        if (!is_writable($this->outputDir))
        {
            throw new DirNotWritableException("Buildpath '{$this->outputDir}' is not writable");
        }
    }

    protected function checkExternalFile(string $file): bool
    {
        return preg_match('/^(https?:)?\/\//', $file);
    }

    protected function buildMinifiedFilename(): void
    {
        $this->filename = $this->getHashedFilename() . (($this->disable_mtime) ? '' : $this->countModificationTime()) . static::EXTENSION;
    }

    /**
     * Build an HTML attribute string from an array.
     */
    protected function attributes(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $key => $value)
        {
            $element = $this->attributeElement($key, $value);

            if ( ! is_null($element)) $html[] = $element;
        }

        $output = count($html) > 0 ? ' '.implode(' ', $html) : '';

        return trim($output);
    }

    /**
     * Build a single attribute element.
     */
    protected function attributeElement(mixed $key, mixed $value): mixed
    {
        if (is_numeric($key)) $key = $value;

        if(is_bool($value))
            return $key;

        if ( ! is_null($value))
            return $key.'="'.htmlentities($value, ENT_QUOTES, 'UTF-8', false).'"';

        return null;
    }

    protected function getHashedFilename(): string
    {
        $publicPath = $this->publicPath;
        return md5(implode('-', array_map(function($file) use ($publicPath) { return str_replace($publicPath, '', $file); }, $this->files)) . $this->hash_salt);
    }

    protected function countModificationTime(): int
    {
        $time = 0;

        foreach ($this->files as $file)
        {
            if ($this->checkExternalFile($file))
            {
                $userAgent = $this->headers['User-Agent'] ?? '';
                $time += hexdec(substr(md5($file . $userAgent), 0, 8));
            }
            else {
                $time += filemtime($file);
            }
        }

        return $time;
    }

    /**
     * @throws CannotRemoveFileException
     */
    protected function removeOldFiles(): void
    {
        $pattern = $this->outputDir . $this->getHashedFilename() . '*';
        $find = glob($pattern);

        if( is_array($find) && count($find) )
        {
            foreach ($find as $file)
            {
                if ( ! unlink($file) ) {
                    throw new CannotRemoveFileException("File '{$file}' cannot be removed");
                }
            }
        }
    }

    /**
     * @throws CannotSaveFileException
     */
    protected function put(mixed $minified): string
    {
        if(file_put_contents($this->outputDir . $this->filename, $minified) === false)
        {
            throw new CannotSaveFileException("File '{$this->outputDir}{$this->filename}' cannot be saved");
        }

        return $this->filename;
    }

    public function getAppended(): string
    {
        return $this->appended;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
