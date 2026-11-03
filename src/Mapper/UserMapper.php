<?php

namespace App\Mapper;

use App\Entity\User;
use App\Pinakes\DataType;
use App\Pinakes\Singleton;

class UserMapper extends PinakesMapper {
    use Singleton;

    public static function getTableName(): string { 
        return 'user';
    }

    public static function getSearchKey(): string { 
        return 'username';
    }

    public static function getEntityClass(): string {
        return User::class;
    }

    protected function defineDataFields(): array {
        return [
            'username' => [
                'caption' => 'Username',
                'data' => 'username',
                'edit' => true
            ],
            'roles' => [
                'caption' => 'Roles',
                'data' => fn(User $u) => $u->getRoles(),
                'data_type' => DataType::array()->setOptions(User::ROLES),
                'edit' => true
            ],
            // 'edit' => [
            //     'caption' => '',
            //     'data' => fn(User $u) => $u->getLinkEdit(Icon::create('pencil-square')),
            //     'data_type' => DataType::action(),
            //     'visibility' => User::ROLE_ADMIN
            // ],
            // 'delete' => [
            //     'caption' => '',
            //     'data' => fn(User $u) => $u->getLinkDelete(Icon::create('trash3')),
            //     'data_type' => DataType::action(),
            //     'visibility' => User::ROLE_ADMIN
            // ],

            // TODO impersonation
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'username', 'roles', /*'edit', 'delete'*/ ]);
    }
    public function getDataFieldsShow(): array {
        return $this->composeDataFields([ 'username', 'roles' ]);
    }
    public function getDataFieldsExport(): array {
        return $this->composeDataFields([ 'username', 'roles' ]);
    }
}
