package com.example.smarthire.controllers.application;

import com.example.smarthire.utils.Navigation;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;

public class ApplyController {

    @FXML private TextField roleField;
    @FXML private TextArea coverLetterArea;

    @FXML
    private void handleSubmit() {
        // Logic for saving to database would go here
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Success");
        alert.setHeaderText(null);
        alert.setContentText("Your application has been submitted successfully!");
        alert.showAndWait();

        // Redirect back to marketplace after success
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/JobMarketPlace.fxml");
    }

    @FXML
    private void goBack() {
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/JobMarketPlace.fxml");
    }
}