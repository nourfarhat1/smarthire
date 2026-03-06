package com.example.smarthire.services;

import com.example.smarthire.entities.test.Question;
import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.utils.AppConfig;
import org.json.JSONArray;
import org.json.JSONObject;

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.ArrayList;
import java.util.List;

public class GroqAIService {



    public static class GeneratedQuiz {
        public Quiz quiz;
        public List<Question> questions;
    }

    public GeneratedQuiz generateQuiz(String title, String description) throws Exception {

        String prompt = buildPrompt(title, description);

        JSONObject requestBody = new JSONObject();
        requestBody.put("model", MODEL);
        requestBody.put("temperature", 0.7);
        requestBody.put("max_tokens", 2500);

        JSONArray messages = new JSONArray();
        JSONObject message = new JSONObject();
        message.put("role", "user");
        message.put("content", prompt);
        messages.put(message);
        requestBody.put("messages", messages);

        HttpClient client = HttpClient.newBuilder()
                .connectTimeout(Duration.ofSeconds(30))
                .build();

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(API_URL))
                .header("Content-Type", "application/json")
                .header("Authorization", "Bearer " + API_KEY)
                .POST(HttpRequest.BodyPublishers.ofString(requestBody.toString()))
                .timeout(Duration.ofSeconds(60))
                .build();

        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());

        if (response.statusCode() != 200) {
            throw new RuntimeException("Groq API error (HTTP " + response.statusCode() + "): " + response.body());
        }

        JSONObject responseJson = new JSONObject(response.body());
        String content = responseJson.getJSONArray("choices")
                .getJSONObject(0)
                .getJSONObject("message")
                .getString("content");

        content = extractJson(content);

        return parseGeneratedQuiz(title, description, content);
    }

    private String buildPrompt(String title, String description) {
        return "You are an expert quiz generator for a job hiring platform. Generate a professional technical quiz.\n\n" +
               "Topic: " + title + "\n" +
               "Description: " + description + "\n\n" +
               "Instructions:\n" +
               "- Generate exactly 8 multiple-choice questions appropriate for the difficulty described.\n" +
               "- Set duration_minutes based on difficulty (easy=15, medium=25, hard=40).\n" +
               "- Set passing_score as a percentage (easy=60, medium=65, hard=70).\n" +
               "- Each question must have exactly 3 options (A, B, C).\n" +
               "- The correct_answer field must contain ONLY the single uppercase letter: A, B, or C.\n\n" +
               "Return ONLY valid JSON with NO markdown, NO code fences, NO explanation. Exact structure:\n" +
               "{\n" +
               "  \"duration_minutes\": 25,\n" +
               "  \"passing_score\": 65,\n" +
               "  \"questions\": [\n" +
               "    {\n" +
               "      \"question_text\": \"What does JVM stand for?\",\n" +
               "      \"option_a\": \"Java Virtual Machine\",\n" +
               "      \"option_b\": \"Java Variable Method\",\n" +
               "      \"option_c\": \"Java Verified Module\",\n" +
               "      \"correct_answer\": \"A\"\n" +
               "    }\n" +
               "  ]\n" +
               "}";
    }

    private String extractJson(String content) {
        content = content.trim();
        
        if (content.startsWith("```")) {
            int newlineIdx = content.indexOf('\n');
            int lastFence = content.lastIndexOf("```");
            if (newlineIdx >= 0 && lastFence > newlineIdx) {
                content = content.substring(newlineIdx + 1, lastFence).trim();
            }
        }
        
        int start = content.indexOf('{');
        int end = content.lastIndexOf('}');
        if (start >= 0 && end > start) {
            content = content.substring(start, end + 1);
        }
        return content;
    }

    private GeneratedQuiz parseGeneratedQuiz(String title, String description, String jsonContent) {
        JSONObject json = new JSONObject(jsonContent);

        Quiz quiz = new Quiz();
        quiz.setTitle(title);
        quiz.setDescription(description);
        quiz.setDurationMinutes(json.getInt("duration_minutes"));
        quiz.setPassingScore(json.getInt("passing_score"));

        List<Question> questions = new ArrayList<>();
        JSONArray arr = json.getJSONArray("questions");
        for (int i = 0; i < arr.length(); i++) {
            JSONObject q = arr.getJSONObject(i);
            Question question = new Question();
            question.setQuestionText(q.getString("question_text"));
            question.setOptionA(q.getString("option_a"));
            question.setOptionB(q.getString("option_b"));
            question.setOptionC(q.getString("option_c"));
            
            String answer = q.getString("correct_answer").trim().toUpperCase();
            if (answer.length() > 1) answer = String.valueOf(answer.charAt(0));
            question.setCorrectAnswer(answer);
            questions.add(question);
        }

        GeneratedQuiz result = new GeneratedQuiz();
        result.quiz = quiz;
        result.questions = questions;
        return result;
    }
}
