<?php

namespace Loconox\EntityRoutingBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class HostServiceRegistrationCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('loconox_entity_routing.host.service.manager');

        $slugServices = $container->findTaggedServiceIds('loconox_entity_routing.host.service');
        foreach ($slugServices as $id => $tags) {
            $idElements = explode('.', $id);
            $alias = $idElements[count($idElements) - 1];
            foreach ($tags as $tag) {
                if (isset($tag['alias'])) {
                    $alias = $tag['alias'];
                }
            }

            $definition->addMethodCall('add', [new Reference($id), $id, $alias]);
        }
    }
}