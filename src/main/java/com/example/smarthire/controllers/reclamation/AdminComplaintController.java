package com.example.smarthire.controllers.reclamation;

import com.example.smarthire.entities.reclamation.Complaint;
import com.example.smarthire.entities.reclamation.Response;
import com.example.smarthire.entities.reclamation.ReclaimType;
import com.example.smarthire.services.ComplaintService;
import com.example.smarthire.services.AISummarizer;
import com.example.smarthire.services.TranslationService;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.chart.PieChart;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;

import java.sql.SQLException;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

public class AdminComplaintController {
    private Response responseToEdit = null;
    //ai
    @FXML private Label recapLabel;
    @FXML private ProgressIndicator aiProgress;
    //

    @FXML private ListView<Complaint> complaintListView;
    @FXML private VBox detailsContainer, chatBox;
    @FXML private Label subjectLabel, senderLabel, statusLabel, placeholderLabel;
    @FXML private TextArea replyField;
    @FXML private TextField searchField;
    @FXML private ComboBox<String> statusFilter, dateSort;
    @FXML private ComboBox<ReclaimType> typeFilter;

    // Pie Chart Reference
    @FXML private PieChart statsPieChart;

    private final ComplaintService service = new ComplaintService();
    private Complaint selectedComplaint;
    private Map<Integer, String> typeMap;
    @FXML private ComboBox<String> langSelector;
    private final Map<String, String> langMap = Map.of(
            "English", "en",
            "French", "fr",
            "Arabic", "ar",
            "Spanish", "es",
            "German", "de"
    );

