package com.example.smarthire.controllers.user;

import com.example.smarthire.HelloApplication;
import com.example.smarthire.entities.user.User;
import com.example.smarthire.utils.Navigation;
import com.example.smarthire.utils.SessionManager;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;
import java.io.IOException;

public class SidebarController {

    @FXML private VBox candidateMenu;
    @FXML private VBox hrMenu;
    @FXML private VBox adminMenu;

    @FXML
    public void initialize() {
        User user = SessionManager.getUser();
        if (user == null) return;

        candidateMenu.setVisible(false); candidateMenu.setManaged(false);
        hrMenu.setVisible(false); hrMenu.setManaged(false);
        adminMenu.setVisible(false); adminMenu.setManaged(false);

        String role = SessionManager.getRole();
        switch (role) {
            case "CANDIDATE":
                candidateMenu.setVisible(true); candidateMenu.setManaged(true);
                break;
            case "HR":
                hrMenu.setVisible(true); hrMenu.setManaged(true);
                break;
            case "ADMIN":
                adminMenu.setVisible(true); adminMenu.setManaged(true);
                break;
        }
    }

    // --- CANDIDATE ---
    @FXML void goToCandidateDashboard() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateDashboard.fxml"); }
    @FXML void goToJobMarket() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/JobMarketPlace.fxml"); }
    @FXML void goToMyApplications() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateAppMakeAndList.fxml"); }
    @FXML void goToCandidateEvents() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateEventList.fxml"); }
    @FXML void goToCandidateQuiz() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/CandidateQuizzList.fxml"); }
    @FXML void goToReclamation() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/candidate/ReclamationMakeAndList.fxml"); }

    // --- HR ---
    @FXML void goToHRDashboard() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/hr/HRDashboard.fxml"); }
    @FXML void goToManageOffers() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/hr/HROfferMakeAndList.fxml"); }
    @FXML void goToInterviews() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/hr/InterviewSchedule.fxml"); }
    @FXML void goToHREvents() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/hr/HREventMakeAndList.fxml"); }
    @FXML void goToHRQuizzes() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/frontend/hr/HRQuizzMakeAndList.fxml"); }

    // --- ADMIN ---
    @FXML void goToAdminStats() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/AdminStatistics.fxml"); }
    @FXML void goToUserManage() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/UserManagement.fxml"); }
    @FXML void goToJobCategories() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/JobCategoryManagement.fxml"); }
    @FXML void goToEventManagement() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/EventManagement.fxml"); }
    @FXML void goToAdminReclamation() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/AdminReclamationManagement.fxml"); }
    @FXML void goToAdminReclamationTypes() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/AdminReclamationTypes.fxml"); }
    @FXML void goToAdminOfferManage() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/AdminOfferManagement.fxml"); }
    @FXML void goToAdminAppManage() { Navigation.loadContent("/com/example/smarthire/fxml/fxmls/backend/AdminAppManagement.fxml"); }
    @FXML
    private void handleLogout() {
        SessionManager.clearSession();
        try {
            // Note the path fix here too
            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource("/com/example/smarthire/fxml/fxmls/frontend/auth/Login.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) candidateMenu.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.centerOnScreen();
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}