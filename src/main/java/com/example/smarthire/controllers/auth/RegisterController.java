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

public class RegisterController {

    @FXML private TextField fnameField, lnameField, emailField, phoneField;
    @FXML private PasswordField passField;
    @FXML private ComboBox<String> roleCombo;
    @FXML private Label faceStatusLabel;
    @FXML private Button captureFaceBtn;

    private final UserService userService = new UserService();
    private final FaceService faceService = new FaceService();
    private String capturedFaceToken = null;

    @FXML
    public void initialize() {
        roleCombo.getItems().addAll("Candidate", "HR");
    }

    @FXML
    private void handleCaptureFace() {
        faceStatusLabel.setText("Opening camera...");
        faceStatusLabel.setStyle("-fx-text-fill: #666;");

        WebcamCaptureDialog dialog = new WebcamCaptureDialog();
        BufferedImage image = dialog.capture();

        if (image == null) {
            faceStatusLabel.setText("❌ Capture cancelled.");
            faceStatusLabel.setStyle("-fx-text-fill: #d9534f;");
            return;
        }

        try {
            String token = faceService.detectFace(image);
            if (token != null) {
                capturedFaceToken = token;
                faceStatusLabel.setText("✔ Face captured successfully!");
                faceStatusLabel.setStyle("-fx-text-fill: #87a042; -fx-font-weight: bold;");
                captureFaceBtn.setText("Re-capture Face");
            } else {
                faceStatusLabel.setText("❌ No face detected. Please try again.");
                faceStatusLabel.setStyle("-fx-text-fill: #d9534f;");
            }
        } catch (IOException e) {
            e.printStackTrace();
            faceStatusLabel.setText("❌ API error. Check your connection.");
            faceStatusLabel.setStyle("-fx-text-fill: #d9534f;");
        }
    }

    private boolean validateInputs() {
        String emailRegex = "^[A-Za-z0-9+_.-]+@(.+)$";

        if (fnameField.getText().isBlank() || lnameField.getText().isBlank() ||
                emailField.getText().isBlank() || phoneField.getText().isBlank() ||
                passField.getText().isBlank() || roleCombo.getValue() == null) {
            showAlert("Error", "All fields are required.");
            return false;
        }

        if (!emailField.getText().matches(emailRegex)) {
            showAlert("Invalid Email", "Please enter a valid email address.");
            return false;
        }

        if (!phoneField.getText().matches("\\d{8}")) {
            showAlert("Invalid Phone", "Phone number must be exactly 8 digits.");
            return false;
        }

        if (passField.getText().length() < 6) {
            showAlert("Weak Password", "Password must be at least 6 characters.");
            return false;
        }

        return true;
    }

    @FXML
    private void handleRegister() {
        if (!validateInputs()) return;

        try {
            if (userService.emailExists(emailField.getText())) {
                showAlert("Error", "This email is already registered.");
                return;
            }

            int roleId = roleCombo.getValue().equals("Candidate") ? 1 : 2;

            User newUser = new User(
                    roleId,
                    emailField.getText(),
                    passField.getText(),
                    fnameField.getText(),
                    lnameField.getText(),
                    phoneField.getText()
            );

            userService.add(newUser);

            // If face was captured, fetch the new user and save the token
            if (capturedFaceToken != null) {
                User saved = userService.getByEmail(emailField.getText());
                if (saved != null) {
                    userService.saveFaceToken(saved.getId(), capturedFaceToken);
                }
            }

            String msg = capturedFaceToken != null
                    ? "Registration Successful!\nFace recognition has been enabled for your account."
                    : "Registration Successful!\n(No face registered — password login only)";

            showAlert("Success", msg);
            goToLogin();

        } catch (SQLException e) {
            e.printStackTrace();
            showAlert("Database Error", "Registration failed: " + e.getMessage());
        }
    }

    private void showAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.showAndWait();
    }

    @FXML
    private void goToLogin() {
        try {
            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource(
                    "/com/example/smarthire/fxml/fxmls/frontend/auth/Login.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) emailField.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}