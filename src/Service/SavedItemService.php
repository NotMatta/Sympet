<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\SavedItem;
use App\Entity\User;
use App\Repository\SavedItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SavedItemService
{
    private const SESSION_SAVED_TOKEN_KEY = 'saved.session_token';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly SavedItemRepository $savedItemRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function count(): int
    {
        $user = $this->getUser();
        if ($user !== null) {
            return $this->savedItemRepository->count(['user' => $user]);
        }

        return $this->savedItemRepository->count(['sessionToken' => $this->getSessionToken()]);
    }

    public function isSaved(Product $product): bool
    {
        $user = $this->getUser();
        if ($user !== null) {
            return $this->savedItemRepository->findOneForUserProduct($user, $product) !== null;
        }

        return $this->savedItemRepository->findOneForSessionProduct($this->getSessionToken(), $product) !== null;
    }

    public function toggle(Product $product): bool
    {
        $saved = $this->findSavedItem($product);
        if ($saved !== null) {
            $this->entityManager->remove($saved);
            $this->entityManager->flush();

            return false;
        }

        $saved = new SavedItem();
        $saved->setProduct($product);
        $user = $this->getUser();
        if ($user !== null) {
            $saved->setUser($user);
        } else {
            $saved->setSessionToken($this->getSessionToken());
        }

        $this->entityManager->persist($saved);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return SavedItem[]
     */
    public function listForCurrentUser(): array
    {
        $user = $this->getUser();
        if ($user !== null) {
            return $this->savedItemRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        }

        return $this->savedItemRepository->findBy(['sessionToken' => $this->getSessionToken()], ['createdAt' => 'DESC']);
    }

    public function mergeGuestSavedIntoUser(User $user): void
    {
        $guestItems = $this->savedItemRepository->findBy(['sessionToken' => $this->getSessionToken()]);
        foreach ($guestItems as $item) {
            $product = $item->getProduct();
            if ($product === null) {
                $this->entityManager->remove($item);
                continue;
            }
            if ($this->savedItemRepository->findOneForUserProduct($user, $product) !== null) {
                $this->entityManager->remove($item);
                continue;
            }

            $item->setUser($user);
            $item->setSessionToken(null);
            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();
    }

    private function findSavedItem(Product $product): ?SavedItem
    {
        $user = $this->getUser();
        if ($user !== null) {
            return $this->savedItemRepository->findOneForUserProduct($user, $product);
        }

        return $this->savedItemRepository->findOneForSessionProduct($this->getSessionToken(), $product);
    }

    private function getUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    private function getSessionToken(): string
    {
        $session = $this->getSession();
        $token = $session->get(self::SESSION_SAVED_TOKEN_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(16));
            $session->set(self::SESSION_SAVED_TOKEN_KEY, $token);
        }

        return $token;
    }

    private function getSession(): SessionInterface
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            throw new \RuntimeException('Session is not available.');
        }

        return $session;
    }
}
