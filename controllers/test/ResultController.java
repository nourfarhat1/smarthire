package com.example.smarthire.controllers.test;

import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.entities.test.QuizResult;
import com.example.smarthire.services.QuizResultService;
import com.example.smarthire.services.TestService;
import com.example.smarthire.utils.Navigation;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.ListCell;
import javafx.scene.control.ListView;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;

import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.List;

public class ResultController {

    @FXML private ListView<QuizResult> resultListView;
    @FXML private ComboBox<Quiz> quizFilter;
    @FXML private Label totalResults;
    @FXML private Label passRate;
    @FXML private Label avgScore;
    @FXML private Label statusLabel;

    private final QuizResultService resultService = new QuizResultService();
    private final TestService testService = new TestService();
    private List<QuizResult> allResults;

    @FXML
    public void initialize() {
        resultListView.setCellFactory(lv -> new ResultListCell());
        loadQuizFilter();
        loadAllResults();
    }

    private void loadQuizFilter() {
        try {
            List<Quiz> quizzes = testService.getAll();
            quizFilter.setItems(FXCollections.observableArrayList(quizzes));
            quizFilter.setCellFactory(lv -> new ListCell<Quiz>() {
                @Override
                protected void updateItem(Quiz item, boolean empty) {
                    super.updateItem(item, empty);
                    setText(empty || item == null ? null : item.getTitle());
                }
            });
            quizFilter.setButtonCell(new ListCell<Quiz>() {
                @Override
                protected void updateItem(Quiz item, boolean empty) {
                    super.updateItem(item, empty);
                    setText(empty || item == null ? "All Quizzes" : item.getTitle());
                }
            });
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error loading quizzes: " + e.getMessage());
        }
    }

    private void loadAllResults() {
        try {
            allResults = resultService.getAll();
            displayResults(allResults);
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error loading results: " + e.getMessage());
        }
    }

    private void displayResults(List<QuizResult> results) {
        resultListView.setItems(FXCollections.observableArrayList(results));
        updateStats(results);
    }

    private void updateStats(List<QuizResult> results) {
        totalResults.setText(String.valueOf(results.size()));
        if (results.isEmpty()) {
            passRate.setText("0%");
            avgScore.setText("0");
            return;
        }
        long passed = results.stream().filter(QuizResult::isPassed).count();
        double rate = (double) passed / results.size() * 100;
        passRate.setText(String.format("%.0f%%", rate));

        double avg = results.stream().mapToInt(QuizResult::getScore).average().orElse(0);
        avgScore.setText(String.format("%.1f", avg));
    }

    @FXML
    private void handleFilterByQuiz() {
        Quiz selected = quizFilter.getValue();
        if (selected == null) {
            displayResults(allResults);
            return;
        }
        try {
            List<QuizResult> filtered = resultService.getByQuizId(selected.getId());
            displayResults(filtered);
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error: " + e.getMessage());
        }
    }

    @FXML
    private void handleBack() {
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/hr/HRQuizzMakeAndList.fxml");
    }

    private class ResultListCell extends ListCell<QuizResult> {
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
                card.setStyle("-fx-background-color: white; -fx-padding: 15; -fx-background-radius: 10; -fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.08), 8, 0, 0, 3);");

                VBox info = new VBox(4);
                Label candidateLbl = new Label(result.getCandidateName());
                candidateLbl.setStyle("-fx-font-size: 15px; -fx-font-weight: bold; -fx-text-fill: #2f4188;");

                Label quizLbl = new Label("Quiz: " + result.getQuizTitle());
                quizLbl.setStyle("-fx-text-fill: #666; -fx-font-size: 13px;");

                String dateStr = result.getAttemptDate() != null ? dateFormat.format(result.getAttemptDate()) : "N/A";
                Label dateLbl = new Label("Date: " + dateStr);
                dateLbl.setStyle("-fx-text-fill: #999; -fx-font-size: 12px;");

                info.getChildren().addAll(candidateLbl, quizLbl, dateLbl);

                Region spacer = new Region();
                HBox.setHgrow(spacer, Priority.ALWAYS);

                VBox scoreBox = new VBox(2);
                scoreBox.setAlignment(Pos.CENTER);
                Label scoreLbl = new Label(result.getScore() + "%");
                scoreLbl.setStyle("-fx-font-size: 20px; -fx-font-weight: bold; -fx-text-fill: " + (result.isPassed() ? "#4CAF50" : "#E91E63") + ";");

                Label passLbl = new Label(result.isPassed() ? "PASSED" : "FAILED");
                passLbl.setStyle("-fx-font-weight: bold; -fx-font-size: 12px; -fx-text-fill: " + (result.isPassed() ? "#4CAF50" : "#E91E63") + ";");

                scoreBox.getChildren().addAll(scoreLbl, passLbl);

                card.getChildren().addAll(info, spacer, scoreBox);
                setGraphic(card);
                setStyle("-fx-background-color: transparent; -fx-padding: 4 0;");
            }
        }
    }
}
