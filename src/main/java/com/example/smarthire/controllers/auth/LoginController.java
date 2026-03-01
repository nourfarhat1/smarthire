package com.example.smarthire.controllers.auth;

import com.example.smarthire.HelloApplication;
import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.UserService;
import com.example.smarthire.utils.Navigation;
import com.example.smarthire.utils.SessionManager;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.PasswordField;
import javafx.scene.control.TextField;
import javafx.stage.Stage;

import java.io.IOException;
import java.sql.SQLException;

public class LoginController {

    @FXML private TextField emailField;
    @FXML private PasswordField passField;
    @FXML private Button loginButton;

    private final UserService userService = new UserService();

    @FXML
    private void handleLogin() {
        if (emailField.getText().isEmpty() || passField.getText().isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Validation Error", "Please enter email and password.");
            return;
        }

        try {
            // 1. Authenticate
            User user = userService.authenticate(emailField.getText(), passField.getText());

            if (user != null) {

                // --- NEW BAN CHECK LOGIC ---
                if (user.isBanned()) {
                    showAlert(Alert.AlertType.ERROR, "Banned Account",
                            "Your account has been suspended by an administrator. Please contact support for more information.");
                    return; // Stop the execution here
                }
                // ----------------------------

                // 2. Set Session
                SessionManager.setUser(user);

                // 3. Determine Paths
                String layoutPath = "/com/example/smarthire/fxml/fxmls/shared/MainLayout.fxml";
                String dashboardPath;

                // Get role safely (ensure getRoleName() handles nulls)
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

                // 4. Load Main Layout
                FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource(layoutPath));
                if (loader.getLocation() == null) {
                    throw new IOException("FILE NOT FOUND: " + layoutPath);
                }

                Parent mainRoot = loader.load();

                // 5. Set Dashboard content
                Navigation.loadContent(dashboardPath);

                // 6. Switch Scene
                Stage stage = (Stage) loginButton.getScene().getWindow();
                stage.setScene(new Scene(mainRoot));
                stage.centerOnScreen();
                stage.show();

            } else {
                showAlert(Alert.AlertType.ERROR, "Login Failed", "Invalid Email or Password.");
            }

        } catch (SQLException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Database Error", e.getMessage());
        } catch (IOException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Loading Error", "Could not load screen: " + e.getMessage());
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