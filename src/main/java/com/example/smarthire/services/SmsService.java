package com.example.smarthire.services;

import com.twilio.Twilio;
import com.twilio.rest.api.v2010.account.Message;
import com.twilio.type.PhoneNumber;

public class SmsService {


    static {
        Twilio.init(ACCOUNT_SID, AUTH_TOKEN);
    }

    public void sendOtp(String toPhoneNumber, String otpCode) {
        String messageBody =
                "Your SmartHire password reset code is: " + otpCode + "\n" +
                        "This code expires in 10 minutes.\n" +
                        "If you did not request this, please ignore this message.";

        Message message = Message.creator(
                new PhoneNumber(toPhoneNumber),
                new PhoneNumber(FROM_NUMBER),
                messageBody
        ).create();

        System.out.println("SMS sent. SID: " + message.getSid());
    }
}