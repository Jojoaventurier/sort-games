<?php

namespace App\Repository;

use App\Entity\GameEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameEntry>
 */
class GameEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameEntry::class);
    }

    public function searchInLists(string $query, array $listIds): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.fuzzyName LIKE :q')
            ->andWhere('g.gameList IN (:lists)')
            ->setParameter('q', '%' . $query . '%')
            ->setParameter('lists', $listIds)
            ->orderBy('g.normalizedName', 'ASC')
            ->getQuery()
            ->getResult();
    }

        public function findDuplicatesInLists(array $listIds): array
    {
        return $this->createQueryBuilder('g')
            ->select('g.fuzzyName AS name, COUNT(g.id) AS total')
            ->where('g.gameList IN (:lists)')
            ->setParameter('lists', $listIds)
            ->groupBy('g.fuzzyName')
            ->having('COUNT(g.id) > 1')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getResult();
    }

        public function findByFuzzyNameInLists(string $fuzzyName, array $listIds): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.fuzzyName = :name')
            ->andWhere('g.gameList IN (:lists)')
            ->setParameter('name', $fuzzyName)
            ->setParameter('lists', $listIds)
            ->orderBy('g.tag', 'ASC')
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return Gameslist[] Returns an array of Gameslist objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Gameslist
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
