<?php
namespace PHST\Bundle\XsltBundle\Assetic;

use Assetic\Factory\Loader\FormulaLoaderInterface;
use Assetic\Factory\Resource\ResourceInterface;
use PHST\Bundle\XsltBundle\XsltEngine;

class XsltFormulaLoader implements FormulaLoaderInterface
{
    protected $engine;

    public function __construct(XsltEngine $engine)
    {
        $this->engine = $engine;
    }

    public function load(ResourceInterface $resource)
    {
        $formulae = array();
        return $formulae;
    }

}
