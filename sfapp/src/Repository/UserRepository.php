<?php
//UserRepository.php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @brief Repository for managing User entities.
 *
 * The UserRepository provides methods to query and manipulate User entities, including password upgrades and role-based searches.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * @brief Constructs the repository with the given registry.
     *
     * @param ManagerRegistry $registry The manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    /**
     * @brief Upgrades the user's password.
     *
     * This method is used to rehash the user's password automatically over time.
     *
     * @param PasswordAuthenticatedUserInterface $user           The user entity to upgrade.
     * @param string                             $newHashedPassword The new hashed password.
     *
     * @return void
     *
     * @throws UnsupportedUserException If the user is not an instance of User.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @brief Finds a user with an exact role.
     *
     * Searches for a single user who possesses the specified role.
     *
     * @param string $role The role to search for (e.g., 'ROLE_MANAGER').
     *
     * @return User|null The User entity if found, or null otherwise.
     */
    public function findOneByExactRole(string $role): ?User
    {
        $users = $this->findAll();

        foreach ($users as $user) {
            if (in_array($role, $user->getRoles(), true)) {
                return $user;
            }
        }

        return null;
    }
}
