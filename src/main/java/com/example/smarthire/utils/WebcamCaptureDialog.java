package com.example.smarthire.utils;

import com.github.sarxos.webcam.Webcam;
import javafx.application.Platform;
import javafx.embed.swing.SwingFXUtils;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.image.ImageView;
import javafx.scene.image.WritableImage;
import javafx.scene.layout.VBox;
import javafx.stage.Modality;
import javafx.stage.Stage;

import java.awt.image.BufferedImage;

public class WebcamCaptureDialog {

    private BufferedImage capturedImage = null;
    private volatile boolean running = true;

    /**
     * Opens a live webcam preview window.
     * User clicks Capture to take the photo, or Cancel to abort.
     * Returns the captured BufferedImage, or null if cancelled.
     */
    public BufferedImage capture() {

        // Check if a webcam is available
        Webcam webcam = Webcam.getDefault();
        if (webcam == null) {
            System.out.println("No webcam detected.");
            return null;
        }

        webcam.open();

        Stage dialog = new Stage();
        dialog.initModality(Modality.APPLICATION_MODAL);
        dialog.setTitle("Face Capture — SmartHire");
        dialog.setResizable(false);

        // Preview window
        ImageView preview = new ImageView();
        preview.setFitWidth(320);
        preview.setFitHeight(240);
        preview.setStyle("-fx-border-color: #2f4188; -fx-border-width: 2;");

        // Instruction label
        Label instruction = new Label("Position your face in the frame, then click Capture.");
        instruction.setStyle("-fx-text-fill: #2f4188; -fx-font-weight: bold; -fx-font-size: 13px;");

        // Status label (updates after capture attempt)
        Label statusLabel = new Label("");
        statusLabel.setStyle("-fx-font-size: 12px;");

        // Capture button
        Button captureBtn = new Button("📸  Capture");
        captureBtn.setMaxWidth(Double.MAX_VALUE);
        captureBtn.setStyle(
                "-fx-background-color: #87a042; -fx-text-fill: white; " +
                        "-fx-font-weight: bold; -fx-padding: 10; -fx-background-radius: 5; -fx-cursor: hand;"
        );

        // Cancel button
        Button cancelBtn = new Button("Cancel");
        cancelBtn.setMaxWidth(Double.MAX_VALUE);
        cancelBtn.setStyle(
                "-fx-background-color: #999; -fx-text-fill: white; " +
                        "-fx-padding: 10; -fx-background-radius: 5; -fx-cursor: hand;"
        );

        // Capture action
        captureBtn.setOnAction(e -> {
            capturedImage = webcam.getImage();
            running = false;
            webcam.close();
            dialog.close();
        });

        // Cancel action
        cancelBtn.setOnAction(e -> {
            running = false;
            webcam.close();
            dialog.close();
        });

        // Close button (X) also cleans up
        dialog.setOnCloseRequest(e -> {
            running = false;
            if (webcam.isOpen()) webcam.close();
        });

        // Layout
        VBox layout = new VBox(12, instruction, preview, statusLabel, captureBtn, cancelBtn);
        layout.setAlignment(Pos.CENTER);
        layout.setPadding(new Insets(20));
        layout.setStyle("-fx-background-color: #f4f7f6;");

        dialog.setScene(new Scene(layout, 380, 380));

        // Background thread — continuously feeds webcam frames into the preview
        Thread previewThread = new Thread(() -> {
            while (running) {
                BufferedImage frame = webcam.getImage();
                if (frame != null) {
                    WritableImage fxImage = SwingFXUtils.toFXImage(frame, null);
                    Platform.runLater(() -> preview.setImage(fxImage));
                }
                try {
                    Thread.sleep(66); // ~15fps, lightweight
                } catch (InterruptedException ignored) {}
            }
        });
        previewThread.setDaemon(true); // dies when app closes
        previewThread.start();

        dialog.showAndWait(); // blocks until dialog is closed
        return capturedImage;
    }
}