package com.example.smarthire.services;

import jakarta.mail.*;
import jakarta.mail.internet.InternetAddress;
import jakarta.mail.internet.MimeMessage;

import java.util.Properties;

public class MailingService {


    // MISE À JOUR : Ajout du paramètre 'meetingLink'
    public void sendInterviewNotification(String recipientEmail, String candidateName, String jobTitle, String dateTime, String meetingLink) {

        Properties props = new Properties();
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");
        props.put("mail.smtp.host", "smtp.gmail.com");
        props.put("mail.smtp.port", "587");

        Session session = Session.getInstance(props, new Authenticator() {
            @Override
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(senderEmail, appPassword);
            }
        });

        try {
            Message message = new MimeMessage(session);
            message.setFrom(new InternetAddress(senderEmail));
            message.setRecipients(Message.RecipientType.TO, InternetAddress.parse(recipientEmail));
            message.setSubject("Interview Scheduled - SmartHire");

            // --- LOGIQUE D'AFFICHAGE DU LIEN ---
            String locationInfo;
            if (meetingLink != null && !meetingLink.isEmpty()) {
                locationInfo = "💻 Online Meeting Link: " + meetingLink;
            } else {
                locationInfo = "📍 Location: Please check the invitation for the physical address.";
            }

            String emailBody = "Dear " + candidateName + ",\n\n"
                    + "We are pleased to inform you that an interview has been scheduled for the "
                    + jobTitle + " position.\n\n"
                    + "📅 Date & Time: " + dateTime + "\n"
                    + locationInfo + "\n\n" // Inclusion du lien ou de l'adresse
                    + "An invitation has also been added to your Google Calendar.\n\n"
                    + "Please confirm your availability by replying to this email.\n\n"
                    + "Best regards,\nThe SmartHire Team";

            message.setText(emailBody);

            Transport.send(message);
            System.out.println("Email sent successfully to: " + recipientEmail);

        } catch (MessagingException e) {
            e.printStackTrace();
            System.err.println("Failed to send email: " + e.getMessage());
        }
    }
}