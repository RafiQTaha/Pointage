<?php

namespace App\Repository;

use App\Entity\ISeanceSalle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ISeanceSalle>
 *
 * @method ISeanceSalle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ISeanceSalle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ISeanceSalle[]    findAll()
 * @method ISeanceSalle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ISeanceSalleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ISeanceSalle::class);
    }

    public function add(ISeanceSalle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ISeanceSalle $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ISeanceSalle[] Returns an array of ISeanceSalle objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ISeanceSalle
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
