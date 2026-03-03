package com.example.smarthire.controllers.test;

import com.example.smarthire.entities.test.Question;
import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.entities.test.QuizResult;
import com.example.smarthire.services.QuestionService;
import com.example.smarthire.services.QuizResultService;
import com.example.smarthire.services.TestService;
import com.example.smarthire.utils.Navigation;
import com.example.smarthire.utils.SessionManager;
import javafx.animation.KeyFrame;
import javafx.animation.Timeline;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.layout.VBox;
import javafx.util.Duration;
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

    private final TestService testService = new TestService();
    private final QuestionService questionService = new QuestionService();
    private final QuizResultService resultService = new QuizResultService();

    private Quiz quiz;
    private List<Question> questions;
    private int currentIndex = 0;
    private Map<Integer, String> answers = new HashMap<>();
    private Timeline timer;
    private int timeRemaining;

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

            quizTitleLabel.setText(quiz.getTitle());
            timeRemaining = quiz.getDurationMinutes() * 60;
            startTimer();
            showQuestion(0);
        } catch (SQLException e) {
            quizTitleLabel.setText("Error loading quiz: " + e.getMessage());
            questionCard.setVisible(false);
        }
    }

    private void startTimer() {
        updateTimerDisplay();
        timer = new Timeline(new KeyFrame(Duration.seconds(1), e -> {
            timeRemaining--;
            updateTimerDisplay();
            if (timeRemaining <= 0) {
                timer.stop();
                submitQuiz();
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
                    submitQuiz();
                }
            });
        } else {
            if (timer != null) timer.stop();
            submitQuiz();
        }
    }

    private void submitQuiz() {
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
            resultService.add(result);
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
    }

    @FXML
    private void handleBackToList() {
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateQuizzList.fxml");
    }
}
