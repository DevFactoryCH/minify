<?php namespace spec\Devfactory\Minify\Providers;

use Devfactory\Minify\Exceptions\DirNotExistException;
use Devfactory\Minify\Exceptions\DirNotWritableException;
use Devfactory\Minify\Exceptions\FileNotExistException;
use Devfactory\Minify\Providers\StyleSheet;
use PhpSpec\ObjectBehavior;
use Prophecy\Prophet;
use org\bovigo\vfs\vfsStream;
use Illuminate\Filesystem\Filesystem;

class StyleSheetSpec extends ObjectBehavior
{
    function it_is_initializable(): void
    {
        $this->shouldHaveType(StyleSheet::class);
    }

    function it_adds_one_file(): void
    {
        vfsStream::setup('css', null, [
            '1.css' => 'a',
        ]);

        $this->add(VfsStream::url('css'));
        $this->shouldHaveCount(1);
    }

    function it_adds_multiple_files(): void
    {
        vfsStream::setup('root', null, [
            '1.css' => 'a',
            '2.css' => 'b',
        ]);

        $this->add([
            VfsStream::url('root/1.css'),
            VfsStream::url('root/2.css')
        ]);

        $this->shouldHaveCount(2);
    }

    function it_adds_custom_attributes(): void
    {
        $this->tag('file', ['foobar' => 'baz', 'defer' => true])
            ->shouldReturn('<link href="file" rel="stylesheet" foobar="baz" defer>' . PHP_EOL);
    }

    function it_adds_without_custom_attributes(): void
    {
        $this->tag('file')
            ->shouldReturn('<link href="file" rel="stylesheet">' . PHP_EOL);
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
        vfsStream::setup('css', 0555);

        $this->shouldThrow(DirNotWritableException::class)
            ->duringMake(vfsStream::url('css'));
    }

    function it_minifies_multiple_files(): void
    {
        vfsStream::setup('root', null, [
            'output' => [],
            '1.css' => 'a',
            '2.css' => 'b',
        ]);

        $this->add(vfsStream::url('root/1.css'));
        $this->add(vfsStream::url('root/2.css'));

        $this->make(vfsStream::url('root/output'));

        $this->getAppended()->shouldBe("a\nb\n");

        $output = md5('vfs://root/1.css-vfs://root/2.css');
        $filemtime = filemtime(vfsStream::url('root/1.css')) + filemtime(vfsStream::url('root/2.css'));
        $extension = '.css';

        $this->getFilename()->shouldBe($output . $filemtime . $extension);
    }
}
