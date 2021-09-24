<?php

namespace App\Repository;

use App\Entity\Cinema;
use App\Entity\Room;
use App\Entity\Session;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Session|null find($id, $lockMode = null, $lockVersion = null)
 * @method Session|null findOneBy(array $criteria, array $orderBy = null)
 * @method Session[]    findAll()
 * @method Session[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

     /**
      * Método que devuelve todas las sesiones que sean posteriores a la fecha actual de un cine concreto,
      * es decir, que estén activas
      * @return Session[] Returns an array of Session objects
      */
    public function findByActiveSessions(Cinema $cinema): ?array
    {
        $now = new DateTime();
        return $this->createQueryBuilder('s')
            ->andWhere('s.cinema = :cinema')
            ->andWhere('s.schedule > :now')
            ->setParameter('cinema',$cinema)
            ->setParameter('now', $now)
            ->getQuery()->execute()
        ;
    }

    /**
     * Método que devuelve las sesiones que entrarian en conflicto con otra sesión con una hora de inicio y fin concretos.
     * Al método hay que indicarle que cine y sala se quieren comparar.
     * @param Cinema $cinema
     * @param Room $room
     * @param DateTime $start
     * @param DateTime $end
     * @return array|null Returns an array of Session objects
     */
    public function findByActiveSessionsByRoom(Cinema $cinema, Room $room, DateTime $start, DateTime $end): ?array
    {
        $qb=$this->createQueryBuilder('s');
        return $qb
            ->where(':start BETWEEN s.schedule AND s.schedule_end')
            ->orWhere(':end BETWEEN s.schedule AND s.schedule_end')
            ->orWhere('s.schedule BETWEEN :start AND :end')
            ->andWhere($qb->expr()->eq('s.cinema',':cinema'))
            ->andWhere($qb->expr()->eq('s.room',':room'))
            ->setParameter('start',$start)
            ->setParameter('end',$end)
            ->setParameter('cinema',$cinema)
            ->setParameter('room', $room)
            ->getQuery()->execute()
            ;
    }

    /**
     * Método que devuelve todas las sesiones comprendidas entre una fecha de inicio y una fecha de fin
     * @param Cinema $cinema
     * @param DateTime $start
     * @param DateTime $end
     * @return array|null Returns an array of Session objects
     */
    public function findByDate(Cinema $cinema, DateTime $start, DateTime $end): ?array
    {
        $qb=$this->createQueryBuilder('s');
        return $qb
            ->where('s.schedule BETWEEN :start AND :end')
            ->andWhere($qb->expr()->eq('s.cinema',':cinema'))
            ->setParameter('start',$start)
            ->setParameter('end',$end)
            ->setParameter('cinema',$cinema)
            ->getQuery()->execute()
            ;
    }

    // /**
    //  * @return Session[] Returns an array of Session objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Session
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
