<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Sulu\Bundle\AbstractBundle;

class SuluLinkedinFeedBundle extends Bundle
{
    use PersistenceExtensionTrait;
    
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
}