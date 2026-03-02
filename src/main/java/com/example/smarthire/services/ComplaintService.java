package com.example.smarthire.services;

import com.example.smarthire.entities.reclamation.Response;
import com.example.smarthire.entities.reclamation.Complaint;
import com.example.smarthire.entities.reclamation.ReclaimType;
import com.example.smarthire.utils.MyDatabase;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

public class ComplaintService implements IService<Complaint> {

    private Connection connection;

    public ComplaintService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    @Override
    public void add(Complaint c) throws SQLException {
        String sql = "INSERT INTO COMPLAINT (user_id, type_id, subject, description, status, submission_date) VALUES (?, ?, ?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, c.getUserId());
            ps.setInt(2, c.getTypeId());
            ps.setString(3, c.getSubject());
            ps.setString(4, c.getDescription());
            ps.setString(5, c.getStatus());
            ps.setTimestamp(6, c.getSubmissionDate());
            ps.executeUpdate();
        }
    }

    @Override
    public void update(Complaint c) throws SQLException {
        String sql = "UPDATE COMPLAINT SET status=?, description=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, c.getStatus());
            ps.setString(2, c.getDescription());
            ps.setInt(3, c.getId());
            ps.executeUpdate();
        }
    }

    @Override
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM COMPLAINT WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    @Override
    public List<Complaint> getAll() throws SQLException {//admin's list
        List<Complaint> list = new ArrayList<>();
        String sql = "SELECT c.*, u.first_name, u.last_name FROM COMPLAINT c LEFT JOIN app_user u ON c.user_id = u.id";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                list.add(mapRowToComplaint(rs));
            }
        }
        return list;
    }

    @Override
    public Complaint getOne(int id) throws SQLException {
        return null;
    }

    public List<Complaint> getComplaintsByUserId(int userId) throws SQLException {//specific user list
        List<Complaint> list = new ArrayList<>();
        // FIXED: Changed 'users' to 'app_user'
        String query = "SELECT c.*, u.first_name, u.last_name FROM COMPLAINT c LEFT JOIN app_user u ON c.user_id = u.id WHERE c.user_id = ?";
        try (PreparedStatement pst = connection.prepareStatement(query)) {
            pst.setInt(1, userId);
            try (ResultSet rs = pst.executeQuery()) {
                while (rs.next()) {
                    list.add(mapRowToComplaint(rs));
                }
            }
        }
        return list;
    }

    private Complaint mapRowToComplaint(ResultSet rs) throws SQLException {
        Complaint c = new Complaint();
        c.setId(rs.getInt("id"));
        c.setUserId(rs.getInt("user_id"));
        c.setTypeId(rs.getInt("type_id"));
        c.setSubject(rs.getString("subject"));
        c.setDescription(rs.getString("description"));
        c.setStatus(rs.getString("status"));
        c.setSubmissionDate(rs.getTimestamp("submission_date"));

        // Safely try to map names if they were joined in the query
        try {
            c.setFirstName(rs.getString("first_name"));
            c.setLastName(rs.getString("last_name"));
        } catch (SQLException e) {
            // Ignored: columns aren't present in this specific ResultSet
        }
        return c;
    }

    public void addResponse(Response r) throws SQLException {
        String sql = "INSERT INTO RESPONSE (complaint_id, message, response_date, admin_name) VALUES (?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, r.getComplaintId());
            ps.setString(2, r.getMessage());
            ps.setTimestamp(3, r.getResponseDate());
            ps.setString(4, r.getAdminName());
            ps.executeUpdate();
        }
    }

    public void updateResponse(Response r) throws SQLException {
        String sql = "UPDATE RESPONSE SET message = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, r.getMessage());
            ps.setInt(2, r.getId());
            ps.executeUpdate();
        }
    }

    public void deleteResponse(int id) throws SQLException {
        String sql = "DELETE FROM RESPONSE WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    public List<Response> getResponsesByComplaintId(int complaintId) throws SQLException {
        List<Response> list = new ArrayList<>();
        String sql = "SELECT * FROM RESPONSE WHERE complaint_id = ? ORDER BY response_date ASC";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, complaintId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Response r = new Response();
                    r.setId(rs.getInt("id"));
                    r.setComplaintId(rs.getInt("complaint_id"));
                    r.setMessage(rs.getString("message"));
                    r.setResponseDate(rs.getTimestamp("response_date"));
                    r.setAdminName(rs.getString("admin_name"));
                    list.add(r);
                }
            }
        }
        return list;
    }

    public void updateStatus(int complaintId, String newStatus) throws SQLException {
        String sql = "UPDATE COMPLAINT SET status = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, newStatus);
            ps.setInt(2, complaintId);
            ps.executeUpdate();
        }
    }

    // UPDATED FILTER METHOD
    public List<Complaint> filterComplaints(int userId, String searchKeyword, String status, int typeId, String sortByDate) throws SQLException {
        List<Complaint> list = new ArrayList<>();

        // FIXED: Changed 'users' to 'app_user'
        StringBuilder sql = new StringBuilder(
                "SELECT c.*, u.first_name, u.last_name FROM COMPLAINT c " +
                        "LEFT JOIN app_user u ON c.user_id = u.id WHERE 1=1 "
        );

        if (userId > 0) sql.append(" AND c.user_id = ? ");

        if (searchKeyword != null && !searchKeyword.trim().isEmpty()) {
            sql.append(" AND (CAST(c.user_id AS CHAR) LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?) ");
        }

        if (status != null && !status.isEmpty() && !status.equals("ALL")) sql.append(" AND c.status = ?");
        if (typeId > 0) sql.append(" AND c.type_id = ?");

        if (sortByDate == null || sortByDate.isEmpty()) sortByDate = "DESC";
        sql.append(" ORDER BY c.submission_date ").append(sortByDate);

        try (PreparedStatement ps = connection.prepareStatement(sql.toString())) {
            int paramIndex = 1;

            if (userId > 0) ps.setInt(paramIndex++, userId);

            if (searchKeyword != null && !searchKeyword.trim().isEmpty()) {
                String searchPattern = "%" + searchKeyword.trim() + "%";
                ps.setString(paramIndex++, searchPattern);
                ps.setString(paramIndex++, searchPattern);
                ps.setString(paramIndex++, searchPattern);
            }

            if (status != null && !status.isEmpty() && !status.equals("ALL")) ps.setString(paramIndex++, status);
            if (typeId > 0) ps.setInt(paramIndex++, typeId);

            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    list.add(mapRowToComplaint(rs));
                }
            }
        }
        return list;
    }

    public void addType(ReclaimType type) throws SQLException {
        String sql = "INSERT INTO complaint_type (name, urgency_level) VALUES (?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, type.getName());
            ps.setString(2, type.getUrgencyLevel());
            ps.executeUpdate();
        }
    }

    public void updateType(ReclaimType type) throws SQLException {
        String sql = "UPDATE complaint_type SET name = ?, urgency_level = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, type.getName());
            ps.setString(2, type.getUrgencyLevel());
            ps.setInt(3, type.getId());
            ps.executeUpdate();
        }
    }

    public void deleteType(int id) throws SQLException {
        String sql = "DELETE FROM complaint_type WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    public List<ReclaimType> getAllTypes() throws SQLException {
        List<ReclaimType> list = new ArrayList<>();
        String sql = "SELECT * FROM complaint_type";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                list.add(new ReclaimType(
                        rs.getInt("id"),
                        rs.getString("name"),
                        rs.getString("urgency_level")
                ));
            }
        }
        return list;
    }

    public void updateComplaintByUser(Complaint c) throws SQLException {
        String sql = "UPDATE COMPLAINT SET subject = ?, description = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, c.getSubject());
            ps.setString(2, c.getDescription());
            ps.setInt(3, c.getId());
            ps.executeUpdate();
        }
    }

    public Map<String, Integer> getComplaintsCountByType() throws SQLException {
        Map<String, Integer> stats = new java.util.HashMap<>();
        String sql = "SELECT t.name, COUNT(c.id) as total FROM COMPLAINT c " +
                "JOIN complaint_type t ON c.type_id = t.id GROUP BY t.name";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                stats.put(rs.getString("name"), rs.getInt("total"));
            }
        }
        return stats;
    }
}