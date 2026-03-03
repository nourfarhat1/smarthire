package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.services.JobService;
import com.example.smarthire.utils.SessionManager;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.VBox;
import java.sql.SQLException;
import java.util.Optional;

public class JobMarketplaceController {

    @FXML private ListView<JobOffer> jobListView;
    private final JobService jobService = new JobService();
    private final ApplicationService appService = new ApplicationService();

    @FXML
    public void initialize() {
        // Custom Cell Factory to display "Cards"
        jobListView.setCellFactory(param -> new ListCell<>() {
            private final VBox layout = new VBox(5);
            private final Label title = new Label();
            private final Label meta = new Label();
            private final Button applyBtn = new Button("Apply Now");

            {
                layout.setStyle("-fx-padding: 10; -fx-border-color: #ddd; -fx-border-radius: 5; -fx-background-color: white;");
                title.setStyle("-fx-font-weight: bold; -fx-font-size: 16px; -fx-text-fill: #2f4188;");
                applyBtn.setStyle("-fx-background-color: #87a042; -fx-text-fill: white;");

                applyBtn.setOnAction(e -> {
                    JobOffer item = getItem();
                    if(item != null) showApplicationDialog(item);
                });

                layout.getChildren().addAll(title, meta, applyBtn);
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
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void showApplicationDialog(JobOffer offer) {
        if (SessionManager.getUser() == null) return;

        Dialog<JobRequest> dialog = new Dialog<>();
        dialog.setTitle("Apply for " + offer.getTitle());
        dialog.setHeaderText("Submit your application details");

        ButtonType applyType = new ButtonType("Submit Application", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(applyType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10); grid.setVgap(10);
        grid.setPadding(new Insets(20, 150, 10, 10));

        TextField cvField = new TextField();
        cvField.setPromptText("Paste CV URL (Drive/LinkedIn)");
        TextArea letterArea = new TextArea();
        letterArea.setPromptText("Write a short cover letter...");

        grid.add(new Label("CV URL:"), 0, 0);
        grid.add(cvField, 1, 0);
        grid.add(new Label("Cover Letter:"), 0, 1);
        grid.add(letterArea, 1, 1);

        dialog.getDialogPane().setContent(grid);

        dialog.setResultConverter(dialogButton -> {
            if (dialogButton == applyType) {
                return new JobRequest(
                        SessionManager.getUser().getId(),
                        offer.getId(),
                        cvField.getText(),
                        letterArea.getText()
                );
            }
            return null;
        });

        Optional<JobRequest> result = dialog.showAndWait();
        result.ifPresent(req -> {
            try {
                appService.apply(req);
                new Alert(Alert.AlertType.INFORMATION, "Application Submitted!").show();
            } catch (SQLException e) {
                new Alert(Alert.AlertType.ERROR, e.getMessage()).show();
            }
        });
    }
}