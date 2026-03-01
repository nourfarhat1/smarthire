package com.example.smarthire.controllers.user;

import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.UserService;
import com.example.smarthire.utils.SessionManager;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.TextField;
import java.sql.SQLException;

public class UserProfileController {

    @FXML private TextField firstNameField, lastNameField, emailField, phoneField;
    private final UserService userService = new UserService();
    private User currentUser;

    @FXML
    public void initialize() {
        currentUser = SessionManager.getUser();
        if (currentUser != null) {
            firstNameField.setText(currentUser.getFirstName());
            lastNameField.setText(currentUser.getLastName());
            emailField.setText(currentUser.getEmail());
            phoneField.setText(currentUser.getPhoneNumber());
        }
    }

    private boolean validateProfile() {
        String emailRegex = "^[A-Za-z0-9+_.-]+@(.+)$";

        if (!emailField.getText().matches(emailRegex)) {
            showSimpleAlert("Invalid Email", "Please enter a valid email address.");
            return false;
        }

        if (!phoneField.getText().matches("\\d{8}")) {
            showSimpleAlert("Invalid Phone", "The phone number must be exactly 8 digits.");
            return false;
        }

        if (firstNameField.getText().isBlank() || lastNameField.getText().isBlank()) {
            showSimpleAlert("Error", "Names cannot be empty.");
            return false;
        }

        return true;
    }

    @FXML
    private void handleUpdateProfile() {
        if (!validateProfile()) return;

        try {
            currentUser.setFirstName(firstNameField.getText());
            currentUser.setLastName(lastNameField.getText());
            currentUser.setEmail(emailField.getText());
            currentUser.setPhoneNumber(phoneField.getText());

            userService.update(currentUser);
            SessionManager.setUser(currentUser); // Refresh session

            showSimpleAlert("Profile Updated", "Your information has been saved successfully.");
        } catch (SQLException e) {
            e.printStackTrace();
            showSimpleAlert("Update Failed", "Error connecting to database.");
        }
    }

    private void showSimpleAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.show();
    }
}