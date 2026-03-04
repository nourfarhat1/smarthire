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
import javafx.animation.KeyFrame;
import javafx.animation.Timeline;
import javafx.application.Platform;
import javafx.beans.value.ChangeListener;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.input.KeyCode;
import javafx.scene.input.KeyCombination;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;
import javafx.util.Duration;

import java.awt.*;
import java.net.URI;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class CandidateQuizTakeController {

    @FXML private Label quizTitleLabel;
    @FXML private Label timerLabel;
    @FXML private VBox questionCard;
    @FXML private Label questionNumberLabel;
    @FXML private Label questionTextLabel;
    @FXML private RadioButton optionA;
    @FXML private RadioButton optionB;
    @FXML private RadioButton optionC;
    @FXML private ToggleGroup answerGroup;
    @FXML private Button btnPrevious;
    @FXML private Button btnNext;
    @FXML private Button btnSubmit;
    @FXML private VBox resultCard;
    @FXML private Label scoreLabel;
    @FXML private Label passFailLabel;
    @FXML private Label detailLabel;
    @FXML private Button btnExportPdf;
    @FXML private Label exportStatusLabel;

    private final TestService testService = new TestService();
    private final QuestionService questionService = new QuestionService();
    private final QuizResultService resultService = new QuizResultService();

    private Quiz quiz;
    private List<Question> questions;
    private int currentIndex = 0;
    private Map<Integer, String> answers = new HashMap<>();
    private Timeline timer;
    private int timeRemaining;

    private int     finalScore;
    private boolean finalPassed;
    private int     finalCorrect;
    private QuizResult savedResult = null;  // holds the persisted result (with id + pdfUrl)

    private boolean quizActive = false;
    private Stage   antiCheatStage;
    private ChangeListener<Boolean> fullScreenListener;
    private ChangeListener<Boolean> focusListener;
    private ChangeListener<Boolean> iconifiedListener;

    @FXML
    public void initialize() {
        try {
            int quizId = CandidateQuizListController.selectedQuizId;
            if (quizId <= 0) {
                quizTitleLabel.setText("No quiz selected");
                questionCard.setVisible(false);
                return;
            }
            quiz = testService.getOne(quizId);
            questions = questionService.getByQuizId(quizId);

            if (quiz == null || questions.isEmpty()) {
                quizTitleLabel.setText("Quiz not found or has no questions");
                questionCard.setVisible(false);
                return;
            }

            int userId = SessionManager.getUser().getId();
            if (resultService.hasAttempted(quizId, userId)) {
                quizTitleLabel.setText(quiz.getTitle());
                questionCard.setVisible(false);
                questionCard.setManaged(false);
                resultCard.setVisible(true);
                resultCard.setManaged(true);
                scoreLabel.setText("\uD83D\uDD12");
                scoreLabel.setStyle("-fx-font-size: 48px; -fx-font-weight: bold; -fx-text-fill: #9E9E9E;");
                passFailLabel.setText("Already Completed");
                passFailLabel.setStyle("-fx-font-size: 20px; -fx-font-weight: bold; -fx-text-fill: #9E9E9E;");
                detailLabel.setText("You have already completed this quiz. Each quiz can only be taken once — check your results in the quiz list.");
                return;
            }

            quizTitleLabel.setText(quiz.getTitle());
            timeRemaining = quiz.getDurationMinutes() * 60;
            quizActive = true;
            Platform.runLater(this::activateAntiCheat);
            startTimer();
            showQuestion(0);
        } catch (SQLException e) {
            quizTitleLabel.setText("Error loading quiz: " + e.getMessage());
            questionCard.setVisible(false);
        }
    }

    private void activateAntiCheat() {
        if (quizTitleLabel.getScene() == null || quizTitleLabel.getScene().getWindow() == null) return;
        antiCheatStage = (Stage) quizTitleLabel.getScene().getWindow();
        antiCheatStage.setFullScreenExitKeyCombination(KeyCombination.NO_MATCH);
        antiCheatStage.setFullScreenExitHint("");
        antiCheatStage.setAlwaysOnTop(true);
        antiCheatStage.setFullScreen(true);

        antiCheatStage.getScene().setOnKeyPressed(e -> {
            if (e.getCode() == KeyCode.ESCAPE || e.getCode() == KeyCode.F11) {
                e.consume();
            }
        });

        fullScreenListener = (obs, wasFS, isFS) -> {
            if (!isFS && quizActive) {
                antiCheatStage.setFullScreen(true);
            }
        };
        antiCheatStage.fullScreenProperty().addListener(fullScreenListener);

        focusListener = (obs, wasFocused, isFocused) -> {
            if (!isFocused && quizActive) {
                Platform.runLater(() -> {
                    if (quizActive) forceSubmitDueToCheat("Quiz auto-submitted: you switched away from the exam window.");
                });
            }
        };
        antiCheatStage.focusedProperty().addListener(focusListener);

        iconifiedListener = (obs, wasMin, isMin) -> {
            if (isMin && quizActive) {
                Platform.runLater(() -> {
                    if (quizActive) forceSubmitDueToCheat("Quiz auto-submitted: exam window was minimized.");
                });
            }
        };
        antiCheatStage.iconifiedProperty().addListener(iconifiedListener);

        antiCheatStage.setOnCloseRequest(e -> {
            if (quizActive) {
                e.consume();
                forceSubmitDueToCheat("Quiz auto-submitted: exam window was closed.");
            }
        });
    }

    private void deactivateAntiCheat() {
        quizActive = false;
        if (antiCheatStage == null) return;
        if (fullScreenListener != null)  antiCheatStage.fullScreenProperty().removeListener(fullScreenListener);
        if (focusListener != null)       antiCheatStage.focusedProperty().removeListener(focusListener);
        if (iconifiedListener != null)   antiCheatStage.iconifiedProperty().removeListener(iconifiedListener);
        antiCheatStage.setOnCloseRequest(null);
        antiCheatStage.setAlwaysOnTop(false);
        antiCheatStage.setFullScreen(false);
        if (antiCheatStage.getScene() != null) antiCheatStage.getScene().setOnKeyPressed(null);
    }

    private void forceSubmitDueToCheat(String reason) {
        if (!quizActive) return;
        if (timer != null) timer.stop();
        deactivateAntiCheat();
        submitQuizInternal(reason);
    }

    private void startTimer() {
        updateTimerDisplay();
        timer = new Timeline(new KeyFrame(Duration.seconds(1), e -> {
            timeRemaining--;
            updateTimerDisplay();
            if (timeRemaining <= 0) {
                timer.stop();
                deactivateAntiCheat();
                submitQuizInternal(null);
            }
        }));
        timer.setCycleCount(Timeline.INDEFINITE);
        timer.play();
    }

    private void updateTimerDisplay() {
        int min = timeRemaining / 60;
        int sec = timeRemaining % 60;
        timerLabel.setText(String.format("Time: %02d:%02d", min, sec));
        if (timeRemaining <= 60) {
            timerLabel.setStyle("-fx-font-size: 16px; -fx-font-weight: bold; -fx-text-fill: red; -fx-font-family: monospace;");
        }
    }

    private void showQuestion(int index) {
        if (index < 0 || index >= questions.size()) return;
        currentIndex = index;
        Question q = questions.get(index);

        questionNumberLabel.setText("Question " + (index + 1) + " of " + questions.size());
        questionTextLabel.setText(q.getQuestionText());
        optionA.setText("A: " + q.getOptionA());
        optionB.setText("B: " + q.getOptionB());
        optionC.setText("C: " + q.getOptionC());

        answerGroup.selectToggle(null);
        String saved = answers.get(q.getId());
        if (saved != null) {
            switch (saved) {
                case "A": answerGroup.selectToggle(optionA); break;
                case "B": answerGroup.selectToggle(optionB); break;
                case "C": answerGroup.selectToggle(optionC); break;
            }
        }

        btnPrevious.setDisable(index == 0);
        btnNext.setVisible(index < questions.size() - 1);
        btnSubmit.setVisible(index == questions.size() - 1);
    }

    private void saveCurrentAnswer() {
        Question q = questions.get(currentIndex);
        Toggle selected = answerGroup.getSelectedToggle();
        if (selected == optionA) answers.put(q.getId(), "A");
        else if (selected == optionB) answers.put(q.getId(), "B");
        else if (selected == optionC) answers.put(q.getId(), "C");
    }

    @FXML
    private void handleNext() {
        saveCurrentAnswer();
        showQuestion(currentIndex + 1);
    }

    @FXML
    private void handlePrevious() {
        saveCurrentAnswer();
        showQuestion(currentIndex - 1);
    }

    @FXML
    private void handleSubmit() {
        saveCurrentAnswer();
        long unanswered = questions.stream().filter(q -> !answers.containsKey(q.getId())).count();
        if (unanswered > 0) {
            Alert alert = new Alert(Alert.AlertType.CONFIRMATION,
                    "You have " + unanswered + " unanswered question(s). Submit anyway?",
                    ButtonType.YES, ButtonType.NO);
            alert.setTitle("Confirm Submit");
            alert.setHeaderText(null);
            alert.showAndWait().ifPresent(response -> {
                if (response == ButtonType.YES) {
                    if (timer != null) timer.stop();
                    deactivateAntiCheat();
                    submitQuizInternal(null);
                }
            });
        } else {
            if (timer != null) timer.stop();
            deactivateAntiCheat();
            submitQuizInternal(null);
        }
    }

    private void submitQuizInternal(String cheatReason) {
        int correct = 0;
        for (Question q : questions) {
            String answer = answers.get(q.getId());
            if (answer != null && answer.equals(q.getCorrectAnswer())) {
                correct++;
            }
        }

        int score = questions.isEmpty() ? 0 : (int) Math.round((double) correct / questions.size() * 100);
        boolean passed = score >= quiz.getPassingScore();

        try {
            QuizResult result = new QuizResult();
            result.setQuizId(quiz.getId());
            result.setCandidateId(SessionManager.getUser().getId());
            result.setScore(score);
            result.setPassed(passed);
            resultService.add(result);  // add() now stores the generated id back into result
            savedResult = result;
        } catch (SQLException e) {
            detailLabel.setText("Error saving result: " + e.getMessage());
        }

        questionCard.setVisible(false);
        questionCard.setManaged(false);
        resultCard.setVisible(true);
        resultCard.setManaged(true);

        scoreLabel.setText(score + "%");
        scoreLabel.setStyle("-fx-font-size: 48px; -fx-font-weight: bold; -fx-text-fill: " + (passed ? "#4CAF50" : "#E91E63") + ";");
        passFailLabel.setText(passed ? "PASSED" : "FAILED");
        passFailLabel.setStyle("-fx-font-size: 20px; -fx-font-weight: bold; -fx-text-fill: " + (passed ? "#4CAF50" : "#E91E63") + ";");
        detailLabel.setText("You got " + correct + " out of " + questions.size() + " questions correct. Passing score: " + quiz.getPassingScore() + "%");
        if (cheatReason != null) {
            detailLabel.setText("⚠ " + cheatReason + "  |  " + correct + "/" + questions.size() + " correct. Pass score: " + quiz.getPassingScore() + "%");
        }

        finalScore   = score;
        finalPassed  = passed;
        finalCorrect = correct;
    }

    @FXML
    private void handleExportPdf() {
        // 1. URL already in memory (same session)
        if (savedResult != null && savedResult.getPdfUrl() != null) {
            openInBrowser(savedResult.getPdfUrl());
            exportStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
            exportStatusLabel.setText("\u2705 Opened in browser!");
            return;
        }
        // 2. URL stored in DB (e.g. user clicked export previously)
        if (savedResult != null) {
            try {
                String stored = resultService.getPdfUrl(quiz.getId(), SessionManager.getUser().getId());
                if (stored != null && !stored.isEmpty()) {
                    savedResult.setPdfUrl(stored);
                    openInBrowser(stored);
                    exportStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
                    exportStatusLabel.setText("\u2705 Opened in browser!");
                    return;
                }
            } catch (Exception ignored) {}
        }

        // 3. Generate, upload, persist, open
        btnExportPdf.setDisable(true);
        btnExportPdf.setText("Uploading...");
        exportStatusLabel.setStyle("-fx-text-fill: #2196F3;");
        exportStatusLabel.setText("Generating and uploading PDF \u2014 please wait...");

        String candidateName = SessionManager.getUser().getFirstName()
                             + " " + SessionManager.getUser().getLastName();

        Task<String> task = new Task<>() {
            @Override
            protected String call() throws Exception {
                byte[] pdfBytes = new QuizPdfExporter().generate(
                        quiz, questions, answers, finalScore, finalPassed, candidateName);
                String safeName = quiz.getTitle().replaceAll("[^a-zA-Z0-9_-]", "_");
                String filePath = "quiz-results/" + SessionManager.getUser().getId()
                                + "/" + safeName + "_" + SessionManager.getUser().getId() + ".pdf";
                return new SupabaseStorageService().uploadPdf(pdfBytes, filePath);
            }
        };

        task.setOnSucceeded(ev -> Platform.runLater(() -> {
            String url = task.getValue();
            if (savedResult != null) {
                savedResult.setPdfUrl(url);
                try { resultService.savePdfUrl(savedResult.getId(), url); } catch (Exception ignored) {}
            }
            btnExportPdf.setDisable(false);
            btnExportPdf.setText("\uD83D\uDCC4 Open PDF Report");
            openInBrowser(url);
            exportStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
            exportStatusLabel.setText("\u2705 PDF uploaded and opened in browser!");
        }));

        task.setOnFailed(ev -> Platform.runLater(() -> {
            btnExportPdf.setDisable(false);
            btnExportPdf.setText("Export PDF Report");
            exportStatusLabel.setStyle("-fx-text-fill: red;");
            Throwable err = task.getException();
            exportStatusLabel.setText("Export failed: " + (err != null ? err.getMessage() : "Unknown error"));
        }));

        Thread thread = new Thread(task);
        thread.setDaemon(true);
        thread.start();
    }

    private void openInBrowser(String url) {
        try {
            if (Desktop.isDesktopSupported()
                    && Desktop.getDesktop().isSupported(Desktop.Action.BROWSE)) {
                Desktop.getDesktop().browse(new URI(url));
            }
        } catch (Exception ignored) {}
    }

    @FXML
    private void handleBackToList() {
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateQuizzList.fxml");
    }
}
