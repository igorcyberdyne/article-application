<?php

namespace App\Repository;

use App\Entity\Article;
use App\Exception\UnknownFieldException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Throwable;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @throws Exception
     */
    public function countAllBy(array $criteria): int
    {
        $qb = $this->getBuilderByCriteria($criteria);

        $qb->select($qb->expr()->count('a.id'));

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (Throwable $throwable) {
            throw new Exception($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @throws Exception
     */
    public function findAllBy(array $criteria, ?int $limit = null, ?int $offset = null): array
    {
        $qb = $this->getBuilderByCriteria($criteria);

        try {
            $articles = $qb->orderBy("a.id", "DESC")->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();
        } catch (Throwable $throwable) {
            throw new Exception($throwable->getMessage(), $throwable->getCode(), $throwable);
        }

        return $articles;
    }

    /**
     * @param array $criteria
     * @return QueryBuilder
     * @throws UnknownFieldException
     */
    public function getBuilderByCriteria(array $criteria): QueryBuilder
    {
        $fieldAuthorized = [
            "title",
            "authorName",
            "sourceName",
        ];
        if (!empty($criteria)) {
            foreach ($criteria as $key => $value) {
                if (in_array($key, $fieldAuthorized)) {
                    continue;
                }

                throw new UnknownFieldException($key);
            }
        }

        $qb = $this->createQueryBuilder("a")->orderBy('a.id', 'ASC');
        foreach ($criteria as $field => $value) {
            if (empty($value)) {
                continue;
            }

            $qb->orWhere($qb->expr()->like("a.$field", ":$field"))
                ->setParameter($field, "%$value%");
        }

        return $qb;
    }

}
