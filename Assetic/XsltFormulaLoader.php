<?php
namespace PHST\Bundle\XsltBundle\Assetic;

use Assetic\Factory\Loader\FormulaLoaderInterface;
use Assetic\Factory\Resource\ResourceInterface;
use PHST\Bundle\XsltBundle\XsltEngine;

class XsltFormulaLoader implements FormulaLoaderInterface
{
    public function load(ResourceInterface $resource)
    {
        $formulae = array();
        return $formulae;
    }
}
