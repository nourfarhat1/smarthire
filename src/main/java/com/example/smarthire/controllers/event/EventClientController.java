package com.example.smarthire.controllers.event;

import com.example.smarthire.entities.event.AppEvent;
import com.example.smarthire.services.AIService;
import com.example.smarthire.services.EventService;
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
import javafx.scene.paint.Color;
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


    private void fetchWeatherAsync(String location, Label label) {
        new Thread(() -> {
            try {
                // 1. Clean the location: Remove the emoji and extra spaces
                String cleanCity = location.replace("📍", "").trim();
                if (cleanCity.contains(",")) {
                    cleanCity = cleanCity.split(",")[0].trim();
                }

                final String finalCity = cleanCity;

                // 2. OpenWeatherMap API Configuration
                // USING YOUR KEY: 81df15740321843dc286b26c0ec1896a
                String apiKey = "81df15740321843dc286b26c0ec1896a";
                String urlString = "https://api.openweathermap.org/data/2.5/weather?q="
                        + java.net.URLEncoder.encode(finalCity, "UTF-8")
                        + "&units=metric&appid=" + apiKey;

                java.net.URL url = new java.net.URL(urlString);
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("GET");

                int responseCode = conn.getResponseCode();

                if (responseCode == 200) {
                    JsonObject json = com.google.gson.JsonParser.parseReader(
                            new java.io.InputStreamReader(conn.getInputStream())).getAsJsonObject();

                    double temp = json.getAsJsonObject("main").get("temp").getAsDouble();
                    String condition = json.getAsJsonArray("weather").get(0).getAsJsonObject().get("main").getAsString();

                    Platform.runLater(() -> {
                        String emoji = getWeatherEmoji(condition);
                        label.setText(emoji + " " + (int)Math.round(temp) + "°C " + condition);
                        label.setStyle("-fx-font-size: 11px; -fx-text-fill: #2c3e50; -fx-font-weight: bold;");
                    });
                } else {
                    // If you still see 401, your key is not active yet!
                    System.err.println("Weather API Error " + responseCode + " for city: " + finalCity);
                    Platform.runLater(() -> label.setText("🌡️ " + finalCity + " (Wait for API Activation)"));
                }
            } catch (Exception e) {
                Platform.runLater(() -> label.setText("⚠️ Weather Error"));
            }
        }).start();
    }
    // Helper method for emojis
    private String getWeatherEmoji(String condition) {
        switch (condition.toLowerCase()) {
            case "clouds": return "☁️";
            case "clear": return "☀️";
            case "rain": return "🌧️";
            case "snow": return "❄️";
            case "thunderstorm": return "⛈️";
            case "mist": case "fog": return "🌫️";
            default: return "🌡️";
        }
    }


    private VBox createCard(AppEvent event) {
        VBox card = new VBox(15); // Espacement plus aéré
        card.setPadding(new Insets(20));
        card.setPrefSize(260, 360);

        // Style de base : Blanc pur avec bordure légère et ombre douce
        String baseStyle = "-fx-background-color: white; " +
                "-fx-background-radius: 20; " +
                "-fx-border-color: #f1f5f9; " +
                "-fx-border-radius: 20; " +
                "-fx-border-width: 1; " +
                "-fx-cursor: hand;";
        card.setStyle(baseStyle + "-fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.05), 15, 0, 0, 10);");

        // --- ANIMATION AU SURVOL (HOVER) ---
        card.setOnMouseEntered(e -> {
            card.setStyle(baseStyle + "-fx-effect: dropshadow(three-pass-box, rgba(47, 65, 136, 0.15), 20, 0, 0, 15); -fx-translate-y: -5;");
        });
        card.setOnMouseExited(e -> {
            card.setStyle(baseStyle + "-fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.05), 15, 0, 0, 10); -fx-translate-y: 0;");
        });

        card.setOnMouseClicked(e -> showEventDetailsPopup(event));

        // Titre : Plus grand et plus sombre pour le contraste
        Label name = new Label(event.getName());
        name.setStyle("-fx-font-weight: 800; -fx-font-size: 18px; -fx-text-fill: #1e293b;");
        name.setWrapText(true);
        name.setMinHeight(50);

        // --- WEATHER BADGE (Modern Pill) ---
        HBox weatherPill = new HBox(8);
        weatherPill.setAlignment(Pos.CENTER_LEFT);
        weatherPill.setPadding(new Insets(5, 12, 5, 12));
        weatherPill.setStyle("-fx-background-color: #f8fafc; -fx-background-radius: 30; -fx-border-color: #e2e8f0; -fx-border-radius: 30;");

        Label tempLabel = new Label("☀️ Loading...");
        tempLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #64748b; -fx-font-weight: bold;");
        weatherPill.getChildren().add(tempLabel);
        fetchWeatherAsync(event.getLocation(), tempLabel);

        // Infos (Date et Lieu) avec icônes stylisées
        VBox infoBox = new VBox(8);
        Label date = new Label("📅  " + event.getEventDate());
        date.setStyle("-fx-text-fill: #475569; -fx-font-size: 13px; -fx-font-weight: 600;");

        Label loc = new Label("📍  " + event.getLocation());
        loc.setStyle("-fx-text-fill: #64748b; -fx-font-size: 13px;");
        infoBox.getChildren().addAll(date, loc);

        // --- BUTTONS ---
        VBox actions = new VBox(10);
        actions.setAlignment(Pos.BOTTOM_CENTER);

        if (!isTicketView) {
            Button joinBtn = new Button("JOIN EVENT");
            stylePrimaryButton(joinBtn, "#6366f1"); // Indigo moderne
            joinBtn.setOnAction(e -> { e.consume(); handleJoin(event); });
            actions.getChildren().add(joinBtn);
        } else {
            Button viewBtn = new Button("VIEW TICKET");
            stylePrimaryButton(viewBtn, "#0f172a"); // Dark mode style
            viewBtn.setOnAction(e -> { e.consume(); showTicketPopup(event); });

            Button cancelBtn = new Button("Cancel Booking");
            cancelBtn.setMaxWidth(Double.MAX_VALUE);
            cancelBtn.setStyle("-fx-background-color: transparent; -fx-text-fill: #ef4444; -fx-font-weight: bold; -fx-cursor: hand;");
            cancelBtn.setOnAction(e -> { e.consume(); handleCancel(event); });
            actions.getChildren().addAll(viewBtn, cancelBtn);
        }

        // Assemblage
        Region spacer = new Region();
        VBox.setVgrow(spacer, Priority.ALWAYS);

        card.getChildren().addAll(name, weatherPill, infoBox, spacer, actions);
        return card;
    }

    // Méthode helper pour styliser les boutons de manière uniforme
    private void stylePrimaryButton(Button btn, String color) {
        btn.setMaxWidth(Double.MAX_VALUE);
        btn.setStyle("-fx-background-color: " + color + "; " +
                "-fx-text-fill: white; " +
                "-fx-font-weight: bold; " +
                "-fx-font-size: 13px; " +
                "-fx-background-radius: 12; " +
                "-fx-padding: 12; " +
                "-fx-cursor: hand;");

        // Effet de feedback au clic
        btn.setOnMousePressed(e -> btn.setStyle(btn.getStyle() + "-fx-opacity: 0.8;"));
        btn.setOnMouseReleased(e -> btn.setStyle(btn.getStyle() + "-fx-opacity: 1.0;"));
    }

    /**
     * --- NEW METHOD: AFFICHE LES DÉTAILS COMPLETS DE L'ÉVÉNEMENT ---
     */
    private void showEventDetailsPopup(AppEvent event) {
        Stage popup = new Stage();
        popup.initModality(Modality.APPLICATION_MODAL);
        // On retire la barre de titre Windows pour un look plus "App"
        popup.initStyle(javafx.stage.StageStyle.TRANSPARENT);

        VBox layout = new VBox(20);
        layout.setPadding(new Insets(35));
        // Design : Fond blanc, coins arrondis (30px), et bordure subtile
        layout.setStyle("-fx-background-color: white; " +
                "-fx-background-radius: 30; " +
                "-fx-border-color: #e2e8f0; " +
                "-fx-border-radius: 30; " +
                "-fx-border-width: 1;");
        layout.setPrefWidth(500);
        layout.setEffect(new javafx.scene.effect.DropShadow(20, Color.rgb(0,0,0,0.2)));

        // Titre : Indigo sombre, pas de majuscules forcées pour plus de modernité
        Label title = new Label(event.getName());
        title.setStyle("-fx-font-size: 26px; -fx-font-weight: 900; -fx-text-fill: #1e293b;");
        title.setWrapText(true);

        // Barre d'infos avec badges
        HBox infoBar = new HBox(15);
        Label dateBadge = new Label("📅 " + event.getEventDate());
        Label locBadge = new Label("📍 " + event.getLocation());
        String badgeStyle = "-fx-background-color: #f1f5f9; -fx-padding: 5 12; -fx-background-radius: 10; -fx-text-fill: #475569; -fx-font-weight: bold; -fx-font-size: 13px;";
        dateBadge.setStyle(badgeStyle);
        locBadge.setStyle(badgeStyle);
        infoBar.getChildren().addAll(dateBadge, locBadge);

        Separator sep = new Separator();
        sep.setPadding(new Insets(10, 0, 10, 0));

        // Zone de Description
        VBox descBox = new VBox(10);
        Label descTitle = new Label("About this event");
        descTitle.setStyle("-fx-font-weight: 800; -fx-font-size: 16px; -fx-text-fill: #6366f1;");

        Text descText = new Text(event.getDescription());
        descText.setWrappingWidth(420);
        descText.setFont(Font.font("Segoe UI", 15));
        descText.setFill(Color.web("#4b5563"));

        // ScrollPane stylisé (on cache les barres de scroll si possible)
        ScrollPane scroll = new ScrollPane(descText);
        scroll.setPrefHeight(250);
        scroll.setFitToWidth(true);
        scroll.setHbarPolicy(ScrollPane.ScrollBarPolicy.NEVER);
        scroll.setStyle("-fx-background-color: transparent; -fx-background: transparent; -fx-padding: 0 5 0 0;");

        // Bouton de fermeture moderne
        Button closeBtn = new Button("CLOSE");
        closeBtn.setPrefWidth(120);
        closeBtn.setStyle("-fx-background-color: #0f172a; -fx-text-fill: white; -fx-font-weight: bold; " +
                "-fx-background-radius: 12; -fx-padding: 12; -fx-cursor: hand;");

        // Animation Hover sur le bouton
        closeBtn.setOnMouseEntered(e -> closeBtn.setStyle(closeBtn.getStyle() + "-fx-background-color: #1e293b;"));
        closeBtn.setOnMouseExited(e -> closeBtn.setStyle(closeBtn.getStyle().replace("-fx-background-color: #1e293b;", "-fx-background-color: #0f172a;")));

        closeBtn.setOnAction(e -> popup.close());

        // Alignement final
        HBox footer = new HBox(closeBtn);
        footer.setAlignment(Pos.CENTER_RIGHT);

        layout.getChildren().addAll(title, infoBar, sep, descTitle, scroll, footer);

        Scene scene = new Scene(layout);
        scene.setFill(Color.TRANSPARENT); // Important pour les coins arrondis du Stage
        popup.setScene(scene);

        // Positionnement au centre de l'écran
        popup.centerOnScreen();
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