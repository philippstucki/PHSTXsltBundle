<?php

namespace PS\Bundle\XsltBundle\Tests;

use PS\Bundle\XsltBundle\XsltEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables;

class XsltEngineTest extends \PHPUnit_Framework_TestCase
{

    protected static $fixturesPath;

    public static function setUpBeforeClass()
    {
        self::$fixturesPath = realpath(__DIR__.'/Fixtures/');
    }

    protected function getParserMock()
    {
        return $this->getMock('Symfony\Component\Templating\TemplateNameParserInterface');
    }

    protected function getLoaderMock()
    {
        return $this->getMock('Symfony\Component\Templating\Loader\LoaderInterface');
    }

    protected function getTemplateMock()
    {
        return $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\TemplateReference');
    }

    protected function getStringStorageMock()
    {
        return $this->getMockBuilder('Symfony\Component\Templating\Storage\StringStorage')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getFileStorageMock()
    {
        return $this->getMockBuilder('Symfony\Component\Templating\Storage\FileStorage')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getEngineForStorageTest($storage)
    {
        $loader = $this->getLoaderMock();
        $parser = $this->getParserMock();
        $fileStorage = $this->getFileStorageMock();

        $loader->expects($this->any())
            ->method('load')
            ->will($this->returnValue($storage));

        return new XsltEngine($parser, $loader);
    }

    public function testSupports()
    {
        $loader = $this->getLoaderMock();
        $parser = new TemplateNameParser();

        $engine = new XsltEngine($parser, $loader);
        $this->assertTrue($engine->supports('BundleNS:ControllerNS:index.html.xsl'), '->supports() returns true when queried for xsl template');
        $this->assertFalse($engine->supports('BundleNS:ControllerNS:index.html.twig'), '->supports() returns false when queried for other template');
    }

    public function testLoadTemplateDoesNotExists()
    {
        $loader = $this->getLoaderMock();
        $parser = $this->getParserMock();
        $template = $this->getTemplateMock();

        $loader->expects($this->any())
            ->method('load')
            ->will($this->returnValue(false));

        $this->setExpectedException('InvalidArgumentException');
        $engine = new XsltEngine($parser, $loader);
        $engine->load($template);
    }

    public function testLoadFileStorage()
    {
        $template = $this->getTemplateMock();
        $fileStorage = $this->getFileStorageMock();
        $fileStorage->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue(self::$fixturesPath.'/xsl/empty.xsl'));

        $engine = $this->getEngineForStorageTest($fileStorage);
        $dom = $engine->load($template);
        $this->assertInstanceOf('\DomDocument', $dom, '->load() returns a DomDocument when loading from valid file');
    }


    public function testLoadStringStorage()
    {
        $template = $this->getTemplateMock();
        $stringStorage = $this->getStringStorageMock();
        $stringStorage->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue(file_get_contents(self::$fixturesPath.'/xsl/empty.xsl')));

        $engine = $this->getEngineForStorageTest($stringStorage);
        $dom = $engine->load($template);
        $this->assertInstanceOf('\DomDocument', $dom, '->load() returns a DomDocument when loading from valid string');
    }

}
