<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\CartService;
use App\Service\SavedItemService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly SavedItemService $savedItemService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->cartService->mergeGuestCartIntoUser($user);
        $this->savedItemService->mergeGuestSavedIntoUser($user);
    }
}
