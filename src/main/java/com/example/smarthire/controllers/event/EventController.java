package com.example.smarthire.controllers.event;

import com.example.smarthire.entities.event.AppEvent;
import com.example.smarthire.services.EventService;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
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

    @FXML private TextField nameField, maxParticipantsField;
    @FXML private ComboBox<String> locationField;
    @FXML private DatePicker datePicker;
    @FXML private TextArea descriptionArea;
    @FXML private Button submitButton;
    @FXML private Label formTitle;

    @FXML private Label nameError, dateError, locationError, descError, maxError;

    private final EventService eventService = new EventService();
    private AppEvent selectedEvent = null;

    private final ObservableList<String> tunisianCities = FXCollections.observableArrayList(
            "Tunis", "Ariana", "Ben Arous", "Manouba", "Sousse", "Sfax", "Kairouan",
            "Bizerte", "Gabès", "Nabeul", "Gafsa", "Monastir", "Kasserine", "Hammamet",
            "Djerba", "Beja", "Mahdia", "Zarzis", "Jendouba", "Tozeur", "Tataouine"
    );

    @FXML
    public void initialize() {
        locationField.setItems(tunisianCities);
        setupTable();
        refreshTable();
        setupRealTimeValidation();
        submitButton.setDisable(true);
    }

    private void setupRealTimeValidation() {
        nameField.textProperty().addListener((obs, old, newVal) -> validateGlobalForm());
        locationField.valueProperty().addListener((obs, old, newVal) -> validateGlobalForm());
        descriptionArea.textProperty().addListener((obs, old, newVal) -> validateGlobalForm());
        maxParticipantsField.textProperty().addListener((obs, old, newVal) -> {
            if (!newVal.matches("\\d*")) maxParticipantsField.setText(newVal.replaceAll("[^\\d]", ""));
            validateGlobalForm();
        });
        datePicker.valueProperty().addListener((obs, old, newVal) -> validateGlobalForm());
    }

    private void validateGlobalForm() {
        boolean nameValid = nameField.getText().trim().length() >= 3;
        boolean locationValid = locationField.getValue() != null;
        boolean descValid = descriptionArea.getText().trim().length() >= 10;
        boolean dateValid = datePicker.getValue() != null && !datePicker.getValue().isBefore(LocalDate.now());
        boolean maxValid = !maxParticipantsField.getText().isEmpty() && Integer.parseInt(maxParticipantsField.getText()) > 0;

        nameError.setText(nameValid ? "" : "Min 3 characters");
        locationError.setText(locationValid ? "" : "Select a city");
        descError.setText(descValid ? "" : "Min 10 characters");
        dateError.setText(dateValid ? "" : "Invalid date");
        maxError.setText(maxValid ? "" : "Must be > 0");

        submitButton.setDisable(!(nameValid && locationValid && descValid && dateValid && maxValid));
    }

    private void setupTable() {
        colName.setCellValueFactory(new PropertyValueFactory<>("name"));
        colDate.setCellValueFactory(new PropertyValueFactory<>("eventDate"));
        colLocation.setCellValueFactory(new PropertyValueFactory<>("location"));
        colMax.setCellValueFactory(new PropertyValueFactory<>("maxParticipants"));
        colDesc.setCellValueFactory(new PropertyValueFactory<>("description"));

        colActions.setCellFactory(param -> new TableCell<>() {
            private final Button editBtn = new Button("Edit");
            private final Button deleteBtn = new Button("Delete");
            private final HBox pane = new HBox(10, editBtn, deleteBtn);
            {
                pane.setAlignment(Pos.CENTER);
                editBtn.getStyleClass().add("button-primary-small");
                deleteBtn.getStyleClass().add("button-danger-small");

                editBtn.setOnAction(e -> prepareUpdate(getTableView().getItems().get(getIndex())));
                deleteBtn.setOnAction(e -> handleDeleteEvent(getTableView().getItems().get(getIndex())));
            }
            @Override protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                setGraphic(empty ? null : pane);
            }
        });
    }

    @FXML
    void handlePublishEvent(ActionEvent event) {
        try {
            String name = nameField.getText().trim();
            String location = locationField.getValue();
            String desc = descriptionArea.getText().trim();
            LocalDate date = datePicker.getValue();
            int max = Integer.parseInt(maxParticipantsField.getText().trim());
            Timestamp timestamp = Timestamp.valueOf(date.atStartOfDay());

            if (selectedEvent == null) {
                eventService.add(new AppEvent(1, name, desc, timestamp, location, max));
            } else {
                selectedEvent.setName(name);
                selectedEvent.setLocation(location);
                selectedEvent.setDescription(desc);
                selectedEvent.setEventDate(timestamp);
                selectedEvent.setMaxParticipants(max);
                eventService.update(selectedEvent);
            }
            resetForm();
            refreshTable();
            mainTabPane.getSelectionModel().select(myEventsTab);
        } catch (Exception e) {
            showAlert("Error", e.getMessage());
        }
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
        locationField.setValue(event.getLocation());
        descriptionArea.setText(event.getDescription());
        datePicker.setValue(event.getEventDate().toLocalDateTime().toLocalDate());
        maxParticipantsField.setText(String.valueOf(event.getMaxParticipants()));
        formTitle.setText("Edit Event");
        submitButton.setText("UPDATE CHANGES");
        mainTabPane.getSelectionModel().select(createEventTab);
    }

    private void refreshTable() {
        try {
            eventTable.setItems(FXCollections.observableArrayList(eventService.getAll()));
        } catch (SQLException e) { e.printStackTrace(); }
    }

    @FXML
    private void resetForm() {
        selectedEvent = null;
        nameField.clear();
        locationField.setValue(null);
        descriptionArea.clear();
        datePicker.setValue(null);
        maxParticipantsField.clear();
        submitButton.setText("PUBLISH EVENT");
        formTitle.setText("Host a New Event");
        mainTabPane.getSelectionModel().select(myEventsTab);
    }

    private void showAlert(String title, String content) {
        Alert a = new Alert(Alert.AlertType.INFORMATION);
        a.setTitle(title); a.setContentText(content); a.show();
    }

    @FXML void switchToCreateTab() {
        selectedEvent = null;
        formTitle.setText("Host a New Event");
        submitButton.setText("PUBLISH EVENT");
        mainTabPane.getSelectionModel().select(createEventTab);
    }
}