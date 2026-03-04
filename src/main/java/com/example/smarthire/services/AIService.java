package com.example.smarthire.services;

import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import okhttp3.*;
import org.apache.tika.Tika;
import java.io.File;
import java.io.IOException;
import java.util.concurrent.TimeUnit;

public class AIService {

    private final OkHttpClient client;

    public AIService() {
        this.client = new OkHttpClient.Builder()
                .connectTimeout(60, TimeUnit.SECONDS)
                .readTimeout(60, TimeUnit.SECONDS)
                .build();
    }

    /**
     * EXTRACTION PDF : Utilisé lors de l'inscription pour lire le CV.
     */
    public String extractTextFromPDF(File file) throws Exception {
        System.out.println("[DEBUG-AI] Extraction du texte PDF : " + file.getName());
        Tika tika = new Tika();
        return tika.parseToString(file);
    }

    /**
     * ANALYSE CV : Transforme le texte du PDF en JSON structuré.
     */
    public JsonObject analyzeResume(String text) throws Exception {
        System.out.println("[DEBUG-AI] Analyse du contenu du CV...");
        String prompt = "Extract candidate info. Return ONLY a JSON object with: firstName, lastName, phone, skills (array), summary. Text: " + cleanForJson(text);
        return callGroq(prompt);
    }

    /**
     * SMART MATCHING : Compare l'événement avec les compétences de l'utilisateur.
     */
    public JsonObject calculateEventMatching(String candidateSkills, String eventDescription) throws Exception {
        // Nettoyage des compétences (retrait des crochets et guillemets de la BDD)
        String cleanSkills = (candidateSkills == null) ? "" : candidateSkills.replace("[", "").replace("]", "").replace("\"", "").trim();

        // --- DEBUG MESSAGE ---
        System.out.println("\n[DEBUG-AI-PROCESS] =======================================");
        System.out.println("[DEBUG-AI-PROCESS] Skills reçus : '" + cleanSkills + "'");

        // --- SÉCURITÉ ANTI-HALLUCINATION ---
        // Si les compétences sont vides, on n'appelle pas l'IA pour éviter qu'elle invente des technos.
        if (cleanSkills.isEmpty() || cleanSkills.equals(",")) {
            System.out.println("[DEBUG-AI-PROCESS] ALERTE : Compétences vides. IA non appelée.");
            System.out.println("[DEBUG-AI-PROCESS] =======================================\n");

            JsonObject emptyResponse = new JsonObject();
            emptyResponse.addProperty("score", 0);
            emptyResponse.addProperty("recommendation", "Nous n'avons pas trouvé de compétences sur votre profil. " +
                    "Veuillez mettre à jour votre CV pour recevoir des recommandations personnalisées.");
            return emptyResponse;
        }

        System.out.println("[DEBUG-AI-PROCESS] Envoi à l'IA pour l'événement : " +
                (eventDescription.length() > 40 ? eventDescription.substring(0, 40) + "..." : eventDescription));

        String prompt = "ACT AS A TECHNICAL CAREER ANALYST.\n" +
                "CANDIDATE_SKILLS: " + cleanForJson(cleanSkills) + "\n" +
                "EVENT_DESCRIPTION: " + cleanForJson(eventDescription) + "\n\n" +
                "STRICT RULES:\n" +
                "1. Find direct links between the event and the candidate's skills.\n" +
                "2. Use EXACT NAMES from the CANDIDATE_SKILLS list.\n" +
                "3. Use this EXACT template for the recommendation:\n" +
                "'This event matches your profile because it focuses on [Topic]. Since you already know [Skill 1] and [Skill 2] from your profile, you have what it takes to succeed.'\n\n" +
                "Return ONLY a JSON object with keys: score (0-100), recommendation (string).";

        JsonObject response = callGroq(prompt);

        // --- DEBUG RÉSULTAT ---
        System.out.println("[DEBUG-AI-RESULT] Score calculé : " + response.get("score").getAsInt());
        System.out.println("[DEBUG-AI-PROCESS] =======================================\n");

        return response;
    }

    /**
     * Appel générique à l'API Groq
     */
    private JsonObject callGroq(String prompt) throws IOException {
        JsonObject root = new JsonObject();
        root.addProperty("model", MODEL);
        JsonObject resFormat = new JsonObject();
        resFormat.addProperty("type", "json_object");
        root.add("response_format", resFormat);

        JsonArray messages = new JsonArray();
        JsonObject message = new JsonObject();
        message.addProperty("role", "user");
        message.addProperty("content", prompt);
        messages.add(message);
        root.add("messages", messages);

        RequestBody body = RequestBody.create(root.toString(), MediaType.get("application/json"));
        Request request = new Request.Builder()
                .url(URL)
                .header("Authorization", "Bearer " + API_KEY)
                .post(body)
                .build();

        try (Response response = client.newCall(request).execute()) {
            if (!response.isSuccessful()) {
                System.err.println("[DEBUG-AI] Groq API Error: " + response.code());
                throw new IOException("Groq API Error: " + response.code());
            }

            String responseBody = response.body().string();
            String content = JsonParser.parseString(responseBody)
                    .getAsJsonObject().get("choices").getAsJsonArray()
                    .get(0).getAsJsonObject().get("message").getAsJsonObject()
                    .get("content").getAsString();

            return JsonParser.parseString(content).getAsJsonObject();
        }
    }

    private String cleanForJson(String text) {
        if (text == null) return "";
        return text.replaceAll("[\\t\\n\\r]", " ")
                .replaceAll("[\\p{Cntrl}]", "")
                .replaceAll("\\s+", " ")
                .trim();
    }
}