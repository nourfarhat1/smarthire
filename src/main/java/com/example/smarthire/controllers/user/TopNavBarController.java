package com.example.smarthire.controllers.user;

import com.example.smarthire.entities.user.User;
import com.example.smarthire.utils.Navigation;
import com.example.smarthire.utils.SessionManager;
import javafx.fxml.FXML;
import javafx.scene.control.Button;
import javafx.scene.control.Label;

public class TopNavBarController {

    @FXML private Label welcomeLabel;
    @FXML private Button profileBtn;

    @FXML
    public void initialize() {
        User user = SessionManager.getUser();
        if (user != null) {
            welcomeLabel.setText("Welcome back, " + user.getFirstName() + "!");
        }
    }

    @FXML
    private void handleGoToProfile() {
        // This will load the UserProfile.fxml into the center of your MainLayout
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/shared/UserProfile.fxml");
    }
}