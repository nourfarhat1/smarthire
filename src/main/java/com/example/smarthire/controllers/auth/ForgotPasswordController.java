package com.example.smarthire.controllers.auth;

import com.example.smarthire.HelloApplication;
import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.EmailService;
import com.example.smarthire.services.OtpService;
import com.example.smarthire.services.SmsService;
import com.example.smarthire.services.UserService;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;

import java.io.IOException;
import java.sql.SQLException;

public class ForgotPasswordController {

    // Stage panels
    @FXML private VBox stageChoose;
    @FXML private VBox stageSend;
    @FXML private VBox stageOtp;
    @FXML private VBox stageReset;

    // Stage 2 fields
    @FXML private TextField contactField;
    @FXML private Label sendInstructionLabel;
    @FXML private Label sendStatusLabel;

    // Stage 3 fields
    @FXML private TextField otpField;
    @FXML private Label otpInstructionLabel;
    @FXML private Label otpStatusLabel;

    // Stage 4 fields
    @FXML private PasswordField newPassField;
    @FXML private PasswordField confirmPassField;
    @FXML private Label resetStatusLabel;

    // Services
    private final UserService  userService  = new UserService();
    private final OtpService   otpService   = new OtpService();
    private final SmsService   smsService   = new SmsService();
    private final EmailService emailService = new EmailService();

    // State
    private boolean usingSms    = false;
    private User    targetUser  = null;

    // ─── STAGE 1: Method choice ───────────────────────────────────────────────

    @FXML
    private void handleChooseSms() {
        usingSms = true;
        sendInstructionLabel.setText("Enter your registered phone number (8 digits):");
        contactField.setPromptText("e.g. 55517435");
        showStage(stageSend);
    }

    @FXML
    private void handleChooseEmail() {
        usingSms = false;
        sendInstructionLabel.setText("Enter your registered email address:");
        contactField.setPromptText("e.g. name@domain.com");
        showStage(stageSend);
    }

    // ─── STAGE 2: Send OTP ────────────────────────────────────────────────────

    @FXML
    private void handleSendOtp() {
        String contact = contactField.getText().trim();

        if (contact.isEmpty()) {
            setStatus(sendStatusLabel, "❌ Please enter your contact info.", false);
            return;
        }

        try {
            // Look up user by phone or email
            if (usingSms) {
                if (!contact.matches("\\d{8}")) {
                    setStatus(sendStatusLabel, "❌ Phone must be exactly 8 digits.", false);
                    return;
                }
                targetUser = userService.getByPhone(contact);
            } else {
                targetUser = userService.getByEmail(contact);
            }

            if (targetUser == null) {
                setStatus(sendStatusLabel, "❌ No account found with this " +
                        (usingSms ? "phone number." : "email address."), false);
                return;
            }

            // Generate and save OTP
            String otp = otpService.generateAndSaveOtp(targetUser.getId());

            // Send via chosen method
            if (usingSms) {
                String fullNumber = "+216" + contact;
                smsService.sendOtp(fullNumber, otp);
                setStatus(sendStatusLabel, "✔ Code sent to +216" + contact, true);
                otpInstructionLabel.setText("Enter the 6-digit code sent to +216" + contact + ":");
            } else {
                emailService.sendOtp(contact, otp);
                setStatus(sendStatusLabel, "✔ Code sent to " + contact, true);
                otpInstructionLabel.setText("Enter the 6-digit code sent to " + contact + ":");
            }

            // Move to Stage 3 after short delay feel
            showStage(stageOtp);

        } catch (SQLException e) {
            e.printStackTrace();
            setStatus(sendStatusLabel, "❌ Database error. Please try again.", false);
        } catch (IOException e) {
            e.printStackTrace();
            setStatus(sendStatusLabel, "❌ Failed to send code. Check your connection.", false);
        } catch (Exception e) {
            e.printStackTrace();
            setStatus(sendStatusLabel, "❌ Unexpected error: " + e.getMessage(), false);
        }
    }

    // ─── STAGE 3: Verify OTP ─────────────────────────────────────────────────

    @FXML
    private void handleVerifyOtp() {
        String entered = otpField.getText().trim();

        if (entered.isEmpty()) {
            setStatus(otpStatusLabel, "❌ Please enter the code.", false);
            return;
        }

        if (targetUser == null) {
            setStatus(otpStatusLabel, "❌ Session expired. Please start over.", false);
            return;
        }

        try {
            boolean valid = otpService.validateOtp(targetUser.getId(), entered);

            if (valid) {
                setStatus(otpStatusLabel, "✔ Code verified!", true);
                showStage(stageReset);
            } else {
                setStatus(otpStatusLabel, "❌ Invalid or expired code. Please try again.", false);
            }

        } catch (SQLException e) {
            e.printStackTrace();
            setStatus(otpStatusLabel, "❌ Database error. Please try again.", false);
        }
    }

    // ─── STAGE 4: Reset Password ──────────────────────────────────────────────

    @FXML
    private void handleResetPassword() {
        String newPass     = newPassField.getText();
        String confirmPass = confirmPassField.getText();

        if (newPass.isEmpty() || confirmPass.isEmpty()) {
            setStatus(resetStatusLabel, "❌ Please fill in both fields.", false);
            return;
        }

        if (newPass.length() < 6) {
            setStatus(resetStatusLabel, "❌ Password must be at least 6 characters.", false);
            return;
        }

        if (!newPass.equals(confirmPass)) {
            setStatus(resetStatusLabel, "❌ Passwords do not match.", false);
            return;
        }

        try {
            userService.updatePassword(targetUser.getId(), newPass);
            otpService.clearOtp(targetUser.getId()); // invalidate used OTP

            showAlert(Alert.AlertType.INFORMATION, "Success",
                    "Your password has been reset successfully!\nPlease log in with your new password.");
            goToLogin();

        } catch (SQLException e) {
            e.printStackTrace();
            setStatus(resetStatusLabel, "❌ Database error. Could not reset password.", false);
        }
    }

    // ─── Navigation ───────────────────────────────────────────────────────────

    @FXML
    private void goToLogin() {
        try {
            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource(
                    "/com/example/smarthire/fxml/fxmls/frontend/auth/Login.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) stageChoose.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Hides all stages then shows only the target one.
     */
    private void showStage(VBox stageToShow) {
        for (VBox stage : new VBox[]{stageChoose, stageSend, stageOtp, stageReset}) {
            stage.setVisible(false);
            stage.setManaged(false);
        }
        stageToShow.setVisible(true);
        stageToShow.setManaged(true);
    }

    /**
     * Sets a status label with green or red styling.
     */
    private void setStatus(Label label, String message, boolean success) {
        label.setText(message);
        label.setStyle(success
                ? "-fx-text-fill: #87a042; -fx-font-weight: bold;"
                : "-fx-text-fill: #d9534f; -fx-font-weight: bold;");
    }

    private void showAlert(Alert.AlertType type, String title, String content) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.showAndWait();
    }
}