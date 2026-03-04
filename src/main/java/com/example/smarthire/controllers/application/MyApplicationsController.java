package com.example.smarthire.controllers.application;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.entities.application.Training;
import com.example.smarthire.services.ApplicationService;
import com.example.smarthire.services.TrainingService;
import com.example.smarthire.utils.Navigation;
import com.example.smarthire.utils.SessionManager;
import javafx.beans.property.ReadOnlyObjectWrapper;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.geometry.Insets;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.GridPane;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;

import java.sql.SQLException;
import java.util.List;
import java.util.Optional;
import java.util.stream.Collectors;
public class MyApplicationsController {

    @FXML private TableView<JobRequest> appTable;
    @FXML private TableColumn<JobRequest, String> colJob, colStatus, colDate, colSaved;
    @FXML private ComboBox<String> filterBox;

    private final ApplicationService appService = new ApplicationService();
    @FXML private TableColumn<JobRequest, JobRequest> colTrainings;
    private final TrainingService trainingService = new TrainingService();
    private List<Training> trainingsList;

    @FXML
    private void goToTrainingCategorie(JobRequest job) {
        // Optional: you can pass job.getCategorie() or other info to the next screen
        Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateTrainingDisplay.fxml");
    }
    @FXML
    public void initialize() {
        // ----------------------------
        // Load trainings from DB
        try {
            trainingsList = trainingService.getAll(); // Make sure getAll() returns List<Training>
        } catch (SQLException e) {
            trainingsList = List.of(); // empty list if error
            e.printStackTrace();
        }
        // Column setup
        colJob.setCellValueFactory(new PropertyValueFactory<>("jobTitle"));
        colStatus.setCellValueFactory(new PropertyValueFactory<>("status"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("submissionDate"));
        // Trainings column
        colTrainings.setCellValueFactory(param -> new ReadOnlyObjectWrapper<>(param.getValue()));
        colTrainings.setCellFactory(tc -> new TableCell<>() {

            private final Button learnMoreBtn = new Button("Learn More");

            {
                learnMoreBtn.setStyle("-fx-background-color: #ff69b4; -fx-text-fill: white; -fx-font-weight: bold;");
            }

            @Override
            protected void updateItem(JobRequest job, boolean empty) {
                super.updateItem(job, empty);

                if (empty || job == null) {
                    setGraphic(null);
                } else {
                    // Check if trainingsList is not null
                    boolean hasTraining = trainingsList != null &&
                            trainingsList.stream()
                                    .anyMatch(t -> t.getCategory().equalsIgnoreCase(job.getCategorie()));

                    if (hasTraining) {
                        // Set the button action
                        learnMoreBtn.setOnAction(e -> {
                            List<Training> relatedTrainings = trainingsList.stream()
                                    .filter(t -> t.getCategory().equalsIgnoreCase(job.getCategorie()))
                                    .toList();

                            Alert alert = new Alert(Alert.AlertType.INFORMATION);
                            alert.setTitle("Related Trainings");
                            alert.setHeaderText("Trainings for category: " + job.getCategorie());

                            if (relatedTrainings.isEmpty()) {
                                alert.setContentText("No trainings available for this category.");
                            } else {
                                String content = relatedTrainings.stream()
                                        .map(t -> "- " + t.getTitle())
                                        .collect(Collectors.joining("\n"));
                                alert.setContentText(content);
                            }

                            alert.showAndWait();
                        });

                        setGraphic(learnMoreBtn);
                    } else {
                        setGraphic(null);
                    }
                }
            }
        });

        // Load all data
        loadData();
        // Row coloring for INTERVIEWING
        appTable.setRowFactory(tv -> new TableRow<>() {
            @Override
            protected void updateItem(JobRequest item, boolean empty) {
                super.updateItem(item, empty);
                if (item == null || empty) setStyle("");
                else if ("INTERVIEWING".equalsIgnoreCase(item.getStatus()))
                    setStyle("-fx-background-color: #D5F7D4;");
                else setStyle("");
            }
        });

        // Filter ComboBox setup
        filterBox.getItems().addAll("All Applications", "Spontaneous Only", "Job Offers Only");
        filterBox.setValue("All Applications");
        filterBox.setOnAction(e -> applyFilter());
    }

    // Load all applications for the logged-in candidate
    public void loadData() {
        try {
            int userId = SessionManager.getUser().getId();
            appTable.setItems(FXCollections.observableArrayList(appService.getByCandidate(userId)));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // Filter table based on ComboBox
    private void applyFilter() {
        try {
            int userId = SessionManager.getUser().getId();
            var allApps = appService.getByCandidate(userId);

            String selected = filterBox.getValue();
            var filtered = allApps.stream().filter(req -> {
                Integer id = req.getJobOfferId();
                switch (selected) {
                    case "Spontaneous Only": return id == null || id == 0;
                    case "Job Offers Only": return id != null && id != 0;
                    default: return true;
                }
            }).toList();

            appTable.setItems(FXCollections.observableArrayList(filtered));
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    private void handleSpontaneous() {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource(
                    "/com/example/smarthire/fxml/fxmls/frontend/candidate/SpontaneousAppForm.fxml"));
            VBox formRoot = loader.load();

            // Get the controller and pass the parent controller (optional, for table refresh)
            SpontaneousAppFormController controller = loader.getController();
            controller.setParentController(this); // MyApplicationsController

            // Open a new window
            Stage stage = new Stage();
            stage.setTitle("New Spontaneous Application");
            stage.setScene(new Scene(formRoot));
            stage.show();

        } catch (Exception e) {
            e.printStackTrace();
            new Alert(Alert.AlertType.ERROR, "Failed to open form: " + e.getMessage()).show();
        }
    }
    @FXML
    private void handleEdit() {
        JobRequest selected = appTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            alert("Please select an application to edit.");
            return;
        }
        showAppDialog(selected);
    }



    @FXML
    private void handleDelete() {
        JobRequest selected = appTable.getSelectionModel().getSelectedItem();
        if (selected == null) return;
        try {
            appService.delete(selected.getId());
            loadData();
        } catch (SQLException e) {
            alert("Error deleting: " + e.getMessage());
        }
    }

    // ----------------------------
    // Dialog for Add / Edit
    private void showAppDialog(JobRequest existing) {
        Dialog<ButtonType> dialog = new Dialog<>();
        dialog.setTitle(existing == null ? "Spontaneous Application" : "Edit Application");
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        grid.setPadding(new Insets(20));

        // Fields
        TextField cvField = new TextField(existing != null ? existing.getCvUrl() : "");
        TextArea letterArea = new TextArea(existing != null ? existing.getCoverLetter() : "");
        TextField jobTitleField = new TextField(existing != null ? existing.getJobTitle() : "");
        TextField locationField = new TextField(existing != null ? existing.getLocation() : "");
        TextField categorieField = new TextField(existing != null ? existing.getCategorie() : "");

        cvField.setPromptText("CV URL");
        letterArea.setPromptText("Cover Letter");
        jobTitleField.setPromptText("Job Title");
        locationField.setPromptText("Location");
        categorieField.setPromptText("Category");


// Add to grid
        grid.add(new Label("CV URL:"), 0, 0);       grid.add(cvField, 1, 0);
        grid.add(new Label("Letter:"), 0, 1);       grid.add(letterArea, 1, 1);
        grid.add(new Label("Job Title:"), 0, 2);    grid.add(jobTitleField, 1, 2);
        grid.add(new Label("Location:"), 0, 3);     grid.add(locationField, 1, 3);

        if (existing == null) {
            // Only show category for NEW spontaneous applications
            grid.add(new Label("Category:"), 0, 4);
            grid.add(categorieField, 1, 4);
        }

        dialog.getDialogPane().setContent(grid);

        Optional<ButtonType> res = dialog.showAndWait();
        if (res.isPresent() && res.get() == ButtonType.OK) {
            try {
                if (existing == null) {
                    // --- NEW SPONTANEOUS APPLICATION ---
                    JobRequest req = new JobRequest(
                            SessionManager.getUser().getId(),
                            null,                        // ✅ NULL because spontaneous
                            jobTitleField.getText(),
                            locationField.getText(),
                            cvField.getText(),
                            letterArea.getText(),
                            null,                        // suggestedSalary (optional)
                            categorieField.getText()    // ✅ category is required
                    );
                    appService.apply(req);
                } else {
                    // --- EDIT EXISTING APPLICATION ---
                    existing.setCvUrl(cvField.getText());
                    existing.setCoverLetter(letterArea.getText());
                    existing.setJobTitle(jobTitleField.getText());
                    existing.setLocation(locationField.getText());

                    // Only set category if this is spontaneous
                    if (existing.getJobOfferId() == null) {
                        existing.setCategorie( categorieField.getText());
                    }

                    appService.update(existing);
                }
                loadData();
            } catch (SQLException e) {
                alert("Error: " + e.getMessage());
            }
        }
    }




    private void alert(String msg) { new Alert(Alert.AlertType.INFORMATION, msg).show(); }
}