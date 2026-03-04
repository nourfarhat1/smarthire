package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.services.JobService;
import com.example.smarthire.utils.SessionManager;
import com.example.smarthire.utils.Navigation;
import javafx.animation.FadeTransition;
import javafx.animation.PauseTransition;
import javafx.animation.SequentialTransition;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.geometry.Insets;
import javafx.util.Duration;

import java.sql.SQLException;
import java.util.Optional;

public class JobMarketplaceController {

    @FXML private ListView<JobOffer> jobListView;
    @FXML private Button btnSavedJobs;
    @FXML private Label toastLabel;

    private final JobService jobService = new JobService();
    private final ApplicationService appService = new ApplicationService();

    @FXML
    public void initialize() {
        updateSavedCount();

        jobListView.setCellFactory(param -> new ListCell<>() {
            private final VBox layout = new VBox(8);
            private final Label title = new Label();
            private final Label meta = new Label();
            private final HBox actions = new HBox(10);
            private final Button applyBtn = new Button("Apply Now");
            private final Button saveBtn = new Button("💾 Save");

            {
                layout.setStyle("-fx-padding: 15; -fx-border-color: #ddd; -fx-border-radius: 8; -fx-background-color: white;");
                title.setStyle("-fx-font-weight: bold; -fx-font-size: 16px; -fx-text-fill: #2f4188;");
                meta.setStyle("-fx-text-fill: #666; -fx-font-size: 13px;");
                applyBtn.setStyle("-fx-background-color: #87a042; -fx-text-fill: white; -fx-font-weight: bold; -fx-cursor: hand;");
                saveBtn.setStyle("-fx-background-color: transparent; -fx-border-color: #2f4188; -fx-text-fill: #2f4188; -fx-cursor: hand;");

                applyBtn.setOnAction(e -> {
                    JobOffer item = getItem();
                    if(item != null) showApplicationDialog(item);
                });

                saveBtn.setOnAction(e -> {
                    JobOffer item = getItem();
                    if(item != null) handleSaveJob(item);
                });

                actions.getChildren().addAll(applyBtn, saveBtn);
                layout.getChildren().addAll(title, meta, actions);
            }

            @Override
            protected void updateItem(JobOffer item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setGraphic(null);
                } else {
                    title.setText(item.getTitle());
                    meta.setText(item.getCategoryName() + " | " + item.getJobType() + " | " + item.getLocation());
                    setGraphic(layout);
                }
            }
        });

        loadJobs();
    }

    private void loadJobs() {
        try {
            jobListView.setItems(FXCollections.observableArrayList(jobService.getAll()));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void updateSavedCount() {
        if (SessionManager.getUser() != null && btnSavedJobs != null) {
            try {
                int count = appService.getSavedJobsCount(SessionManager.getUser().getId());
                btnSavedJobs.setText("⭐ Saved (" + count + ")");
            } catch (SQLException e) {
                e.printStackTrace();
            }
        }
    }

    private void showToast(String message) {
        if (toastLabel == null) return;
        toastLabel.setText(message);
        toastLabel.setVisible(true);

        FadeTransition fadeIn = new FadeTransition(Duration.millis(300), toastLabel);
        fadeIn.setFromValue(0.0);
        fadeIn.setToValue(1.0);

        PauseTransition stay = new PauseTransition(Duration.seconds(1.5));

        FadeTransition fadeOut = new FadeTransition(Duration.millis(500), toastLabel);
        fadeOut.setFromValue(1.0);
        fadeOut.setToValue(0.0);
        fadeOut.setOnFinished(e -> toastLabel.setVisible(false));

        new SequentialTransition(fadeIn, stay, fadeOut).play();
    }

    @FXML
    private void handleSaveJob(JobOffer offer) {
        if (SessionManager.getUser() == null) {
            showToast("⚠️ Please log in first!");
            return;
        }
        try {
            appService.saveJob(SessionManager.getUser().getId(), offer.getId());
            updateSavedCount();
            showToast("⭐ Job saved to favorites!");
        } catch (SQLException e) {
            new Alert(Alert.AlertType.WARNING, e.getMessage()).show();
        }
    }

    // ✅ Updated navigation to use Navigation.loadContent
    @FXML
    private void goToSavedJobs() {
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/saved_jobs.fxml");
    }

    private void showApplicationDialog(JobOffer offer) {
        Dialog<JobRequest> dialog = new Dialog<>();
        dialog.setTitle("Apply for " + offer.getTitle());
        ButtonType applyType = new ButtonType("Submit", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(applyType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10); grid.setVgap(10);
        grid.setPadding(new Insets(20, 10, 10, 10));

        TextField cvField = new TextField();
        TextArea letterArea = new TextArea();
        grid.add(new Label("CV URL:"), 0, 0);
        grid.add(cvField, 1, 0);
        grid.add(new Label("Letter:"), 0, 1);
        grid.add(letterArea, 1, 1);
        dialog.getDialogPane().setContent(grid);

        dialog.setResultConverter(btn -> btn == applyType ? new JobRequest(SessionManager.getUser().getId(), offer.getId(), cvField.getText(), letterArea.getText()) : null);

        Optional<JobRequest> result = dialog.showAndWait();
        result.ifPresent(req -> {
            try {
                appService.apply(req);
                showToast("✅ Application Sent!");
            } catch (SQLException e) {
                e.printStackTrace();
            }
        });
    }
}