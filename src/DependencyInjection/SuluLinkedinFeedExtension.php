<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use sunsetbeat\SuluLinkedinFeed\Entity\Travel;
use sunsetbeat\SuluLinkedinFeed\Entity\TravelBooking;
use sunsetbeat\SuluLinkedinFeed\Entity\TravelBookingDate;

class SuluLinkedinFeedExtension extends Extension implements PrependExtensionInterface
{
    use PersistenceExtensionTrait;

    public function prepend(ContainerBuilder $container): void
    {

        $container->prependExtensionConfig(
            'sulu_core',
            [
                'content' => [
                    'structure' => [
                        'paths' => [
                            'blocks_tm' => [
                                'path' => __DIR__.'/../Resources/templates/blocks',
                                'type' => 'block',
                            ],
                        ],
                    ],
                ],
            ]
        );
        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__.'/../Resources/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            __DIR__.'/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'linkedin_feed' => [
                            'routes' => [
                                'list' => 'sunsetbeat.get_linkedin_feed_list',
                                'detail' => 'sunsetbeat.get_linkedin_feed',
                            ],
                        ],
                    ],
                ]
            );
        }
        if ($container->hasExtension('sulu_media')) {
            $container->prependExtensionConfig(
                'sulu_media',
                [
                    'system_collections' => [
                        'sunsetbeat' => [
                            'meta_title' => [
                                'en' => 'Social Media',
                                'de' => 'Social Media',
                            ],
                            'collections' => [
                                'images_linkedin' => [
                                    'meta_title' => [
                                        'en' => 'Linkedin Images',
                                        'de' => 'Linkedin Bilder',
                                    ]
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }
    }


    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'sunsetbeat_sulu_linkedin_feed.config',
            $config
        );
        
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('router.yaml');
    }

}
