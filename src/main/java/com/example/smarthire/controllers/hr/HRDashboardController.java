package com.example.smarthire.controllers.hr;

import com.example.smarthire.utils.Navigation;
import javafx.fxml.FXML;
import javafx.scene.control.Label;

public class HRDashboardController {

    @FXML
    private Label activeOffersLabel;

    @FXML
    private Label totalCandidatesLabel;

    @FXML
    public void initialize() {
        // In a real app, you would call a service here:
        // int offerCount = jobService.countActiveOffers(SessionManager.getUser().getId());

        // For now, we set dummy data or fetch from DB if your services are ready
        activeOffersLabel.setText("3"); // Example data
        totalCandidatesLabel.setText("12");
    }
}