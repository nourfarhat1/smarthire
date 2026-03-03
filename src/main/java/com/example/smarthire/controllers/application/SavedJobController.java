package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.utils.SessionManager;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.sql.SQLException;

public class SavedJobController {

    @FXML private ListView<JobOffer> savedListView;
    private final ApplicationService appService = new ApplicationService();

    @FXML
    public void initialize() {
        // Setup each saved job item
        savedListView.setCellFactory(param -> new ListCell<>() {
            private final HBox root = new HBox(10);
            private final VBox textData = new VBox(5);
            private final Label title = new Label();
            private final Label meta = new Label();
            private final Button removeBtn = new Button("❌ Remove");
            private final Region spacer = new Region();

            {
                title.setStyle("-fx-font-weight: bold; -fx-text-fill: #2f4188; -fx-font-size: 14px;");
                meta.setStyle("-fx-text-fill: #666;");
                removeBtn.setStyle("-fx-background-color: #ffeded; -fx-text-fill: #d32f2f; -fx-border-color: #d32f2f; -fx-cursor: hand;");

                HBox.setHgrow(spacer, Priority.ALWAYS);
                textData.getChildren().addAll(title, meta);
                root.getChildren().addAll(textData, spacer, removeBtn);
                root.setStyle("-fx-padding: 10;");

                removeBtn.setOnAction(e -> {
                    JobOffer item = getItem();
                    if (item != null) handleUnsave(item);
                });
            }

            @Override
            protected void updateItem(JobOffer item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setGraphic(null);
                } else {
                    title.setText(item.getTitle());
                    meta.setText(item.getLocation() + " | " + item.getJobType());
                    setGraphic(root);
                }
            }
        });

        loadSavedJobs();
    }

    private void loadSavedJobs() {
        if (SessionManager.getUser() == null) return;
        try {
            int userId = SessionManager.getUser().getId();
            savedListView.setItems(FXCollections.observableArrayList(appService.getSavedJobs(userId)));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void handleUnsave(JobOffer offer) {
        try {
            appService.unsaveJob(SessionManager.getUser().getId(), offer.getId());
            loadSavedJobs(); // Refresh list
        } catch (SQLException e) {
            new Alert(Alert.AlertType.ERROR, "Error: " + e.getMessage()).show();
        }
    }

    // ✅ Removed goBack() — navigation now via Sidebar
}