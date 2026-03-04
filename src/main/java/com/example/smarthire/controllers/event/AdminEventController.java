package com.example.smarthire.controllers.event;

import com.example.smarthire.entities.event.AppEvent;
import com.example.smarthire.services.EventService;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.Scene;
import javafx.scene.chart.BarChart;
import javafx.scene.chart.CategoryAxis;
import javafx.scene.chart.NumberAxis;
import javafx.scene.chart.XYChart;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.VBox;
import javafx.stage.Modality;
import javafx.stage.Stage;

import java.sql.SQLException;
import java.util.Comparator;
import java.util.List;

public class AdminEventController {

    @FXML private TableView<AppEvent> eventTable;
    @FXML private TableColumn<AppEvent, Integer> colId;
    @FXML private TableColumn<AppEvent, String> colName;
    @FXML private TableColumn<AppEvent, String> colHrId; // CHANGÉ EN STRING
    @FXML private TableColumn<AppEvent, Object> colDate;
    @FXML private TableColumn<AppEvent, String> colLocation;
    @FXML private TableColumn<AppEvent, Integer> colMax;
    @FXML private TextField searchField;

    private final EventService eventService = new EventService();
    private ObservableList<AppEvent> masterData = FXCollections.observableArrayList();

    @FXML
    public void initialize() {
        colId.setCellValueFactory(new PropertyValueFactory<>("id"));
        colName.setCellValueFactory(new PropertyValueFactory<>("name"));

        // ICI : On utilise organizerName au lieu de organizerId
        colHrId.setCellValueFactory(new PropertyValueFactory<>("organizerName"));

        colDate.setCellValueFactory(new PropertyValueFactory<>("eventDate"));
        colLocation.setCellValueFactory(new PropertyValueFactory<>("location"));
        colMax.setCellValueFactory(new PropertyValueFactory<>("maxParticipants"));

        loadEvents();
    }

    @FXML
    public void loadEvents() {
        try {
            List<AppEvent> events = eventService.getAll();
            masterData.setAll(events);
            eventTable.setItems(masterData);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    void sortByName() {
        masterData.sort(Comparator.comparing(AppEvent::getName, String.CASE_INSENSITIVE_ORDER));
    }

    @FXML
    void sortByDate() {
        masterData.sort(Comparator.comparing(AppEvent::getEventDate).reversed());
    }

    @FXML
    void sortByCapacity() {
        masterData.sort(Comparator.comparingInt(AppEvent::getMaxParticipants).reversed());
    }

    @FXML
    void handleSearch() {
        String query = searchField.getText().toLowerCase();
        if (query.isEmpty()) {
            eventTable.setItems(masterData);
        } else {
            ObservableList<AppEvent> filtered = masterData.filtered(e ->
                    e.getName().toLowerCase().contains(query) ||
                            e.getLocation().toLowerCase().contains(query) ||
                            (e.getOrganizerName() != null && e.getOrganizerName().toLowerCase().contains(query))
            );
            eventTable.setItems(filtered);
        }
    }

    @FXML
    void handleDeleteEvent() {
        AppEvent selected = eventTable.getSelectionModel().getSelectedItem();
        if (selected == null) {
            showAlert("Selection Required", "Please select an event to delete.");
            return;
        }

        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION, "Delete event '" + selected.getName() + "'? This will remove all associated tickets.", ButtonType.YES, ButtonType.NO);
        confirm.showAndWait().ifPresent(response -> {
            if (response == ButtonType.YES) {
                try {
                    eventService.delete(selected.getId());
                    loadEvents();
                } catch (SQLException e) {
                    showAlert("Error", "Could not delete: " + e.getMessage());
                }
            }
        });
    }

    @FXML
    void showStatistics() {
        Stage statsStage = new Stage();
        statsStage.initModality(Modality.APPLICATION_MODAL);
        statsStage.setTitle("Events Statistics");

        CategoryAxis xAxis = new CategoryAxis();
        xAxis.setLabel("Event Names");
        NumberAxis yAxis = new NumberAxis();
        yAxis.setLabel("Max Capacity");

        BarChart<String, Number> barChart = new BarChart<>(xAxis, yAxis);
        barChart.setTitle("Capacity Distribution per Event");

        XYChart.Series<String, Number> series = new XYChart.Series<>();
        series.setName("Maximum Participants Allowed");

        for (AppEvent event : masterData) {
            series.getData().add(new XYChart.Data<>(event.getName(), event.getMaxParticipants()));
        }

        barChart.getData().add(series);

        VBox root = new VBox(barChart);
        Scene scene = new Scene(root, 700, 450);
        statsStage.setScene(scene);
        statsStage.show();
    }

    private void showAlert(String title, String content) {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(content);
        alert.showAndWait();
    }
}