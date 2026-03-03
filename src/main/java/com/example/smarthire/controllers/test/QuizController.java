package com.example.smarthire.controllers.test;

import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.entities.test.Question;
import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.services.TestService;
import com.example.smarthire.services.JobService;
import com.example.smarthire.services.QuestionService;
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

public class QuizController {

    @FXML private ListView<Quiz> quizListView;
    @FXML private TextField titleField;
    @FXML private TextArea descriptionField;
    @FXML private TextField durationField;
    @FXML private TextField passingScoreField;
    @FXML private ComboBox<JobOffer> jobCombo;
    @FXML private Label titleError;
    @FXML private Label descriptionError;
    @FXML private Label durationError;
    @FXML private Label passingScoreError;
    @FXML private Label statusLabel;
    @FXML private Label createStatusLabel;
    @FXML private Button btnAdd;
    @FXML private Button btnUpdate;
    @FXML private Button btnEdit;
    @FXML private Button btnDelete;
    @FXML private Tab questionsTab;
    @FXML private TextArea questionTextField;
    @FXML private TextField optionAField;
    @FXML private TextField optionBField;
    @FXML private TextField optionCField;
    @FXML private ComboBox<String> correctAnswerCombo;
    @FXML private Label questionTextError;
    @FXML private Label optionAError;
    @FXML private Label optionBError;
    @FXML private Label optionCError;
    @FXML private Label correctAnswerError;
    @FXML private Label questionStatusLabel;
    @FXML private Label questionsForLabel;
    @FXML private ListView<Question> questionListView;
    @FXML private Button btnAddQ;
    @FXML private Button btnUpdateQ;
    @FXML private Button btnDeleteQ;

    private final TestService testService = new TestService();
    private final QuestionService questionService = new QuestionService();
    private final JobService jobService = new JobService();
    private Quiz selectedQuiz;
    private Quiz currentQuizForQuestions;
    private Question selectedQuestion;

