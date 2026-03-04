package com.example.smarthire.utils;

import com.example.smarthire.HelloApplication;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.layout.BorderPane;
import java.io.IOException;

public class Navigation {

    private static BorderPane mainLayout;

    public static void setMainLayout(BorderPane pane) {
        mainLayout = pane;
    }

    public static void loadContent(String fxmlPath) {
        if (mainLayout == null) {
            System.err.println("❌ ERROR: MainLayout is not initialized!");
            return;
        }
        try {
            FXMLLoader loader = new FXMLLoader(HelloApplication.class.getResource(fxmlPath));
            Parent view = loader.load();
            mainLayout.setCenter(view); // Switches the center content
        } catch (IOException e) {
            System.err.println("❌ ERROR: Could not find FXML at " + fxmlPath);
            e.printStackTrace();
        }
    }
}