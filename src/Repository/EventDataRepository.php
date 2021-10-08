<?php

namespace App\Repository;

use App\Entity\EventData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EventData|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventData|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventData[]    findAll()
 * @method EventData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventData::class);
    }



    /**
     * Método para buscar eventos que contengan la categoría que recibe el método
     * @return EventData[] Returns an array of Session objects
     */
    public function findByCategory(string $category): ?array
    {
        $qb = $this->createQueryBuilder('e');
        return $qb
            ->where(
                $qb->expr()->like('e.gender', ':category')
            )
            ->setParameter('category',"%$category%")
            ->getQuery()->execute()
            ;
    }

    // /**
    //  * @return EventData[] Returns an array of EventData objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EventData
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
