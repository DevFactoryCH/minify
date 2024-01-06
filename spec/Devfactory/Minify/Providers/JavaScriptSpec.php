<?php namespace spec\Devfactory\Minify\Providers;

use Devfactory\Minify\Exceptions\DirNotExistException;
use Devfactory\Minify\Exceptions\DirNotWritableException;
use Devfactory\Minify\Exceptions\FileNotExistException;
use Devfactory\Minify\Providers\JavaScript;
use PhpSpec\ObjectBehavior;
use Prophecy\Prophet;
use org\bovigo\vfs\vfsStream;
use Illuminate\Filesystem\Filesystem;


class JavaScriptSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(JavaScript::class);
    }

    function it_adds_one_file(): void
    {
        vfsStream::setup('js', null, [
            '1.js' => 'a',
        ]);

        $this->add(VfsStream::url('js'));
        $this->shouldHaveCount(1);
    }

    function it_adds_multiple_files(): void
    {
        vfsStream::setup('root', null, [
            '1.js' => 'a',
            '2.js' => 'b',
        ]);

        $this->add([
            VfsStream::url('root/1.js'),
            VfsStream::url('root/2.js')
        ]);

        $this->shouldHaveCount(2);
    }

    function it_adds_custom_attributes(): void
    {
        $this->tag('file', ['foobar' => 'baz', 'defer' => true])
            ->shouldReturn('<script src="file" foobar="baz" defer></script>' . PHP_EOL);
    }

    function it_adds_without_custom_attributes(): void
    {
        $this->tag('file')
            ->shouldReturn('<script src="file"></script>' . PHP_EOL);
    }

    function it_throws_exception_when_file_not_exists(): void
    {
        $this->shouldThrow(FileNotExistException::class)
            ->duringAdd('foobar');
    }

    function it_should_throw_exception_when_buildpath_not_exist(): void
    {
        $prophet = new Prophet;
        $file = $prophet->prophesize(Filesystem::class);
        $file->makeDirectory('dir_bar', 0775, true)->willReturn(false);

        $this->beConstructedWith(null, null, $file);
        $this->shouldThrow(DirNotExistException::class)
            ->duringMake('dir_bar');
    }

    function it_should_throw_exception_when_buildpath_not_writable(): void
    {
        vfsStream::setup('js', 0555);

        $this->shouldThrow(DirNotWritableException::class)
            ->duringMake(vfsStream::url('js'));
    }

    function it_minifies_multiple_files(): void
    {
        vfsStream::setup('root', null, [
            'output' => [],
            '1.js' => 'a',
            '2.js' => 'b',
        ]);

        $this->add(vfsStream::url('root/1.js'));
        $this->add(vfsStream::url('root/2.js'));

        $this->make(vfsStream::url('root/output'));

        $this->getAppended()->shouldBe("a\nb\n");

        $output = md5('vfs://root/1.js-vfs://root/2.js');
        $filemtime = filemtime(vfsStream::url('root/1.js')) + filemtime(vfsStream::url('root/2.js'));
        $extension = '.js';

        $this->getFilename()->shouldBe($output . $filemtime . $extension);
    }
}
