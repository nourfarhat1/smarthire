package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.Training;
import com.example.smarthire.services.TrainingService;
import com.example.smarthire.utils.SessionManager;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.GridPane;
import okhttp3.*;
import org.json.JSONObject;

import java.sql.SQLException;
import java.util.Optional;

public class AdminTrainingController {

    @FXML private TableView<Training> trainingTable;
    @FXML private TableColumn<Training, String> colTitle, colCategory, colAdmin;
    @FXML private TableColumn<Training, Integer> colLikes, colDislikes;
    @FXML private TableColumn<Training, String> colDate;
    @FXML private AnchorPane mainContent;
    @FXML private Button btnTrainings;

    private final TrainingService service = new TrainingService();



    private void loadData() {
        try {
            trainingTable.setItems(FXCollections.observableArrayList(service.getAll()));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // ---------------- ADD ----------------
    @FXML
    private void handleAdd() {
        showTrainingDialog(null);
    }

    // ---------------- EDIT ----------------
    @FXML
    private void handleEdit() {
        Training selected = trainingTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            alert("Select a training first.");
            return;
        }
        showTrainingDialog(selected);
    }

    // ---------------- DELETE ----------------
    @FXML
    private void handleDelete() {
        Training selected = trainingTable.getSelectionModel().getSelectedItem();
        if (selected == null) return;

        try {
            service.delete(selected.getId());
            loadData();
        } catch (SQLException e) {
            alert("Error deleting: " + e.getMessage());
        }
    }

    // ---------------- DIALOG ----------------
    private void showTrainingDialog(Training existing) {
        Dialog<ButtonType> dialog = new Dialog<>();
        dialog.setTitle(existing == null ? "Add Training" : "Edit Training");
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        grid.setPadding(new Insets(20));

        // Fields
        TextField titleField = new TextField(existing != null ? existing.getTitle() : "");
        TextField categoryField = new TextField(existing != null ? existing.getCategory() : "");
        TextArea descArea = new TextArea(existing != null ? existing.getDescription() : "");
        TextField urlField = new TextField(existing != null ? existing.getUrl() : "");

        titleField.setPromptText("Title");
        categoryField.setPromptText("Category");
        descArea.setPromptText("Description");
        urlField.setPromptText("YouTube URL (optional)");

        // Add labels & fields
        grid.add(new Label("Title:"), 0, 0);
        grid.add(titleField, 1, 0);

        grid.add(new Label("Category:"), 0, 1);
        grid.add(categoryField, 1, 1);

        grid.add(new Label("Description:"), 0, 2);
        grid.add(descArea, 1, 2);

        grid.add(new Label("URL:"), 0, 3);
        grid.add(urlField, 1, 3);

        // ✅ "Generate with AI" button under description
        Button generateAIButton = new Button("Generate with AI");
        grid.add(generateAIButton, 1, 4);

        generateAIButton.setOnAction(e -> {
            String title = titleField.getText().trim();
            String category = categoryField.getText().trim();

            if (title.isEmpty() || category.isEmpty()) {
                new Alert(Alert.AlertType.WARNING, "Please enter Title and Category first.").show();
                return;
            }

            try {
                String aiDescription = generateTrainingDescriptionGemini(title, category);
                descArea.setText(aiDescription);
            } catch (Exception ex) {
                ex.printStackTrace();
                new Alert(Alert.AlertType.ERROR, "Failed to generate description: " + ex.getMessage()).show();
            }
        });

        dialog.getDialogPane().setContent(grid);

        // ✅ Disable OK button until required fields are filled
        Button okButton = (Button) dialog.getDialogPane().lookupButton(ButtonType.OK);
        okButton.setDisable(true);

        Runnable inputChecker = () -> {
            boolean disable = titleField.getText().trim().isEmpty() ||
                    categoryField.getText().trim().isEmpty() ||
                    descArea.getText().trim().isEmpty();
            okButton.setDisable(disable);
        };

        titleField.textProperty().addListener((obs, oldVal, newVal) -> inputChecker.run());
        categoryField.textProperty().addListener((obs, oldVal, newVal) -> inputChecker.run());
        descArea.textProperty().addListener((obs, oldVal, newVal) -> inputChecker.run());

        inputChecker.run(); // initial check

        // ✅ Handle OK
        Optional<ButtonType> res = dialog.showAndWait();
        if (res.isPresent() && res.get() == ButtonType.OK) {
            try {
                if (existing == null) {
                    Training t = new Training(
                            titleField.getText().trim(),
                            categoryField.getText().trim(),
                            descArea.getText().trim(),
                            urlField.getText().trim(),
                            SessionManager.getUser().getId()
                    );
                    service.addTraining(t);
                } else {
                    existing.setTitle(titleField.getText().trim());
                    existing.setCategory(categoryField.getText().trim());
                    existing.setDescription(descArea.getText().trim());
                    existing.setUrl(urlField.getText().trim());
                    service.update(existing);
                }
                loadData();
            } catch (SQLException e) {
                new Alert(Alert.AlertType.ERROR, "Error: " + e.getMessage()).show();
            }
        }
    }

    private void alert(String msg) {
        new Alert(Alert.AlertType.INFORMATION, msg).show();
    }

    private final OkHttpClient client = new OkHttpClient();

    private String generateTrainingDescriptionGemini(String title, String category) throws Exception {
        String prompt = "Write a professional and engaging description for a training titled '"
                + title + "' in the category '" + category + "'. Make it concise and informative.";

        JSONObject requestBody = new JSONObject();
        requestBody.put("contents", new org.json.JSONArray()
                .put(new JSONObject()
                        .put("parts", new org.json.JSONArray()
                                .put(new JSONObject().put("text", prompt))
                        )
                )
        );

        RequestBody body = RequestBody.create(
                requestBody.toString(),
                MediaType.get("application/json")
        );

        Request request = new Request.Builder()
                .url("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" +)
                .post(body)
                .build();

        try (Response response = client.newCall(request).execute()) {
            if (!response.isSuccessful()) {
                throw new Exception("Unexpected code " + response);
            }

            String responseBody = response.body().string();
            JSONObject jsonResponse = new JSONObject(responseBody);

            return jsonResponse
                    .getJSONArray("candidates")
                    .getJSONObject(0)
                    .getJSONObject("content")
                    .getJSONArray("parts")
                    .getJSONObject(0)
                    .getString("text");
        }
    }

    @FXML
    private void initialize() {
        // Map columns to Training properties
        colTitle.setCellValueFactory(new PropertyValueFactory<>("title"));
        colCategory.setCellValueFactory(new PropertyValueFactory<>("category"));
        colAdmin.setCellValueFactory(new PropertyValueFactory<>("adminName")); // or adminId if you store IDs
        colLikes.setCellValueFactory(new PropertyValueFactory<>("likes"));
        colDislikes.setCellValueFactory(new PropertyValueFactory<>("dislikes"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("createdAt"));

        // Load data into table
        loadData();
    }

}