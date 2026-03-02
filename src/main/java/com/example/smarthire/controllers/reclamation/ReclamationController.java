package com.example.smarthire.controllers.reclamation;

import com.example.smarthire.entities.reclamation.Complaint;
import com.example.smarthire.entities.reclamation.Response;
import com.example.smarthire.entities.reclamation.ReclaimType;
import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.ComplaintService;
import com.example.smarthire.utils.SessionManager;
import com.example.smarthire.utils.BadWordFilter;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;
import javafx.util.StringConverter;

import java.sql.SQLException;
import java.util.List;
import java.util.Optional;

public class ReclamationController {
    @FXML private ComboBox<ReclaimType> typeComboBox;
    @FXML private TextField subjectField;
    @FXML private TextArea descriptionArea;
    @FXML private Label charCountLabel;
    @FXML private ListView<Complaint> historyListView;
    @FXML private ComboBox<String> historyStatusFilter;
    @FXML private ComboBox<String> historySortFilter;
    @FXML private Button submitButton;

    private final ComplaintService complaintService = new ComplaintService();
    private Complaint complaintToEdit = null;
    private static final int MAX_CHARS = 500;

    @FXML
    public void initialize() {
        loadComplaintTypes();

        // Character counter logic
        descriptionArea.textProperty().addListener((observable, oldValue, newValue) -> {
            if (newValue != null) {
                if (newValue.length() > MAX_CHARS) {
                    descriptionArea.setText(oldValue);
                } else {
                    int remaining = newValue.length();
                    charCountLabel.setText(remaining + " / " + MAX_CHARS);
                    if (remaining >= MAX_CHARS) {
                        charCountLabel.setStyle("-fx-text-fill: #EF4444; -fx-font-weight: bold;"); // Red warning
                    } else {
                        charCountLabel.setStyle("-fx-text-fill: #3B82F6; -fx-font-weight: bold;"); // Blue normal
                    }
                }
            }
        });

        historyStatusFilter.setItems(FXCollections.observableArrayList("ALL", "PENDING", "OPEN", "RESOLVED"));
        historyStatusFilter.setValue("ALL");
        historyStatusFilter.setOnAction(e -> loadHistory());

        historySortFilter.setItems(FXCollections.observableArrayList("Newest First", "Oldest First"));
        historySortFilter.setValue("Newest First");
        historySortFilter.setOnAction(e -> loadHistory());

        setupListView();
        loadHistory();
    }

    private void loadComplaintTypes() {
        try {
            List<ReclaimType> types = complaintService.getAllTypes();
            typeComboBox.setItems(FXCollections.observableArrayList(types));
            typeComboBox.setConverter(new StringConverter<ReclaimType>() {
                @Override public String toString(ReclaimType t) { return (t == null) ? "" : t.getName(); }
                @Override public ReclaimType fromString(String s) { return null; }
            });
        } catch (SQLException e) {
            showErrorAlert("Database Error", "Could not load complaint categories.");
            e.printStackTrace();
        }
    }

    @FXML
    public void handleSubmit() {
        User user = SessionManager.getUser();
        if (user == null) {
            showErrorAlert("Authentication Error", "You must be logged in to submit a request.");
            return;
        }

        String subj = subjectField.getText().trim();
        String desc = descriptionArea.getText().trim();
        ReclaimType selectedType = typeComboBox.getValue();

        if (selectedType == null || subj.isEmpty() || desc.isEmpty()) {
            showWarningAlert("Missing Information", "Please fill out all fields before submitting.");
            return;
        }

        if (BadWordFilter.containsProfanity(subj) || BadWordFilter.containsProfanity(desc)) {
            showWarningAlert("Inappropriate Content", "Please keep your message professional and respectful.");
            return;
        }

        try {
            int typeId = selectedType.getId();
            if (complaintToEdit == null) {
                Complaint c = new Complaint(user.getId(), typeId, subj, desc);
                c.setStatus("PENDING");
                complaintService.add(c);
                showSuccessAlert("Request Submitted", "Your reclamation has been successfully sent to our team.");
            } else {
                complaintToEdit.setSubject(subj);
                complaintToEdit.setDescription(desc);
                complaintToEdit.setTypeId(typeId);
                complaintService.updateComplaintByUser(complaintToEdit);
                showSuccessAlert("Request Updated", "Your changes have been saved successfully.");
            }
            clearForm();
            loadHistory();
        } catch (SQLException e) {
            showErrorAlert("Submission Failed", "An error occurred while saving your request.");
            e.printStackTrace();
        }
    }

