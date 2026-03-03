package com.example.smarthire.controllers.job;

import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.services.JobService;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import java.sql.SQLException;

public class AdminOfferController {

    @FXML private TableView<JobOffer> table;
    @FXML private TableColumn<JobOffer, String> colTitle, colRecruiter, colDate;

    private final JobService service = new JobService();

    @FXML
    public void initialize() {
        colTitle.setCellValueFactory(new PropertyValueFactory<>("title"));
        colRecruiter.setCellValueFactory(new PropertyValueFactory<>("recruiterId")); // Ideally join to get Name
        colDate.setCellValueFactory(new PropertyValueFactory<>("postedDate"));

        loadData();
    }

    private void loadData() {
        try {
            table.setItems(FXCollections.observableArrayList(service.getAll()));
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void handleDelete() {
        JobOffer selected = table.getSelectionModel().getSelectedItem();
        if (selected == null) {
            new Alert(Alert.AlertType.WARNING, "Select an offer first").show();
            return;
        }

        try {
            service.delete(selected.getId());
            loadData(); // Refresh list
            new Alert(Alert.AlertType.INFORMATION, "Offer Deleted").show();
        } catch (SQLException e) {
            new Alert(Alert.AlertType.ERROR, "Error: " + e.getMessage()).show();
        }
    }
}