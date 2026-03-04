package com.example.smarthire.controllers.event;

import com.example.smarthire.entities.event.AppEvent;
import com.example.smarthire.services.EventService;
import javafx.collections.FXCollections;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.HBox;
import javafx.util.Callback;

import java.sql.SQLException;
import java.sql.Timestamp;
import java.time.LocalDate;

public class EventController {

    @FXML private TabPane mainTabPane;
    @FXML private Tab myEventsTab, createEventTab;
    @FXML private TableView<AppEvent> eventTable;
    @FXML private TableColumn<AppEvent, String> colName, colLocation, colDesc;
    @FXML private TableColumn<AppEvent, Timestamp> colDate;
    @FXML private TableColumn<AppEvent, Integer> colMax;
    @FXML private TableColumn<AppEvent, Void> colActions;

    @FXML private TextField nameField, locationField, maxParticipantsField;
    @FXML private DatePicker datePicker;
    @FXML private TextArea descriptionArea;
    @FXML private Button submitButton;
    @FXML private Label formTitle;

    // Labels d'erreurs (les spans)
    @FXML private Label nameError, dateError, locationError, descError, maxError;

    private final EventService eventService = new EventService();
    private AppEvent selectedEvent = null;

    @FXML
    public void initialize() {
        setupTable();
        refreshTable();
        setupRealTimeValidation();

        // Désactiver le bouton submit par défaut au début
        submitButton.setDisable(true);
    }

