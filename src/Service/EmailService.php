<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }


    public function sendEmail(string $form, string $to, string $subject, string $content): void
    {
        $form = $form ?? $_ENV['MAIL_SENDER'];
        $email = (new Email())
            ->from($form)
            ->to($to)
            ->subject($subject)
            ->text($content)
            ->html('<p>' . $content . '</p>');

        $this->mailer->send($email);
    }
}
