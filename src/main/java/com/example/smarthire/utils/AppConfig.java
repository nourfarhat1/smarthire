package com.example.smarthire.utils;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

public class AppConfig {

    private static final Map<String, String> CONFIG = new HashMap<>();

    static {
        loadEnvFile();
    }

    private static void loadEnvFile() {
        File envFile = new File(System.getProperty("user.dir"), ".env");
        if (!envFile.exists()) {
            envFile = new File(".env");
        }
        if (!envFile.exists()) {
            System.err.println("⚠ .env file not found – falling back to system environment variables.");
            return;
        }

        try (BufferedReader reader = new BufferedReader(new FileReader(envFile))) {
            String line;
            while ((line = reader.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty() || line.startsWith("#")) continue;
                int eqIndex = line.indexOf('=');
                if (eqIndex < 1) continue;
                String key   = line.substring(0, eqIndex).trim();
                String value = line.substring(eqIndex + 1).trim();
                CONFIG.put(key, value);
            }
            System.out.println("✅ .env loaded from: " + envFile.getAbsolutePath());
        } catch (IOException e) {
            System.err.println("⚠ Failed to read .env: " + e.getMessage());
        }
    }

    public static String get(String key) {
        String value = CONFIG.get(key);
        if (value == null) {
            value = System.getenv(key);
        }
        if (value == null) {
            throw new IllegalStateException("Missing config key: " + key + ". Add it to your .env file.");
        }
        return value;
    }

    public static String getOrDefault(String key, String defaultValue) {
        String value = CONFIG.get(key);
        if (value == null) {
            value = System.getenv(key);
        }
        return value != null ? value : defaultValue;
    }
}
