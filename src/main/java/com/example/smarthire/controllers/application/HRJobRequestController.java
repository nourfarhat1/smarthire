package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.utils.SessionManager;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.VBox;

import java.sql.SQLException;
import java.util.List;
import java.util.stream.Collectors;
public class HRJobRequestController {

    @FXML
    private ListView<JobRequest> appListView;

    private final ApplicationService appService = new ApplicationService();
    @FXML
    private TextField searchField;
    @FXML
    public void initialize() {
        // Custom Cell Factory for "cards"
        appListView.setCellFactory(param -> new ListCell<>() {
            private final VBox layout = new VBox(5);
            private final Label candidateLabel = new Label();
            private final Label jobLabel = new Label();
            private final Label salaryLabel = new Label(); // NEW
            private final Label statusLabel = new Label();
            private final Button viewBtn = new Button("View cover letter");

            {
                layout.setStyle("-fx-padding: 10; -fx-border-color: #ddd; -fx-border-radius: 5; -fx-background-color: white;");
                candidateLabel.setStyle("-fx-font-weight: bold; -fx-font-size: 14px;");
                jobLabel.setStyle("-fx-font-size: 13px; -fx-text-fill: #2f4188;");
                salaryLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #2f4188;"); // NEW
                statusLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #555;");
                viewBtn.setStyle("-fx-background-color: #87a042; -fx-text-fill: white;");

                viewBtn.setOnAction(e -> {
                    JobRequest req = getItem();
                    if(req != null) showCoverLetter(req);
                });

                layout.getChildren().addAll(candidateLabel, jobLabel, salaryLabel, statusLabel, viewBtn); // ADD salaryLabel
            }

            @Override
            protected void updateItem(JobRequest req, boolean empty) {
                super.updateItem(req, empty);
                if(empty || req == null) {
                    setGraphic(null);
                } else {
                    candidateLabel.setText(req.getCandidateName());
                    jobLabel.setText("Job: " + req.getJobTitle());
                    salaryLabel.setText("Salary: " + (req.getSuggestedSalary() != null ? req.getSuggestedSalary() + " DT" : "N/A")); // NEW
                    statusLabel.setText("Status: " + req.getStatus());
                    setGraphic(layout);
                }
            }
        });

        loadApplications();
    }
    private void showCoverLetter(JobRequest req) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Cover Letter");
        alert.setHeaderText("Candidate: " + req.getCandidateName());

        // Create a TextArea for better readability
        TextArea letterArea = new TextArea(
                req.getCoverLetter() != null && !req.getCoverLetter().isEmpty()
                        ? req.getCoverLetter()
                        : "No cover letter submitted."
        );
        letterArea.setWrapText(true);               // Wrap long lines
        letterArea.setEditable(false);              // Read-only
        letterArea.setStyle(
                "-fx-font-size: 14px; " +
                        "-fx-font-family: 'Verdana'; " +
                        "-fx-control-inner-background: #f9f9f9; " + // light background
                        "-fx-text-fill: #333333;"                 // dark text
        );
        letterArea.setPrefWidth(500);               // Wider width
        letterArea.setPrefHeight(300);              // Taller height

        // Put the TextArea into the dialog pane
        alert.getDialogPane().setContent(letterArea);

        // Make the dialog resizable (optional but helpful)
        alert.getDialogPane().setPrefSize(550, 350);
        alert.setResizable(true);

        alert.showAndWait();
    }
    private ObservableList<JobRequest> allApps = FXCollections.observableArrayList();

    private void loadApplications() {
        try {
            int hrId = SessionManager.getUser().getId(); // HR ID
            List<JobRequest> appsFromDB = appService.getForHR(hrId);

            // Store all applications in master list
            allApps.setAll(appsFromDB);

            // Apply initial filter
            applySearchFilter(searchField != null ? searchField.getText() : "");

            // Add listener to search bar if it exists
            if (searchField != null) {
                searchField.textProperty().addListener((obs, oldText, newText) -> applySearchFilter(newText));
            }

        } catch (SQLException e) {
            e.printStackTrace();
            new Alert(Alert.AlertType.ERROR, "Error loading applications: " + e.getMessage()).show();
        }
    }

    private void applySearchFilter(String query) {
        if (query == null) query = "";
        String lowerQuery = query.toLowerCase();

        List<JobRequest> filtered = allApps.stream()
                // Keep only spontaneous + PENDING
                .filter(req -> (req.getJobOfferId() == null || req.getJobOfferId() == 0)
                        && "PENDING".equalsIgnoreCase(req.getStatus()))
                // Filter by search query
                .filter(req -> req.getJobTitle().toLowerCase().contains(lowerQuery)
                        || req.getCandidateName().toLowerCase().contains(lowerQuery))
                .collect(Collectors.toList());

        appListView.setItems(FXCollections.observableArrayList(filtered));
    }
    private void showDetails(JobRequest req) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Application Details");
        alert.setHeaderText("Candidate: " + req.getCandidateName());
        alert.setContentText(
                "Job: " + req.getJobTitle() + "\n" +
                        "Salary: " + (req.getSuggestedSalary() != null ? req.getSuggestedSalary() + " DT" : "N/A") + "\n" + // NEW
                        "CV: " + req.getCvUrl() + "\n" +
                        "Cover Letter:\n" + req.getCoverLetter() + "\n" +
                        "Status: " + req.getStatus() + "\n" +
                        "Submitted on: " + req.getSubmissionDate()
        );
        alert.showAndWait();
    }
}