<?php

namespace App\Repository;

use App\Document\Session;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class SessionRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetadata = $dm->getClassMetadata(Session::class);
        parent::__construct($dm, $uow, $classMetadata);
    }

    public function findPaginated(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder();
        $total = (clone $qb)->count()->getQuery()->execute();

        $items = $qb
            ->sort('scheduledAt', 'asc')
            ->skip($offset)
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();

        return [
            'items' => array_values($items),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => (int) ceil($total / $limit),
        ];
    }
}
