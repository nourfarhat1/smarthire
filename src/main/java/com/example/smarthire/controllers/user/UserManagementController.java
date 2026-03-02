package com.example.smarthire.controllers.user;

import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.UserService;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import java.sql.SQLException;
import java.util.List;
import javafx.scene.chart.PieChart;

public class UserManagementController {
    @FXML private PieChart verificationChart;
    @FXML private TableView<User> userTable;
    @FXML private TableColumn<User, Integer> colId;
    @FXML private TableColumn<User, String> colEmail;
    @FXML private TableColumn<User, String> colFirstName;
    @FXML private TableColumn<User, String> colLastName;
    @FXML private TableColumn<User, String> colRole;
    @FXML private TableColumn<User, Boolean> colStatus; // Column for is_verified
    @FXML private TableColumn<User, Boolean> colBanned; // Column for is_banned

    private final UserService userService = new UserService();
    private ObservableList<User> userList = FXCollections.observableArrayList();

    @FXML
    public void initialize() {
        // 1. Basic Value Mapping
        colId.setCellValueFactory(new PropertyValueFactory<>("id"));
        colEmail.setCellValueFactory(new PropertyValueFactory<>("email"));
        colFirstName.setCellValueFactory(new PropertyValueFactory<>("firstName"));
        colLastName.setCellValueFactory(new PropertyValueFactory<>("lastName"));
        colRole.setCellValueFactory(new PropertyValueFactory<>("roleName"));

        // 2. Custom Rendering for Verification Status
        colStatus.setCellValueFactory(new PropertyValueFactory<>("verified"));
        colStatus.setCellFactory(col -> new TableCell<>() {
            @Override
            protected void updateItem(Boolean item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setText(null);
                    setStyle("");
                } else {
                    setText(item ? "✔ Verified" : "✘ Pending");
                    setStyle(item ? "-fx-text-fill: #87a042; -fx-font-weight: bold;" : "-fx-text-fill: #999;");
                }
            }
        });

        // 3. Custom Rendering for Banned Status
        colBanned.setCellValueFactory(new PropertyValueFactory<>("banned"));
        colBanned.setCellFactory(col -> new TableCell<>() {
            @Override
            protected void updateItem(Boolean item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setText(null);
                    setStyle("");
                } else {
                    if (item) {
                        setText("BANNED");
                        setStyle("-fx-background-color: #f2dede; -fx-text-fill: #a94442; -fx-alignment: center; -fx-font-weight: bold;");
                    } else {
                        setText("Active");
                        setStyle("-fx-text-fill: green; -fx-alignment: center;");
                    }
                }
            }
        });

        loadUserData();
    }

    private void loadUserData() {
        try {
            userList.clear();
            List<User> users = userService.getAll();
            userList.addAll(users);
            userTable.setItems(userList);
            userTable.refresh();
            updateChart(users); // <-- add this line
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Database Error: " + e.getMessage());
        }
    }

    private void updateChart(List<User> users) {
        long verified = users.stream().filter(User::isVerified).count();
        long unverified = users.size() - verified;

        ObservableList<PieChart.Data> chartData = FXCollections.observableArrayList(
                new PieChart.Data("Verified (" + verified + ")", verified),
                new PieChart.Data("Pending (" + unverified + ")", unverified)
        );

        verificationChart.setData(chartData);
    }

    // --- BAN ACTIONS ---

    @FXML
    private void handleDeleteUser() {
        User selectedUser = userTable.getSelectionModel().getSelectedItem();
        if (selectedUser == null) {
            showAlert(Alert.AlertType.WARNING, "Please select a user to delete.");
            return;
        }

        // Confirmation dialog before deleting
        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION);
        confirm.setHeaderText(null);
        confirm.setContentText("Are you sure you want to delete " + selectedUser.getEmail() + "? This cannot be undone.");
        confirm.showAndWait().ifPresent(response -> {
            if (response == ButtonType.OK) {
                try {
                    userService.delete(selectedUser.getId());
                    loadUserData();
                    showAlert(Alert.AlertType.INFORMATION, "User " + selectedUser.getEmail() + " has been deleted.");
                } catch (SQLException e) {
                    e.printStackTrace();
                    showAlert(Alert.AlertType.ERROR, "Database error: Could not delete user.");
                }
            }
        });
    }
    @FXML
    private void handleBanUser() {
        User selectedUser = userTable.getSelectionModel().getSelectedItem();
        if (selectedUser == null) return;
        try {
            userService.updateBannedStatus(selectedUser.getId(), true);
            loadUserData(); // Reloads from DB
            userTable.refresh(); // Forces UI update
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    private void handleUnbanUser() {
        User selectedUser = userTable.getSelectionModel().getSelectedItem();
        if (selectedUser == null) return;
        try {
            userService.updateBannedStatus(selectedUser.getId(), false);
            loadUserData();
            userTable.refresh();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // --- VERIFICATION ACTIONS ---

    @FXML
    private void handleVerifyUser() {
        User selectedUser = userTable.getSelectionModel().getSelectedItem();
        if (selectedUser == null) return;
        try {
            userService.updateVerificationStatus(selectedUser.getId(), true);
            loadUserData();
            userTable.refresh();
            showAlert(Alert.AlertType.INFORMATION, "User " + selectedUser.getEmail() + " is now verified.");
        } catch (SQLException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Database error: Could not verify user.");
        }
    }

    @FXML
    private void handleUnverifyUser() {
        User selectedUser = userTable.getSelectionModel().getSelectedItem();
        if (selectedUser == null) return;
        try {
            userService.updateVerificationStatus(selectedUser.getId(), false);
            loadUserData();
            userTable.refresh();
            showAlert(Alert.AlertType.INFORMATION, "Verification revoked for " + selectedUser.getEmail());
        } catch (SQLException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Database error: Could not unverify user.");
        }
    }

    private void showAlert(Alert.AlertType type, String msg) {
        Alert a = new Alert(type);
        a.setHeaderText(null);
        a.setContentText(msg);
        a.show();
    }
}