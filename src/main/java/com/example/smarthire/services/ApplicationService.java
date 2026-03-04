package com.example.smarthire.services;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.utils.MyDatabase;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ApplicationService {
    private Connection connection;

    public ApplicationService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    // --- 1. SAVED JOBS FEATURE ---

    public void saveJob(int userId, int jobId) throws SQLException {
        String sql = "INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            ps.setInt(2, jobId);
            ps.executeUpdate();
        } catch (SQLException e) {
            if (e.getErrorCode() == 1062) {
                throw new SQLException("You have already saved this job!");
            }
            throw e;
        }
    }

    public void unsaveJob(int userId, int jobId) throws SQLException {
        String sql = "DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            ps.setInt(2, jobId);
            ps.executeUpdate();
        }
    }

    public int getSavedJobsCount(int userId) throws SQLException {
        String sql = "SELECT COUNT(*) FROM saved_jobs WHERE user_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) return rs.getInt(1);
            }
        }
        return 0;
    }

    public List<JobOffer> getSavedJobs(int userId) throws SQLException {
        List<JobOffer> list = new ArrayList<>();
        String sql = "SELECT jo.* FROM job_offer jo " +
                "JOIN saved_jobs sj ON jo.id = sj.job_id " +
                "WHERE sj.user_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JobOffer offer = new JobOffer();
                    offer.setId(rs.getInt("id"));
                    offer.setTitle(rs.getString("title"));
                    offer.setLocation(rs.getString("location"));
                    offer.setJobType(rs.getString("job_type"));
                    offer.setSalaryRange(rs.getString("salary_range"));
                    offer.setCategoryId(rs.getInt("category_id"));
                    list.add(offer);
                }
            }
        }
        return list;
    }

    // --- 2. CREATE APPLICATION ---

    public void apply(JobRequest request) throws SQLException {
        String sql = "INSERT INTO job_request (candidate_id, job_offer_id, status, cv_url, cover_letter) VALUES (?, ?, 'PENDING', ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, request.getCandidateId());
            if (request.getJobOfferId() == 0) ps.setNull(2, Types.INTEGER);
            else ps.setInt(2, request.getJobOfferId());
            ps.setString(3, request.getCvUrl());
            ps.setString(4, request.getCoverLetter());
            ps.executeUpdate();
        }
    }

    // --- 3. READ (Candidate View) ---

    public List<JobRequest> getByCandidate(int candidateId) throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        String sql = "SELECT r.*, j.title FROM job_request r LEFT JOIN job_offer j ON r.job_offer_id = j.id WHERE r.candidate_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, candidateId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JobRequest req = mapRow(rs);
                    req.setJobTitle(rs.getString("title") == null ? "Spontaneous Application" : rs.getString("title"));
                    list.add(req);
                }
            }
        }
        return list;
    }

    // --- 4. READ (HR View) - UPDATED TO INCLUDE EMAIL ---

    public List<JobRequest> getForHR(int recruiterId) throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        // Added u.email to the SELECT
        String sql = "SELECT r.*, j.title, u.first_name, u.last_name, u.email " +
                "FROM job_request r " +
                "JOIN app_user u ON r.candidate_id = u.id " +
                "LEFT JOIN job_offer j ON r.job_offer_id = j.id " +
                "WHERE (j.recruiter_id = ? OR r.job_offer_id IS NULL) " +
                "ORDER BY r.submission_date DESC";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, recruiterId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JobRequest req = mapRow(rs);
                    req.setJobTitle(rs.getString("title") == null ? "⭐ Spontaneous Application" : rs.getString("title"));
                    req.setCandidateName(rs.getString("first_name") + " " + rs.getString("last_name"));
                    // Set the candidate email for notifications
                    req.setCandidateEmail(rs.getString("email"));
                    list.add(req);
                }
            }
        }
        return list;
    }

    // --- 5. ADMIN / UPDATE / DELETE - UPDATED TO INCLUDE EMAIL ---

    public List<JobRequest> getAll() throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        // Added u.email to the SELECT
        String sql = "SELECT r.*, j.title, u.first_name, u.last_name, u.email " +
                "FROM job_request r " +
                "JOIN app_user u ON r.candidate_id = u.id " +
                "LEFT JOIN job_offer j ON r.job_offer_id = j.id";
        try (PreparedStatement ps = connection.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                JobRequest req = mapRow(rs);
                req.setJobTitle(rs.getString("title") == null ? "Spontaneous" : rs.getString("title"));
                req.setCandidateName(rs.getString("first_name") + " " + rs.getString("last_name"));
                req.setCandidateEmail(rs.getString("email"));
                list.add(req);
            }
        }
        return list;
    }

    public void update(JobRequest req) throws SQLException {
        String sql = "UPDATE job_request SET cv_url=?, cover_letter=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, req.getCvUrl());
            ps.setString(2, req.getCoverLetter());
            ps.setInt(3, req.getId());
            ps.executeUpdate();
        }
    }

    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM job_request WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    // --- HELPERS ---

    private JobRequest mapRow(ResultSet rs) throws SQLException {
        JobRequest req = new JobRequest();
        req.setId(rs.getInt("id"));
        req.setCandidateId(rs.getInt("candidate_id"));
        int jobId = rs.getInt("job_offer_id");
        req.setJobOfferId(rs.wasNull() ? 0 : jobId);
        req.setStatus(rs.getString("status"));
        req.setCvUrl(rs.getString("cv_url"));
        req.setCoverLetter(rs.getString("cover_letter"));
        req.setSubmissionDate(rs.getTimestamp("submission_date"));
        return req;
    }
}