    @FXML
    public void initialize() {
        quizListView.setCellFactory(lv -> new QuizListCell());
        questionListView.setCellFactory(lv -> new QuestionListCell());
        correctAnswerCombo.setItems(FXCollections.observableArrayList("A", "B", "C"));
        btnUpdate.setDisable(true);
        btnUpdateQ.setDisable(true);
        btnDeleteQ.setDisable(true);

        setupErrorBindings();
        setupClearOnType();

        loadQuizzes();
        loadJobs();

        questionListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                fillQuestionForm(newVal);
            }
        });

        quizListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                currentQuizForQuestions = newVal;
                questionsForLabel.setText("Questions for: " + newVal.getTitle());
                loadQuestions(newVal.getId());
            }
        });
    }

    private void setupErrorBindings() {
        Label[] errors = {titleError, descriptionError, durationError, passingScoreError,
                questionTextError, optionAError, optionBError, optionCError, correctAnswerError};
        for (Label lbl : errors) {
            lbl.managedProperty().bind(lbl.textProperty().isNotEmpty());
            lbl.visibleProperty().bind(lbl.textProperty().isNotEmpty());
        }
    }

    private void setupClearOnType() {
        titleField.textProperty().addListener((o, ov, nv) -> titleError.setText(""));
        descriptionField.textProperty().addListener((o, ov, nv) -> descriptionError.setText(""));
        durationField.textProperty().addListener((o, ov, nv) -> durationError.setText(""));
        passingScoreField.textProperty().addListener((o, ov, nv) -> passingScoreError.setText(""));
        questionTextField.textProperty().addListener((o, ov, nv) -> questionTextError.setText(""));
        optionAField.textProperty().addListener((o, ov, nv) -> optionAError.setText(""));
        optionBField.textProperty().addListener((o, ov, nv) -> optionBError.setText(""));
        optionCField.textProperty().addListener((o, ov, nv) -> optionCError.setText(""));
        correctAnswerCombo.valueProperty().addListener((o, ov, nv) -> correctAnswerError.setText(""));
    }

    private void loadQuizzes() {
        try {
            quizListView.setItems(FXCollections.observableArrayList(testService.getAll()));
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error loading quizzes: " + e.getMessage());
        }
    }

    private void loadJobs() {
        try {
            jobCombo.setItems(FXCollections.observableArrayList(jobService.getAll()));
        } catch (SQLException e) {
            createStatusLabel.setStyle("-fx-text-fill: red;");
            createStatusLabel.setText("Error loading jobs: " + e.getMessage());
        }
    }

    private void loadQuestions(int quizId) {
        try {
            questionListView.setItems(FXCollections.observableArrayList(questionService.getByQuizId(quizId)));
        } catch (SQLException e) {
            questionStatusLabel.setStyle("-fx-text-fill: red;");
            questionStatusLabel.setText("Error loading questions: " + e.getMessage());
        }
    }

    private boolean validateQuiz() {
        boolean valid = true;
        clearQuizErrors();

        if (titleField.getText() == null || titleField.getText().trim().isEmpty()) {
            titleError.setText("Title is required");
            valid = false;
        } else if (titleField.getText().trim().length() < 3) {
            titleError.setText("Title must be at least 3 characters");
            valid = false;
        } else if (titleField.getText().trim().length() > 100) {
            titleError.setText("Title must not exceed 100 characters");
            valid = false;
        }

        if (descriptionField.getText() == null || descriptionField.getText().trim().isEmpty()) {
            descriptionError.setText("Description is required");
            valid = false;
        } else if (descriptionField.getText().trim().length() < 10) {
            descriptionError.setText("Description must be at least 10 characters");
            valid = false;
        }

        if (durationField.getText() == null || durationField.getText().trim().isEmpty()) {
            durationError.setText("Duration is required");
            valid = false;
        } else {
            try {
                int d = Integer.parseInt(durationField.getText().trim());
                if (d <= 0) { durationError.setText("Duration must be greater than 0"); valid = false; }
                else if (d > 300) { durationError.setText("Duration must not exceed 300 minutes"); valid = false; }
            } catch (NumberFormatException e) {
                durationError.setText("Duration must be a valid number");
                valid = false;
            }
        }

        if (passingScoreField.getText() == null || passingScoreField.getText().trim().isEmpty()) {
            passingScoreError.setText("Passing score is required");
            valid = false;
        } else {
            try {
                int s = Integer.parseInt(passingScoreField.getText().trim());
                if (s < 0) { passingScoreError.setText("Score must be 0 or greater"); valid = false; }
                else if (s > 100) { passingScoreError.setText("Score must not exceed 100"); valid = false; }
            } catch (NumberFormatException e) {
                passingScoreError.setText("Score must be a valid number");
                valid = false;
            }
        }

        return valid;
    }

    private void clearQuizErrors() {
        titleError.setText("");
        descriptionError.setText("");
        durationError.setText("");
        passingScoreError.setText("");
        createStatusLabel.setText("");
    }

    @FXML
    private void handleAdd() {
        if (!validateQuiz()) return;
        try {
            Quiz quiz = new Quiz();
            quiz.setTitle(titleField.getText().trim());
            quiz.setDescription(descriptionField.getText().trim());
            quiz.setDurationMinutes(Integer.parseInt(durationField.getText().trim()));
            quiz.setPassingScore(Integer.parseInt(passingScoreField.getText().trim()));
            if (jobCombo.getValue() != null) {
                quiz.setRelatedJobId(jobCombo.getValue().getId());
            }
            testService.add(quiz);
            loadQuizzes();
            handleClear();
            createStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
            createStatusLabel.setText("Quiz added successfully!");
        } catch (SQLException e) {
            createStatusLabel.setStyle("-fx-text-fill: red;");
            createStatusLabel.setText("Error: " + e.getMessage());
        }
    }

    @FXML
    private void handleUpdate() {
        if (selectedQuiz == null) return;
        if (!validateQuiz()) return;
        try {
            selectedQuiz.setTitle(titleField.getText().trim());
            selectedQuiz.setDescription(descriptionField.getText().trim());
            selectedQuiz.setDurationMinutes(Integer.parseInt(durationField.getText().trim()));
            selectedQuiz.setPassingScore(Integer.parseInt(passingScoreField.getText().trim()));
            if (jobCombo.getValue() != null) {
                selectedQuiz.setRelatedJobId(jobCombo.getValue().getId());
            } else {
                selectedQuiz.setRelatedJobId(0);
            }
            testService.update(selectedQuiz);
            loadQuizzes();
            handleClear();
            createStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
            createStatusLabel.setText("Quiz updated successfully!");
        } catch (SQLException e) {
            createStatusLabel.setStyle("-fx-text-fill: red;");
            createStatusLabel.setText("Error: " + e.getMessage());
        }
    }

    @FXML
    private void handleEditQuiz() {
        Quiz selected = quizListView.getSelectionModel().getSelectedItem();
        if (selected == null) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Please select a quiz to edit");
            return;
        }
        selectedQuiz = selected;
        titleField.setText(selected.getTitle());
        descriptionField.setText(selected.getDescription());
        durationField.setText(String.valueOf(selected.getDurationMinutes()));
        passingScoreField.setText(String.valueOf(selected.getPassingScore()));
        jobCombo.getSelectionModel().clearSelection();
        if (selected.getRelatedJobId() != 0) {
            for (JobOffer job : jobCombo.getItems()) {
                if (job.getId() == selected.getRelatedJobId()) {
                    jobCombo.setValue(job);
                    break;
                }
            }
        }
        btnAdd.setDisable(true);
        btnUpdate.setDisable(false);
        questionsTab.getTabPane().getSelectionModel().select(1);
        statusLabel.setText("");
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
                "Delete quiz: " + selected.getTitle() + "? This will also delete all its questions and results.",
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
    private void handleClear() {
        selectedQuiz = null;
        titleField.clear();
        descriptionField.clear();
        durationField.clear();
        passingScoreField.clear();
        jobCombo.getSelectionModel().clearSelection();
        jobCombo.setValue(null);
        btnAdd.setDisable(false);
        btnUpdate.setDisable(true);
        clearQuizErrors();
    }

    @FXML
    private void handleRefresh() {
        loadQuizzes();
        statusLabel.setText("");
    }

    @FXML
    private void handleViewQuestions() {
        Quiz selected = quizListView.getSelectionModel().getSelectedItem();
        if (selected == null) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Please select a quiz first");
            return;
        }
        currentQuizForQuestions = selected;
        questionsForLabel.setText("Questions for: " + selected.getTitle());
        loadQuestions(selected.getId());
        questionsTab.getTabPane().getSelectionModel().select(questionsTab);
        statusLabel.setText("");
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

    private boolean validateQuestion() {
        boolean valid = true;
        clearQuestionErrors();

        if (currentQuizForQuestions == null) {
            questionStatusLabel.setStyle("-fx-text-fill: red;");
            questionStatusLabel.setText("Please select a quiz from the My Quizzes tab first");
            return false;
        }

        if (questionTextField.getText() == null || questionTextField.getText().trim().isEmpty()) {
            questionTextError.setText("Question text is required");
            valid = false;
        } else if (questionTextField.getText().trim().length() < 5) {
            questionTextError.setText("Question must be at least 5 characters");
            valid = false;
        }

        if (optionAField.getText() == null || optionAField.getText().trim().isEmpty()) {
            optionAError.setText("Option A is required");
            valid = false;
        }

        if (optionBField.getText() == null || optionBField.getText().trim().isEmpty()) {
            optionBError.setText("Option B is required");
            valid = false;
        }

        if (optionCField.getText() == null || optionCField.getText().trim().isEmpty()) {
            optionCError.setText("Option C is required");
            valid = false;
        }

        if (correctAnswerCombo.getValue() == null) {
            correctAnswerError.setText("Please select the correct answer");
            valid = false;
        }

        String a = optionAField.getText() != null ? optionAField.getText().trim() : "";
        String b = optionBField.getText() != null ? optionBField.getText().trim() : "";
        String c = optionCField.getText() != null ? optionCField.getText().trim() : "";
        if (!a.isEmpty() && !b.isEmpty() && a.equalsIgnoreCase(b)) {
            optionBError.setText("Option B must differ from Option A");
            valid = false;
        }
        if (!a.isEmpty() && !c.isEmpty() && a.equalsIgnoreCase(c)) {
            optionCError.setText("Option C must differ from Option A");
            valid = false;
        }
        if (!b.isEmpty() && !c.isEmpty() && b.equalsIgnoreCase(c)) {
            optionCError.setText("Option C must differ from Option B");
            valid = false;
        }

        return valid;
    }

    private void clearQuestionErrors() {
        questionTextError.setText("");
        optionAError.setText("");
        optionBError.setText("");
        optionCError.setText("");
        correctAnswerError.setText("");
        questionStatusLabel.setText("");
    }

    private void fillQuestionForm(Question q) {
        selectedQuestion = q;
        questionTextField.setText(q.getQuestionText());
        optionAField.setText(q.getOptionA());
        optionBField.setText(q.getOptionB());
        optionCField.setText(q.getOptionC());
        correctAnswerCombo.setValue(q.getCorrectAnswer());
        btnAddQ.setDisable(true);
        btnUpdateQ.setDisable(false);
        btnDeleteQ.setDisable(false);
        clearQuestionErrors();
    }

    @FXML
    private void handleAddQuestion() {
        if (!validateQuestion()) return;
        try {
            Question q = new Question();
            q.setQuizId(currentQuizForQuestions.getId());
            q.setQuestionText(questionTextField.getText().trim());
            q.setOptionA(optionAField.getText().trim());
            q.setOptionB(optionBField.getText().trim());
            q.setOptionC(optionCField.getText().trim());
            q.setCorrectAnswer(correctAnswerCombo.getValue());
            questionService.add(q);
            loadQuestions(currentQuizForQuestions.getId());
            handleClearQuestion();
            questionStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
            questionStatusLabel.setText("Question added successfully!");
        } catch (SQLException e) {
            questionStatusLabel.setStyle("-fx-text-fill: red;");
            questionStatusLabel.setText("Error: " + e.getMessage());
        }
    }

    @FXML
    private void handleUpdateQuestion() {
        if (selectedQuestion == null) return;
        if (!validateQuestion()) return;
        try {
            selectedQuestion.setQuizId(currentQuizForQuestions.getId());
            selectedQuestion.setQuestionText(questionTextField.getText().trim());
            selectedQuestion.setOptionA(optionAField.getText().trim());
            selectedQuestion.setOptionB(optionBField.getText().trim());
            selectedQuestion.setOptionC(optionCField.getText().trim());
            selectedQuestion.setCorrectAnswer(correctAnswerCombo.getValue());
            questionService.update(selectedQuestion);
            loadQuestions(currentQuizForQuestions.getId());
            handleClearQuestion();
            questionStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
            questionStatusLabel.setText("Question updated successfully!");
        } catch (SQLException e) {
            questionStatusLabel.setStyle("-fx-text-fill: red;");
            questionStatusLabel.setText("Error: " + e.getMessage());
        }
    }

    @FXML
    private void handleDeleteQuestion() {
        if (selectedQuestion == null) return;
        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION, "Delete this question?", ButtonType.YES, ButtonType.NO);
        confirm.setTitle("Confirm Delete");
        confirm.setHeaderText(null);
        confirm.showAndWait().ifPresent(response -> {
            if (response == ButtonType.YES) {
                try {
                    questionService.delete(selectedQuestion.getId());
                    loadQuestions(currentQuizForQuestions.getId());
                    handleClearQuestion();
                    questionStatusLabel.setStyle("-fx-text-fill: #4CAF50;");
                    questionStatusLabel.setText("Question deleted successfully!");
                } catch (SQLException e) {
                    questionStatusLabel.setStyle("-fx-text-fill: red;");
                    questionStatusLabel.setText("Error: " + e.getMessage());
                }
            }
        });
    }

    @FXML
    private void handleClearQuestion() {
        selectedQuestion = null;
        questionTextField.clear();
        optionAField.clear();
        optionBField.clear();
        optionCField.clear();
        correctAnswerCombo.getSelectionModel().clearSelection();
        correctAnswerCombo.setValue(null);
        questionListView.getSelectionModel().clearSelection();
        btnAddQ.setDisable(false);
        btnUpdateQ.setDisable(true);
        btnDeleteQ.setDisable(true);
        clearQuestionErrors();
    }

    private class QuizListCell extends ListCell<Quiz> {
        private VBox card;

        @Override
        protected void updateItem(Quiz quiz, boolean empty) {
            super.updateItem(quiz, empty);
            if (empty || quiz == null) {
                setGraphic(null);
                setText(null);
                card = null;
            } else {
                card = new VBox(6);

                Label titleLbl = new Label(quiz.getTitle());
                titleLbl.setStyle("-fx-font-size: 16px; -fx-font-weight: bold; -fx-text-fill: #2f4188;");

                Label descLbl = new Label(quiz.getDescription());
                descLbl.setStyle("-fx-text-fill: #666; -fx-font-size: 13px;");
                descLbl.setWrapText(true);

                HBox meta = new HBox(15);
                meta.setAlignment(Pos.CENTER_LEFT);

                Label durationLbl = new Label("Duration: " + quiz.getDurationMinutes() + " min");
                durationLbl.setStyle("-fx-text-fill: #e91e63; -fx-font-weight: bold;");

                Label scoreLbl = new Label("Pass: " + quiz.getPassingScore() + "%");
                scoreLbl.setStyle("-fx-text-fill: #4CAF50; -fx-font-weight: bold;");

                int qCount = 0;
                try { qCount = questionService.getByQuizId(quiz.getId()).size(); } catch (SQLException ignored) {}
                Label questionsLbl = new Label("Questions: " + qCount);
                questionsLbl.setStyle("-fx-text-fill: #2196F3; -fx-font-weight: bold;");

                Region spacer = new Region();
                HBox.setHgrow(spacer, Priority.ALWAYS);

                Label idLbl = new Label("#" + quiz.getId());
                idLbl.setStyle("-fx-text-fill: #999; -fx-font-size: 11px;");

                meta.getChildren().addAll(durationLbl, scoreLbl, questionsLbl, spacer, idLbl);
                card.getChildren().addAll(titleLbl, descLbl, meta);

                applyCardStyle(isSelected());
                setGraphic(card);
                setStyle("-fx-background-color: transparent; -fx-padding: 4 0;");
            }
        }

        @Override
        public void updateSelected(boolean selected) {
            super.updateSelected(selected);
            if (card != null) {
                applyCardStyle(selected);
            }
        }

        private void applyCardStyle(boolean selected) {
            if (selected) {
                card.setStyle("-fx-background-color: #e8f0fe; -fx-padding: 15; -fx-background-radius: 10; " +
                        "-fx-border-color: #2196F3; -fx-border-width: 2; -fx-border-radius: 10; " +
                        "-fx-effect: dropshadow(three-pass-box, rgba(33,150,243,0.25), 10, 0, 0, 3);");
            } else {
                card.setStyle("-fx-background-color: white; -fx-padding: 15; -fx-background-radius: 10; " +
                        "-fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.08), 8, 0, 0, 3);");
            }
        }
    }

    private class QuestionListCell extends ListCell<Question> {
        private VBox card;

        @Override
        protected void updateItem(Question q, boolean empty) {
            super.updateItem(q, empty);
            if (empty || q == null) {
                setGraphic(null);
                setText(null);
                card = null;
            } else {
                card = new VBox(4);

                Label qText = new Label(q.getQuestionText());
                qText.setStyle("-fx-font-weight: bold; -fx-text-fill: #333; -fx-font-size: 14px;");
                qText.setWrapText(true);

                HBox options = new HBox(10);
                Label optA = new Label("A: " + q.getOptionA());
                optA.setStyle("-fx-text-fill: #555;");
                Label optB = new Label("B: " + q.getOptionB());
                optB.setStyle("-fx-text-fill: #555;");
                Label optC = new Label("C: " + q.getOptionC());
                optC.setStyle("-fx-text-fill: #555;");
                options.getChildren().addAll(optA, optB, optC);

                Label correct = new Label("Correct: " + q.getCorrectAnswer());
                correct.setStyle("-fx-text-fill: #4CAF50; -fx-font-weight: bold;");

                card.getChildren().addAll(qText, options, correct);

                applyCardStyle(isSelected());
                setGraphic(card);
                setStyle("-fx-background-color: transparent; -fx-padding: 3 0;");
            }
        }

        @Override
        public void updateSelected(boolean selected) {
            super.updateSelected(selected);
            if (card != null) {
                applyCardStyle(selected);
            }
        }

        private void applyCardStyle(boolean selected) {
            if (selected) {
                card.setStyle("-fx-background-color: #e8f0fe; -fx-padding: 12; -fx-background-radius: 8; " +
                        "-fx-border-color: #2196F3; -fx-border-width: 2; -fx-border-radius: 8; " +
                        "-fx-effect: dropshadow(three-pass-box, rgba(33,150,243,0.2), 6, 0, 0, 2);");
            } else {
                card.setStyle("-fx-background-color: white; -fx-padding: 12; -fx-background-radius: 8; " +
                        "-fx-effect: dropshadow(three-pass-box, rgba(0,0,0,0.06), 5, 0, 0, 2);");
            }
        }
    }
}
