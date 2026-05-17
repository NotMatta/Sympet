<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordFormType;
use App\Form\RegistrationFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\CartService;
use App\Service\SavedItemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EmailVerifier $emailVerifier,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_shop');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $user->setIsVerified(false);
            $user->setVerificationToken(bin2hex(random_bytes(32)));

            $entityManager->persist($user);
            $entityManager->flush();

            $emailVerifier->sendVerificationEmail($user);
            $this->addFlash('success', 'Registration complete. Please check your email to verify your account.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $userRepository->findOneByVerificationToken($token);
        if ($user === null) {
            throw $this->createNotFoundException('Invalid verification token.');
        }

        $user->setIsVerified(true)->setVerificationToken(null);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Email verified. You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(
        AuthenticationUtils $authenticationUtils,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_shop');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the firewall logout.');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailVerifier $emailVerifier,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        $form = $this->createForm(ForgotPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim((string) $form->get('email')->getData()));
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user !== null) {
                $user
                    ->setResetToken(bin2hex(random_bytes(32)))
                    ->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                $entityManager->persist($user);
                $entityManager->flush();
                $emailVerifier->sendResetPasswordEmail($user);
            }

            $this->addFlash('success', 'If an account exists for that email, a reset link has been sent.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'requestForm' => $form,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        CartService $cartService,
        SavedItemService $savedItemService,
    ): Response {
        $user = $userRepository->findOneByResetToken($token);
        if ($user === null || $user->getResetTokenExpiresAt() === null || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            throw $this->createNotFoundException('Reset link is invalid or expired.');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user
                ->setPassword($passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData()))
                ->setResetToken(null)
                ->setResetTokenExpiresAt(null);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Password reset complete. You can now log in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form,
            'cartItemCount' => $cartService->itemCount(),
            'savedItemsCount' => $savedItemService->count(),
            'search' => null,
            'sort' => null,
            'selectedCategories' => [],
        ]);
    }
}
