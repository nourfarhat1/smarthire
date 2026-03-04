package com.example.smarthire.controllers.test;

import com.example.smarthire.entities.test.Question;
import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.services.GroqAIService;
import com.example.smarthire.services.QuestionService;
import com.example.smarthire.services.TestService;
import com.example.smarthire.utils.Navigation;
import javafx.application.Platform;
import javafx.collections.FXCollections;
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
        TextField titleField = new TextField();
        titleField.setPromptText("e.g. Java, SQL, Python, Machine Learning...");
        titleField.setStyle("-fx-padding: 8; -fx-border-color: #ccc; -fx-border-radius: 6;");

        Label descLbl = new Label("Description / Difficulty Hint:");
        descLbl.setStyle("-fx-font-weight: bold;");
        TextArea descArea = new TextArea();
        descArea.setPromptText("e.g. Basic knowledge of Java OOP concepts, intermediate SQL queries...");
        descArea.setPrefRowCount(3);
        descArea.setWrapText(true);
        descArea.setStyle("-fx-padding: 8; -fx-border-color: #ccc; -fx-border-radius: 6;");

        Label infoLbl = new Label("The AI will generate questions, set the duration and passing score automatically.");
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

        root.getChildren().addAll(heading, titleLbl, titleField, descLbl, descArea, infoLbl, btnRow, genStatusLbl);

        generateBtn.setOnAction(e -> {
            String title = titleField.getText().trim();
            String desc = descArea.getText().trim();
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

            task.setOnFailed(ev -> {
                Platform.runLater(() -> {
                    spinner.setVisible(false);
                    generateBtn.setDisable(false);
                    cancelBtn.setDisable(false);
                    genStatusLbl.setStyle("-fx-text-fill: red;");
                    Throwable err = task.getException();
                    genStatusLbl.setText("AI error: " + (err != null ? err.getMessage() : "Unknown error"));
                });
            });

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

        // ── HEADER ──────────────────────────────────────────────────────────
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

        // ── QUESTION CARDS ──────────────────────────────────────────────────
        List<TextArea> qTextAreas = new ArrayList<>();
        List<TextField[]> qOptions = new ArrayList<>();
        List<ComboBox<String>> qAnswers = new ArrayList<>();

        String[] badgeColors = {"#3949ab","#1565c0","#00695c","#558b2f","#6a1b9a","#ad1457","#e65100","#4527a0"};

        javafx.collections.ObservableList<VBox> cards = FXCollections.observableArrayList();
        for (int i = 0; i < generated.questions.size(); i++) {
            Question q = generated.questions.get(i);
            String color = badgeColors[i % badgeColors.length];

            Label badge = new Label("Q" + (i + 1));
            badge.setStyle("-fx-background-color:" + color + ";-fx-text-fill:white;" +
                "-fx-font-weight:bold;-fx-font-size:12px;-fx-padding:3 10;" +
                "-fx-background-radius:20;");

            TextArea qText = new TextArea(q.getQuestionText());
            qText.setPrefRowCount(2);
            qText.setPrefHeight(62);
            qText.setWrapText(true);
            qText.setMaxWidth(Double.MAX_VALUE);
            qText.setStyle("-fx-font-size:13px;-fx-border-color:#e0e0e0;" +
                "-fx-border-radius:6;-fx-background-radius:6;");

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
            card.setPadding(new Insets(14));
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

        // ── FOOTER ──────────────────────────────────────────────────────────
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