    @FXML
    public void initialize() {
        loadFilters();
        loadComplaints();
        loadStatistics();

        complaintListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) showDetails(newVal);
        });

        complaintListView.setCellFactory(param -> new ListCell<>() {
            @Override
            protected void updateItem(Complaint item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) setText(null);
                else setText(String.format("ID %d: %s %s - %s\n[%s] - %s",
                        item.getId(),
                        item.getFirstName() != null ? item.getFirstName() : "",
                        item.getLastName() != null ? item.getLastName() : "",
                        item.getSubject(),
                        item.getStatus(),
                        typeMap.getOrDefault(item.getTypeId(), "Other")));
            }
        });
        langSelector.setItems(FXCollections.observableArrayList(langMap.keySet().stream().sorted().toList()));
        langSelector.setValue("English");
    }

    private void loadStatistics() {
        try {
            Map<String, Integer> stats = service.getComplaintsCountByType();
            ObservableList<PieChart.Data> pieData = FXCollections.observableArrayList();
            stats.forEach((name, count) -> pieData.add(new PieChart.Data(name + " (" + count + ")", count)));
            statsPieChart.setData(pieData);
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void loadFilters() {
        try {
            List<ReclaimType> types = service.getAllTypes();
            typeMap = types.stream().collect(Collectors.toMap(ReclaimType::getId, ReclaimType::getName));
            typeFilter.setItems(FXCollections.observableArrayList(types));
            statusFilter.setItems(FXCollections.observableArrayList("ALL", "OPEN", "RESOLVED"));
            statusFilter.setValue("ALL");
            dateSort.setItems(FXCollections.observableArrayList("Newest First", "Oldest First"));
            dateSort.setValue("Newest First");

            statusFilter.setOnAction(e -> loadComplaints());
            typeFilter.setOnAction(e -> loadComplaints());
            dateSort.setOnAction(e -> loadComplaints());
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML private void handleSearch() { loadComplaints(); }

    private void loadComplaints() {
        try {
            String status = "ALL".equals(statusFilter.getValue()) ? null : statusFilter.getValue();
            String sort = "Newest First".equals(dateSort.getValue()) ? "DESC" : "ASC";
            int typeId = typeFilter.getValue() != null ? typeFilter.getValue().getId() : 0;

            // Replaced the int parse logic with this String keyword logic
            String searchKeyword = (searchField != null && !searchField.getText().isEmpty()) ? searchField.getText() : null;
            complaintListView.getItems().setAll(service.filterComplaints(-1, searchKeyword, status, typeId, sort));

        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void showDetails(Complaint c) {
        this.selectedComplaint = c;
        placeholderLabel.setVisible(false);
        detailsContainer.setVisible(true);
        subjectLabel.setText(c.getSubject());

        String fName = c.getFirstName() != null ? c.getFirstName() : "";
        String lName = c.getLastName() != null ? c.getLastName() : "";
        senderLabel.setText(String.format("From User: %s %s (ID: %d)", fName, lName, c.getUserId()));

        statusLabel.setText(c.getStatus());
        loadChatHistory();
        // TRIGGER AI RECAP
        generateAIRecap(c.getDescription());
    }

    private void generateAIRecap(String description) {

        recapLabel.setText("AI Agent is analyzing the complaint...");
        aiProgress.setVisible(true);

        javafx.concurrent.Task<String> recapTask = new javafx.concurrent.Task<>() {
            @Override
            protected String call() {
                return AISummarizer.summarize(description);
            }
        };

        recapTask.setOnSucceeded(e -> {
            recapLabel.setText(recapTask.getValue());
            aiProgress.setVisible(false);
        });

        recapTask.setOnFailed(e -> {
            recapLabel.setText("AI analysis failed.");
            aiProgress.setVisible(false);
        });

        Thread thread = new Thread(recapTask);
        thread.setDaemon(true);
        thread.start();
    }

    @FXML
    private void handleRetryRecap() {
        if (selectedComplaint != null) {
            generateAIRecap(selectedComplaint.getDescription());
        }
    }

    @FXML
    private void handleUseAISuggestion() {

        String aiText = recapLabel.getText();

        if (aiText == null || aiText.isBlank())
            return;

        try {
            // Look for "Suggested Reply:"
            String marker = "Suggested Reply:";
            int index = aiText.indexOf(marker);

            if (index != -1) {
                // Extract only the reply part
                String replyOnly = aiText.substring(index + marker.length()).trim();
                replyField.setText(replyOnly);
            } else {
                // Fallback if AI format slightly changes
                replyField.setText(aiText);
            }

        } catch (Exception e) {
            replyField.setText(aiText);
        }
    }

    private void loadChatHistory() {
        chatBox.getChildren().clear();
        Label userMsg = new Label("User: " + selectedComplaint.getDescription());
        userMsg.setStyle("-fx-background-color: #f4f7f6; -fx-padding: 10; -fx-background-radius: 10; -fx-text-fill: black;");
        userMsg.setWrapText(true);
        chatBox.getChildren().add(userMsg);

        try {
            List<Response> responses = service.getResponsesByComplaintId(selectedComplaint.getId());
            for (Response r : responses) {
                HBox hb = new HBox(10);
                hb.setAlignment(Pos.CENTER_LEFT);

                Label adminMsg = new Label("Me: " + r.getMessage());
                adminMsg.setStyle("-fx-background-color: #e3f2fd; -fx-padding: 10; -fx-background-radius: 10; -fx-text-fill: #2f4188;");
                adminMsg.setWrapText(true);

                Button btnEdit = new Button("✎");
                btnEdit.setStyle("-fx-background-color: #1E3A8A; -fx-text-fill: white; -fx-font-weight: bold; -fx-background-radius: 6; -fx-cursor: hand;");
                btnEdit.setOnAction(e -> prepareEditResponse(r));

                Button btnDel = new Button("✖");
                btnDel.setStyle("-fx-background-color: #EF4444; -fx-text-fill: white; -fx-font-weight: bold; -fx-background-radius: 6; -fx-cursor: hand;");
                btnDel.setOnAction(e -> deleteResponse(r));

                hb.getChildren().addAll(adminMsg, btnEdit, btnDel);
                chatBox.getChildren().add(hb);
            }
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void handleSendReply() {
        if (selectedComplaint == null || replyField.getText().trim().isEmpty()) return;
        try {
            if (responseToEdit == null) {
                service.addResponse(new Response(selectedComplaint.getId(), replyField.getText(), "Admin"));
                if ("PENDING".equals(selectedComplaint.getStatus())) {
                    service.updateStatus(selectedComplaint.getId(), "OPEN");
                    selectedComplaint.setStatus("OPEN");
                    statusLabel.setText("OPEN");
                }
            } else {
                responseToEdit.setMessage(replyField.getText());
                service.updateResponse(responseToEdit);
                responseToEdit = null;
            }
            replyField.clear();
            loadChatHistory();
            loadStatistics(); // Refresh pie chart if count changes
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void prepareEditResponse(Response r) { this.responseToEdit = r; replyField.setText(r.getMessage()); }

    private void deleteResponse(Response r) {
        try { service.deleteResponse(r.getId()); loadChatHistory(); } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void handleResolve() {
        if (selectedComplaint == null) return;
        try {
            service.updateStatus(selectedComplaint.getId(), "RESOLVED");
            selectedComplaint.setStatus("RESOLVED");
            statusLabel.setText("RESOLVED");
            loadComplaints();
            loadStatistics();
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void handleTranslateAction() {
        if (selectedComplaint == null) return;

        // 1. Get the language selected in the UI dropdown
        String selectedLangName = langSelector.getValue();

        // 2. Look up the 2-letter code (e.g., "fr" for French). Default to "en" if nothing is selected.
        String targetLangCode = langMap.getOrDefault(selectedLangName, "en");

        // Show loading state in the existing recap label
        recapLabel.setText("Translating to " + selectedLangName + "...");
        aiProgress.setVisible(true);

        javafx.concurrent.Task<String> translationTask = new javafx.concurrent.Task<>() {
            @Override
            protected String call() {
                // 3. Pass the dynamic targetLangCode instead of the hardcoded "en"
                return TranslationService.translate(selectedComplaint.getDescription(), "autodetect", targetLangCode);
            }
        };

        translationTask.setOnSucceeded(e -> {
            // Added the language code to the UI output so the admin knows what language they are looking at
            recapLabel.setText("TR (" + targetLangCode.toUpperCase() + "): " + translationTask.getValue());
            aiProgress.setVisible(false);
        });

        translationTask.setOnFailed(e -> {
            recapLabel.setText("Translation failed.");
            aiProgress.setVisible(false);
        });

        // Best practice: Set as daemon so it doesn't block the app from closing
        Thread thread = new Thread(translationTask);
        thread.setDaemon(true);
        thread.start();
    }}