package com.example.smarthire.controllers.test;

import com.example.smarthire.entities.test.Question;
import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.entities.test.QuizResult;
import com.example.smarthire.services.QuestionService;
import com.example.smarthire.services.QuizResultService;
import com.example.smarthire.services.SupabaseStorageService;
import com.example.smarthire.services.TestService;
import com.example.smarthire.utils.Navigation;
import com.example.smarthire.utils.QuizPdfExporter;
import com.example.smarthire.utils.SessionManager;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.ListCell;
import javafx.scene.control.ListView;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.awt.*;
import java.net.URI;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

public class CandidateQuizListController {

    @FXML private ListView<Quiz> quizListView;
    @FXML private ListView<QuizResult> myResultsListView;
    @FXML private Label statusLabel;

    public static int selectedQuizId = -1;

    private final TestService testService = new TestService();
    private final QuestionService questionService = new QuestionService();
    private final QuizResultService resultService = new QuizResultService();

    private final Map<Integer, QuizResult> attemptedResults = new HashMap<>();

    @FXML
    public void initialize() {
        quizListView.setCellFactory(lv -> new QuizCardCell());
        myResultsListView.setCellFactory(lv -> new MyResultCell());
        
        loadMyResults();
        loadQuizzes();
    }

    private void loadQuizzes() {
        try {
            quizListView.setItems(FXCollections.observableArrayList(testService.getAll()));
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error loading quizzes: " + e.getMessage());
        }
    }

    private void loadMyResults() {
        try {
            int userId = SessionManager.getUser().getId();
            List<QuizResult> all = resultService.getAll();
            List<QuizResult> mine = all.stream()
                    .filter(r -> r.getCandidateId() == userId)
                    .collect(Collectors.toList());
            
            attemptedResults.clear();
            for (QuizResult r : mine) {
                attemptedResults.putIfAbsent(r.getQuizId(), r);
            }
            myResultsListView.setItems(FXCollections.observableArrayList(mine));
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error loading results: " + e.getMessage());
        }
    }

    private class QuizCardCell extends ListCell<Quiz> {
        @Override
        protected void updateItem(Quiz quiz, boolean empty) {
            super.updateItem(quiz, empty);
            if (empty || quiz == null) {
                setGraphic(null);
                setText(null);
            } else {
                HBox card = new HBox(15);
                card.setAlignment(Pos.CENTER_LEFT);
                card.setStyle("-fx-background-color: white; -fx-padding: 15; -fx-background-radius: 10; -fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.08), 8, 0, 0, 3);");

                VBox info = new VBox(4);
                Label titleLbl = new Label(quiz.getTitle());
                titleLbl.setStyle("-fx-font-size: 16px; -fx-font-weight: bold; -fx-text-fill: #2f4188;");

                Label descLbl = new Label(quiz.getDescription());
                descLbl.setStyle("-fx-text-fill: #666; -fx-font-size: 13px;");
                descLbl.setWrapText(true);

                HBox meta = new HBox(12);
                Label durationLbl = new Label("Duration: " + quiz.getDurationMinutes() + " min");
                durationLbl.setStyle("-fx-text-fill: #e91e63; -fx-font-weight: bold;");

                int qCount = 0;
                try { qCount = questionService.getByQuizId(quiz.getId()).size(); } catch (SQLException ignored) {}
                Label questionsLbl = new Label(qCount + " Questions");
                questionsLbl.setStyle("-fx-text-fill: #2196F3; -fx-font-weight: bold;");

                meta.getChildren().addAll(durationLbl, questionsLbl);
                info.getChildren().addAll(titleLbl, descLbl, meta);

                Region spacer = new Region();
                HBox.setHgrow(spacer, Priority.ALWAYS);

                QuizResult previous = attemptedResults.get(quiz.getId());
                if (previous != null) {
                    
                    VBox statusBox = new VBox(2);
                    statusBox.setAlignment(Pos.CENTER);
                    String icon = previous.isPassed() ? "\u2705" : "\u274C";
                    String label = previous.isPassed() ? "PASSED" : "FAILED";
                    String color = previous.isPassed() ? "#4CAF50" : "#E91E63";
                    Label iconLbl = new Label(icon + "  " + label);
                    iconLbl.setStyle("-fx-font-size: 15px; -fx-font-weight: bold; -fx-text-fill: " + color + ";");
                    Label lockedLbl = new Label("Already attempted");
                    lockedLbl.setStyle("-fx-font-size: 11px; -fx-text-fill: #999;");
                    statusBox.getChildren().addAll(iconLbl, lockedLbl);
                    card.getChildren().addAll(info, spacer, statusBox);
                } else {
                    Button startBtn = new Button("Start Quiz");
                    startBtn.setStyle("-fx-background-color: #4CAF50; -fx-text-fill: white; -fx-font-weight: bold; -fx-padding: 10 20; -fx-background-radius: 20; -fx-cursor: hand;");
                    startBtn.setOnAction(e -> {
                        selectedQuizId = quiz.getId();
                        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateQuizzTake.fxml");
                    });
                    card.getChildren().addAll(info, spacer, startBtn);
                }
                setGraphic(card);
                setStyle("-fx-background-color: transparent; -fx-padding: 4 0;");
            }
        }
    }

