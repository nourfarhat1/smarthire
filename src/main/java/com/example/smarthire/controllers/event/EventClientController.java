package com.example.smarthire.controllers.event;

import com.example.smarthire.entities.event.AppEvent;
import com.example.smarthire.services.EventService;
import com.example.smarthire.services.AIService;
import com.example.smarthire.services.ProfileService;
import com.example.smarthire.utils.QRCodeGenerator;
import com.example.smarthire.utils.SessionManager;
import com.google.gson.JsonObject;
import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.image.ImageView;
import javafx.scene.layout.*;
import javafx.scene.text.Font;
import javafx.scene.text.Text;
import javafx.stage.Modality;
import javafx.stage.Stage;

import java.sql.SQLException;
import java.util.List;

public class EventClientController {

    @FXML private FlowPane eventsContainer;
    @FXML private TextField searchField;
    @FXML private Label viewTitle;

    private final EventService eventService = new EventService();
    private final AIService aiService = new AIService();
    private final ProfileService profileService = new ProfileService();

    private int getCurrentUserId() {
        return SessionManager.getUser().getId();
    }

    @FXML
    public void initialize() {
        if (SessionManager.getUser() == null) {
            System.err.println("[SESSION-CHECK] CRITICAL ERROR: Session is empty!");
            return;
        }
        System.out.println("[SESSION-CHECK] Controller initialized for User: " +
                SessionManager.getUser().getFirstName() + " (ID: " + getCurrentUserId() + ")");
        loadAllEvents();
    }

