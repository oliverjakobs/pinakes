<?php

namespace App\Mapper;

use App\Entity\User;
use App\Pinakes\DataType;
use App\Renderable\Icon;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserMapper extends PinakesMapper implements PasswordUpgraderInterface {

    public static function getTableName(): string { 
        return 'user';
    }

    public static function getSearchKey(): string { 
        return 'username';
    }

    public static function getEntityClass(): string {
        return User::class;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
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
