<?php declare(strict_types=1);

namespace sunsetbeat\SuluLinkedinFeed\Repository;

use sunsetbeat\SuluLinkedinFeed\Entity\LinkedinFeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

/**
 * @extends ServiceEntityRepository<LinkedinFeed>
 */
class LinkedinFeedRepository extends EntityRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait {
        findByFilters as parentFindByFilters;
    }

    public function create(string $locale): LinkedinFeed
    {
        $linkedin_feed = new LinkedinFeed();

        return $linkedin_feed;
    }

    public function remove(int $id): void
    {
        /** @var object $linkedin_feed */
        $linkedin_feed = $this->getEntityManager()->getReference(
            $this->getClassName(),
            $id,
        );

        $this->getEntityManager()->remove($linkedin_feed);
        $this->getEntityManager()->flush();
    }

    public function save(LinkedinFeed $linkedin_feed): void
    {
        $this->getEntityManager()->persist($linkedin_feed);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id, string $locale): ?LinkedinFeed
    {
        $linkedin_feed = $this->find($id);
        if (!$linkedin_feed instanceof LinkedinFeed) {
            return null;
        }
        return $linkedin_feed;
    }

    /**
     * @param mixed[] $filters
     */
    public function findByFilters($filters, $page, $pageSize, $limit, $locale, $options = [])
    {
        $entities = $this->parentFindByFilters($filters, $page, $pageSize, $limit, $locale, $options);

        return $entities;
    }

    /**
     * @param string $alias
     * @param string $locale
     *
     * @return void
     */
    protected function appendJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
        // join and select entities that are used for creating data items or resource items in the DataProvider here
    }

    /**
     * @param mixed[] $options
     *
     * @return string[]
     */
    protected function append(QueryBuilder $queryBuilder, string $alias, string $locale, $options = []): array
    {
        $queryBuilder->andWhere($alias . '.enabled = true');

        return [];
    }

    protected function appendSortByJoins(QueryBuilder $queryBuilder, string $alias, string $locale): void
    {
    }

}