    @FXML
    private void handleAiNotifications() {
        int userId = getCurrentUserId();
        String userName = SessionManager.getUser().getFirstName();

        System.out.println("\n--- AI ANALYSIS START ---");
        String skills = profileService.getSkillsByUserId(userId);

        try {
            List<AppEvent> events = eventService.getAll();
            Alert loading = new Alert(Alert.AlertType.INFORMATION);
            loading.setTitle("SmartHire AI");
            loading.setHeaderText("Analyse en cours pour " + userName);
            loading.setContentText("Vérification de la correspondance avec vos compétences...");
            loading.show();

            new Thread(() -> {
                StringBuilder recs = new StringBuilder();
                int count = 0;
                for (AppEvent event : events) {
                    try {
                        JsonObject match = aiService.calculateEventMatching(skills, event.getDescription());
                        int score = match.get("score").getAsInt();
                        if (score >= 70) {
                            count++;
                            recs.append("⭐ ").append(event.getName().toUpperCase())
                                    .append(" (Match: ").append(score).append("%)\n")
                                    .append(match.get("recommendation").getAsString()).append("\n\n");
                        }
                    } catch (Exception e) { e.printStackTrace(); }
                }

                String finalRecs = recs.toString();
                int finalCount = count;
                Platform.runLater(() -> {
                    loading.close();
                    showAiDialog(finalCount, finalRecs);
                });
            }).start();
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void showAiDialog(int count, String content) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("AI Recommendations");
        alert.setHeaderText(count + " Événements correspondent à votre profil !");
        TextArea area = new TextArea(content.isEmpty() ? "Aucun match trouvé pour le moment." : content);
        area.setWrapText(true);
        area.setEditable(false);
        alert.getDialogPane().setContent(area);
        alert.showAndWait();
    }

    @FXML
    public void loadAllEvents() {
        isTicketView = false;
        if (viewTitle != null) viewTitle.setText("Upcoming Events");
        refreshDisplay();
    }

    @FXML
    public void loadMyTickets() {
        isTicketView = true;
        if (viewTitle != null) viewTitle.setText("My Registered Tickets");
        refreshDisplay();
    }

    private void refreshDisplay() {
        try {
            List<AppEvent> events = isTicketView ?
                    eventService.getJoinedEvents(getCurrentUserId()) :
                    eventService.getAll();
            displayEvents(events);
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void displayEvents(List<AppEvent> events) {
        eventsContainer.getChildren().clear();
        for (AppEvent event : events) {
            eventsContainer.getChildren().add(createCard(event));
        }
    }

    private VBox createCard(AppEvent event) {
        VBox card = new VBox(10);
        card.setStyle("-fx-background-color: white; -fx-background-radius: 15; -fx-padding: 15; " +
                "-fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.1), 10, 0, 0, 5); -fx-cursor: hand;");
        card.setPrefSize(230, 280);

        // --- NEW: CLIQUE SUR LA CARTE POUR VOIR LES DÉTAILS ---
        card.setOnMouseClicked(e -> showEventDetailsPopup(event));

        Label name = new Label(event.getName());
        name.setStyle("-fx-font-weight: bold; -fx-font-size: 16px; -fx-text-fill: #2f4188;");
        name.setWrapText(true);

        Label date = new Label("📅 " + event.getEventDate());
        Label loc = new Label("📍 " + event.getLocation());
        loc.setStyle("-fx-text-fill: #7f8c8d;");

        Button actionBtn = new Button();
        actionBtn.setMaxWidth(Double.MAX_VALUE);

        if (!isTicketView) {
            actionBtn.setText("JOIN EVENT");
            actionBtn.setStyle("-fx-background-color: #87a042; -fx-text-fill: white; -fx-font-weight: bold;");
            actionBtn.setOnAction(e -> {
                e.consume(); // Empêche l'ouverture des détails lors du clic sur le bouton
                handleJoin(event);
            });
            card.getChildren().addAll(name, date, loc, new Region(), actionBtn);
        } else {
            actionBtn.setText("VIEW TICKET");
            actionBtn.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white; -fx-font-weight: bold;");
            actionBtn.setOnAction(e -> {
                e.consume();
                showTicketPopup(event);
            });

            Button cancelBtn = new Button("CANCEL");
            cancelBtn.setMaxWidth(Double.MAX_VALUE);
            cancelBtn.setStyle("-fx-background-color: #e74c3c; -fx-text-fill: white; -fx-font-weight: bold;");
            cancelBtn.setOnAction(e -> {
                e.consume();
                handleCancel(event);
            });

            card.getChildren().addAll(name, date, loc, new Region(), actionBtn, cancelBtn);
        }

        VBox.setVgrow(card.getChildren().get(card.getChildren().size()-2), Priority.ALWAYS);
        return card;
    }

    /**
     * --- NEW METHOD: AFFICHE LES DÉTAILS COMPLETS DE L'ÉVÉNEMENT ---
     */
    private void showEventDetailsPopup(AppEvent event) {
        Stage popup = new Stage();
        popup.initModality(Modality.APPLICATION_MODAL);
        popup.setTitle("Event Details: " + event.getName());

        VBox layout = new VBox(20);
        layout.setPadding(new Insets(25));
        layout.setStyle("-fx-background-color: #f4f7f6;");
        layout.setPrefWidth(450);

        Label title = new Label(event.getName().toUpperCase());
        title.setStyle("-fx-font-size: 22px; -fx-font-weight: bold; -fx-text-fill: #2f4188;");
        title.setWrapText(true);

        HBox infoBar = new HBox(20);
        infoBar.getChildren().addAll(
                new Label("📅 " + event.getEventDate()),
                new Label("📍 " + event.getLocation())
        );
        infoBar.setStyle("-fx-font-size: 14px; -fx-text-fill: #555;");

        Separator sep = new Separator();

        Label descTitle = new Label("Description");
        descTitle.setStyle("-fx-font-weight: bold; -fx-font-size: 16px;");

        Text descText = new Text(event.getDescription());
        descText.setWrappingWidth(400);
        descText.setFont(Font.font("System", 14));

        ScrollPane scroll = new ScrollPane(descText);
        scroll.setPrefHeight(200);
        scroll.setStyle("-fx-background-color: transparent; -fx-background: transparent;");
        scroll.setFitToWidth(true);

        Button closeBtn = new Button("CLOSE");
        closeBtn.setPrefWidth(100);
        closeBtn.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white; -fx-font-weight: bold; -fx-cursor: hand;");
        closeBtn.setOnAction(e -> popup.close());

        layout.getChildren().addAll(title, infoBar, sep, descTitle, scroll, closeBtn);
        layout.setAlignment(Pos.TOP_LEFT);

        Scene scene = new Scene(layout);
        popup.setScene(scene);
        popup.show();
    }

    private void handleJoin(AppEvent event) {
        try {
            eventService.joinEvent(event.getId(), getCurrentUserId());
            showAlert("Success", "Registration confirmed!");
            showTicketPopup(event);
            loadAllEvents();
        } catch (SQLException e) {
            showAlert("Error", "Registration failed: " + e.getMessage());
        }
    }

    private void handleCancel(AppEvent event) {
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION, "Annuler l'inscription ?", ButtonType.YES, ButtonType.NO);
        alert.showAndWait().ifPresent(response -> {
            if (response == ButtonType.YES) {
                try {
                    eventService.leaveEvent(event.getId(), getCurrentUserId());
                    if (isTicketView) loadMyTickets(); else loadAllEvents();
                } catch (SQLException e) { e.printStackTrace(); }
            }
        });
    }

    private void showTicketPopup(AppEvent event) {
        Stage popup = new Stage();
        popup.initModality(Modality.APPLICATION_MODAL);
        popup.setTitle("SmartHire Ticket - " + event.getName());

        VBox ticket = new VBox(15);
        ticket.setPadding(new Insets(30));
        ticket.setAlignment(Pos.CENTER);
        // Un style plus "Ticket" avec des bordures arrondies et un fond blanc pur
        ticket.setStyle("-fx-background-color: white; -fx-border-color: #2f4188; -fx-border-width: 2; -fx-border-radius: 15; -fx-background-radius: 15;");

        // En-tête du ticket
        Label header = new Label("OFFICIAL ACCESS PASS");
        header.setStyle("-fx-font-weight: bold; -fx-font-size: 18px; -fx-text-fill: #2f4188;");

        Separator sep = new Separator();
        sep.setPadding(new Insets(10, 0, 10, 0));

        // Détails lisibles (Noms au lieu des IDs)
        VBox details = new VBox(8);
        details.setAlignment(Pos.CENTER);

        Label eventName = new Label(event.getName().toUpperCase());
        eventName.setStyle("-fx-font-size: 16px; -fx-font-weight: bold;");

        Label userName = new Label("Attendee: " + SessionManager.getUser().getFirstName() + " " + SessionManager.getUser().getLastName());
        userName.setStyle("-fx-font-size: 14px; -fx-text-fill: #555;");

        Label dateLabel = new Label("Date: " + event.getEventDate());
        dateLabel.setStyle("-fx-font-size: 13px; -fx-font-style: italic;");

        details.getChildren().addAll(eventName, userName, dateLabel);

        // Génération du QR Code (on garde les IDs dans les données brutes du QR pour le scan machine,
        // mais l'humain voit les noms)
        ImageView qrView = new ImageView();
        String qrData = "EVENT:" + event.getName() + "|USER:" + SessionManager.getUser().getEmail();
        qrView.setImage(QRCodeGenerator.generateQRCode(qrData));
        qrView.setFitWidth(180);
        qrView.setFitHeight(180);
        qrView.setPreserveRatio(true);

        Button closeBtn = new Button("CLOSE");
        closeBtn.setPrefWidth(120);
        closeBtn.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white; -fx-font-weight: bold; -fx-cursor: hand;");
        closeBtn.setOnAction(e -> popup.close());

        // Assemblage final
        ticket.getChildren().addAll(header, sep, details, qrView, new Label("Scan at entrance"), closeBtn);

        Scene scene = new Scene(ticket, 400, 550);
        popup.setScene(scene);
        popup.show();
    }
    private void showAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.showAndWait();
    }

    private boolean isTicketView = false;

    @FXML
    void handleFilter() {
        String query = searchField.getText().toLowerCase();
        try {
            List<AppEvent> filtered = eventService.getAll().stream()
                    .filter(e -> e.getName().toLowerCase().contains(query) || e.getLocation().toLowerCase().contains(query))
                    .toList();
            displayEvents(filtered);
        } catch (SQLException e) { e.printStackTrace(); }
    }
}