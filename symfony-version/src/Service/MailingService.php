<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailingService
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }

    public function sendInterviewNotification(
        string $recipientEmail, 
        string $candidateName, 
        string $jobTitle, 
        string $dateTime, 
        ?string $meetingLink = null
    ): bool {
        try {
            $email = (new Email())
                ->from('mrdridi22@gmail.com')
                ->to($recipientEmail)
                ->subject('Interview Scheduled - SmartHire');

            // Location information logic
            $locationInfo = '';
            if ($meetingLink && !empty($meetingLink)) {
                $locationInfo = "💻 Online Meeting Link: " . $meetingLink;
            } else {
                $locationInfo = "📍 Location: Please check the invitation for the physical address.";
            }

            $emailBody = "Dear " . $candidateName . ",\n\n"
                . "We are pleased to inform you that an interview has been scheduled for the "
                . $jobTitle . " position.\n\n"
                . "📅 Date & Time: " . $dateTime . "\n"
                . $locationInfo . "\n"
                . "An invitation has also been added to your Google Calendar.\n\n"
                . "Please confirm your availability by replying to this email.\n\n"
                . "Best regards,\nThe SmartHire Team";

            $email->text($emailBody);

            $this->mailer->send($email);

            return true;
        } catch (\Exception $e) {
            error_log('Failed to send email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendInterviewReminder(
        string $recipientEmail, 
        string $candidateName, 
        string $jobTitle, 
        string $dateTime, 
        ?string $meetingLink = null
    ): bool {
        try {
            $email = (new Email())
                ->from('mrdridi22@gmail.com')
                ->to($recipientEmail)
                ->subject('Interview Reminder - SmartHire');

            $locationInfo = '';
            if ($meetingLink && !empty($meetingLink)) {
                $locationInfo = "💻 Online Meeting Link: " . $meetingLink;
            } else {
                $locationInfo = "📍 Location: Please check the invitation for the physical address.";
            }

            $emailBody = "Dear " . $candidateName . ",\n\n"
                . "This is a friendly reminder about your upcoming interview for the "
                . $jobTitle . " position.\n\n"
                . "📅 Date & Time: " . $dateTime . "\n"
                . $locationInfo . "\n\n"
                . "Please make sure to join the interview 5 minutes before the scheduled time.\n\n"
                . "Best regards,\nThe SmartHire Team";

            $email->text($emailBody);

            $this->mailer->send($email);

            return true;
        } catch (\Exception $e) {
            error_log('Failed to send reminder email: ' . $e->getMessage());
            return false;
        }
    }
}
