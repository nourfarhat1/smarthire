package com.example.smarthire;

import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.stage.Stage;

import java.io.IOException;

public class HelloApplication extends Application {

    @Override
    public void start(Stage stage) throws IOException {
        // ---------------------------------------------------------
        // CHANGE THIS PATH to match exactly where your FXML file is
        // ---------------------------------------------------------
        FXMLLoader fxmlLoader = new FXMLLoader(HelloApplication.class.getResource("/com/example/smarthire/fxml/fxmls/frontend/auth/Login.fxml"));

        // Error Checking: If this is null, your path string is wrong
        if (fxmlLoader.getLocation() == null) {
            System.err.println("❌ ERROR: FXML file not found! Check the path string.");
            System.exit(1);
        }

        Scene scene = new Scene(fxmlLoader.load(), 800, 600);
        stage.setTitle("SmartHire - Reclamation Test");
        stage.setScene(scene);
        stage.show();
    }

    public static void main(String[] args) {
        launch();
    }
}