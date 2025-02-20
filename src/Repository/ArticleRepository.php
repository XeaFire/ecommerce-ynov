<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return Article[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Article $article, bool $flush = false): void
    {
        $this->getEntityManager()->persist($article);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $article, bool $flush = false): void
    {
        $this->getEntityManager()->remove($article);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 