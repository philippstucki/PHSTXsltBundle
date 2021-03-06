<?php

namespace PHST\Bundle\XsltBundle\Tests;

use PHST\Bundle\XsltBundle\XsltEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\Storage;
use Symfony\Component\Templating\Storage\StringStorage;
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

    protected function getEngine($storage = null)
    {
        $loader = $this->getLoaderMock();
        $parser = new TemplateNameParser();

        if (null === $storage) {
            $storage = $this->getStringStorageMock();
        }

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

        $this->assertTrue($engine->supports('BundleNS:ControllerNS:index.html.xslt'), '->supports() returns true when queried for xslt template');
        $this->assertFalse($engine->supports('BundleNS:ControllerNS:index.html.twig'), '->supports() returns false when queried for other template');
    }

    public function testExists()
    {
        $loader = $this->getLoaderMock();
        $loader->expects($this->once())
            ->method('load')
            ->will($this->returnValue(false));
        $parser = new TemplateNameParser();
        $engine = new XsltEngine($parser, $loader);

        $this->assertFalse($engine->exists('BundleNS:ControllerNS:index.html.xslt'));
    }

    public function testRender()
    {
        $fileStorage = $this->getFileStorageMock();
        $fileStorage->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue(self::$fixturesPath.'/xsl/xmloutput-basic.xslt'));
        $engine = $this->getEngine($fileStorage);

        $output = $engine->render('BundleNS:ControllerNS:index.html.xslt');
        $this->assertEquals('<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL.'<html><body>body text</body></html>', trim($output));
    }

    public function testRenderResponse()
    {
        $engine = $this->getMockBuilder('PHST\Bundle\XsltBundle\XsltEngine')
            ->setMethods(array('render'))
            ->disableOriginalConstructor()
            ->getMock();

        $engine->expects($this->once())
            ->method('render')
            ->will($this->returnValue('Rendered Response'));

        $response = $engine->renderResponse('BundleNS:ControllerNS:index.html.xslt');
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response, '->renderResponse() creates a Response instance if none has been passed');
        $this->assertEquals('Rendered Response', $response->getContent());
    }

    public function testLoadInvalid()
    {
        $template = $this->getTemplateMock();
        $fileStorage = $this->getFileStorageMock();
        $fileStorage->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue(self::$fixturesPath.'/xsl/invalid.xslt'));
        $engine = $this->getEngine($fileStorage);

        $this->setExpectedException('InvalidArgumentException');
        $dom = $engine->load($template);
    }

    public function testLoadTemplateDoesNotExists()
    {
        $loader = $this->getLoaderMock();
        $parser = $this->getParserMock();
        $template = $this->getTemplateMock();

        $loader->expects($this->once())
            ->method('load')
            ->will($this->returnValue(false));

        $this->setExpectedException('InvalidArgumentException');
        $engine = new XsltEngine($parser, $loader);
        $engine->load($template);
    }

    public function testLoadFromFileStorage()
    {
        $template = $this->getTemplateMock();
        $fileStorage = $this->getFileStorageMock();
        $fileStorage->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue(self::$fixturesPath.'/xsl/empty.xslt'));

        $engine = $this->getEngine($fileStorage);
        $dom = $engine->load($template);
        $this->assertInstanceOf('\DomDocument', $dom, '->load() returns a DomDocument when loading from valid file');
    }


    public function testLoadFromStringStorage()
    {
        $template = $this->getTemplateMock();
        $stringStorage = $this->getStringStorageMock();
        $stringStorage->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue(file_get_contents(self::$fixturesPath.'/xsl/empty.xslt')));

        $engine = $this->getEngine($stringStorage);
        $dom = $engine->load($template);
        $this->assertInstanceOf('\DomDocument', $dom, '->load() returns a DomDocument when loading from valid string');
    }

    public function testSerializeParameters()
    {
        $engine = $this->getEngine();
        $this->assertInstanceOf('DOMDocument', $engine->serializeParameters(array()));
    }

}
