<?php declare(strict_types=1);


namespace sunsetbeat\SuluLinkedinFeed\Content;

use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\DataProviderAliasInterface;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderResult;

use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use sunsetbeat\SuluLinkedinFeed\Content\LinkedinFeedDataItem;

class LinkedinFeedDataProvider extends BaseDataProvider
{
    public function __construct(
        DataProviderRepositoryInterface $repository,
        ArraySerializerInterface $serializer,
    ) {
        parent::__construct($repository, $serializer);
    }

    public function getConfiguration()
    {
        if (null === $this->configuration) {
            $this->configuration = self::createConfigurationBuilder()
                ->enableTags()
                ->enableLimit()
                ->enablePagination()
                ->enablePresentAs()
                ->enableSorting(
                    [
                        ['column' => 'entity.created', 'title' => 'sulu_admin.created'],
                        ['column' => 'entity.last_change', 'title' => 'sunsetbeat.input.last_change'],
                    ]
                )
                ->getConfiguration()
            ;
        }

        return parent::getConfiguration();
    }

    public function getMetadata($locale): array
    {
        return [
            'key' => 'linkedin_feed',
            'title' => 'Linkedin Feed DataProvider',
            'icon' => 'fa-gift',
            'resourceKey' => 'linkedin_feed',
            'view' => 'sulu-admin-smart-content@sandbox',
        ];
    }

    protected function decorateDataItems(array $data)
    {
        
        $data = array_map(
            function ($item) {
                return new LinkedinFeedDataItem($item );
            },
            $data
        );
        return $data;
    }

    public function resolveDataItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        return parent::resolveDataItems($filters,
        $propertyParameter,
        $options,
        $limit,
        $page,
        $pageSize);
    }

    public function resolveResourceItems(
        array $filters,
        array $propertyParameter,
        array $options = [],
        $limit = null,
        $page = 1,
        $pageSize = null
    ) {
        return parent::resolveResourceItems(
            $filters,
            $propertyParameter,
            $options,
            $limit,
            $page,
            $pageSize);
    }
}
