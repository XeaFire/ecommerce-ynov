<?php

namespace App\Repository;

use App\Entity\MasterclassPage;
use App\Entity\Masterclass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MasterclassPage>
 */
class MasterclassPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MasterclassPage::class);
    }

    //    /**
    //     * @return MasterclassPage[] Returns an array of MasterclassPage objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Trouve la page suivante dans une masterclass
     */
    public function findNextPage(Masterclass $masterclass, int $currentPosition): ?MasterclassPage
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.masterclass = :masterclass')
            ->andWhere('p.position > :position')
            ->setParameter('masterclass', $masterclass)
            ->setParameter('position', $currentPosition)
            ->orderBy('p.position', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
