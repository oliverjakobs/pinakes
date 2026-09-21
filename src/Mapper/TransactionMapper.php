<?php declare(strict_types=1);

namespace App\Mapper;

use App\Entity\Transaction;
use App\Pinakes\DataType;
use App\Renderable\Icon;

class TransactionMapper extends PinakesMapper {

    public static function getTableName(): string { 
        return '`transaction`';
    }

    public static function getSearchKey(): string { 
        return 'reason';
    }

    public static function getEntityClass(): string {
        return Transaction::class;
    }

    public static function getDefaultOrder(): array {
        return [ 'timestamp' => 'DESC' ];
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
                // 'visibility' => User::ROLE_ADMIN
            ],
            'delete' => [
                'caption' => '',
                'data' => fn(Transaction $t) => $t->getLinkDelete(Icon::create('trash3')),
                'data_type' => DataType::action(),
                // 'visibility' => User::ROLE_ADMIN
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