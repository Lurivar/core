<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Tests\FileFormat\Archive\ArchiveBuilder;
use Symfony\Component\DependencyInjection\Container;
use Thelia\Core\FileFormat\Archive\ArchiveBuilder\ZipArchiveBuilder;
use Thelia\Core\Translation\Translator;
use Thelia\Log\Tlog;
use Thelia\Tests\Tools\FakeFileDownloader;

/**
 * Class ZipArchiveBuilderTest
 * @package Thelia\Tests\FileFormat\Archive\ArchiveBuilder
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class ZipArchiveBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ZipArchiveBuilder */
    protected $zip;

    /** @var  ZipArchiveBuilder */
    protected $loadedZip;

    public function setUp()
    {
        new Translator(
            new Container()
        );

        Tlog::getNewInstance();

        $this->zip = new ZipArchiveBuilder();

        $this->loadedZip = $this->zip->loadArchive(
            __DIR__ . DS . "TestResources/well_formatted.zip",
            "dev"
        );
    }

    /**
     * This method formats a path to be compatible with \ZipArchive
     */
    public function testGetFilePath()
    {
        $this->assertEquals(
            "foo",
            $this->zip->getFilePath("foo")
        );

        $this->assertEquals(
            "foo",
            $this->zip->getFilePath("/foo")
        );

        $this->assertEquals(
            "foo",
            $this->zip->getFilePath("foo/")
        );

        $this->assertEquals(
            "foo",
            $this->zip->getFilePath("/foo/")
        );

        $this->assertEquals(
            "/foo/bar",
            $this->zip->getFilePath("foo/bar")
        );

        $this->assertEquals(
            "/foo/bar",
            $this->zip->getFilePath("/foo/bar")
        );

        $this->assertEquals(
            "/foo/bar",
            $this->zip->getFilePath("/foo//bar/")
        );

        $this->assertEquals(
            "/foo/bar",
            $this->zip->getFilePath("/foo/bar/")
        );

        $this->assertEquals(
            "/foo/bar/baz",
            $this->zip->getFilePath("foo/bar/baz")
        );

        $this->assertEquals(
            "/foo/bar/baz",
            $this->zip->getFilePath("//foo/bar///baz/")
        );
    }

    public function testGetDirectoryPath()
    {
        $this->assertEquals(
            "/foo/",
            $this->zip->getDirectoryPath("foo")
        );

        $this->assertEquals(
            "/foo/",
            $this->zip->getDirectoryPath("/foo")
        );

        $this->assertEquals(
            "/foo/",
            $this->zip->getDirectoryPath("foo/")
        );

        $this->assertEquals(
            "/foo/",
            $this->zip->getDirectoryPath("/foo/")
        );

        $this->assertEquals(
            "/foo/bar/",
            $this->zip->getDirectoryPath("foo/bar")
        );

        $this->assertEquals(
            "/foo/bar/",
            $this->zip->getDirectoryPath("/foo/bar")
        );

        $this->assertEquals(
            "/foo/bar/",
            $this->zip->getDirectoryPath("/foo//bar/")
        );

        $this->assertEquals(
            "/foo/bar/",
            $this->zip->getDirectoryPath("/foo/bar/")
        );

        $this->assertEquals(
            "/foo/bar/baz/",
            $this->zip->getDirectoryPath("foo/bar/baz")
        );

        $this->assertEquals(
            "/foo/bar/baz/",
            $this->zip->getDirectoryPath("//foo/bar///baz/")
        );
    }

    public function testLoadValidZip()
    {
        $loadedZip = $this->zip->loadArchive(
            __DIR__ . DS . "TestResources/well_formatted.zip",
            "dev"
        );

        $this->assertInstanceOf(
            get_class($this->loadedZip),
            $loadedZip
        );
    }

    /**
     * @expectedException \Thelia\Core\FileFormat\Archive\ArchiveBuilder\ZipArchiveException
     * @expectedExceptionMessage [Zip Error] The file is not a zip archive
     */
    public function testLoadNotValidZip()
    {
        $this->zip->loadArchive(
            __DIR__ . DS . "TestResources/bad_formatted.zip",
            "dev"
        );
    }

    /**
     * @expectedException \Thelia\Exception\FileNotFoundException
     */
    public function testLoadNotExistingFile()
    {
        $this->zip->loadArchive(
            __DIR__ . DS . "TestResources/this_file_doesn_t_exist.zip",
            "dev"
        );
    }

    public function testLoadOnlineAvailableAndValidFile()
    {
        $this->zip->loadArchive(
            __DIR__ . DS . "TestResources/well_formatted.zip",
            "dev",
            true,
            FakeFileDownloader::getInstance()
        );
    }

    /**
     * @expectedException \Thelia\Core\FileFormat\Archive\ArchiveBuilder\ZipArchiveException
     * @expectedExceptionMessage [Zip Error] The file is not a zip archive
     */
    public function testLoadOnlineAvailableAndNotValidFile()
    {
        $this->zip->loadArchive(
            __DIR__ . DS . "TestResources/bad_formatted.zip",
            "dev",
            true,
            FakeFileDownloader::getInstance()
        );
    }

    /**
     * @expectedException \Thelia\Exception\FileNotFoundException
     */
    public function testLoadOnlineNotExistingFile()
    {
        $this->zip->loadArchive(
            __DIR__ . DS . "TestResources/this_file_doesn_t_exist.zip",
            "dev",
            true,
            FakeFileDownloader::getInstance()
        );
    }

    public function testHasFile()
    {
        $this->assertTrue(
            $this->loadedZip->hasFile("LICENSE.txt")
        );

        $this->assertFalse(
            $this->loadedZip->hasFile("foo")
        );

        $this->assertFalse(
            $this->loadedZip->hasFile("LICENSE.TXT")
        );
    }

    public function testDeleteFile()
    {
        $this->assertInstanceOf(
            get_class($this->loadedZip),
            $this->loadedZip->deleteFile("LICENSE.txt")
        );
    }

    /**
     * @expectedException \Thelia\Exception\FileNotFoundException
     */
    public function testDeleteNotExistingFile()
    {
        $this->loadedZip->deleteFile("foo");
    }

    public function testAddExistingFile()
    {
        $this->assertInstanceOf(
            get_class($this->loadedZip),
            $this->loadedZip->addFile(
                __DIR__ . DS . "TestResources/test_file",
                "/f/f/"
            )
        );

        /**
         * Show that even weird paths are correctly interpreted
         */
        $this->assertTrue(
            $this->loadedZip->hasFile("///f//f/test_file/")
        );
    }

    public function testAddExistingFileInNewDirectory()
    {
        $this->assertInstanceOf(
            get_class($this->loadedZip),
            $this->loadedZip->addFile(
                __DIR__ . DS . "TestResources/test_file",
                "testDir"
            )
        );

        /**
         * You can create and check the directory and files
         * without giving the initial and final slashes
         */
        $this->assertTrue(
            $this->loadedZip->hasDirectory("testDir")
        );

        $this->assertTrue(
            $this->loadedZip->hasDirectory("/testDir")
        );

        $this->assertTrue(
            $this->loadedZip->hasDirectory("testDir/")
        );

        $this->assertTrue(
            $this->loadedZip->hasDirectory("/testDir/")
        );

        $this->assertTrue(
            $this->loadedZip->hasFile("testDir/test_file")
        );

        $this->assertTrue(
            $this->loadedZip->hasFile("/testDir/test_file")
        );

        $this->assertTrue(
            $this->loadedZip->hasFile("testDir/test_file/")
        );

        $this->assertTrue(
            $this->loadedZip->hasFile("/testDir/test_file/")
        );
    }

    public function testBuildArchiveResponse()
    {
        $loadedArchiveResponse = $this->loadedZip
            ->buildArchiveResponse()
        ;

        $loadedArchiveResponseContent = $loadedArchiveResponse->getContent();

        $content = file_get_contents(__DIR__ . DS . "TestResources/well_formatted.zip");

        $this->assertEquals(
            $content,
            $loadedArchiveResponseContent
        );
    }
} 