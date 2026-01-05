<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserManager
{
    public function __construct(private EntityManagerInterface $em, private RequestStack $s)
    {

    }

    public function getCurrentUser(): ?User
    {
        $session = $this->s->getSession();
        return $this->getUser($session->get('user_id'));
    }

    public function getUser($userId): ?User
    {
        $q = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $userId);
        $user = $q->getQuery()->getOneOrNullResult();
        return $user;
    }

    public function authorize($userId): bool
    {
        $session = $this->s->getSession();
        $session->set('user_id', '');

        $user = $this->getUser($userId);
        if ($user === null) {
            return false;
        }

        $session->set('user_id', $user->getId());
        return true;
    }

}
