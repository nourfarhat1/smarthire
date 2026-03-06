package com.example.smarthire.controllers.reclamation;

import com.example.smarthire.entities.reclamation.ReclaimType;
import com.example.smarthire.services.ComplaintService;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;

import java.sql.SQLException;

public class AdminTypeController {

    @FXML private ListView<ReclaimType> typeListView;
    @FXML private TextField nameField;
    @FXML private ComboBox<String> urgencyComboBox; // Changed from TextArea

    private final ComplaintService service = new ComplaintService();
    private ReclaimType selectedType;

    @FXML
    public void initialize() {
        // Initialize Urgency Levels
        urgencyComboBox.setItems(FXCollections.observableArrayList("Low", "Medium", "High", "Critical"));

        loadTypes();

        // Listener for List Selection
        typeListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                selectedType = newVal;
                nameField.setText(newVal.getName());
                urgencyComboBox.setValue(newVal.getUrgencyLevel());
            }
        });
    }

    private void loadTypes() {
        try {
            typeListView.getItems().setAll(service.getAllTypes());
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    private void handleSave() {
        if (nameField.getText().isEmpty() || urgencyComboBox.getValue() == null) {
            showAlert("Missing Info", "Please fill in Name and Urgency.");
            return;
        }

        try {
            if (selectedType == null) {
                // ADD NEW
                ReclaimType newType = new ReclaimType(nameField.getText(), urgencyComboBox.getValue());
                service.addType(newType);
            } else {
                // UPDATE EXISTING
                selectedType.setName(nameField.getText());
                selectedType.setUrgencyLevel(urgencyComboBox.getValue());
                service.updateType(selectedType);
            }
            clearForm();
            loadTypes();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    private void handleDelete() {
        if (selectedType == null) return;
        try {
            service.deleteType(selectedType.getId());
            clearForm();
            loadTypes();
        } catch (SQLException e) {
            showAlert("Error", "Cannot delete this category. It might be in use.");
        }
    }

    @FXML
    private void handleClear() {
        clearForm();
    }

    private void clearForm() {
        nameField.clear();
        urgencyComboBox.getSelectionModel().clearSelection();
        selectedType = null;
        typeListView.getSelectionModel().clearSelection();
    }

    private void showAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setContentText(content);
        alert.show();
    }
}