<?php

namespace Loconox\EntityRoutingBundle\Annotation;

use Loconox\EntityRoutingBundle\Route\RouteCompiler;
use Symfony\Component\Routing\Annotation\Route as BaseRoute;

/**
 * Annotation class for @Route()
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Route extends BaseRoute
{
    public function __construct(array $data)
    {
        if (!isset($data['options']['compiler_class'])) {
            $data['options']['compiler_class'] = RouteCompiler::class;
        }
        parent::__construct($data);
    }
}