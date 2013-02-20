<?php


namespace PS\Bundle\XsltBundle\Tests\DependencyInjection;

use PS\Bundle\XsltBundle\DependencyInjection\XsltExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class XsltExtensionTest extends \PHPUnit_Framework_TestCase
{

    public function testDefaultConfig()
    {
        $container = $this->createContainer();
        $container->registerExtension(new XsltExtension());
        $container->loadFromExtension('xslt', array());
        $this->compileContainer($container);

        $this->assertEquals('PS\Bundle\XsltBundle\XsltEngine', $container->getParameter('templating.engine.xslt.class'), '->load() loads the xslt.xml file');
    }

    private function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => __DIR__,
            'kernel.root_dir'  => __DIR__.'/Fixtures',
            'kernel.charset'   => 'UTF-8',
            'kernel.debug'     => false,
            'kernel.bundles'   => array('XsltBundle' => 'PS\\Bundle\\XsltBundle\\XsltBundle'),
        )));

        return $container;
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();
    }

}
