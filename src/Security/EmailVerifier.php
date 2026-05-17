<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(string:APP_URL)%')]
        private readonly string $appUrl,
    ) {
    }

    public function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->appUrl.$this->urlGenerator->generate('app_verify_email', [
            'token' => $user->getVerificationToken(),
        ]);

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@sympet.local', 'Sympet'))
            ->to((string) $user->getEmail())
            ->subject('Verify your Sympet account')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendResetPasswordEmail(User $user): void
    {
        $resetUrl = $this->appUrl.$this->urlGenerator->generate('app_reset_password', [
            'token' => $user->getResetToken(),
        ]);

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@sympet.local', 'Sympet'))
            ->to((string) $user->getEmail())
            ->subject('Reset your Sympet password')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }
}
