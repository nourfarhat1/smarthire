package com.example.smarthire.services;

import com.example.smarthire.utils.AppConfig;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;

public class SupabaseStorageService {


    private final HttpClient client = HttpClient.newBuilder()
            .connectTimeout(Duration.ofSeconds(30))
            .build();

    public String uploadPdf(byte[] pdfBytes, String filePath) throws Exception {

        String safePath = filePath.replaceAll("[^a-zA-Z0-9/_\\-.]+", "_");

        String uploadUrl = SUPABASE_URL + "/storage/v1/object/" + BUCKET_NAME + "/" + safePath;

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(uploadUrl))
                .header("Authorization",  "Bearer " + SUPABASE_ANON_KEY)
                .header("Content-Type",   "application/pdf")
                .header("x-upsert",       "true")            
                .POST(HttpRequest.BodyPublishers.ofByteArray(pdfBytes))
                .timeout(Duration.ofSeconds(60))
                .build();

        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());

        if (response.statusCode() < 200 || response.statusCode() >= 300) {
            throw new RuntimeException(
                    "Supabase upload failed (HTTP " + response.statusCode() + "): " + response.body());
        }

        return buildPublicUrl(safePath);
    }

    public String buildPublicUrl(String filePath) {
        return SUPABASE_URL + "/storage/v1/object/public/" + BUCKET_NAME + "/" + filePath;
    }
}
