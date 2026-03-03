package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.services.ApplicationService;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import java.sql.SQLException;

public class AdminAppController {

    @FXML private TableView<JobRequest> table;
    @FXML private TableColumn<JobRequest, String> colCandidate, colJob, colStatus, colDate;

    private final ApplicationService service = new ApplicationService();

    @FXML
    public void initialize() {
        colCandidate.setCellValueFactory(new PropertyValueFactory<>("candidateName"));
        colJob.setCellValueFactory(new PropertyValueFactory<>("jobTitle"));
        colStatus.setCellValueFactory(new PropertyValueFactory<>("status"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("submissionDate"));

        loadData();
    }

    private void loadData() {
        try {
            // Use the NEW getAll() method we created in step 1
            table.setItems(FXCollections.observableArrayList(service.getAll()));
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void handleDelete() {
        JobRequest selected = table.getSelectionModel().getSelectedItem();
        if (selected == null) {
            new Alert(Alert.AlertType.WARNING, "Select an application first").show();
            return;
        }

        try {
            service.delete(selected.getId());
            loadData(); // Refresh list
            new Alert(Alert.AlertType.INFORMATION, "Application Deleted").show();
        } catch (SQLException e) {
            new Alert(Alert.AlertType.ERROR, "Error: " + e.getMessage()).show();
        }
    }
}