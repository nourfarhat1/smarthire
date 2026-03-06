package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.utils.SessionManager;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.stage.FileChooser;
import javafx.stage.Stage;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;

import java.io.File;
import java.sql.SQLException;
public class SpontaneousAppFormController {

    @FXML private TextField cvField;
    @FXML private TextArea letterArea;
    @FXML private TextField jobTitleField;
    @FXML private TextField salaryField; // Optional suggested salary
    @FXML private TextField locationField;
    @FXML private TextField categorieField;

    private final ApplicationService appService = new ApplicationService();

    // Optional: reference to parent controller to refresh table
    private MyApplicationsController parentController;

    public void setParentController(MyApplicationsController parentController) {
        this.parentController = parentController;
    }
    // 1️⃣ Add the Adzuna API method HERE
    private final OkHttpClient client = new OkHttpClient.Builder()
            .connectTimeout(15, java.util.concurrent.TimeUnit.SECONDS)  // connection timeout
            .writeTimeout(15, java.util.concurrent.TimeUnit.SECONDS)    // send timeout
            .readTimeout(60, java.util.concurrent.TimeUnit.SECONDS)     // read timeout (response)
            .build();

    private Double fetchSuggestedSalary(String jobTitle, String location) throws Exception {
        String what = java.net.URLEncoder.encode(jobTitle, "UTF-8");
        String where = java.net.URLEncoder.encode(location, "UTF-8");

        String url = "https://api.adzuna.com/v1/api/jobs/gb/search/1" +
                "?app_id=492f1619" +
                "&what=" + what +
                "&where=" + where +
                "&results_per_page=10" +
                "&content-type=application/json";

        Request request = new Request.Builder().url(url).build();
        try (Response response = client.newCall(request).execute()) {
            if (!response.isSuccessful()) throw new Exception("Unexpected code " + response);

            String jsonData = response.body().string();
            JSONObject obj = new JSONObject(jsonData);
            JSONArray results = obj.getJSONArray("results");

            if (results.length() == 0) return null;

            double sum = 0;
            int count = 0;
            for (int i = 0; i < Math.min(10, results.length()); i++) {
                JSONObject job = results.getJSONObject(i);
                if (!job.isNull("salary_min") && !job.isNull("salary_max")) {
                    double min = job.getDouble("salary_min");
                    double max = job.getDouble("salary_max");
                    sum += (min + max) / 2;
                    count++;
                }
            }
            return count > 0 ? sum / count : null;
        }
    }
    @FXML
    private void handleGenerateSalary() {
        String jobTitle = jobTitleField.getText().trim();
        String location = locationField.getText().trim();

        if (jobTitle.isEmpty()) {
            new Alert(Alert.AlertType.WARNING, "Please enter a job title first.").show();
            return;
        }

        try {
            Double generatedSalary = fetchSuggestedSalary(jobTitle, location); // use your implemented method
            if (generatedSalary != null && generatedSalary > 0) {
                salaryField.setText(String.valueOf(generatedSalary.intValue())); // round to int
            } else {
                new Alert(Alert.AlertType.INFORMATION, "No salary data found for this job.").show();
            }
        } catch (Exception ex) {
            ex.printStackTrace();
            new Alert(Alert.AlertType.ERROR, "Failed to generate salary: " + ex.getMessage()).show();
        }
    }


    private static final String GEMINI_API_KEY = "AIzaSyCDlkSWIvSNwE-pqOOx2hmi2S9Iabudlfc"; // Replace with your real key
    private String generateCoverLetterGemini(String jobTitle, String location) throws Exception {

        String prompt = "Write a professional cover letter for a " + jobTitle;
        if (!location.isEmpty()) {
            prompt += " position in " + location;
        }
        prompt += ". Make it formal and professional.";

        JSONObject requestBody = new JSONObject();

        // Create content structure required by Gemini
        requestBody.put("contents", new JSONArray()
                .put(new JSONObject()
                        .put("parts", new JSONArray()
                                .put(new JSONObject().put("text", prompt))
                        )
                )
        );

        RequestBody body = RequestBody.create(
                requestBody.toString(),
                MediaType.get("application/json")
        );

        Request request = new Request.Builder()
                .url("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" + GEMINI_API_KEY)
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
    private void handleGenerateCoverLetter() {
        String jobTitle = jobTitleField.getText().trim();
        String location = locationField.getText().trim();

        if (jobTitle.isEmpty()) {
            new Alert(Alert.AlertType.WARNING, "Please enter a job title first.").show();
            return;
        }

        try {
            String coverLetter = generateCoverLetterGemini(jobTitle, location);
            letterArea.setText(coverLetter);
        } catch (Exception ex) {
            ex.printStackTrace();
            new Alert(Alert.AlertType.ERROR, "Failed to generate cover letter: " + ex.getMessage()).show();
        }
    }

    @FXML
    private void handleUploadCV() {

        FileChooser fileChooser = new FileChooser();
        fileChooser.setTitle("Select Your CV");

        fileChooser.getExtensionFilters().addAll(
                new FileChooser.ExtensionFilter("PDF Files", "*.pdf"),
                new FileChooser.ExtensionFilter("Word Files", "*.docx")
        );

        Stage stage = (Stage) cvField.getScene().getWindow();
        File selectedFile = fileChooser.showOpenDialog(stage);

        if (selectedFile != null) {
            cvField.setText(selectedFile.getAbsolutePath());
        }
    }
    private String cvContent; // extracted text from uploaded CV
    @FXML
    private void handleCreate() {
        String cv = cvField.getText().trim();
        String letter = letterArea.getText().trim();
        String jobTitle = jobTitleField.getText().trim();
        String location = locationField.getText().trim(); // optional
        Double salary = null;
        String categorie = categorieField.getText().trim();

        // Validate required fields
        if(cv.isEmpty() || letter.isEmpty() || jobTitle.isEmpty() || categorie == null) {
            new Alert(Alert.AlertType.WARNING, "Please fill all required fields, including Category.").show();
            return;
        }

        // Parse optional salary
        if(!salaryField.getText().trim().isEmpty()) {
            try {
                salary = Double.parseDouble(salaryField.getText().trim());
            } catch (NumberFormatException e) {
                new Alert(Alert.AlertType.WARNING, "Invalid salary format").show();
                return;
            }
        }

        try {
            // ✅ Create spontaneous application (jobOfferId = null)
            JobRequest req = new JobRequest(
                    SessionManager.getUser().getId(),
                    null,              // jobOfferId = null for spontaneous
                    jobTitle,
                    location,
                    cv,
                    letter,
                    salary,
                    categorie          // ✅ Include category
            );

            appService.apply(req); // Save to DB

            // Refresh parent table if exists
            if(parentController != null) parentController.loadData();

            new Alert(Alert.AlertType.INFORMATION, "Application submitted successfully!").show();

            // Close form
            Stage stage = (Stage) cvField.getScene().getWindow();
            stage.close();

        } catch (SQLException e) {
            e.printStackTrace();
            new Alert(Alert.AlertType.ERROR, "Failed to submit application: " + e.getMessage()).show();
        }
    }

    @FXML
    private void handleCancel() {
        Stage stage = (Stage) cvField.getScene().getWindow();
        stage.close();
    }
}