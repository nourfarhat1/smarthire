package com.example.smarthire.controllers;

import com.example.smarthire.utils.Navigation;
import javafx.fxml.FXML;
import javafx.scene.layout.BorderPane;

public class MainLayoutController {

    @FXML
    private BorderPane mainPane;

    @FXML
    public void initialize() {
        if (mainPane != null) {
            Navigation.setMainLayout(mainPane);
            System.out.println("✅ Main Layout Initialized");
        } else {
            System.err.println("❌ Error: mainPane is null via FX:ID");
        }
    }
}