package com.example.smarthire.services;

import com.example.smarthire.entities.test.QuizResult;
import com.example.smarthire.utils.MyDatabase;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class QuizResultService implements IService<QuizResult> {
    private Connection connection;

    public QuizResultService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    @Override
    public void add(QuizResult r) throws SQLException {
        String sql = "INSERT INTO quiz_result (quiz_id, candidate_id, score, is_passed) VALUES (?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, r.getQuizId());
            ps.setInt(2, r.getCandidateId());
            ps.setInt(3, r.getScore());
            ps.setBoolean(4, r.isPassed());
            ps.executeUpdate();
        }
    }

    @Override
    public void update(QuizResult r) throws SQLException {
        String sql = "UPDATE quiz_result SET quiz_id=?, candidate_id=?, score=?, is_passed=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, r.getQuizId());
            ps.setInt(2, r.getCandidateId());
            ps.setInt(3, r.getScore());
            ps.setBoolean(4, r.isPassed());
            ps.setInt(5, r.getId());
            ps.executeUpdate();
        }
    }

    @Override
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM quiz_result WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    @Override
    public QuizResult getOne(int id) throws SQLException {
        String sql = "SELECT qr.*, q.title as quiz_title, CONCAT(u.first_name, ' ', u.last_name) as candidate_name FROM quiz_result qr LEFT JOIN quiz q ON qr.quiz_id = q.id LEFT JOIN app_user u ON qr.candidate_id = u.id WHERE qr.id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return mapRow(rs);
                }
            }
        }
        return null;
    }

    @Override
    public List<QuizResult> getAll() throws SQLException {
        List<QuizResult> list = new ArrayList<>();
        String sql = "SELECT qr.*, q.title as quiz_title, CONCAT(u.first_name, ' ', u.last_name) as candidate_name FROM quiz_result qr LEFT JOIN quiz q ON qr.quiz_id = q.id LEFT JOIN app_user u ON qr.candidate_id = u.id ORDER BY qr.attempt_date DESC";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                list.add(mapRow(rs));
            }
        }
        return list;
    }

    public List<QuizResult> getByQuizId(int quizId) throws SQLException {
        List<QuizResult> list = new ArrayList<>();
        String sql = "SELECT qr.*, q.title as quiz_title, CONCAT(u.first_name, ' ', u.last_name) as candidate_name FROM quiz_result qr LEFT JOIN quiz q ON qr.quiz_id = q.id LEFT JOIN app_user u ON qr.candidate_id = u.id WHERE qr.quiz_id=? ORDER BY qr.attempt_date DESC";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, quizId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    list.add(mapRow(rs));
                }
            }
        }
        return list;
    }

    private QuizResult mapRow(ResultSet rs) throws SQLException {
        QuizResult r = new QuizResult();
        r.setId(rs.getInt("id"));
        r.setQuizId(rs.getInt("quiz_id"));
        r.setCandidateId(rs.getInt("candidate_id"));
        r.setScore(rs.getInt("score"));
        r.setAttemptDate(rs.getTimestamp("attempt_date"));
        r.setPassed(rs.getBoolean("is_passed"));
        r.setQuizTitle(rs.getString("quiz_title"));
        r.setCandidateName(rs.getString("candidate_name"));
        return r;
    }

    public List<String[]> getCandidates() throws SQLException {
        List<String[]> list = new ArrayList<>();
        String sql = "SELECT id, first_name, last_name FROM app_user WHERE role_id=1";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                list.add(new String[]{String.valueOf(rs.getInt("id")), rs.getString("first_name") + " " + rs.getString("last_name")});
            }
        }
        return list;
    }
}
