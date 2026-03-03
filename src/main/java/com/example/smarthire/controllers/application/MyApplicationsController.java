package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.utils.SessionManager;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.GridPane;

import java.sql.SQLException;
import java.util.Optional;

public class MyApplicationsController {

    @FXML private TableView<JobRequest> appTable;
    @FXML private TableColumn<JobRequest, String> colJob, colStatus, colDate;

    private final ApplicationService appService = new ApplicationService();

    @FXML
    public void initialize() {
        colJob.setCellValueFactory(new PropertyValueFactory<>("jobTitle"));
        colStatus.setCellValueFactory(new PropertyValueFactory<>("status"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("submissionDate"));
        loadData();
    }

    private void loadData() {
        try {
            int userId = SessionManager.getUser().getId();
            appTable.setItems(FXCollections.observableArrayList(appService.getByCandidate(userId)));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    private void handleSpontaneous() {
        showAppDialog(null); // Null means new spontaneous
    }

    @FXML
    private void handleEdit() {
        JobRequest selected = appTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            alert("Please select an application to edit.");
            return;
        }
        showAppDialog(selected);
    }

    @FXML
    private void handleDelete() {
        JobRequest selected = appTable.getSelectionModel().getSelectedItem();
        if (selected == null) return;
        try {
            appService.delete(selected.getId());
            loadData();
        } catch (SQLException e) {
            alert("Error deleting: " + e.getMessage());
        }
    }

    // Reuse dialog for Spontaneous Add AND Edit
    private void showAppDialog(JobRequest existing) {
        Dialog<ButtonType> dialog = new Dialog<>();
        dialog.setTitle(existing == null ? "Spontaneous Application" : "Edit Application");
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10); grid.setVgap(10); grid.setPadding(new Insets(20));

        TextField cvField = new TextField(existing != null ? existing.getCvUrl() : "");
        TextArea letterArea = new TextArea(existing != null ? existing.getCoverLetter() : "");

        cvField.setPromptText("CV URL");
        letterArea.setPromptText("Why do you want to join us?");

        grid.add(new Label("CV URL:"), 0, 0); grid.add(cvField, 1, 0);
        grid.add(new Label("Letter:"), 0, 1); grid.add(letterArea, 1, 1);
        dialog.getDialogPane().setContent(grid);

        Optional<ButtonType> res = dialog.showAndWait();
        if (res.isPresent() && res.get() == ButtonType.OK) {
            try {
                if (existing == null) {
                    // NEW SPONTANEOUS (Job ID = 0)
                    JobRequest req = new JobRequest(SessionManager.getUser().getId(), 0, cvField.getText(), letterArea.getText());
                    appService.apply(req);
                } else {
                    // UPDATE EXISTING
                    existing.setCvUrl(cvField.getText());
                    existing.setCoverLetter(letterArea.getText());
                    appService.update(existing);
                }
                loadData();
            } catch (SQLException e) {
                alert(e.getMessage());
            }
        }
    }

    private void alert(String msg) { new Alert(Alert.AlertType.INFORMATION, msg).show(); }
}