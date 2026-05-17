<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @param int[] $categoryIds
     * @return array{products: Product[], totalFiltered: int, totalVisible: int}
     */
    public function findShopProducts(
        array $categoryIds,
        ?string $search,
        string $sort,
        int $page,
        int $limit,
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c')
            ->andWhere('p.visibility = :visible')
            ->setParameter('visible', true);

        $this->applyFilters($qb, $categoryIds, $search, $sort, true);

        $products = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $countQb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.visibility = :visible')
            ->setParameter('visible', true);

        $this->applyFilters($countQb, $categoryIds, $search, $sort, false);

        $totalVisible = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.visibility = :visible')
            ->setParameter('visible', true)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'products' => $products,
            'totalFiltered' => (int) $countQb->getQuery()->getSingleScalarResult(),
            'totalVisible' => $totalVisible,
        ];
    }

    /**
     * @param int[] $categoryIds
     */
    private function applyFilters(
        QueryBuilder $qb,
        array $categoryIds,
        ?string $search,
        string $sort,
        bool $applySort,
    ): void {
        if ($categoryIds !== []) {
            $qb
                ->andWhere('IDENTITY(p.category) IN (:categories)')
                ->setParameter('categories', $categoryIds);
        }

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere('(LOWER(p.name) LIKE :search OR LOWER(p.description) LIKE :search)')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        if (!$applySort) {
            return;
        }

        [$field, $direction] = match ($sort) {
            'price_asc' => ['p.price', 'ASC'],
            'price_desc' => ['p.price', 'DESC'],
            'name_asc' => ['p.name', 'ASC'],
            default => ['p.createdAt', 'DESC'],
        };

        $qb->orderBy($field, $direction);
    }
}
