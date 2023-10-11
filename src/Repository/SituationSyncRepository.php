<?php

namespace App\Repository;

use App\Entity\SituationSync;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SituationSync>
 *
 * @method SituationSync|null find($id, $lockMode = null, $lockVersion = null)
 * @method SituationSync|null findOneBy(array $criteria, array $orderBy = null)
 * @method SituationSync[]    findAll()
 * @method SituationSync[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SituationSyncRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SituationSync::class);
    }

//    /**
//     * @return SituationSync[] Returns an array of SituationSync objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SituationSync
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
