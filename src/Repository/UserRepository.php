<?php

namespace App\Repository;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends PinakesRepository implements PasswordUpgraderInterface {
    
    protected static function getEntityClass(): string {
        return User::class;
    }

    public function getSearchKey(): string{
        return 'username';
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    protected function defineDataFields(): array {
        return [
            'username' => [
                'caption' => 'Username',
                'data' => 'username'
            ],
            'roles' => [
                'caption' => 'Roles',
                'data' => fn(User $u) => implode('; ', $u->getRoles()),
            ],

            // TODO impersonation
        ];
    }

    public function getDataFieldsList(): array {
        return $this->composeDataFields([ 'username', 'roles' ]);
    }
}
