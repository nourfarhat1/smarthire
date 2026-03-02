package com.example.smarthire.services;

import com.example.smarthire.entities.user.User;
import com.example.smarthire.utils.MyDatabase;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class UserService implements IService<User> {

    private Connection connection;

    public UserService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    @Override
    public void add(User user) throws SQLException {
        // Oracle 11g: We let the TRIGGER handle the ID generation
        String sql = "INSERT INTO APP_USER (role_id, email, password_hash, first_name, last_name, phone_number, is_verified, is_banned) VALUES (?, ?, ?, ?, ?, ?, 0, 0)";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, user.getRoleId());
            ps.setString(2, user.getEmail());
            ps.setString(3, user.getPasswordHash());
            ps.setString(4, user.getFirstName());
            ps.setString(5, user.getLastName());
            ps.setString(6, user.getPhoneNumber());
            ps.executeUpdate();
            System.out.println("User added successfully!");
        }
    }

    @Override
    public void update(User user) throws SQLException {
        String sql = "UPDATE APP_USER SET email=?, first_name=?, last_name=?, phone_number=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, user.getEmail());
            ps.setString(2, user.getFirstName());
            ps.setString(3, user.getLastName());
            ps.setString(4, user.getPhoneNumber());
            ps.setInt(5, user.getId());
            ps.executeUpdate();
        }
    }

    @Override
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM APP_USER WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    @Override
    public List<User> getAll() throws SQLException {
        List<User> users = new ArrayList<>();
        String sql = "SELECT * FROM APP_USER";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                users.add(mapRowToUser(rs));
            }
        }
        return users;
    }

    @Override
    public User getOne(int id) throws SQLException {
        String sql = "SELECT * FROM APP_USER WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return mapRowToUser(rs);
            }
        }
        return null;
    }
    public User authenticate(String email, String password) throws SQLException {
        String sql = "SELECT * FROM APP_USER WHERE email = ? AND password_hash = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, email);
            ps.setString(2, password);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return mapRowToUser(rs);
            }
        }
        return null;
    }

    private User mapRowToUser(ResultSet rs) throws SQLException {
        User u = new User();
        u.setId(rs.getInt("id"));
        u.setRoleId(rs.getInt("role_id"));
        u.setEmail(rs.getString("email"));
        u.setPasswordHash(rs.getString("password_hash"));
        u.setFirstName(rs.getString("first_name"));
        u.setLastName(rs.getString("last_name"));
        u.setPhoneNumber(rs.getString("phone_number"));
        u.setVerified(rs.getInt("is_verified") == 1);
        u.setBanned(rs.getInt("is_banned") == 1);
        u.setCreatedAt(rs.getTimestamp("created_at"));
        return u;
    }


    public boolean emailExists(String email) throws SQLException {
        String sql = "SELECT COUNT(*) FROM APP_USER WHERE email = ?";
        try (java.sql.PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, email);
            try (java.sql.ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return rs.getInt(1) > 0;
                }
            }
        }
        return false;
    }

    public void updateBannedStatus(int userId, boolean status) throws SQLException {
        // FIXED: Column name changed from isBanned to is_banned
        String sql = "UPDATE app_user SET is_banned = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, status ? 1 : 0); // 1 for true, 0 for false
            ps.setInt(2, userId);
            ps.executeUpdate();
        }
    }

    public void updateVerificationStatus(int userId, boolean status) throws SQLException {
        // FIXED: Column name changed from isVerified to is_verified
        String sql = "UPDATE app_user SET is_verified = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, status ? 1 : 0); // 1 for true, 0 for false
            ps.setInt(2, userId);
            ps.executeUpdate();
        }
    }
    public User findByEmailAndPhone(String email, String phone) throws SQLException {
        String sql = "SELECT * FROM APP_USER WHERE email = ? AND phone_number = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, email);
            ps.setString(2, phone);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return mapRowToUser(rs);
            }
        }
        return null;
    }

    public void updatePassword(int userId, String newPassword) throws SQLException {
        String sql = "UPDATE APP_USER SET password_hash = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, newPassword);
            ps.setInt(2, userId);
            ps.executeUpdate();
        }
    }

    public void saveFaceToken(int userId, String faceToken) throws SQLException {
        String sql = "UPDATE app_user SET face_token = ? WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, faceToken);
            ps.setInt(2, userId);
            ps.executeUpdate();
        }
    }

    public String getFaceToken(int userId) throws SQLException {
        String sql = "SELECT face_token FROM app_user WHERE id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getString("face_token");
            }
        }
        return null;
    }

    public User getByEmail(String email) throws SQLException {
        String sql = "SELECT * FROM app_user WHERE email = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, email);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return mapRowToUser(rs);
            }
        }
        return null;
    }

    public User getByPhone(String phone) throws SQLException {
        String sql = "SELECT * FROM app_user WHERE phone_number = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, phone);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return mapRowToUser(rs);
            }
        }
        return null;
    }
}
