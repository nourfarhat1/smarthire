package com.example.smarthire.controllers.job;

import com.example.smarthire.entities.job.JobCategory;
import com.example.smarthire.services.JobService;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.TextField;
import javafx.scene.control.cell.PropertyValueFactory;

import java.sql.SQLException;

public class JobCategoryManagementController {

    @FXML private TextField categoryNameField;
    @FXML private TableView<JobCategory> categoryTable;
    @FXML private TableColumn<JobCategory, String> colName;

    private final JobService jobService = new JobService();

    @FXML
    public void initialize() {
        colName.setCellValueFactory(new PropertyValueFactory<>("name"));
        loadCategories();
    }

    private void loadCategories() {
        try {
            categoryTable.setItems(FXCollections.observableArrayList(jobService.getCategories()));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    private void handleAddCategory() {
        String name = categoryNameField.getText().trim();

        if (name.isEmpty()) {
            new Alert(Alert.AlertType.WARNING, "Please enter a category name.").show();
            return;
        }

        try {
            jobService.addCategory(name);
            categoryNameField.clear();
            loadCategories(); // Refresh the table
            new Alert(Alert.AlertType.INFORMATION, "Category added successfully!").show();
        } catch (SQLException e) {
            new Alert(Alert.AlertType.ERROR, "Error adding category: " + e.getMessage()).show();
        }
    }

    @FXML
    private void handleDeleteCategory() {
        JobCategory selected = categoryTable.getSelectionModel().getSelectedItem();
        if (selected == null) return;

        // Add delete logic in JobService if not already there
        // jobService.deleteCategory(selected.getId());
        // loadCategories();
    }
}