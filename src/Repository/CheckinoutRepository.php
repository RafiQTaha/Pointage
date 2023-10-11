<?php

namespace App\Repository;

use App\Entity\Checkinout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Checkinout>
 *
 * @method Checkinout|null find($id, $lockMode = null, $lockVersion = null)
 * @method Checkinout|null findOneBy(array $criteria, array $orderBy = null)
 * @method Checkinout[]    findAll()
 * @method Checkinout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CheckinoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Checkinout::class);
    }

    public function add(Checkinout $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Checkinout $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Checkinout[] Returns an array of Checkinout objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

   public function findOneBySNAndDateAndUserIdMin($sn,$userId,$date)
   {
       return $this->createQueryBuilder('c')
           ->Where('c.sn in (:sn)')
           ->andWhere('c.USERID = :userId')
           ->andWhere('c.CHECKTIME <= :date')
           ->setParameter('sn', $sn)
           ->setParameter('userId', $userId)
           ->setParameter('date', $date)
           ->orderBy('c.CHECKTIME','DESC')
           ->setMaxResults(1)
           ->getQuery()
           ->getOneOrNullResult()
       ;
    //    dd($return);
   }

   public function findOneBySNAndDateAndUserIdMax($sn,$userId,$date)
   {
       return $this->createQueryBuilder('c')
           ->Where('c.sn in (:sn)')
           ->andWhere('c.USERID = :userId')
           ->andWhere('c.CHECKTIME >= :date')
           ->setParameter('sn', $sn)
           ->setParameter('userId', $userId)
           ->setParameter('date', $date)
           ->orderBy('c.CHECKTIME','ASC')
           ->setMaxResults(1)
           ->getQuery()
           ->getOneOrNullResult()
       ;
    //    dd($return);
   }

   public function findOneBySNAndDateAndUserId($sn,$userId,$date)
   {
       return $this->createQueryBuilder('c')
           ->Where('c.sn in (:sn)')
           ->andWhere('c.USERID = :userId')
           ->andWhere('c.CHECKTIME >= :date')
           ->setParameter('sn', $sn)
           ->setParameter('userId', $userId)
           ->setParameter('date', $date)
           ->orderBy('c.CHECKTIME','ASC')
           ->setMaxResults(1)
           ->getQuery()
           ->getOneOrNullResult()
       ;
    //    dd($return);
   }
}
