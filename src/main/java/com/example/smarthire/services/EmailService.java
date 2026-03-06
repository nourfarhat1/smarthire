package com.example.smarthire.services;

import com.sendgrid.Method;
import com.sendgrid.Request;
import com.sendgrid.Response;
import com.sendgrid.SendGrid;
import com.sendgrid.helpers.mail.Mail;
import com.sendgrid.helpers.mail.objects.Content;
import com.sendgrid.helpers.mail.objects.Email;

import java.io.IOException;

public class EmailService {


    /**
     * Sends an OTP code via email to the given address.
     */
    public void sendOtp(String toEmail, String otpCode) throws IOException {
        Email from = new Email(SENDER_EMAIL, SENDER_NAME);
        Email to = new Email(toEmail);

        String subject = "SmartHire — Your Password Reset Code";

        String body =
                "Hello,\n\n" +
                        "You requested a password reset for your SmartHire account.\n\n" +
                        "Your verification code is:\n\n" +
                        "        " + otpCode + "\n\n" +
                        "This code is valid for 10 minutes.\n" +
                        "If you did not request this, please ignore this email.\n\n" +
                        "— The SmartHire Team";

        Content content = new Content("text/plain", body);
        Mail mail = new Mail(from, subject, to, content);

        SendGrid sg = new SendGrid(API_KEY);
        Request request = new Request();

        request.setMethod(Method.POST);
        request.setEndpoint("mail/send");
        request.setBody(mail.build());

        Response response = sg.api(request);

        System.out.println("SendGrid status: " + response.getStatusCode());
        System.out.println("SendGrid body: " + response.getBody());
    }
}