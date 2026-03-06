package com.example.smarthire.services;

import com.example.smarthire.entities.event.AppEvent;
import com.example.smarthire.utils.MyDatabase;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class EventService implements IService<AppEvent> {
    private Connection connection;

    public EventService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    @Override
    public void add(AppEvent t) throws SQLException {
        String sql = "INSERT INTO APP_EVENT (organizer_id, name, description, event_date, location, max_participants) VALUES (?, ?, ?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, t.getOrganizerId());
            ps.setString(2, t.getName());
            ps.setString(3, t.getDescription());
            ps.setTimestamp(4, t.getEventDate());
            ps.setString(5, t.getLocation());
            ps.setInt(6, t.getMaxParticipants());
            ps.executeUpdate();
        }
    }

    @Override
    public void update(AppEvent t) throws SQLException {
        String sql = "UPDATE APP_EVENT SET name=?, description=?, location=?, event_date=?, max_participants=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, t.getName());
            ps.setString(2, t.getDescription());
            ps.setString(3, t.getLocation());
            ps.setTimestamp(4, t.getEventDate());
            ps.setInt(5, t.getMaxParticipants());
            ps.setInt(6, t.getId());
            ps.executeUpdate();
        }
    }

    @Override
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM APP_EVENT WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    @Override
    public List<AppEvent> getAll() throws SQLException {
        List<AppEvent> list = new ArrayList<>();
        // JOINTURE pour récupérer le first_name de l'utilisateur
        String sql = "SELECT e.*, u.first_name AS organizer_name " +
                "FROM APP_EVENT e " +
                "JOIN app_user u ON e.organizer_id = u.id";

        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                AppEvent e = mapResultSetToAppEvent(rs);
                // On récupère la colonne de la jointure
                e.setOrganizerName(rs.getString("organizer_name"));
                list.add(e);
            }
        }
        return list;
    }

    @Override
    public AppEvent getOne(int id) throws SQLException {
        String sql = "SELECT e.*, u.first_name AS organizer_name " +
                "FROM APP_EVENT e " +
                "JOIN app_user u ON e.organizer_id = u.id " +
                "WHERE e.id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    AppEvent e = mapResultSetToAppEvent(rs);
                    e.setOrganizerName(rs.getString("organizer_name"));
                    return e;
                }
            }
        }
        return null;
    }

    public void joinEvent(int eventId, int userId) throws SQLException {
        String insertTicketSql = "INSERT INTO ticket (event_id, user_id, ticket_type, price) VALUES (?, ?, 'STANDARD', 0.00)";
        String insertAttendanceSql = "INSERT INTO attendance (ticket_id, user_id, status) VALUES (?, ?, 'PENDING')";

        try {
            connection.setAutoCommit(false);
            int newTicketId = -1;
            try (PreparedStatement psTicket = connection.prepareStatement(insertTicketSql, Statement.RETURN_GENERATED_KEYS)) {
                psTicket.setInt(1, eventId);
                psTicket.setInt(2, userId);
                psTicket.executeUpdate();
                try (ResultSet rs = psTicket.getGeneratedKeys()) {
                    if (rs.next()) newTicketId = rs.getInt(1);
                }
            }
            if (newTicketId != -1) {
                try (PreparedStatement psAtt = connection.prepareStatement(insertAttendanceSql)) {
                    psAtt.setInt(1, newTicketId);
                    psAtt.setInt(2, userId);
                    psAtt.executeUpdate();
                }
            }
            connection.commit();
        } catch (SQLException e) {
            connection.rollback();
            throw e;
        } finally {
            connection.setAutoCommit(true);
        }
    }

    public void leaveEvent(int eventId, int userId) throws SQLException {
        String sql = "DELETE FROM ticket WHERE event_id = ? AND user_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, eventId);
            ps.setInt(2, userId);
            ps.executeUpdate();
        }
    }

    public List<AppEvent> getJoinedEvents(int userId) throws SQLException {
        List<AppEvent> list = new ArrayList<>();
        String sql = "SELECT e.*, u.first_name AS organizer_name " +
                "FROM APP_EVENT e " +
                "INNER JOIN ticket t ON e.id = t.event_id " +
                "INNER JOIN app_user u ON e.organizer_id = u.id " +
                "WHERE t.user_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    AppEvent e = mapResultSetToAppEvent(rs);
                    e.setOrganizerName(rs.getString("organizer_name"));
                    list.add(e);
                }
            }
        }
        return list;
    }

    private AppEvent mapResultSetToAppEvent(ResultSet rs) throws SQLException {
        AppEvent e = new AppEvent();
        e.setId(rs.getInt("id"));
        e.setOrganizerId(rs.getInt("organizer_id"));
        e.setName(rs.getString("name"));
        e.setDescription(rs.getString("description"));
        e.setEventDate(rs.getTimestamp("event_date"));
        e.setLocation(rs.getString("location"));
        e.setMaxParticipants(rs.getInt("max_participants"));
        return e;
    }
}