package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.Training;
import com.example.smarthire.services.TrainingService;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.input.Clipboard;
import javafx.scene.input.ClipboardContent;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.sql.SQLException;
import java.util.List;
import java.util.stream.Collectors;
public class CandidateTrainingController {

    @FXML private VBox cardContainer;
    @FXML private TextField searchField;

    private final TrainingService service = new TrainingService();
    private List<Training> trainings;

    @FXML
    private void initialize() {
        loadData();

        // Filter trainings on typing
        searchField.textProperty().addListener((obs, oldVal, newVal) -> filterTrainings(newVal));
    }

    private void loadData() {
        try {
            trainings = service.getAll(); // Load all trainings
            displayTrainings(trainings);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void filterTrainings(String query) {
        String lower = query.toLowerCase();
        List<Training> filtered = trainings.stream()
                .filter(t -> t.getTitle().toLowerCase().contains(lower) ||
                        t.getCategory().toLowerCase().contains(lower))
                .collect(Collectors.toList());
        displayTrainings(filtered);
    }

    private void displayTrainings(List<Training> list) {
        cardContainer.getChildren().clear();

        for (Training t : list) {
            VBox card = new VBox(5);
            card.setPadding(new Insets(10));
            card.setStyle("-fx-background-color: white; -fx-border-color: #ccc; -fx-border-radius: 5; -fx-background-radius: 5;");

            Label title = new Label(t.getTitle());
            title.setStyle("-fx-font-size: 16px; -fx-font-weight: bold;");

            Label category = new Label("Category: " + t.getCategory());
            category.setStyle("-fx-font-size: 14px; -fx-text-fill: #555;");

            // Learn More button
            Button learnMore = new Button("Learn More");
            learnMore.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white;");
            learnMore.setOnAction(e -> showDetails(t));

            HBox bottom = new HBox();
            Region spacer = new Region();
            HBox.setHgrow(spacer, Priority.ALWAYS);
            bottom.getChildren().addAll(spacer, learnMore);

            card.getChildren().addAll(title, category, bottom);
            cardContainer.getChildren().add(card);
        }
    }

    private void showDetails(Training t) {
        // Create a custom dialog
        Dialog<Void> dialog = new Dialog<>();
        dialog.setTitle("Training Details");

        // Remove default buttons
        dialog.getDialogPane().getButtonTypes().add(ButtonType.CLOSE);

        // Main container
        VBox container = new VBox(10);
        container.setPadding(new Insets(15));

        // Title Label (green)
        Label titleLabel = new Label(t.getTitle());
        titleLabel.setStyle("-fx-font-size: 20px; -fx-font-weight: bold; -fx-text-fill: #F71E74;");

        // Category label
        Label categoryLabel = new Label("Category: " + t.getCategory());
        categoryLabel.setStyle("-fx-font-size: 14px; -fx-font-weight: bold;");

        // Description
        String originalDescription = t.getDescription();

        TextArea descArea = new TextArea(originalDescription);
        descArea.setWrapText(true);
        descArea.setEditable(false);
        descArea.setStyle("-fx-font-size: 14px; -fx-font-family: 'Arial'; -fx-control-inner-background: #f9f9f9;");

        // Translation buttons
        Button btnFrench = new Button("French 🇫🇷");
        Button btnArabic = new Button("Arabic 🇸🇦");
        Button btnOriginal = new Button("Original");

        btnFrench.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white;");
        btnArabic.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white;");
        btnOriginal.setStyle("-fx-background-color: #777; -fx-text-fill: white;");

        HBox translateBox = new HBox(10, btnFrench, btnArabic, btnOriginal);
        btnFrench.setOnAction(e -> {
            try {
                String translated = translateText(originalDescription, "fr");
                descArea.setText(translated);
            } catch (Exception ex) {
                ex.printStackTrace();
                new Alert(Alert.AlertType.ERROR, "Translation failed").show();
            }
        });

        btnArabic.setOnAction(e -> {
            try {
                String translated = translateText(originalDescription, "ar");
                descArea.setText(translated);
            } catch (Exception ex) {
                ex.printStackTrace();
                new Alert(Alert.AlertType.ERROR, "Translation failed").show();
            }
        });

        btnOriginal.setOnAction(e -> {
            descArea.setText(originalDescription);
        });
        // Video link
        Hyperlink link = new Hyperlink(t.getUrl() != null ? t.getUrl() : "No video available");
        link.setStyle("-fx-text-fill: #1565c0; -fx-font-size: 14px;");
        link.setOnAction(e -> {
            if (t.getUrl() != null && !t.getUrl().isEmpty()) {
                // Copy URL to clipboard
                Clipboard clipboard = Clipboard.getSystemClipboard();
                ClipboardContent content = new ClipboardContent();
                content.putString(t.getUrl());
                clipboard.setContent(content);

                // Optional: show a confirmation
                System.out.println("URL copied: " + t.getUrl());
            }

        });

        container.getChildren().addAll(
                titleLabel,
                categoryLabel,
                translateBox,
                descArea,
                link
        );

        dialog.getDialogPane().setContent(container);

        dialog.showAndWait();
    }

    private String translateText(String text, String targetLang) throws Exception {


        String encodedText = URLEncoder.encode(text, StandardCharsets.UTF_8);

        String requestUrl = endpoint + "?q=" + encodedText + "&target=" + targetLang;

        URL url = new URL(requestUrl);
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("GET");

        int responseCode = conn.getResponseCode();
        if (responseCode != 200) {
            throw new RuntimeException("HTTP error code: " + responseCode);
        }

        BufferedReader in = new BufferedReader(
                new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8)
        );

        String inputLine;
        StringBuilder response = new StringBuilder();

        while ((inputLine = in.readLine()) != null) {
            response.append(inputLine);
        }

        in.close();
        conn.disconnect();

        return response.toString();
    }
}