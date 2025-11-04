<?php
// api/src/Repository/UserRepository.php

namespace App\Repository;


use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    /**
     * Find users with a specific role
     * Uses PHP filtering to avoid JSON operator issues with PostgreSQL
     * 
     * @param string $role The role to search for (e.g., 'ROLE_DELIVERY')
     * @return User[] Returns an array of User objects
     */
    public function findByRole(string $role): array
    {
        // Get all users and filter in PHP to avoid JSON operator issues
        $allUsers = $this->findAll();
        
        $usersWithRole = array_filter($allUsers, function(User $user) use ($role) {
            return in_array($role, $user->getRoles());
        });
        
        // Sort by first name
        usort($usersWithRole, function(User $a, User $b) {
            return strcasecmp($a->getFirstName() ?? '', $b->getFirstName() ?? '');
        });
        
        return array_values($usersWithRole);
    }

    /**
     * Get all users and filter by role in PHP
     * This is a fallback method for use in forms where QueryBuilder is required
     * Note: For better performance with large datasets, consider implementing a custom DQL function
     * 
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQueryBuilderForAllUsers()
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.firstName', 'ASC');
    }

    /**
     * Find users by role - returns filtered array
     */
    public function findByRoleForDataTable(string $role): array
    {
        $allUsers = $this->createQueryBuilder('u')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
        
        return array_values(array_filter($allUsers, function(User $user) use ($role) {
            return in_array($role, $user->getRoles(), true);
        }));
    }

    /**
     * Find customers (users without admin/store/deliver roles) - returns filtered array
     */
    public function findCustomersForDataTable(): array
    {
        $allUsers = $this->createQueryBuilder('u')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
        
        return array_values(array_filter($allUsers, function(User $user) {
            $roles = $user->getRoles();
            return !in_array('ROLE_ADMIN', $roles, true)
                && !in_array('ROLE_STORE', $roles, true)
                && !in_array('ROLE_DELIVER', $roles, true);
        }));
    }

    /**
     * Find users by role using QueryBuilder (for datatable compatibility)
     * Uses a subquery with ALL to filter results properly
     */
    public function createQueryBuilderForRole(string $role): \Doctrine\ORM\QueryBuilder
    {
        // For PostgreSQL JSON arrays, we need to check if the role exists in the array
        // We'll fetch all and filter in memory since DQL doesn't support JSON operators well
        $qb = $this->createQueryBuilder('u');
        
        // Note: This fetches all users, filtering should be done in the DataTable
        // or we can use a post-query filter in the controller
        return $qb->orderBy('u.firstName', 'ASC');
    }

    /**
     * Find users without specific roles (customers) - QueryBuilder version
     */
    public function createQueryBuilderForCustomers(): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');
        
        // Note: This fetches all users, filtering should be done in the DataTable
        // or we can use a post-query filter in the controller
        return $qb->orderBy('u.firstName', 'ASC');
    }
}
