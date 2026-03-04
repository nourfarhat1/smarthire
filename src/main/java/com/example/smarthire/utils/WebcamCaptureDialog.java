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

    private volatile boolean running = true;

    /**
     * Opens a live webcam preview window.
     * User clicks Capture to take the photo, or Cancel to abort.
     * Returns the captured BufferedImage, or null if cancelled.
     */
    public BufferedImage capture() {

        Webcam webcam = Webcam.getDefault();
        if (webcam == null) {
            System.out.println("No webcam detected.");
            return null;
        }

        webcam.open();

        // Array used so lambda can store the result
        final BufferedImage[] result = {null};

        Stage dialog = new Stage();
        dialog.initModality(Modality.APPLICATION_MODAL);
        dialog.setTitle("Face Capture — SmartHire");
        dialog.setResizable(false);

        // Preview
        ImageView preview = new ImageView();
        preview.setFitWidth(320);
        preview.setFitHeight(240);
        preview.setStyle("-fx-border-color: #2f4188; -fx-border-width: 2;");

        // Instruction label
        Label instruction = new Label("Position your face in the frame, then click Capture.");
        instruction.setStyle("-fx-text-fill: #2f4188; -fx-font-weight: bold; -fx-font-size: 13px;");

        // Status label
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

        // Capture action — stores image BEFORE closing dialog
        captureBtn.setOnAction(e -> {
            statusLabel.setText("Capturing...");
            BufferedImage captured = webcam.getImage();
            if (captured != null) {
                result[0] = captured;
                statusLabel.setText("✔ Captured!");
            }
            running = false;
            webcam.close();
            dialog.close();
        });

        // Cancel action
        cancelBtn.setOnAction(e -> {
            running = false;
            if (webcam.isOpen()) webcam.close();
            dialog.close();
        });

        // X button cleanup
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

        // Background thread — feeds webcam frames into preview
        Thread previewThread = new Thread(() -> {
            while (running) {
                if (webcam.isOpen()) {
                    BufferedImage frame = webcam.getImage();
                    if (frame != null) {
                        WritableImage fxImage = SwingFXUtils.toFXImage(frame, null);
                        Platform.runLater(() -> preview.setImage(fxImage));
                    }
                }
                try {
                    Thread.sleep(66); // ~15fps
                } catch (InterruptedException ignored) {}
            }
        });
        previewThread.setDaemon(true);
        previewThread.start();

        dialog.showAndWait();

        // Small buffer to ensure capture action completes before returning
        try { Thread.sleep(100); } catch (InterruptedException ignored) {}

        return result[0];
    }
}