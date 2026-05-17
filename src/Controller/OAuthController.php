<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\AppAuthenticator;
use App\Security\GitHubAuthenticator;
use App\Security\GoogleAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

#[Route('/login/oauth')]
final class OAuthController extends AbstractController
{
    #[Route('/google', name: 'app_oauth_google', methods: ['GET'])]
    public function googleRedirect(Request $request, GoogleAuthenticator $googleAuthenticator): RedirectResponse
    {
        $provider = $googleAuthenticator->getProvider();
        $url = $provider->getAuthorizationUrl(['scope' => ['openid', 'email', 'profile']]);
        $request->getSession()->set('oauth.google.state', $provider->getState());

        return $this->redirect($url);
    }

    #[Route('/google/check', name: 'app_oauth_google_check', methods: ['GET'])]
    public function googleCheck(
        Request $request,
        GoogleAuthenticator $googleAuthenticator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $appAuthenticator,
    ): RedirectResponse {
        $state = (string) $request->query->get('state');
        if ($state === '' || $state !== (string) $request->getSession()->get('oauth.google.state')) {
            throw $this->createAccessDeniedException('Invalid OAuth state.');
        }

        $provider = $googleAuthenticator->getProvider();
        try {
            $token = $provider->getAccessToken('authorization_code', ['code' => (string) $request->query->get('code')]);
            $oauthUser = $provider->getResourceOwner($token);
        } catch (IdentityProviderException) {
            $this->addFlash('error', 'Google authentication failed.');

            return $this->redirectToRoute('app_login');
        }

        $email = mb_strtolower((string) $oauthUser->getEmail());
        $providerId = (string) $oauthUser->getId();
        $user = $userRepository->findOneBy(['googleId' => $providerId]) ?? $userRepository->findOneBy(['email' => $email]);
        if ($user === null) {
            $user = (new User())
                ->setEmail($email)
                ->setIsVerified(true);
            $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(24))));
        }

        $user
            ->setGoogleId($providerId)
            ->setIsVerified(true)
            ->setDisplayName($user->getDisplayName() ?: $oauthUser->getName())
            ->setAvatarUrl((string) ($oauthUser->toArray()['picture'] ?? $user->getAvatarUrl()));

        $entityManager->persist($user);
        $entityManager->flush();

        return $userAuthenticator->authenticateUser($user, $appAuthenticator, $request);
    }

    #[Route('/github', name: 'app_oauth_github', methods: ['GET'])]
    public function githubRedirect(Request $request, GitHubAuthenticator $githubAuthenticator): RedirectResponse
    {
        $provider = $githubAuthenticator->getProvider();
        $url = $provider->getAuthorizationUrl(['scope' => ['user:email']]);
        $request->getSession()->set('oauth.github.state', $provider->getState());

        return $this->redirect($url);
    }

    #[Route('/github/check', name: 'app_oauth_github_check', methods: ['GET'])]
    public function githubCheck(
        Request $request,
        GitHubAuthenticator $githubAuthenticator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $appAuthenticator,
    ): RedirectResponse {
        $state = (string) $request->query->get('state');
        if ($state === '' || $state !== (string) $request->getSession()->get('oauth.github.state')) {
            throw $this->createAccessDeniedException('Invalid OAuth state.');
        }

        $provider = $githubAuthenticator->getProvider();
        try {
            $token = $provider->getAccessToken('authorization_code', ['code' => (string) $request->query->get('code')]);
            $oauthUser = $provider->getResourceOwner($token);
        } catch (IdentityProviderException) {
            $this->addFlash('error', 'GitHub authentication failed.');

            return $this->redirectToRoute('app_login');
        }

        $oauthData = $oauthUser->toArray();
        $email = mb_strtolower((string) ($oauthData['email'] ?? ''));
        if ($email === '') {
            $email = mb_strtolower((string) ($oauthData['login'] ?? 'github-user')).'@users.noreply.github.com';
        }

        $providerId = (string) $oauthUser->getId();
        $user = $userRepository->findOneBy(['githubId' => $providerId]) ?? $userRepository->findOneBy(['email' => $email]);
        if ($user === null) {
            $user = (new User())
                ->setEmail($email)
                ->setIsVerified(true);
            $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(24))));
        }

        $user
            ->setGithubId($providerId)
            ->setIsVerified(true)
            ->setDisplayName($user->getDisplayName() ?: (string) ($oauthData['name'] ?? $oauthData['login'] ?? null))
            ->setAvatarUrl((string) ($oauthData['avatar_url'] ?? $user->getAvatarUrl()));

        $entityManager->persist($user);
        $entityManager->flush();

        return $userAuthenticator->authenticateUser($user, $appAuthenticator, $request);
    }
}
