package com.example.smarthire.services;

import okhttp3.*;
import org.json.JSONObject;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.util.Base64;

public class FaceService {

    private final OkHttpClient client = new OkHttpClient();

    /**
     * Sends an image to Face++ and returns a face_token.
     * Returns null if no face is detected.
     */
    public String detectFace(BufferedImage image) throws IOException {
        String base64Image = toBase64(image);

        RequestBody body = new MultipartBody.Builder()
                .setType(MultipartBody.FORM)
                .addFormDataPart("api_key", API_KEY)
                .addFormDataPart("api_secret", API_SECRET)
                .addFormDataPart("image_base64", base64Image)
                .build();

        Request request = new Request.Builder()
                .url(DETECT_URL)
                .post(body)
                .build();

        try (Response response = client.newCall(request).execute()) {
            String responseBody = response.body().string();
            System.out.println("Face++ Detect Response: " + responseBody);
            JSONObject json = new JSONObject(responseBody);

            if (!json.has("faces") || json.getJSONArray("faces").length() == 0) {
                return null; // No face detected
            }

            return json.getJSONArray("faces")
                    .getJSONObject(0)
                    .getString("face_token");
        }
    }

    /**
     * Compares two face tokens.
     * Returns a confidence score between 0 and 100.
     * Face++ considers 75+ a match.
     */
    public double compareFaces(String token1, String token2) throws IOException {
        // Free tier limit: 1 request/sec — wait before second call
        try { Thread.sleep(1500); } catch (InterruptedException ignored) {}

        RequestBody body = new MultipartBody.Builder()
                .setType(MultipartBody.FORM)
                .addFormDataPart("api_key", API_KEY)
                .addFormDataPart("api_secret", API_SECRET)
                .addFormDataPart("face_token1", token1)
                .addFormDataPart("face_token2", token2)
                .build();

        Request request = new Request.Builder()
                .url(COMPARE_URL)
                .post(body)
                .build();

        try (Response response = client.newCall(request).execute()) {
            String responseBody = response.body().string();
            System.out.println("Face++ Compare Response: " + responseBody);
            JSONObject json = new JSONObject(responseBody);

            // Handle API rate limit error gracefully
            if (json.has("error_message")) {
                throw new IOException("Face++ API error: " + json.getString("error_message"));
            }

            return json.getDouble("confidence");
        }
    }

    /**
     * Converts BufferedImage to Base64 string for API transmission.
     */
    private String toBase64(BufferedImage image) throws IOException {
        ByteArrayOutputStream baos = new ByteArrayOutputStream();
        ImageIO.write(image, "jpg", baos);
        return Base64.getEncoder().encodeToString(baos.toByteArray());
    }
}