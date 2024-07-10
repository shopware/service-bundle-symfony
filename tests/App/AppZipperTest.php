<?php

namespace Shopware\ServiceBundle\Test\App;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\ServiceBundle\App\App;
use Shopware\ServiceBundle\App\AppZipper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use ZipArchive;

#[CoversClass(AppZipper::class)]
#[CoversClass(App::class)]
class AppZipperTest extends TestCase
{
    private string $tempDir;
    private Filesystem $fs;

    public function setUp(): void
    {
        $this->tempDir = Path::join((string) realpath(sys_get_temp_dir()), $this->name());
        $this->fs = new Filesystem();
    }

    public function testAppZipperAddsAllFiles(): void
    {
        $zipper = new AppZipper();

        $this->fs->mkdir($this->tempDir . '/app');
        $this->fs->touch($this->tempDir . '/app/file1');
        $this->fs->touch($this->tempDir . '/app/file2');
        $this->fs->mkdir($this->tempDir . '/app/folder');
        $this->fs->touch($this->tempDir . '/app/folder/file3');

        $app = new App($this->tempDir . '/app', 'MyCoolService', '6.6.0.0', 'hash');

        $zipContent = $zipper->zip($app);

        $this->fs->dumpFile($this->tempDir . '/app.zip', $zipContent);

        $zip = new \ZipArchive();
        $zip->open($this->tempDir . '/app.zip', ZipArchive::RDONLY);

        $names = array_map(
            static fn($i) => $zip->getNameIndex($i),
            range(0, $zip->numFiles - 1),
        );

        sort($names);
        ;

        static::assertEquals(
            [
                'MyCoolService/file1',
                'MyCoolService/file2',
                'MyCoolService/folder/file3',
            ],
            $names,
        );
    }

    public function tearDown(): void
    {
        $this->fs->remove($this->tempDir);
    }
}
