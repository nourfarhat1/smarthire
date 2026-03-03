package com.example.smarthire.controllers.job;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.utils.MyDatabase;
import com.example.smarthire.utils.SessionManager;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.VBox;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

public class InterviewScheduleController {

    // --- LEFT SIDE: List ---
    @FXML private TableView<JobRequest> requestTable;
    @FXML private TableColumn<JobRequest, String> colCandidate;
    @FXML private TableColumn<JobRequest, String> colJobTitle;
    @FXML private TableColumn<JobRequest, String> colStatus;

    // --- RIGHT SIDE: Form ---
    @FXML private VBox formContainer; // To disable form when nothing is selected
    @FXML private Label selectedCandidateLabel;
    @FXML private Label selectedJobLabel;

    @FXML private DatePicker datePicker;
    @FXML private TextField timeField; // HH:mm
    @FXML private TextField locationField;
    @FXML private TextArea notesArea;
    @FXML private Button btnSave;

    private final ApplicationService appService = new ApplicationService();
    private JobRequest selectedRequest;

    @FXML
    public void initialize() {
        // 1. Setup Table Columns
        colCandidate.setCellValueFactory(new PropertyValueFactory<>("candidateName"));
        colJobTitle.setCellValueFactory(new PropertyValueFactory<>("jobTitle"));
        colStatus.setCellValueFactory(new PropertyValueFactory<>("status"));

        // 2. Load Data for logged-in HR
        loadRequests();

        // 3. Listener: When a row is clicked, populate the right side
        requestTable.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                selectRequest(newVal);
            }
        });

        // 4. Initially disable form
        formContainer.setDisable(true);
    }

    private void loadRequests() {
        try {
            int hrId = SessionManager.getUser().getId();
            requestTable.setItems(FXCollections.observableArrayList(appService.getForHR(hrId)));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void selectRequest(JobRequest req) {
        this.selectedRequest = req;
        formContainer.setDisable(false); // Enable the form

        selectedCandidateLabel.setText(req.getCandidateName());
        selectedJobLabel.setText(req.getJobTitle());

        // Clear previous inputs
        datePicker.setValue(null);
        timeField.clear();
        locationField.clear();
        notesArea.clear();
    }

    @FXML
    private void handleSaveInterview() {
        if (selectedRequest == null) return;

        if (datePicker.getValue() == null || timeField.getText().isEmpty() || locationField.getText().isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Missing Info", "Please fill in Date, Time, and Location.");
            return;
        }

        String dateTime = datePicker.getValue() + " " + timeField.getText() + ":00";
        String sql = "INSERT INTO interview (job_request_id, date_time, location, notes, status) VALUES (?, ?, ?, ?, 'SCHEDULED')";

        try (Connection conn = MyDatabase.getInstance().getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {

            ps.setInt(1, selectedRequest.getId());
            ps.setString(2, dateTime);
            ps.setString(3, locationField.getText());
            ps.setString(4, notesArea.getText());
            ps.executeUpdate();

            // Update status in DB and Table
            updateRequestStatus(selectedRequest.getId());
            selectedRequest.setStatus("INTERVIEWING");
            requestTable.refresh();

            showAlert(Alert.AlertType.INFORMATION, "Success", "Interview Scheduled!");

        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Error", e.getMessage());
        }
    }

    private void updateRequestStatus(int reqId) throws SQLException {
        String sql = "UPDATE job_request SET status='INTERVIEWING' WHERE id=?";
        try (Connection conn = MyDatabase.getInstance().getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, reqId);
            ps.executeUpdate();
        }
    }

    private void showAlert(Alert.AlertType type, String title, String msg) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(msg);
        alert.show();
    }
}