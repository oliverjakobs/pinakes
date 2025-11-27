<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use App\Pinakes\Renderer;
use App\Pinakes\ViewElement;
use Doctrine\Persistence\ManagerRegistry;

class TransactionRepository extends PinakesRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Transaction::class);
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
            ],
            'amount' => [
                'caption' => 'Amount',
                'data' => 'amount',
                'render' => fn($data) => Renderer::RenderCurrency($data),
                'style_class' => 'align-right fit-content'
            ],
            'timestamp' => [
                'caption' => 'Timestamp',
                'data' => 'timestamp',
                'style_class' => 'align-right fit-content'
            ],
            'edit' => [
                'caption' => '',
                'data' => fn(Transaction $t) => $t->getLinkEdit(ViewElement::icon('pencil-square')),
                'visibility' => User::ROLE_ADMIN
            ],
            'delete' => [
                'caption' => '',
                'data' => fn(Transaction $t) => $t->getLinkDelete(ViewElement::icon('trash3')),
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
}
