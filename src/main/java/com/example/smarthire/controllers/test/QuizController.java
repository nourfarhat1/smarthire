package com.example.smarthire.controllers.test;

import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.entities.test.Question;
import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.services.GroqAIService;
import com.example.smarthire.services.JobService;
import com.example.smarthire.services.QuestionService;
import com.example.smarthire.services.TestService;
import com.example.smarthire.utils.Navigation;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.collections.transformation.FilteredList;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.*;
import javafx.stage.Modality;
import javafx.stage.Stage;

import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

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
    @FXML private TextField quizSearchField;

    private final TestService testService = new TestService();
    private final QuestionService questionService = new QuestionService();
    private final JobService jobService = new JobService();
    private Quiz selectedQuiz;
    private Quiz currentQuizForQuestions;
    private Question selectedQuestion;
    private ObservableList<Quiz> masterQuizData = FXCollections.observableArrayList();
    private FilteredList<Quiz> filteredQuizData;

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

        filteredQuizData = new FilteredList<>(masterQuizData, p -> true);
        quizListView.setItems(filteredQuizData);

        if (quizSearchField != null) {
            quizSearchField.textProperty().addListener((obs, oldVal, newVal) -> {
                filteredQuizData.setPredicate(quiz -> {
                    if (newVal == null || newVal.isEmpty()) return true;
                    String lower = newVal.toLowerCase();
                    return (quiz.getTitle() != null && quiz.getTitle().toLowerCase().contains(lower))
                            || (quiz.getDescription() != null && quiz.getDescription().toLowerCase().contains(lower));
                });
            });
        }

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
            masterQuizData.setAll(testService.getAll());
        } catch (SQLException e) {
            statusLabel.setStyle("-fx-text-fill: red;");
            statusLabel.setText("Error loading quizzes: " + e.getMessage());
        }
    }

    @FXML
    private void handleClearSearch() {
        if (quizSearchField != null) quizSearchField.clear();
    }

    @FXML
    private void handleGenerateAI() {
        Stage dialog = new Stage();
        dialog.initModality(Modality.APPLICATION_MODAL);
        dialog.setTitle("Generate Quiz with AI");
        dialog.setResizable(false);

        VBox root = new VBox(14);
        root.setPadding(new Insets(24));
        root.setStyle("-fx-background-color: #f9f9f9;");
        root.setPrefWidth(460);

        Label heading = new Label("\uD83E\uDD16  AI Quiz Generator");
        heading.setStyle("-fx-font-size: 18px; -fx-font-weight: bold; -fx-text-fill: #2f4188;");

        Label titleLbl = new Label("Quiz Title / Topic:");
        titleLbl.setStyle("-fx-font-weight: bold;");
        TextField titleInput = new TextField();
        titleInput.setPromptText("e.g. Java, SQL, Python, Machine Learning...");
        titleInput.setStyle("-fx-padding: 8; -fx-border-color: #ccc; -fx-border-radius: 6;");

        Label descLbl = new Label("Description / Difficulty Hint:");
        descLbl.setStyle("-fx-font-weight: bold;");
        TextArea descInput = new TextArea();
        descInput.setPromptText("e.g. Basic Java OOP, intermediate SQL queries...");
        descInput.setPrefRowCount(3);
        descInput.setWrapText(true);
        descInput.setStyle("-fx-padding: 8; -fx-border-color: #ccc; -fx-border-radius: 6;");

        Label infoLbl = new Label("The AI will generate questions, duration and passing score automatically.");
        infoLbl.setStyle("-fx-text-fill: #888; -fx-font-size: 12px;");
        infoLbl.setWrapText(true);

        ProgressIndicator spinner = new ProgressIndicator();
        spinner.setVisible(false);
        spinner.setPrefSize(32, 32);

        Label genStatusLbl = new Label();
        genStatusLbl.setWrapText(true);
        genStatusLbl.setStyle("-fx-font-size: 12px;");

        Button generateBtn = new Button("\u2728  Generate Quiz");
        generateBtn.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white; -fx-font-weight: bold; " +
                             "-fx-padding: 10 24; -fx-background-radius: 20; -fx-cursor: hand; -fx-font-size: 14px;");

        Button cancelBtn = new Button("Cancel");
        cancelBtn.setStyle("-fx-background-color: transparent; -fx-text-fill: #666; -fx-border-color: #ccc; " +
                           "-fx-border-radius: 20; -fx-padding: 8 20; -fx-cursor: hand;");
        cancelBtn.setOnAction(e -> dialog.close());

        HBox btnRow = new HBox(10, generateBtn, cancelBtn, spinner);
        btnRow.setAlignment(Pos.CENTER_LEFT);

        root.getChildren().addAll(heading, titleLbl, titleInput, descLbl, descInput, infoLbl, btnRow, genStatusLbl);

        generateBtn.setOnAction(e -> {
            String title = titleInput.getText().trim();
            String desc = descInput.getText().trim();
            if (title.isEmpty()) {
                genStatusLbl.setStyle("-fx-text-fill: red;");
                genStatusLbl.setText("Please enter a quiz title.");
                return;
            }
            if (desc.isEmpty()) {
                genStatusLbl.setStyle("-fx-text-fill: red;");
                genStatusLbl.setText("Please enter a description or difficulty hint.");
                return;
            }

            generateBtn.setDisable(true);
            cancelBtn.setDisable(true);
            spinner.setVisible(true);
            genStatusLbl.setStyle("-fx-text-fill: #2196F3;");
            genStatusLbl.setText("Generating quiz with AI... please wait.");

            Task<GroqAIService.GeneratedQuiz> task = new Task<>() {
                @Override
                protected GroqAIService.GeneratedQuiz call() throws Exception {
                    System.out.println("[AI] Starting generation for: " + title);
                    GroqAIService.GeneratedQuiz result = new GroqAIService().generateQuiz(title, desc);
                    System.out.println("[AI] Done. quiz=" + (result.quiz != null ? result.quiz.getTitle() : "NULL") + "  questions=" + (result.questions != null ? result.questions.size() : "NULL"));
                    return result;
                }
            };

            task.setOnSucceeded(ev -> {
                GroqAIService.GeneratedQuiz generated = task.getValue();
                System.out.println("[UI] succeeded generated=" + (generated != null ? "OK" : "NULL"));
                Platform.runLater(() -> {
                    System.out.println("[UI] closing gen dialog");
                    dialog.close();
                    // Double runLater: wait for dialog's nested event loop to fully unwind
                    // before starting review's showAndWait. Without this, macOS renders blank.
                    Platform.runLater(() -> {
                        System.out.println("[UI] opening review dialog");
                        showReviewDialog(generated);
                        System.out.println("[UI] showReviewDialog returned");
                    });
                });
            });

            task.setOnFailed(ev -> Platform.runLater(() -> {
                spinner.setVisible(false);
                generateBtn.setDisable(false);
                cancelBtn.setDisable(false);
                genStatusLbl.setStyle("-fx-text-fill: red;");
                Throwable err = task.getException();
                genStatusLbl.setText("AI error: " + (err != null ? err.getMessage() : "Unknown error"));
            }));

            Thread thread = new Thread(task);
            thread.setDaemon(true);
            thread.start();
        });

        dialog.setScene(new Scene(root));
        dialog.showAndWait();
    }

    private void showReviewDialog(GroqAIService.GeneratedQuiz generated) {
        System.out.println("[REVIEW] called - quiz=" + (generated != null && generated.quiz != null ? generated.quiz.getTitle() : "NULL") + "  questions=" + (generated != null && generated.questions != null ? generated.questions.size() : "NULL"));
        if (generated == null || generated.quiz == null || generated.questions == null) {
            System.err.println("[REVIEW] aborted - null data");
            return;
        }

        Stage review = new Stage();
        review.initModality(Modality.APPLICATION_MODAL);
        review.setTitle("Review AI-Generated Quiz");
        review.setResizable(true);

        // ── HEADER ───────────────────────────────────────────────────────────
        Label titleBadge = new Label("\uD83E\uDD16  AI Generated Quiz");
        titleBadge.setStyle("-fx-font-size:18px;-fx-font-weight:bold;-fx-text-fill:#1a237e;");

        Label titleLbl = new Label("Quiz Title");
        titleLbl.setStyle("-fx-font-size:11px;-fx-text-fill:#888;");
        TextField titleInput = new TextField(generated.quiz.getTitle());
        titleInput.setStyle("-fx-font-size:14px;-fx-font-weight:bold;-fx-background-color:white;" +
            "-fx-border-color:#c5cae9;-fx-border-radius:6;-fx-background-radius:6;-fx-padding:8 12;");
        titleInput.setMaxWidth(Double.MAX_VALUE);
        HBox.setHgrow(titleInput, Priority.ALWAYS);

        Label durLbl = new Label("Duration (min)");
        durLbl.setStyle("-fx-font-size:11px;-fx-text-fill:#888;");
        TextField durInput = new TextField(String.valueOf(generated.quiz.getDurationMinutes()));
        durInput.setPrefWidth(90);
        durInput.setStyle("-fx-font-size:13px;-fx-background-color:white;" +
            "-fx-border-color:#c5cae9;-fx-border-radius:6;-fx-background-radius:6;-fx-padding:8 12;");
        VBox durBox = new VBox(3, durLbl, durInput);

        Label passLbl = new Label("Passing Score (%)");
        passLbl.setStyle("-fx-font-size:11px;-fx-text-fill:#888;");
        TextField passInput = new TextField(String.valueOf(generated.quiz.getPassingScore()));
        passInput.setPrefWidth(90);
        passInput.setStyle("-fx-font-size:13px;-fx-background-color:white;" +
            "-fx-border-color:#c5cae9;-fx-border-radius:6;-fx-background-radius:6;-fx-padding:8 12;");
        VBox passBox = new VBox(3, passLbl, passInput);

        VBox titleBox = new VBox(3, titleLbl, titleInput);
        HBox.setHgrow(titleBox, Priority.ALWAYS);
        HBox metaRow = new HBox(12, titleBox, durBox, passBox);
        metaRow.setAlignment(Pos.BOTTOM_LEFT);

        Label qCountLbl = new Label(generated.questions.size() + " questions generated  ·  Review and edit before saving");
        qCountLbl.setStyle("-fx-font-size:12px;-fx-text-fill:#5c6bc0;");

        VBox header = new VBox(10, titleBadge, metaRow, qCountLbl);
        header.setPadding(new Insets(20, 20, 14, 20));
        header.setStyle("-fx-background-color:linear-gradient(to bottom, #e8eaf6, #f3f4fb);" +
            "-fx-border-color:#c5cae9;-fx-border-width:0 0 1 0;");

        // ── QUESTION CARDS in ListView ───────────────────────────────────────
        List<TextArea> qTextAreas = new ArrayList<>();
        List<TextField[]> qOptions = new ArrayList<>();
        List<ComboBox<String>> qAnswers = new ArrayList<>();

        String[] badgeColors = {"#3949ab","#1565c0","#00695c","#558b2f","#6a1b9a","#ad1457","#e65100","#4527a0"};

        ObservableList<VBox> cards = FXCollections.observableArrayList();
        for (int i = 0; i < generated.questions.size(); i++) {
            Question q = generated.questions.get(i);
            String color = badgeColors[i % badgeColors.length];

            // Badge
            Label badge = new Label("Q" + (i + 1));
            badge.setStyle("-fx-background-color:" + color + ";-fx-text-fill:white;" +
                "-fx-font-weight:bold;-fx-font-size:12px;-fx-padding:3 10;" +
                "-fx-background-radius:20;");

            // Question text
            TextArea qText = new TextArea(q.getQuestionText());
            qText.setPrefRowCount(2);
            qText.setPrefHeight(62);
            qText.setWrapText(true);
            qText.setMaxWidth(Double.MAX_VALUE);
            qText.setStyle("-fx-font-size:13px;-fx-border-color:#e0e0e0;" +
                "-fx-border-radius:6;-fx-background-radius:6;");

            // Option fields
            TextField optA = new TextField(q.getOptionA());
            TextField optB = new TextField(q.getOptionB());
            TextField optC = new TextField(q.getOptionC());
            String optStyle = "-fx-font-size:13px;-fx-border-color:#e0e0e0;" +
                "-fx-border-radius:6;-fx-background-radius:6;-fx-padding:6 10;";
            optA.setStyle(optStyle); optB.setStyle(optStyle); optC.setStyle(optStyle);
            HBox.setHgrow(optA, Priority.ALWAYS);
            HBox.setHgrow(optB, Priority.ALWAYS);
            HBox.setHgrow(optC, Priority.ALWAYS);

            String lblStyle = "-fx-font-size:11px;-fx-font-weight:bold;-fx-text-fill:white;" +
                "-fx-padding:4 8;-fx-background-radius:4;";
            Label aLbl = new Label("A"); aLbl.setStyle(lblStyle + "-fx-background-color:#43a047;");
            Label bLbl = new Label("B"); bLbl.setStyle(lblStyle + "-fx-background-color:#1e88e5;");
            Label cLbl = new Label("C"); cLbl.setStyle(lblStyle + "-fx-background-color:#8e24aa;");

            HBox optRow = new HBox(6, aLbl, optA, bLbl, optB, cLbl, optC);
            optRow.setAlignment(Pos.CENTER_LEFT);

            // Correct answer
            ComboBox<String> ansCombo = new ComboBox<>(FXCollections.observableArrayList("A", "B", "C"));
            ansCombo.setValue(q.getCorrectAnswer());
            ansCombo.setStyle("-fx-font-weight:bold;-fx-font-size:13px;");
            ansCombo.setPrefWidth(80);
            Label ansLbl = new Label("\u2713  Correct Answer:");
            ansLbl.setStyle("-fx-font-size:12px;-fx-font-weight:bold;-fx-text-fill:#2e7d32;");
            HBox ansRow = new HBox(8, ansLbl, ansCombo);
            ansRow.setAlignment(Pos.CENTER_LEFT);
            ansRow.setPadding(new Insets(4, 0, 0, 0));

            VBox card = new VBox(8, badge, qText, optRow, ansRow);
            card.setPadding(new Insets(14, 14, 14, 14));
            card.setStyle("-fx-background-color:white;-fx-background-radius:10;" +
                "-fx-border-color:#e8eaf6;-fx-border-radius:10;-fx-border-width:1;" +
                "-fx-effect:dropshadow(gaussian,rgba(0,0,0,0.06),8,0,0,2);");

            cards.add(card);
            qTextAreas.add(qText);
            qOptions.add(new TextField[]{optA, optB, optC});
            qAnswers.add(ansCombo);
        }

        ListView<VBox> listView = new ListView<>(cards);
        listView.setFixedCellSize(218);
        listView.setPrefHeight(218 * Math.min(generated.questions.size(), 3) + 20);
        listView.setStyle("-fx-background-color:#f5f6fa;-fx-border-color:transparent;");
        listView.setCellFactory(lv -> new ListCell<>() {
            @Override
            protected void updateItem(VBox item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setGraphic(null); setText(null);
                    setStyle("-fx-background-color:transparent;");
                } else {
                    setGraphic(item); setText(null);
                    setStyle("-fx-background-color:#f5f6fa;-fx-padding:6 12;");
                }
            }
        });

        // ── FOOTER ───────────────────────────────────────────────────────────
        Label errorLbl = new Label();
        errorLbl.setStyle("-fx-text-fill:#c62828;-fx-font-size:12px;");
        errorLbl.setWrapText(true);

        Button saveBtn = new Button("\u2714  Save Quiz");
        saveBtn.setStyle("-fx-background-color:#3949ab;-fx-text-fill:white;-fx-font-weight:bold;" +
            "-fx-padding:10 32;-fx-background-radius:22;-fx-font-size:13px;" +
            "-fx-effect:dropshadow(gaussian,rgba(57,73,171,0.4),8,0,0,3);");
        Button discardBtn = new Button("\u2716  Discard");
        discardBtn.setStyle("-fx-background-color:white;-fx-text-fill:#c62828;-fx-font-weight:bold;" +
            "-fx-padding:10 32;-fx-background-radius:22;-fx-font-size:13px;" +
            "-fx-border-color:#e57373;-fx-border-radius:22;");
        discardBtn.setOnAction(e -> review.close());

        saveBtn.setOnAction(e -> {
            String newTitle = titleInput.getText().trim();
            if (newTitle.isEmpty()) { errorLbl.setText("Title cannot be empty."); return; }
            int newDur, newPass;
            try { newDur = Integer.parseInt(durInput.getText().trim()); }
            catch (NumberFormatException ex) { errorLbl.setText("Duration must be a number."); return; }
            try { newPass = Integer.parseInt(passInput.getText().trim()); }
            catch (NumberFormatException ex) { errorLbl.setText("Passing score must be a number."); return; }

            generated.quiz.setTitle(newTitle);
            generated.quiz.setDurationMinutes(newDur);
            generated.quiz.setPassingScore(newPass);
            for (int i = 0; i < generated.questions.size(); i++) {
                Question q = generated.questions.get(i);
                q.setQuestionText(qTextAreas.get(i).getText().trim());
                q.setOptionA(qOptions.get(i)[0].getText().trim());
                q.setOptionB(qOptions.get(i)[1].getText().trim());
                q.setOptionC(qOptions.get(i)[2].getText().trim());
                q.setCorrectAnswer(qAnswers.get(i).getValue());
            }
            try {
                int newId = testService.addAndGetId(generated.quiz);
                if (newId < 0) throw new SQLException("Failed to get quiz ID.");
                for (Question q : generated.questions) { q.setQuizId(newId); questionService.add(q); }
                System.out.println("[SAVE] Quiz saved: id=" + newId + " title=" + generated.quiz.getTitle());
                review.close();
                loadQuizzes();
                statusLabel.setStyle("-fx-text-fill:#4CAF50;");
                statusLabel.setText("Quiz \"" + generated.quiz.getTitle() + "\" saved with " + generated.questions.size() + " questions!");
            } catch (Exception ex) {
                System.err.println("[SAVE] Error: " + ex.getMessage());
                errorLbl.setText("Error saving: " + ex.getMessage());
            }
        });

        Region spacer = new Region();
        HBox.setHgrow(spacer, Priority.ALWAYS);
        HBox footer = new HBox(12, errorLbl, spacer, discardBtn, saveBtn);
        footer.setAlignment(Pos.CENTER_LEFT);
        footer.setPadding(new Insets(14, 20, 16, 20));
        footer.setStyle("-fx-background-color:white;-fx-border-color:#e8eaf6;-fx-border-width:1 0 0 0;");

        BorderPane root = new BorderPane();
        root.setTop(header);
        root.setCenter(listView);
        root.setBottom(footer);
        root.setStyle("-fx-background-color:#f5f6fa;");

        Scene scene = new Scene(root, 760, 660);
        review.setScene(scene);
        System.out.println("[REVIEW] scene set, calling showAndWait");
        review.showAndWait();
        System.out.println("[REVIEW] showAndWait returned");
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
