<?php

namespace PS\Bundle\XsltBundle;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\Storage\FileStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class XsltEngine implements EngineInterface
{

    protected $parser;
    protected $loader;
    protected $globals;
    
    /**
     * Constructor.
     *
     * @param TemplateNameParserInterface $parser    A TemplateNameParserInterface instance
     * @param LoaderInterface             $loader    A LoaderInterface instance
     * @param GlobalVariables|null        $globals   A GlobalVariables instance or null
     */
    public function __construct(TemplateNameParserInterface $parser,
        LoaderInterface $loader,
        GlobalVariables $globals = null)
    {
        $this->parser = $parser;
        $this->loader = $loader;
        $this->globals = $globals;
    }
 
    /**
     * Renders a template.
     *
     * @param mixed $name       A template name or a TemplateReferenceInterface instance
     * @param array $parameters An array of parameters to pass to the template
     *
     * @return string The evaluated template as a string
     *
     * @throws \RuntimeException if the template cannot be rendered
     *
     * @api
     */
    public function render($name, array $parameters = array())
    {
        $template = $this->parse($name);
        $xsltDom = $this->load($template);
        var_dump($xsltDom->saveXML());
        die;
    }

    /**
     * Returns true if the template exists.
     *
     * @param mixed $name A template name or a TemplateReferenceInterface instance
     *
     * @return Boolean true if the template exists, false otherwise
     *
     * @api
     */
    public function exists($name)
    {
        d();
    }

    /**
     * Returns true if this class is able to render the given template.
     *
     * @param mixed $name A template name or a TemplateReferenceInterface instance
     *
     * @return Boolean true if this class supports the given template, false otherwise
     */
    public function supports($name)
    {
        return $this->parse($name)->get('engine') === 'xsl';
    }

    /**
     * Renders a view and returns a Response.
     *
     * @param string   $view       The view name
     * @param array    $parameters An array of parameters to pass to the view
     * @param Response $response   A Response instance
     *
     * @return Response A Response instance
     */
    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($view, $parameters));

        return $response;
    }

    /**
     * Parses the given template.
     *
     * @param string $name A template name
     *
     * @return mixed The resource handle of the template file or template object
     *
     * @throws \InvalidArgumentException if the template cannot be found
     */
    public function parse($name)
    {
        return $this->parser->parse($name);
    }

    /**
     * Loads the given template.
     *
     * @param TemplateReferenceInterface $name The template to be loaded
     *
     * @return DomDocument The DOM Document representing the template
     *
     * @throws \InvalidArgumentException if the template cannot be found
     */
    public function load($template)
    {

        $storage = $this->loader->load($template);

        if (false === $storage) {
            throw new \InvalidArgumentException(sprintf('The template "%s" does not exist.', $template->getLogicalName()));
        }

        $xsltDom = new \DomDocument();

        if ($storage instanceof FileStorage) {
            $xsltDom->load((string) $storage);
        } else {
            $xsltDom->loadXML($storage);
        }

        return $xsltDom;
    }
}
