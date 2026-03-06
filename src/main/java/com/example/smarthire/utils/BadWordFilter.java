package com.example.smarthire.utils;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;

public class BadWordFilter {

    // API Source: https://www.purgomalum.com
    private static final String API_URL = "https://www.purgomalum.com/service/containsprofanity?text=";

    public static boolean containsProfanity(String text) {
        try {
            String encodedText = URLEncoder.encode(text, StandardCharsets.UTF_8);
            URL url = new URL(API_URL + encodedText);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");

            BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
            String result = rd.readLine(); // API returns "true" or "false" as a string
            rd.close();

            return Boolean.parseBoolean(result);
        } catch (Exception e) {
            System.err.println("Bad Word Filter API Error: " + e.getMessage());
            // If API is down, we allow the message to be safe
            return false;
        }
    }
}