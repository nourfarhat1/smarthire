package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.services.ApplicationService;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.ComboBox;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;

import java.sql.SQLException;
import java.util.List;
import java.util.stream.Collectors;

public class AdminAppController {

    @FXML private TableView<JobRequest> table;
    @FXML private TableColumn<JobRequest, String> colCandidate, colJob, colStatus, colDate;
    @FXML private ComboBox<String> filterBox;

    private final ApplicationService service = new ApplicationService();
    private ObservableList<JobRequest> allApps = FXCollections.observableArrayList();

    @FXML
    public void initialize() {
        // Set up table columns
        colCandidate.setCellValueFactory(new PropertyValueFactory<>("candidateName"));
        colJob.setCellValueFactory(new PropertyValueFactory<>("jobTitle"));
        colStatus.setCellValueFactory(new PropertyValueFactory<>("status"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("submissionDate"));

        // Set up filter ComboBox
        filterBox.getItems().addAll(
                "All Applications",
                "Spontaneous Only",
                "Job Offers Only"
        );
        filterBox.setValue("All Applications");
        filterBox.setOnAction(e -> applyFilter());

        // Load data from database
        loadData();
    }

    private void loadData() {
        try {
            // Load all applications into the master list
            allApps.setAll(service.getAll());
            // Apply the currently selected filter
            applyFilter();
        } catch (SQLException e) {
            e.printStackTrace();
            new Alert(Alert.AlertType.ERROR, "Error loading applications: " + e.getMessage()).show();
        }
    }

    private void applyFilter() {
        String filter = filterBox.getValue();
        List<JobRequest> filtered;

        if ("Spontaneous Only".equals(filter)) {
            // Spontaneous apps have jobOfferId == null
            filtered = allApps.stream()
                    .filter(req -> req.getJobOfferId() == null)
                    .collect(Collectors.toList());
        } else if ("Job Offers Only".equals(filter)) {
            // Job offer apps have jobOfferId != null
            filtered = allApps.stream()
                    .filter(req -> req.getJobOfferId() != null)
                    .collect(Collectors.toList());
        } else { // All Applications
            filtered = allApps;
        }

        table.setItems(FXCollections.observableArrayList(filtered));
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