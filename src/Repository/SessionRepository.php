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
     * Método que devuelve todas las sesiones que sean posteriores a la fecha actual de un cine y salas concretos
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
            ->where($qb->expr()->orX(
                $qb->expr()->andX($qb->expr()->lt('s.schedule', ':start'),$qb->expr()->lt(':start', 's.schedule_end')),
                $qb->expr()->andX($qb->expr()->lt('s.schedule', ':end'),$qb->expr()->lt(':end', 's.schedule_end')),
                $qb->expr()->andX($qb->expr()->lt(':start', 's.schedule'),$qb->expr()->lt('s.schedule', ':end'))
            ))
            ->andWhere($qb->expr()->eq('s.cinema',':cinema'))
            ->andWhere($qb->expr()->eq('s.room',':room'))
            ->setParameter('start',$start)
            ->setParameter('end',$end)
            ->setParameter('cinema',$cinema)
            ->setParameter('room', $room)
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
