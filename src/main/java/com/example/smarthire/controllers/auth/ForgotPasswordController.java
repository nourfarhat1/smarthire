package com.example.smarthire.controllers.auth;

import com.example.smarthire.HelloApplication;
import com.example.smarthire.services.UserService;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.TextField;
import javafx.stage.Stage;

import java.io.IOException;
import java.sql.SQLException;
import java.util.UUID;

public class ForgotPasswordController {

    @FXML private TextField emailField;
    @FXML private Button sendButton;

    private final UserService userService = new UserService();

    @FXML
    private void handleSendResetLink() {
        String email = emailField.getText();

        if (email.isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Warning", "Please enter your email address.");
            return;
        }

        try {
            // 1. Check if email exists in DB
            if (userService.emailExists(email)) {

                // 2. Generate a fake token (Simulate logic)
                String token = UUID.randomUUID().toString().substring(0, 8);

                // 3. Simulate Sending Email (In real app, integrate JavaMail API here)
                System.out.println("------------------------------------------------");
                System.out.println("📧 EMAIL SIMULATION for: " + email);
                System.out.println("🔗 Reset Link: app://reset-password?token=" + token);
                System.out.println("------------------------------------------------");

                showAlert(Alert.AlertType.INFORMATION, "Success", "A reset link has been sent to your email.");

                // Optional: Redirect back to login automatically
                goToLogin();

            } else {
                showAlert(Alert.AlertType.ERROR, "Error", "No account found with this email.");
            }
        } catch (SQLException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Database Error", "Could not verify email.");
        }
    }

    @FXML
    private void goToLogin() {
        try {
            // Ensure path matches your structure exactly
            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource("/com/example/smarthire/fxml/fxmls/frontend/auth/Login.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) emailField.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    // Helper for popups
    private void showAlert(Alert.AlertType type, String title, String content) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.showAndWait();
    }
}