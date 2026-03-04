package com.example.smarthire.controllers.test;

import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.services.QuestionService;
import com.example.smarthire.services.TestService;
import com.example.smarthire.utils.Navigation;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;
import java.sql.SQLException;
import java.util.List;
import java.util.stream.Collectors;

public class AdminQuizController {

    @FXML private TextField searchField;
    @FXML private ListView<Quiz> quizListView;
    @FXML private Label statusLabel;

    private final TestService testService = new TestService();
    private final QuestionService questionService = new QuestionService();
    private List<Quiz> allQuizzes;

    @FXML
    public void initialize() {
        quizListView.setCellFactory(lv -> new AdminQuizCell());
        loadQuizzes();
    }

    private void loadQuizzes() {
        try {
            allQuizzes = testService.getAll();
            quizListView.setItems(FXCollections.observableArrayList(allQuizzes));
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error loading quizzes: " + e.getMessage());
        }
    }

    @FXML
    private void handleSearch() {
        String query = searchField.getText();
        if (query == null || query.trim().isEmpty()) {
            loadQuizzes();
            return;
        }
        String lower = query.trim().toLowerCase();
        List<Quiz> filtered = allQuizzes.stream()
                .filter(q -> q.getTitle().toLowerCase().contains(lower) || q.getDescription().toLowerCase().contains(lower))
                .collect(Collectors.toList());
        quizListView.setItems(FXCollections.observableArrayList(filtered));
    }

    @FXML
    private void handleShowAll() {
        searchField.clear();
        loadQuizzes();
    }

    @FXML
    private void handleDelete() {
        Quiz selected = quizListView.getSelectionModel().getSelectedItem();
        if (selected == null) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Please select a quiz to delete");
            return;
        }
        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION,
                "Delete quiz \"" + selected.getTitle() + "\"? This will also delete all its questions and results.",
                ButtonType.YES, ButtonType.NO);
        confirm.setTitle("Confirm Delete");
        confirm.setHeaderText(null);
        confirm.showAndWait().ifPresent(response -> {
            if (response == ButtonType.YES) {
                try {
                    testService.delete(selected.getId());
                    loadQuizzes();
                    statusLabel.setStyle("-fx-text-fill: #4CAF50;");
                    statusLabel.setText("Quiz deleted successfully!");
                } catch (SQLException e) {
                    statusLabel.setStyle("-fx-text-fill: red;");
                    statusLabel.setText("Error: " + e.getMessage());
                }
            }
        });
    }

    @FXML
    private void handleViewResults() {
        Quiz selected = quizListView.getSelectionModel().getSelectedItem();
        if (selected == null) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Please select a quiz first");
            return;
        }
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/hr/Quizzresults.fxml");
    }

    private class AdminQuizCell extends ListCell<Quiz> {
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
                card.setPrefWidth(700);

                VBox info = new VBox(4);
                Label titleLbl = new Label(quiz.getTitle());
                titleLbl.setStyle("-fx-font-size: 16px; -fx-font-weight: bold; -fx-text-fill: #2f4188;");

                Label descLbl = new Label(quiz.getDescription());
                descLbl.setStyle("-fx-text-fill: #666; -fx-font-size: 13px;");
                descLbl.setWrapText(true);

                HBox meta = new HBox(15);
                Label durationLbl = new Label("Duration: " + quiz.getDurationMinutes() + " min");
                durationLbl.setStyle("-fx-text-fill: #e91e63; -fx-font-weight: bold;");

                Label scoreLbl = new Label("Pass Score: " + quiz.getPassingScore() + "%");
                scoreLbl.setStyle("-fx-text-fill: #4CAF50; -fx-font-weight: bold;");

                int qCount = 0;
                try {
                    qCount = questionService.getByQuizId(quiz.getId()).size();
                } catch (SQLException ignored) {}
                Label questionsLbl = new Label("Questions: " + qCount);
                questionsLbl.setStyle("-fx-text-fill: #2196F3; -fx-font-weight: bold;");

                meta.getChildren().addAll(durationLbl, scoreLbl, questionsLbl);
                info.getChildren().addAll(titleLbl, descLbl, meta);

                Region spacer = new Region();
                HBox.setHgrow(spacer, Priority.ALWAYS);

                Label idLbl = new Label("ID: " + quiz.getId());
                idLbl.setStyle("-fx-text-fill: #999; -fx-font-size: 11px;");

                card.getChildren().addAll(info, spacer, idLbl);
                setGraphic(card);
                setStyle("-fx-background-color: transparent; -fx-padding: 4 0;");
            }
        }
    }
}
