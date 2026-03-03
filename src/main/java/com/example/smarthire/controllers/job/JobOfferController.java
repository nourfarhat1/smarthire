package com.example.smarthire.controllers.job;

import com.example.smarthire.entities.job.JobCategory;
import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.services.JobService;
import com.example.smarthire.utils.SessionManager;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import java.sql.SQLException;

public class JobOfferController {

    @FXML private TextField titleField, locationField, salaryField;
    @FXML private TextArea descArea;
    @FXML private ComboBox<JobCategory> categoryCombo;
    @FXML private ComboBox<String> typeCombo;

    @FXML private TableView<JobOffer> offerTable;
    @FXML private TableColumn<JobOffer, String> colTitle, colCategory, colSalary, colType;

    // Add Buttons to FXML and inject them here
    @FXML private Button btnAdd, btnUpdate, btnDelete;

    private final JobService jobService = new JobService();
    private JobOffer selectedOffer; // To hold the offer being edited

    @FXML
    public void initialize() {
        colTitle.setCellValueFactory(new PropertyValueFactory<>("title"));
        colCategory.setCellValueFactory(new PropertyValueFactory<>("categoryName"));
        colSalary.setCellValueFactory(new PropertyValueFactory<>("salaryRange"));
        colType.setCellValueFactory(new PropertyValueFactory<>("jobType"));

        typeCombo.getItems().addAll("Full Time", "Part Time", "Remote", "Internship");

        try {
            categoryCombo.setItems(FXCollections.observableArrayList(jobService.getCategories()));
            loadOffers();
        } catch (SQLException e) { e.printStackTrace(); }

        // Listen for table selection
        offerTable.getSelectionModel().selectedItemProperty().addListener((obs, oldSelection, newSelection) -> {
            if (newSelection != null) {
                fillForm(newSelection);
            }
        });

        // Initial button state
        btnUpdate.setDisable(true);
        btnDelete.setDisable(true);
    }

    private void fillForm(JobOffer offer) {
        selectedOffer = offer;
        titleField.setText(offer.getTitle());
        locationField.setText(offer.getLocation());
        salaryField.setText(offer.getSalaryRange());
        descArea.setText(offer.getDescription());
        typeCombo.setValue(offer.getJobType());

        // Loop to find matching category object for ComboBox
        for (JobCategory c : categoryCombo.getItems()) {
            if (c.getId() == offer.getCategoryId()) {
                categoryCombo.setValue(c);
                break;
            }
        }

        // Enable Edit/Delete, Disable Add (to prevent duplicates)
        btnUpdate.setDisable(false);
        btnDelete.setDisable(false);
        btnAdd.setDisable(true);
    }

    private void loadOffers() {
        try {
            offerTable.setItems(FXCollections.observableArrayList(jobService.getAll()));
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void handleAddOffer() {
        if (titleField.getText().isEmpty() || categoryCombo.getValue() == null || typeCombo.getValue() == null) {
            new Alert(Alert.AlertType.WARNING, "Please fill in all required fields (Title, Category, and Type).").show();
            return;
        }
        try {
            // 2. Get the current user ID from SessionManager
            // Assuming your SessionManager stores the app_user ID
            int recruiterId = SessionManager.getInstance().getUser().getId();

            // 3. Create the JobOffer object
            JobOffer newOffer = new JobOffer();
            newOffer.setRecruiterId(recruiterId);
            newOffer.setCategoryId(categoryCombo.getValue().getId());
            newOffer.setTitle(titleField.getText());
            newOffer.setDescription(descArea.getText());
            newOffer.setLocation(locationField.getText());
            newOffer.setSalaryRange(salaryField.getText());
            newOffer.setJobType(typeCombo.getValue());
            // Status and PostedDate are usually handled by DB defaults,
            // but you can set them here if your service requires it.

            // 4. Save to Database via Service
            jobService.add(newOffer);

            // 5. Update UI
            loadOffers();
            resetForm();

            new Alert(Alert.AlertType.INFORMATION, "Job Offer posted successfully!").show();

        } catch (NullPointerException e) {
            new Alert(Alert.AlertType.ERROR, "Session Error: No recruiter logged in.").show();
        } catch (SQLException e) {
            e.printStackTrace();
            new Alert(Alert.AlertType.ERROR, "Database Error: " + e.getMessage()).show();
        }
    }

    @FXML
    private void handleUpdateOffer() {
        if (selectedOffer == null) return;

        try {
            selectedOffer.setTitle(titleField.getText());
            selectedOffer.setLocation(locationField.getText());
            selectedOffer.setSalaryRange(salaryField.getText());
            selectedOffer.setDescription(descArea.getText());
            selectedOffer.setJobType(typeCombo.getValue());
            selectedOffer.setCategoryId(categoryCombo.getValue().getId());

            jobService.update(selectedOffer);
            loadOffers();
            resetForm();
            new Alert(Alert.AlertType.INFORMATION, "Offer Updated!").show();
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void handleDeleteOffer() {
        if (selectedOffer == null) return;
        try {
            jobService.delete(selectedOffer.getId());
            loadOffers();
            resetForm();
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void resetForm() {
        selectedOffer = null;
        titleField.clear(); locationField.clear(); salaryField.clear(); descArea.clear();
        categoryCombo.getSelectionModel().clearSelection();
        typeCombo.getSelectionModel().clearSelection();

        btnAdd.setDisable(false);
        btnUpdate.setDisable(true);
        btnDelete.setDisable(true);
    }
}