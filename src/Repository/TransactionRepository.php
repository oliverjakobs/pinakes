<?php

namespace App\Repository;

use App\Entity\Transaction;
use function App\Pinakes\RenderCurrency;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Transaction::class);
    }

    public function getSearchKey(): string{
        return 'reason';
    }

    public function getBalance(): float {
        $qb = $this->createQueryBuilder('t')->select('SUM(t.amount) as balance');
        return $qb->getQuery()->getSingleResult()['balance'];
    }

    protected function defineDataFields(): array {
        return [
            'reason' => array(
                'caption' => 'Reason',
                'data' => 'reason',
                'link' => self::LINK_SELF
            ),
            'amount' => array(
                'caption' => 'Amount',
                'data' => 'amount',
                'render' => fn($data) => RenderCurrency($data),
            ),
            'timestamp' => array(
                'caption' => 'Timestamp',
                'data' => 'timestamp',
            ),
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields(array(
            'reason', 'amount', //'timestamp'
        ));
    }
}
