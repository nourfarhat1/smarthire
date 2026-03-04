package com.example.smarthire.services;

import com.example.smarthire.entities.application.Training;
import com.example.smarthire.utils.MyDatabase;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class TrainingService {

    private final Connection connection;

    public TrainingService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    // ✅ CREATE
    public void addTraining(Training t) throws SQLException {
        String sql = "INSERT INTO training (title, category, description, video_url, admin_id) " +
                "VALUES (?, ?, ?, ?, ?)";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, t.getTitle());
            ps.setString(2, t.getCategory());
            ps.setString(3, t.getDescription());
            ps.setString(4, t.getUrl());
            ps.setObject(5, t.getAdminId());
            ps.executeUpdate();
        }
    }

    // ✅ READ ALL
    // ✅ READ ALL
    public List<Training> getAll() throws SQLException {
        List<Training> list = new ArrayList<>();

        String sql = """
        SELECT t.*, 
               u.first_name, 
               u.last_name,
               CONCAT(u.first_name, ' ', u.last_name) AS admin_name
        FROM training t
        LEFT JOIN app_user u ON t.admin_id = u.id
        ORDER BY t.created_at DESC
        """;

        try (PreparedStatement ps = connection.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {

            while (rs.next()) {
                Training t = mapRow(rs);

                // safer: directly use alias
                t.setAdminName(rs.getString("admin_name"));

                list.add(t);
            }
        }
        return list;
    }

    // ✅ UPDATE
    public void update(Training t) throws SQLException {
        String sql = "UPDATE training SET title=?, category=?, description=?, video_url=? WHERE id=?";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, t.getTitle());
            ps.setString(2, t.getCategory());
            ps.setString(3, t.getDescription());
            ps.setString(4, t.getUrl());
            ps.setInt(5, t.getId());
            ps.executeUpdate();
        }
    }

    // ✅ DELETE
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM training WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    // ✅ LIKE
    public void like(int trainingId) throws SQLException {
        String sql = "UPDATE training SET likes = likes + 1 WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, trainingId);
            ps.executeUpdate();
        }
    }

    // ✅ DISLIKE
    public void dislike(int trainingId) throws SQLException {
        String sql = "UPDATE training SET dislikes = dislikes + 1 WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, trainingId);
            ps.executeUpdate();
        }
    }

    // 🔁 Map ResultSet → Training
    private Training mapRow(ResultSet rs) throws SQLException {
        Training t = new Training();
        t.setId(rs.getInt("id"));
        t.setTitle(rs.getString("title"));
        t.setCategory(rs.getString("category"));
        t.setDescription(rs.getString("description"));
        t.setVideoUrl(rs.getString("video_url"));
        t.setLikes(rs.getInt("likes"));
        t.setDislikes(rs.getInt("dislikes"));
        t.setCreatedAt(rs.getTimestamp("created_at"));
        t.setAdminId(rs.getObject("admin_id", Integer.class));
        return t;
    }
}