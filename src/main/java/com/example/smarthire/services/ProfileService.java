package com.example.smarthire.services;

import com.example.smarthire.utils.MyDatabase;
import java.sql.*;

public class ProfileService {
    private Connection connection = MyDatabase.getInstance().getConnection();

    public String getSkillsByUserId(int userId) {
        String sql = "SELECT skills FROM CANDIDATE_PROFILES WHERE user_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getString("skills");
            }
        } catch (SQLException e) { e.printStackTrace(); }
        return "[]";
    }
}