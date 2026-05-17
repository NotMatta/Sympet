<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\SavedItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SavedItem>
 */
class SavedItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SavedItem::class);
    }

    public function findOneForUserProduct(User $user, Product $product): ?SavedItem
    {
        return $this->findOneBy(['user' => $user, 'product' => $product]);
    }

    public function findOneForSessionProduct(string $sessionToken, Product $product): ?SavedItem
    {
        return $this->findOneBy(['sessionToken' => $sessionToken, 'product' => $product]);
    }

    /**
     * @return SavedItem[]
     */
    public function findByUserSorted(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }
}