    private class MyResultCell extends ListCell<QuizResult> {
        private final SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm");

        @Override
        protected void updateItem(QuizResult result, boolean empty) {
            super.updateItem(result, empty);
            if (empty || result == null) {
                setGraphic(null);
                setText(null);
            } else {
                HBox card = new HBox(15);
                card.setAlignment(Pos.CENTER_LEFT);
                card.setStyle("-fx-background-color: white; -fx-padding: 12; -fx-background-radius: 8; -fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.06), 5, 0, 0, 2);");

                VBox info = new VBox(2);
                Label quizLbl = new Label(result.getQuizTitle());
                quizLbl.setStyle("-fx-font-weight: bold; -fx-text-fill: #333; -fx-font-size: 14px;");

                String dateStr = result.getAttemptDate() != null ? dateFormat.format(result.getAttemptDate()) : "N/A";
                Label dateLbl = new Label(dateStr);
                dateLbl.setStyle("-fx-text-fill: #999; -fx-font-size: 12px;");
                info.getChildren().addAll(quizLbl, dateLbl);

                Region spacer = new Region();
                HBox.setHgrow(spacer, Priority.ALWAYS);

                Label scoreLbl = new Label(result.getScore() + "%");
                scoreLbl.setStyle("-fx-font-size: 18px; -fx-font-weight: bold; -fx-text-fill: " + (result.isPassed() ? "#4CAF50" : "#E91E63") + ";");

                Label passLbl = new Label(result.isPassed() ? "PASSED" : "FAILED");
                passLbl.setStyle("-fx-font-weight: bold; -fx-text-fill: " + (result.isPassed() ? "#4CAF50" : "#E91E63") + ";");

                Button exportBtn = new Button("\uD83D\uDCC4 Export PDF");
                exportBtn.setStyle("-fx-background-color:#3949ab;-fx-text-fill:white;-fx-font-size:11px;" +
                        "-fx-padding:5 12;-fx-background-radius:14;-fx-cursor:hand;");
                exportBtn.setOnAction(e -> exportResultPdf(result, exportBtn));

                VBox scoreBox = new VBox(4);
                scoreBox.setAlignment(Pos.CENTER);
                scoreBox.getChildren().addAll(scoreLbl, passLbl, exportBtn);

                card.getChildren().addAll(info, spacer, scoreBox);
                setGraphic(card);
                setStyle("-fx-background-color: transparent; -fx-padding: 3 0;");
            }
        }
    }

    private void exportResultPdf(QuizResult result, Button exportBtn) {
        // 1. URL already on the result object (loaded from DB or just uploaded)
        if (result.getPdfUrl() != null && !result.getPdfUrl().isEmpty()) {
            openInBrowser(result.getPdfUrl());
            statusLabel.setStyle("-fx-text-fill:#4CAF50;");
            statusLabel.setText("\u2705 Opened in browser!");
            return;
        }

        // 2. Generate, upload, persist, open
        exportBtn.setDisable(true);
        exportBtn.setText("Uploading...");

        Task<String> task = new Task<>() {
            @Override
            protected String call() throws Exception {
                Quiz quiz = testService.getOne(result.getQuizId());
                List<Question> qs = questionService.getByQuizId(result.getQuizId());
                String candidateName = SessionManager.getUser().getFirstName()
                        + " " + SessionManager.getUser().getLastName();
                byte[] pdf = new QuizPdfExporter().generate(
                        quiz, qs, new HashMap<>(), result.getScore(), result.isPassed(), candidateName);
                String safe = result.getQuizTitle().replaceAll("[^a-zA-Z0-9_-]", "_");
                String filePath = "quiz-results/" + SessionManager.getUser().getId()
                        + "/" + safe + "_" + result.getId() + ".pdf";
                return new SupabaseStorageService().uploadPdf(pdf, filePath);
            }
        };

        task.setOnSucceeded(ev -> Platform.runLater(() -> {
            String url = task.getValue();
            // Persist so next click (and next session) skips generation
            result.setPdfUrl(url);
            try { new QuizResultService().savePdfUrl(result.getId(), url); } catch (Exception ignored) {}
            exportBtn.setDisable(false);
            exportBtn.setText("\uD83D\uDCC4 Open PDF");
            openInBrowser(url);
            statusLabel.setStyle("-fx-text-fill:#4CAF50;");
            statusLabel.setText("\u2705 PDF uploaded and opened in browser!");
        }));

        task.setOnFailed(ev -> Platform.runLater(() -> {
            exportBtn.setDisable(false);
            exportBtn.setText("\uD83D\uDCC4 Export PDF");
            statusLabel.setStyle("-fx-text-fill:red;");
            Throwable err = task.getException();
            statusLabel.setText("Export failed: " + (err != null ? err.getMessage() : "unknown error"));
        }));

        Thread t = new Thread(task);
        t.setDaemon(true);
        t.start();
    }

    private void openInBrowser(String url) {
        try {
            if (Desktop.isDesktopSupported()
                    && Desktop.getDesktop().isSupported(Desktop.Action.BROWSE))
                Desktop.getDesktop().browse(new URI(url));
        } catch (Exception ignored) {}
    }
}
