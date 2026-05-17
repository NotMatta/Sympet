<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ProfileSettingsFormType;
use App\Security\EmailVerifier;
use App\Service\CartService;
use App\Service\SavedItemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        EmailVerifier $emailVerifier,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $profileForm = $this->createForm(ProfileSettingsFormType::class, $user, [
            'action' => $this->generateUrl('app_settings'),
        ]);
        $profileForm->handleRequest($request);

        $passwordForm = $this->createForm(ChangePasswordFormType::class, null, [
            'action' => $this->generateUrl('app_settings'),
        ]);
        $passwordForm->handleRequest($request);

        if ($request->request->has('profile_settings_form') && $profileForm->isSubmitted() && $profileForm->isValid()) {
            $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($user);
            $originalEmail = mb_strtolower((string) ($originalData['email'] ?? $user->getEmail()));
            $newEmail = mb_strtolower((string) $user->getEmail());

            if ($newEmail !== $originalEmail) {
                $user
                    ->setIsVerified(false)
                    ->setVerificationToken(bin2hex(random_bytes(32)));
                $emailVerifier->sendVerificationEmail($user);
                $this->addFlash('warning', 'Email changed. Please verify your new email address.');
            }

            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Profile settings saved.');

            return $this->redirectToRoute('app_settings');
        }

        if ($request->request->has('change_password_form') && $passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $current = (string) $passwordForm->get('currentPassword')->getData();
            if (!$passwordHasher->isPasswordValid($user, $current)) {
                $this->addFlash('error', 'Current password is incorrect.');

                return $this->redirectToRoute('app_settings');
            }

            $newPassword = (string) $passwordForm->get('newPassword')->getData();
            $user
                ->setPassword($passwordHasher->hashPassword($user, $newPassword))
                ->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Password updated.');

            return $this->redirectToRoute('app_settings');
        }

        return $this->render('settings/index.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }

    #[Route('/settings/delete-account', name: 'app_settings_delete_account', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isCsrfTokenValid('delete_account', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user
            ->setEmail('deleted_user_'.time().'@example.com')
            ->setDisplayName('Deleted User')
            ->setAvatarUrl(null)
            ->setGoogleId(null)
            ->setGithubId(null)
            ->setIsVerified(false)
            ->setVerificationToken(null)
            ->setResetToken(null)
            ->setResetTokenExpiresAt(null)
            ->setPassword(bin2hex(random_bytes(24)));

        $entityManager->persist($user);
        $entityManager->flush();

        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Your account has been deleted.');

        return $this->redirectToRoute('app_shop');
    }
}
