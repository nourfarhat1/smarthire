package com.example.smarthire.controllers.auth;

import com.example.smarthire.HelloApplication;
import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.UserService;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.stage.Stage;

import java.io.IOException;
import java.sql.SQLException;

public class RegisterController {

    @FXML private TextField fnameField, lnameField, emailField, phoneField;
    @FXML private PasswordField passField;
    @FXML private ComboBox<String> roleCombo;

    private final UserService userService = new UserService();

    @FXML
    public void initialize() {
        roleCombo.getItems().addAll("Candidate", "HR");
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
            showAlert("Invalid Email", "Please enter a valid email address (e.g., name@domain.com).");
            return false;
        }

        if (!phoneField.getText().matches("\\d{8}")) {
            showAlert("Invalid Phone", "The phone number must contain exactly 8 digits.");
            return false;
        }

        if (passField.getText().length() < 6) {
            showAlert("Weak Password", "The password must be at least 6 characters long.");
            return false;
        }

        return true;
    }

    @FXML
    private void handleRegister() {
        if (!validateInputs()) return;

        try {
            // Check if email already exists
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
            showAlert("Success", "Registration Successful!");
            goToLogin();

        } catch (SQLException e) {
            e.printStackTrace();
            showAlert("Database Error", "Registration failed: " + e.getMessage());
        }
    }

    private void showAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.showAndWait();
    }

    @FXML
    private void goToLogin() {
        try {
            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource("/com/example/smarthire/fxml/fxmls/frontend/auth/Login.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) emailField.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}