package com.example.smarthire.services;

import com.example.smarthire.utils.MyDatabase;

import java.sql.*;
import java.time.LocalDateTime;
import java.util.Random;

public class OtpService {

    private Connection connection;

    public OtpService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    /**
     * Generates a 6-digit OTP, saves it to the DB with a 10-minute expiry.
     * Returns the generated code.
     */
    public String generateAndSaveOtp(int userId) throws SQLException {
        String otp = String.format("%06d", new Random().nextInt(999999));
        LocalDateTime expiry = LocalDateTime.now().plusMinutes(10);

        String sql = "UPDATE app_user SET otp_code = ?, otp_expiry = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, otp);
            ps.setTimestamp(2, Timestamp.valueOf(expiry));
            ps.setInt(3, userId);
            ps.executeUpdate();
        }

        System.out.println("Generated OTP: " + otp + " for user ID: " + userId);
        return otp;
    }

    /**
     * Validates the entered OTP against the stored one.
     * Returns true if it matches and hasn't expired.
     */
    public boolean validateOtp(int userId, String enteredOtp) throws SQLException {
        String sql = "SELECT otp_code, otp_expiry FROM app_user WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    String storedOtp = rs.getString("otp_code");
                    Timestamp expiry = rs.getTimestamp("otp_expiry");

                    if (storedOtp == null || expiry == null) return false;

                    boolean notExpired = expiry.toLocalDateTime().isAfter(LocalDateTime.now());
                    boolean matches = storedOtp.equals(enteredOtp);

                    return matches && notExpired;
                }
            }
        }
        return false;
    }

    /**
     * Clears the OTP after successful use so it can't be reused.
     */
    public void clearOtp(int userId) throws SQLException {
        String sql = "UPDATE app_user SET otp_code = NULL, otp_expiry = NULL WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            ps.executeUpdate();
        }
    }
}