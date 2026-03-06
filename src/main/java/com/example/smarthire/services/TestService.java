package com.example.smarthire.services;

import com.example.smarthire.entities.test.Quiz;
import com.example.smarthire.utils.MyDatabase;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class TestService implements IService<Quiz> {
    private Connection connection;

    public TestService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    @Override
    public void add(Quiz t) throws SQLException {
        String sql = "INSERT INTO quiz (related_job_id, title, description, duration_minutes, passing_score) VALUES (?, ?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            if (t.getRelatedJobId() == 0) {
                ps.setNull(1, Types.INTEGER);
            } else {
                ps.setInt(1, t.getRelatedJobId());
            }
            ps.setString(2, t.getTitle());
            ps.setString(3, t.getDescription());
            ps.setInt(4, t.getDurationMinutes());
            ps.setInt(5, t.getPassingScore());
            ps.executeUpdate();
        }
    }

    public int addAndGetId(Quiz t) throws SQLException {
        String sql = "INSERT INTO quiz (related_job_id, title, description, duration_minutes, passing_score) VALUES (?, ?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            if (t.getRelatedJobId() == 0) {
                ps.setNull(1, Types.INTEGER);
            } else {
                ps.setInt(1, t.getRelatedJobId());
            }
            ps.setString(2, t.getTitle());
            ps.setString(3, t.getDescription());
            ps.setInt(4, t.getDurationMinutes());
            ps.setInt(5, t.getPassingScore());
            ps.executeUpdate();
            try (ResultSet rs = ps.getGeneratedKeys()) {
                if (rs.next()) {
                    return rs.getInt(1);
                }
            }
        }
        return -1;
    }

    @Override
    public void update(Quiz t) throws SQLException {
        String sql = "UPDATE quiz SET related_job_id=?, title=?, description=?, duration_minutes=?, passing_score=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            if (t.getRelatedJobId() == 0) {
                ps.setNull(1, Types.INTEGER);
            } else {
                ps.setInt(1, t.getRelatedJobId());
            }
            ps.setString(2, t.getTitle());
            ps.setString(3, t.getDescription());
            ps.setInt(4, t.getDurationMinutes());
            ps.setInt(5, t.getPassingScore());
            ps.setInt(6, t.getId());
            ps.executeUpdate();
        }
    }

    @Override
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM quiz WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    @Override
    public Quiz getOne(int id) throws SQLException {
        String sql = "SELECT * FROM quiz WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    Quiz q = new Quiz();
                    q.setId(rs.getInt("id"));
                    q.setRelatedJobId(rs.getInt("related_job_id"));
                    q.setTitle(rs.getString("title"));
                    q.setDescription(rs.getString("description"));
                    q.setDurationMinutes(rs.getInt("duration_minutes"));
                    q.setPassingScore(rs.getInt("passing_score"));
                    return q;
                }
            }
        }
        return null;
    }

    @Override
    public List<Quiz> getAll() throws SQLException {
        List<Quiz> list = new ArrayList<>();
        String sql = "SELECT * FROM quiz";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                Quiz q = new Quiz();
                q.setId(rs.getInt("id"));
                q.setRelatedJobId(rs.getInt("related_job_id"));
                q.setTitle(rs.getString("title"));
                q.setDescription(rs.getString("description"));
                q.setDurationMinutes(rs.getInt("duration_minutes"));
                q.setPassingScore(rs.getInt("passing_score"));
                list.add(q);
            }
        }
        return list;
    }

}
