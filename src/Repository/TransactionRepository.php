<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use App\Pinakes\DataType;
use App\Renderable\Icon;

class TransactionRepository extends PinakesRepository {

    protected static function getEntityClass(): string {
        return Transaction::class;
    }

    public function getSearchKey(): string{
        return 'reason';
    }

    public function getDefaultOrder(): array {
        return [ 'timestamp' => 'DESC' ];
    }

    public function getBalance(): float {
        $qb = $this->createQueryBuilder('t')->select('SUM(t.amount) as balance');
        return $qb->getQuery()->getSingleResult()['balance'];
    }

    protected function defineDataFields(): array {
        return [
            'reason' => [
                'caption' => 'Reason',
                'data' => 'reason',
                'edit' => true
            ],
            'amount' => [
                'caption' => 'Amount',
                'data' => 'amount',
                'data_type' => DataType::currency(),
                'edit' => true
            ],
            'timestamp' => [
                'caption' => 'Timestamp',
                'data' => 'timestamp',
                'edit' => true
            ],
            'edit' => [
                'caption' => '',
                'data' => fn(Transaction $t) => $t->getLinkEdit(Icon::create('pencil-square')),
                'data_type' => DataType::action(),
                'visibility' => User::ROLE_ADMIN
            ],
            'delete' => [
                'caption' => '',
                'data' => fn(Transaction $t) => $t->getLinkDelete(Icon::create('trash3')),
                'data_type' => DataType::action(),
                'visibility' => User::ROLE_ADMIN
            ],
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'timestamp', 'reason', 'amount', 'edit', 'delete' ]);
    }

    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'reason', 'amount', 'timestamp' ]);
    }

    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'reason', 'amount', 'timestamp' ]);
    }
}
