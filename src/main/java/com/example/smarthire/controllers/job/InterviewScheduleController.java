package com.example.smarthire.controllers.job;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.services.GoogleCalendarService;
import com.example.smarthire.services.MailingService;
import com.example.smarthire.utils.MyDatabase;
import com.example.smarthire.utils.SessionManager;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.VBox;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.time.LocalDateTime;

public class InterviewScheduleController {

    @FXML private TableView<JobRequest> requestTable;
    @FXML private TableColumn<JobRequest, String> colCandidate;
    @FXML private TableColumn<JobRequest, String> colJobTitle;
    @FXML private TableColumn<JobRequest, String> colStatus;

    @FXML private VBox formContainer;
    @FXML private Label selectedCandidateLabel;
    @FXML private Label selectedJobLabel;

    @FXML private DatePicker datePicker;
    @FXML private TextField timeField; // Format attendu: HH:mm
    @FXML private TextField locationField;
    @FXML private TextArea notesArea;
    @FXML private Button btnSave;

    private final ApplicationService appService = new ApplicationService();
    private final MailingService emailService = new MailingService();

    // 1. Initialisation du service Google (Assure-toi qu'il est configuré)
    private final GoogleCalendarService googleCalendarService = new GoogleCalendarService();

    private JobRequest selectedRequest;

    @FXML
    public void initialize() {
        colCandidate.setCellValueFactory(new PropertyValueFactory<>("candidateName"));
        colJobTitle.setCellValueFactory(new PropertyValueFactory<>("jobTitle"));
        colStatus.setCellValueFactory(new PropertyValueFactory<>("status"));

        loadRequests();

        requestTable.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                selectRequest(newVal);
            }
        });

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
        formContainer.setDisable(false);
        selectedCandidateLabel.setText(req.getCandidateName());
        selectedJobLabel.setText(req.getJobTitle());
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

        // Préparation des formats
        String displayDateTime = datePicker.getValue() + " at " + timeField.getText();
        String sqlDateTime = datePicker.getValue() + " " + timeField.getText() + ":00";

        // --- LOGIQUE GOOGLE CALENDAR ---
        boolean isOnline = locationField.getText().equalsIgnoreCase("Online");
        final String[] meetingLink = {null}; // Utilisation d'un tableau pour l'accès dans le thread

        // On affiche un message de chargement si nécessaire
        btnSave.setDisable(true);

        new Thread(() -> {
            if (isOnline) {
                try {
                    // Conversion pour l'API Google
                    LocalDateTime ldt = LocalDateTime.parse(datePicker.getValue() + "T" + timeField.getText());

                    // 2. Appel de l'API : Crée l'event + invite le candidat + génère Meet
                    meetingLink[0] = googleCalendarService.createInterviewEvent(
                            selectedRequest.getCandidateEmail(),
                            selectedRequest.getJobTitle(),
                            ldt,
                            true
                    );
                } catch (Exception e) {
                    System.err.println("Erreur Google Calendar: " + e.getMessage());
                }
            }

            // Retour au thread UI pour la base de données
            Platform.runLater(() -> {
                saveToDatabase(sqlDateTime, meetingLink[0]);
                btnSave.setDisable(false);
            });
        }).start();
    }

    private void saveToDatabase(String sqlDateTime, String link) {
        // 3. Update SQL pour inclure meeting_link
        String sql = "INSERT INTO interview (job_request_id, date_time, location, notes, status, meeting_link) VALUES (?, ?, ?, ?, 'SCHEDULED', ?)";

        try (Connection conn = MyDatabase.getInstance().getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {

            ps.setInt(1, selectedRequest.getId());
            ps.setString(2, sqlDateTime);
            ps.setString(3, locationField.getText());
            ps.setString(4, notesArea.getText());
            ps.setString(5, link); // Sauvegarde le lien Meet ici
            ps.executeUpdate();

            updateRequestStatus(selectedRequest.getId());
            selectedRequest.setStatus("INTERVIEWING");
            requestTable.refresh();

            // 4. Notification Email avec le lien
            String recipientEmail = selectedRequest.getCandidateEmail();
            String candidateName = selectedRequest.getCandidateName();
            String jobTitle = selectedRequest.getJobTitle();
            String displayDateTime = datePicker.getValue() + " at " + timeField.getText();

            new Thread(() -> {
                // Modifie ta méthode sendInterviewNotification pour accepter le lien en 5ème paramètre
                emailService.sendInterviewNotification(recipientEmail, candidateName, jobTitle, displayDateTime, link);
            }).start();

            showAlert(Alert.AlertType.INFORMATION, "Success",
                    "Interview Scheduled!\nInvitation sent to " + candidateName +
                            (link != null ? "\nMeet Link: " + link : ""));

        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Database Error", e.getMessage());
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
        Platform.runLater(() -> {
            Alert alert = new Alert(type);
            alert.setTitle(title);
            alert.setHeaderText(null);
            alert.setContentText(msg);
            alert.show();
        });
    }
}