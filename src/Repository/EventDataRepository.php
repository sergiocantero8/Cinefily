<?php

namespace App\Repository;

use App\Entity\EventData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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



    /**
     * Método para buscar eventos que contengan el título que recibe
     * @return EventData[] Returns an array of Session objects
     */
    public function findByTitle(string $title): ?array
    {
        $qb = $this->createQueryBuilder('e');
        return $qb
            ->where(
                $qb->expr()->like('e.title', ':title')
            )
            ->setParameter('title',"%$title%")
            ->getQuery()->execute()
            ;
    }

    /**
     * Método para buscar eventos que contengan el título que recibe
     * @return Query Returns an array of Session objects
     */
    public function findByTitleQuery(string $title): Query
    {
        $qb = $this->createQueryBuilder('e');
        return $qb
            ->where(
                $qb->expr()->like('e.title', ':title')
            )
            ->setParameter('title',"%$title%")
            ->getQuery()
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
