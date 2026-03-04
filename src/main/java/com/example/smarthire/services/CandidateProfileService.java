package com.example.smarthire.services;

import com.example.smarthire.utils.MyDatabase;
import com.google.gson.JsonObject;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

public class CandidateProfileService {
    private Connection connection;

    public CandidateProfileService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    public void createProfile(int userId, JsonObject aiData) throws SQLException {
        String sql = "INSERT INTO candidate_profiles (user_id, skills, summary, raw_analysis) VALUES (?, ?, ?, ?)";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            // Convert skills array to string or keep as JSON string
            ps.setString(2, aiData.get("skills").toString());
            ps.setString(3, aiData.get("summary").getAsString());
            ps.setString(4, aiData.toString()); // Save full JSON in raw_analysis
            ps.executeUpdate();
        }
    }
}