<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function searchPosts(string $query)
    {
        return $this->createQueryBuilder('p')
            ->where('p.title LIKE :query OR p.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery();
    }

    //    /**
    //     * @return Post[] Returns an array of Post objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    // public function findByCategory($id)
    // {
    //     return $this->createQueryBuilder('p')
    //         ->andWhere('p.category = :val')
    //         ->setParameter('val', $id)
    //         ->orderBy('p.createdAt', 'DESC')
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }
}
