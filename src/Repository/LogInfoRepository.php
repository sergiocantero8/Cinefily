<?php

namespace App\Repository;

use App\Entity\LogInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LogInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogInfo[]    findAll()
 * @method LogInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogInfo::class);
    }

    // /**
    //  * @return LogInfo[] Returns an array of LogInfo objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LogInfo
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
