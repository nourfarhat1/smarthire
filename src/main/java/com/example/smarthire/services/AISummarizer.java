package com.example.smarthire.services;

import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.concurrent.TimeUnit;

public class AISummarizer {


    private static final OkHttpClient client = new OkHttpClient.Builder()
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .build();

    public static String summarize(String complaintText) {

        if (complaintText == null || complaintText.trim().length() < 5) {
            return "Text too short for AI analysis.";
        }

        try {

            JSONArray messages = new JSONArray();

            JSONObject system = new JSONObject();
            system.put("role", "system");
            system.put("content",
                    "You are a professional customer support assistant. " +
                            "Summarize the complaint in ONE sentence and provide a short professional reply.\n" +
                            "Format:\nRecap: ...\nSuggested Reply: ...");
            messages.put(system);

            JSONObject user = new JSONObject();
            user.put("role", "user");
            user.put("content", complaintText);
            messages.put(user);

            JSONObject root = new JSONObject();
            root.put("model", "llama-3.3-70b-versatile");  // ✅ updated working model
            root.put("messages", messages);
            root.put("temperature", 0.3);
            root.put("max_completion_tokens", 300); // ✅ IMPORTANT FIX

            RequestBody body = RequestBody.create(
                    root.toString(),
                    MediaType.parse("application/json; charset=utf-8")
            );

            Request request = new Request.Builder()
                    .url(API_URL)
                    .addHeader("Authorization", "Bearer " + API_KEY)
                    .addHeader("Content-Type", "application/json")
                    .post(body)
                    .build();

            try (Response response = client.newCall(request).execute()) {

                String responseBody = response.body() != null
                        ? response.body().string()
                        : "";

                if (!response.isSuccessful()) {
                    return "AI Error " + response.code() + ": " + responseBody;
                }

                JSONObject json = new JSONObject(responseBody);

                return json.getJSONArray("choices")
                        .getJSONObject(0)
                        .getJSONObject("message")
                        .getString("content");
            }

        } catch (IOException e) {
            e.printStackTrace();
            return "AI service unavailable.";
        }
    }
}