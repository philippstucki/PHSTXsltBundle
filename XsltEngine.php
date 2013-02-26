<?php

namespace PHST\Bundle\XsltBundle;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Templating\Loader\LoaderInterface;
use Symfony\Component\Templating\Storage\FileStorage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\XmlSerializationVisitor;

class XsltEngine implements EngineInterface
{

    protected $parser;
    protected $loader;
    protected $globals;

    protected static $internalErrors;
    protected static $disableEntities;
    
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
        $xsltDoc = $this->load($template);

        $xslProc = new \XSLTProcessor();
        $xslProc->importStylesheet($xsltDoc);
        $dataDoc = $this->serializeParameters($parameters);
        return $xslProc->transformToXML($dataDoc);
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
        try {
            $template = $this->parse($name);
            $this->load($template);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
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
     * @param string   $name       The view name
     * @param array    $parameters An array of parameters to pass to the view
     * @param Response $response   A Response instance
     *
     * @return Response A Response instance
     */
    public function renderResponse($name, array $parameters = array(), Response $response = null)
    {
        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($this->render($name, $parameters));

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

        if ($storage instanceof FileStorage) {
            $xml = file_get_contents((string) $storage);
        } else {
            $xml = $storage;
        }

        self::startLibXmlErrorHandler();
        $xsltDom = new \DomDocument();
        $xsltDom->validateOnParse = true;
        if (false === $xsltDom->loadXML($xml,  LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            throw new \InvalidArgumentException(implode("\n", self::getLibXmlErrors()));
        }
        self::stopLibXmlErrorHandler();

        return $xsltDom;
    }

    public function serializeParameters($parameters)
    {
        $serializer = SerializerBuilder::create()->build();
        $xml = $serializer->serialize($parameters, 'xml');

        self::startLibXmlErrorHandler();
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        if (false === $dom->loadXML($xml,  LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            throw new \InvalidArgumentException(implode("\n", self::getLibXmlErrors()));
        }
        self::stopLibXmlErrorHandler();

        return $dom;
    }

    protected static function startLibXmlErrorHandler()
    {
        self::$internalErrors = libxml_use_internal_errors(true);
        self::$disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();
    }

    protected static function getLibXmlErrors()
    {
        $errors = array();
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ? $error->file : 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        self::stopLibXmlErrorHandler();

        return $errors;
    }

    protected static function stopLibXmlErrorHandler()
    {
        libxml_use_internal_errors(self::$internalErrors);
        libxml_disable_entity_loader(self::$disableEntities);
    }

}