    private void setupRealTimeValidation() {
        // Validation Nom
        nameField.textProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal.trim().isEmpty()) {
                showFieldError(nameField, nameError, "Name cannot be empty");
            } else if (newVal.length() < 3) {
                showFieldError(nameField, nameError, "At least 3 characters required");
            } else {
                clearFieldError(nameField, nameError);
            }
            validateGlobalForm();
        });

        // Validation Location
        locationField.textProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal.trim().isEmpty()) {
                showFieldError(locationField, locationError, "Location is required");
            } else {
                clearFieldError(locationField, locationError);
            }
            validateGlobalForm();
        });

        // Validation Description
        descriptionArea.textProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal.trim().length() < 10) {
                showFieldError(descriptionArea, descError, "Description too short (min 10)");
            } else {
                clearFieldError(descriptionArea, descError);
            }
            validateGlobalForm();
        });

        // Validation Max Participants
        maxParticipantsField.textProperty().addListener((obs, oldVal, newVal) -> {
            if (!newVal.matches("\\d*")) {
                maxParticipantsField.setText(newVal.replaceAll("[^\\d]", ""));
            } else if (newVal.isEmpty()) {
                showFieldError(maxParticipantsField, maxError, "Required");
            } else if (Integer.parseInt(newVal) <= 0) {
                showFieldError(maxParticipantsField, maxError, "Must be > 0");
            } else {
                clearFieldError(maxParticipantsField, maxError);
            }
            validateGlobalForm();
        });

        // Validation Date
        datePicker.valueProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal == null) {
                dateError.setText("Please select a date");
            } else if (newVal.isBefore(LocalDate.now())) {
                dateError.setText("Date cannot be in the past");
            } else {
                dateError.setText("");
            }
            validateGlobalForm();
        });
    }

    // Affiche l'erreur visuellement
    private void showFieldError(Control field, Label errorLabel, String msg) {
        errorLabel.setText(msg);
        field.setStyle("-fx-border-color: #e74c3c; -fx-padding: 8;");
    }

    // Efface l'erreur
    private void clearFieldError(Control field, Label errorLabel) {
        errorLabel.setText("");
        field.setStyle("-fx-border-color: #ddd; -fx-padding: 8;");
    }

    // Active ou désactive le bouton de validation
    private void validateGlobalForm() {
        boolean isValid = !nameField.getText().trim().isEmpty() &&
                nameField.getText().length() >= 3 &&
                !locationField.getText().trim().isEmpty() &&
                descriptionArea.getText().trim().length() >= 10 &&
                datePicker.getValue() != null &&
                !datePicker.getValue().isBefore(LocalDate.now()) &&
                !maxParticipantsField.getText().isEmpty() &&
                Integer.parseInt(maxParticipantsField.getText()) > 0;

        submitButton.setDisable(!isValid);
    }

    @FXML
    void handlePublishEvent(ActionEvent event) {
        try {
            String name = nameField.getText().trim();
            String location = locationField.getText().trim();
            String desc = descriptionArea.getText().trim();
            LocalDate date = datePicker.getValue();
            int max = Integer.parseInt(maxParticipantsField.getText().trim());
            Timestamp timestamp = Timestamp.valueOf(date.atStartOfDay());

            if (selectedEvent == null) {
                eventService.add(new AppEvent(1, name, desc, timestamp, location, max));
                showAlert("Success", "Event Published!");
            } else {
                selectedEvent.setName(name);
                selectedEvent.setLocation(location);
                selectedEvent.setDescription(desc);
                selectedEvent.setEventDate(timestamp);
                selectedEvent.setMaxParticipants(max);
                eventService.update(selectedEvent);
                showAlert("Success", "Event Updated!");
            }
            resetForm();
            refreshTable();
            mainTabPane.getSelectionModel().select(myEventsTab);
        } catch (Exception e) {
            showAlert("Error", e.getMessage());
        }
    }

    // --- Les autres méthodes (setupTable, handleDeleteEvent, prepareUpdate, etc.) restent identiques ---

    private void setupTable() {
        colName.setCellValueFactory(new PropertyValueFactory<>("name"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("eventDate"));
        colLocation.setCellValueFactory(new PropertyValueFactory<>("location"));
        colMax.setCellValueFactory(new PropertyValueFactory<>("maxParticipants"));
        colDesc.setCellValueFactory(new PropertyValueFactory<>("description"));

        Callback<TableColumn<AppEvent, Void>, TableCell<AppEvent, Void>> cellFactory = param -> new TableCell<>() {
            private final Button editBtn = new Button("Edit");
            private final Button deleteBtn = new Button("Delete");
            private final HBox pane = new HBox(10, editBtn, deleteBtn);
            {
                editBtn.setStyle("-fx-background-color: #2f4188; -fx-text-fill: white;");
                deleteBtn.setStyle("-fx-background-color: #e74c3c; -fx-text-fill: white;");
                deleteBtn.setOnAction(e -> handleDeleteEvent(getTableView().getItems().get(getIndex())));
                editBtn.setOnAction(e -> prepareUpdate(getTableView().getItems().get(getIndex())));
            }
            @Override protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                setGraphic(empty ? null : pane);
            }
        };
        colActions.setCellFactory(cellFactory);
    }

    private void handleDeleteEvent(AppEvent event) {
        try {
            eventService.delete(event.getId());
            refreshTable();
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void prepareUpdate(AppEvent event) {
        selectedEvent = event;
        nameField.setText(event.getName());
        locationField.setText(event.getLocation());
        descriptionArea.setText(event.getDescription());
        datePicker.setValue(event.getEventDate().toLocalDateTime().toLocalDate());
        maxParticipantsField.setText(String.valueOf(event.getMaxParticipants()));
        formTitle.setText("Edit Event");
        submitButton.setText("Update Event");
        mainTabPane.getSelectionModel().select(createEventTab);
        validateGlobalForm(); // Re-vérifier pour activer le bouton
    }

    private void refreshTable() {
        try { eventTable.setItems(FXCollections.observableArrayList(eventService.getAll())); }
        catch (SQLException e) { e.printStackTrace(); }
    }

    private void resetForm() {
        selectedEvent = null;
        nameField.clear();
        locationField.clear();
        descriptionArea.clear();
        datePicker.setValue(null);
        maxParticipantsField.clear();
        nameError.setText(""); dateError.setText(""); locationError.setText(""); descError.setText(""); maxError.setText("");
        validateGlobalForm();
    }

    private void showAlert(String title, String content) {
        Alert a = new Alert(Alert.AlertType.INFORMATION);
        a.setTitle(title); a.setContentText(content); a.show();
    }

    @FXML void switchToCreateTab() { resetForm(); mainTabPane.getSelectionModel().select(createEventTab); }
}