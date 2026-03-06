package com.example.smarthire.controllers.auth;

import com.example.smarthire.HelloApplication;
import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.FaceService;
import com.example.smarthire.services.UserService;
import com.example.smarthire.utils.WebcamCaptureDialog;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.stage.Stage;

import java.awt.image.BufferedImage;
import java.io.IOException;
import java.sql.SQLException;

public class LoginController {

    @FXML private TextField emailField;
    @FXML private PasswordField passField;
    @FXML private Button loginButton;

    private final UserService userService = new UserService();
    private final FaceService faceService = new FaceService();

    @FXML
    private void handleLogin() {
        if (emailField.getText().isEmpty() || passField.getText().isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Validation Error", "Please enter email and password.");
            return;
        }

        try {
            // Step 1 — Authenticate with email + password
            User user = userService.authenticate(emailField.getText(), passField.getText());

            if (user == null) {
                showAlert(Alert.AlertType.ERROR, "Login Failed", "Invalid Email or Password.");
                return;
            }

            // Step 2 — Ban check
            if (user.isBanned()) {
                showAlert(Alert.AlertType.ERROR, "Banned Account",
                        "Your account has been suspended by an administrator. " +
                                "Please contact support for more information.");
                return;
            }

            // Step 3 — Face recognition check (only if user has a face token)
            String storedToken = userService.getFaceToken(user.getId());
            if (storedToken != null) {
                boolean faceVerified = runFaceVerification(storedToken);
                if (!faceVerified) return; // blocked — message already shown inside method
            }

            // Step 4 — All checks passed, set session and navigate
            com.example.smarthire.utils.SessionManager.setUser(user);

            String layoutPath = "/com/example/smarthire/fxml/fxmls/shared/MainLayout.fxml";
            String dashboardPath;

            String role = (user.getRoleName() != null) ? user.getRoleName().toUpperCase() : "CANDIDATE";

            switch (role) {
                case "CANDIDATE":
                    dashboardPath = "/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateDashboard.fxml";
                    break;
                case "HR":
                    dashboardPath = "/com/example/smarthire/fxml/fxmls/frontend/hr/HRDashboard.fxml";
                    break;
                case "ADMIN":
                    dashboardPath = "/com/example/smarthire/fxml/fxmls/backend/AdminStatistics.fxml";
                    break;
                default:
                    dashboardPath = "/com/example/smarthire/fxml/fxmls/frontend/candidate/JobMarketPlace.fxml";
                    break;
            }

            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource(layoutPath));
            if (loader.getLocation() == null) {
                throw new IOException("FILE NOT FOUND: " + layoutPath);
            }

            Parent mainRoot = loader.load();
            com.example.smarthire.utils.Navigation.loadContent(dashboardPath);

            Stage stage = (Stage) loginButton.getScene().getWindow();
            stage.setScene(new Scene(mainRoot));
            stage.centerOnScreen();
            stage.show();

        } catch (SQLException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Database Error", e.getMessage());
        } catch (IOException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Loading Error", "Could not load screen: " + e.getMessage());
        }
    }

    /**
     * Handles the full face verification flow.
     * Returns true if verified successfully, false if failed or cancelled.
     */
    private boolean runFaceVerification(String storedToken) {
        Alert prompt = new Alert(Alert.AlertType.CONFIRMATION);
        prompt.setTitle("Face Verification Required");
        prompt.setHeaderText(null);
        prompt.setContentText(
                "This account has face recognition enabled.\n" +
                        "Please verify your identity to continue."
        );
        prompt.getButtonTypes().setAll(ButtonType.OK, ButtonType.CANCEL);

        var result = prompt.showAndWait();
        if (result.isEmpty() || result.get() != ButtonType.OK) {
            showAlert(Alert.AlertType.WARNING, "Cancelled", "Face verification was cancelled.");
            return false;
        }

        // Run capture on JavaFX thread but with explicit stage owner cleared
        WebcamCaptureDialog dialog = new WebcamCaptureDialog();

        // Small pause to let the confirmation dialog fully close first
        try { Thread.sleep(300); } catch (InterruptedException ignored) {}

        BufferedImage image = dialog.capture();

        System.out.println("Captured image: " + image); // debug line

        if (image == null) {
            showAlert(Alert.AlertType.ERROR, "Face Verification Failed",
                    "No image captured. Access denied.");
            return false;
        }

        try {
            String liveToken = faceService.detectFace(image);
            System.out.println("Live token: " + liveToken); // debug line

            if (liveToken == null) {
                showAlert(Alert.AlertType.ERROR, "Face Verification Failed",
                        "No face detected in the image. Please try again.");
                return false;
            }

            double confidence = faceService.compareFaces(storedToken, liveToken);
            System.out.println("Face match confidence: " + confidence);

            if (confidence >= 75) {
                showAlert(Alert.AlertType.INFORMATION, "Face Verified",
                        "Identity confirmed! Welcome back.\n" +
                                "(Confidence: " + String.format("%.1f", confidence) + "%)");
                return true;
            } else {
                showAlert(Alert.AlertType.ERROR, "Face Verification Failed",
                        "Face does not match our records. Access denied.\n" +
                                "(Confidence: " + String.format("%.1f", confidence) + "%)");
                return false;
            }

        } catch (IOException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "API Error",
                    "Could not reach Face++ API. Check your internet connection.");
            return false;
        }
    }


    @FXML
    private void goToRegister() {
        switchScreen("/com/example/smarthire/fxml/fxmls/frontend/auth/Register.fxml");
    }

    @FXML
    private void goToForgotPassword() {
        switchScreen("/com/example/smarthire/fxml/fxmls/frontend/auth/ForgotPassword.fxml");
    }

    private void switchScreen(String fxmlPath) {
        try {
            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource(fxmlPath));
            if (loader.getLocation() == null) {
                showAlert(Alert.AlertType.ERROR, "File Error", "Cannot find file: " + fxmlPath);
                return;
            }
            Parent root = loader.load();
            Stage stage = (Stage) loginButton.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private void showAlert(Alert.AlertType type, String title, String content) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.showAndWait();
    }
}