    private void loadHistory() {
        User user = SessionManager.getUser();
        if (user == null) return;
        try {
            String status = "ALL".equals(historyStatusFilter.getValue()) ? null : historyStatusFilter.getValue();
            String sort = "Newest First".equals(historySortFilter.getValue()) ? "DESC" : "ASC";

            historyListView.getItems().setAll(complaintService.filterComplaints(user.getId(), null, status, 0, sort));
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void setupListView() {
        historyListView.setCellFactory(param -> new ListCell<Complaint>() {
            @Override
            protected void updateItem(Complaint item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) { setGraphic(null); setText(null); }
                else {
                    HBox container = new HBox(15);
                    container.setAlignment(Pos.CENTER_LEFT);
                    container.setStyle("-fx-padding: 10; -fx-background-color: white; -fx-background-radius: 8; -fx-border-color: #E5E7EB; -fx-border-radius: 8;");

                    // Status Badge
                    Label statusLabel = new Label(item.getStatus());
                    statusLabel.setStyle(getStatusStyle(item.getStatus()));

                    // Subject
                    Label subjLabel = new Label(item.getSubject());
                    subjLabel.setStyle("-fx-font-weight: bold; -fx-text-fill: #1E293B; -fx-font-size: 14px;");

                    Region spacer = new Region();
                    HBox.setHgrow(spacer, Priority.ALWAYS);

                    // FIXED: Replaced complex emojis with clean Unicode symbols to remove the rectangle
                    Button btnResp = new Button("👁 View");
                    btnResp.setStyle("-fx-background-color: #E0F2FE; -fx-text-fill: #0284C7; -fx-font-weight: bold; -fx-background-radius: 6; -fx-cursor: hand;");

                    Button btnEdit = new Button("✎ Edit");
                    btnEdit.setStyle("-fx-background-color: #D1FAE5; -fx-text-fill: #059669; -fx-font-weight: bold; -fx-background-radius: 6; -fx-cursor: hand;");
                    btnEdit.setVisible("PENDING".equalsIgnoreCase(item.getStatus())); // Only show if pending

                    Button btnDel = new Button("✖"); // Clean multiplication/delete cross
                    // Alternatively, you can use "🗑" if your system font supports it cleanly!
                    btnDel.setStyle("-fx-background-color: #FEE2E2; -fx-text-fill: #DC2626; -fx-font-weight: bold; -fx-background-radius: 6; -fx-cursor: hand;");
                    btnDel.setVisible("PENDING".equalsIgnoreCase(item.getStatus())); // Only show if pending

                    btnResp.setOnAction(e -> handleSeeResponse(item));
                    btnEdit.setOnAction(e -> handleEditRequest(item));
                    btnDel.setOnAction(e -> handleDeleteRequest(item));

                    container.getChildren().addAll(statusLabel, subjLabel, spacer, btnResp, btnEdit, btnDel);
                    setGraphic(container);
                }
            }
        });
    }

    private String getStatusStyle(String status) {
        if ("RESOLVED".equalsIgnoreCase(status)) return "-fx-background-color: #10B981; -fx-text-fill: white; -fx-padding: 4 8; -fx-background-radius: 12; -fx-font-size: 11px; -fx-font-weight: bold;";
        if ("OPEN".equalsIgnoreCase(status)) return "-fx-background-color: #F59E0B; -fx-text-fill: white; -fx-padding: 4 8; -fx-background-radius: 12; -fx-font-size: 11px; -fx-font-weight: bold;";
        return "-fx-background-color: #64748B; -fx-text-fill: white; -fx-padding: 4 8; -fx-background-radius: 12; -fx-font-size: 11px; -fx-font-weight: bold;"; // PENDING
    }

    private void handleSeeResponse(Complaint c) {
        try {
            List<Response> responses = complaintService.getResponsesByComplaintId(c.getId());
            if (responses.isEmpty()) {
                showInfoAlert("No Updates", "There is no admin response for this request yet.");
            } else {
                StringBuilder sb = new StringBuilder();
                for (Response r : responses) {
                    sb.append("Admin (").append(r.getResponseDate().toString().substring(0, 16)).append("):\n")
                            .append(r.getMessage()).append("\n\n-----------------\n\n");
                }
                TextArea ta = new TextArea(sb.toString());
                ta.setEditable(false);
                ta.setWrapText(true);
                ta.setStyle("-fx-font-family: 'Segoe UI'; -fx-font-size: 14px;");

                Alert a = new Alert(Alert.AlertType.INFORMATION);
                a.setTitle("Admin Response");
                a.setHeaderText("Communication History");
                a.getDialogPane().setContent(ta);
                a.showAndWait();
            }
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void handleEditRequest(Complaint c) {
        if (!"PENDING".equalsIgnoreCase(c.getStatus())) {
            showWarningAlert("Action Denied", "Only pending reclamations can be edited.");
            return;
        }
        subjectField.setText(c.getSubject());
        descriptionArea.setText(c.getDescription());
        typeComboBox.getItems().stream().filter(t -> t.getId() == c.getTypeId()).findFirst().ifPresent(typeComboBox::setValue);
        complaintToEdit = c;
        submitButton.setText("Update Reclaim");
        submitButton.setStyle("-fx-background-color: #10B981; -fx-text-fill: white; -fx-font-size: 14px; -fx-font-weight: 700; -fx-padding: 12; -fx-background-radius: 6; -fx-cursor: hand;");
    }

    private void handleDeleteRequest(Complaint c) {
        if (!"PENDING".equalsIgnoreCase(c.getStatus())) return;

        if (showDeleteConfirmation("Delete Request", "Are you sure you want to delete this request?\nThis action cannot be undone.")) {
            try {
                complaintService.delete(c.getId());
                loadHistory();
                showSuccessAlert("Deleted", "Your request has been permanently deleted.");
            } catch (SQLException e) {
                showErrorAlert("Deletion Failed", "Could not delete the request.");
                e.printStackTrace();
            }
        }
    }

    private void clearForm() {
        subjectField.clear();
        descriptionArea.clear();
        typeComboBox.getSelectionModel().clearSelection();
        charCountLabel.setText("0 / 500");
        complaintToEdit = null;
        submitButton.setText("Submit Request");
        submitButton.setStyle("-fx-background-color: #2563EB; -fx-text-fill: white; -fx-font-size: 14px; -fx-font-weight: 700; -fx-padding: 12; -fx-background-radius: 6; -fx-cursor: hand;");
    }

    @FXML private void handleRefresh() { clearForm(); }

    // ==========================================
    // CUSTOM STYLED ALERTS
    // ==========================================

    private void showSuccessAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setHeaderText("✅ Success!");
        alert.setContentText(content);

        // Style the DialogPane for Success (Green)
        DialogPane dialogPane = alert.getDialogPane();
        dialogPane.setStyle("-fx-background-color: #F0FDF4; -fx-font-family: 'Segoe UI';");
        dialogPane.lookup(".header-panel").setStyle("-fx-background-color: #22C55E; -fx-font-weight: bold;");

        alert.showAndWait();
    }

    private void showWarningAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle(title);
        alert.setHeaderText("⚠️ Notice");
        alert.setContentText(content);
        alert.showAndWait();
    }

    private void showInfoAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setHeaderText("ℹ️ Information");
        alert.setContentText(content);
        alert.showAndWait();
    }

    private void showErrorAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle(title);
        alert.setHeaderText("❌ Error");
        alert.setContentText(content);
        alert.showAndWait();
    }

    private boolean showDeleteConfirmation(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle(title);
        alert.setHeaderText("🛑 Confirm Deletion");
        alert.setContentText(content);

        // Style the DialogPane for Danger (Red)
        DialogPane dialogPane = alert.getDialogPane();
        dialogPane.setStyle("-fx-background-color: #FEF2F2; -fx-font-family: 'Segoe UI';");
        dialogPane.lookup(".header-panel").setStyle("-fx-background-color: #EF4444; -fx-font-weight: bold;");

        // Custom Buttons
        ButtonType deleteButtonType = new ButtonType("Yes, Delete", ButtonBar.ButtonData.OK_DONE);
        ButtonType cancelButtonType = new ButtonType("Cancel", ButtonBar.ButtonData.CANCEL_CLOSE);
        alert.getButtonTypes().setAll(deleteButtonType, cancelButtonType);

        Optional<ButtonType> result = alert.showAndWait();
        return result.isPresent() && result.get() == deleteButtonType;
    }
}