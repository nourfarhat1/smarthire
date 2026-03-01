package com.example.smarthire.services;

import okhttp3.*;
import org.json.JSONObject;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.TimeUnit;

public class TranslationService {
    // MyMemory API is free and does not require an API key for up to 1000 words/day
    private static final String API_URL = "https://api.mymemory.translated.net/get?q=%s&langpair=%s|%s";

    public static String translate(String text, String sourceLang, String targetLang) {
        if (text == null || text.trim().isEmpty()) return text;

        OkHttpClient client = new OkHttpClient.Builder()
                .connectTimeout(15, TimeUnit.SECONDS)
                .build();

        try {
            // Encode the text for a URL
            String encodedText = URLEncoder.encode(text, StandardCharsets.UTF_8);
            String url = String.format(API_URL, encodedText, sourceLang, targetLang);

            Request request = new Request.Builder().url(url).get().build();

            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    JSONObject json = new JSONObject(response.body().string());
                    return json.getJSONObject("responseData").getString("translatedText");
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
        return "Translation failed. Check internet connection.";
    }
}