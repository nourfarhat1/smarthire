package com.example.smarthire.services;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.utils.MyDatabase;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ApplicationService {
    private Connection connection;

    public ApplicationService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    // 1. CREATE (Standard & Spontaneous)
    public void apply(JobRequest request) throws SQLException {
        // If jobOfferId is 0, it's spontaneous. logic:
        String sql = "INSERT INTO job_request (candidate_id, job_offer_id, status, cv_url, cover_letter) VALUES (?, ?, 'PENDING', ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, request.getCandidateId());
            if (request.getJobOfferId() == 0) ps.setNull(2, Types.INTEGER); // Spontaneous
            else ps.setInt(2, request.getJobOfferId());
            ps.setString(3, request.getCvUrl());
            ps.setString(4, request.getCoverLetter());
            ps.executeUpdate();
        }
    }

    // 2. READ (For Candidate: My Applications)
    public List<JobRequest> getByCandidate(int candidateId) throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        // Left Join to get Job Title even if null (Spontaneous)
        String sql = "SELECT r.*, j.title FROM job_request r LEFT JOIN job_offer j ON r.job_offer_id = j.id WHERE r.candidate_id = ?";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, candidateId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JobRequest req = mapRow(rs);
                    // Use a transient field or helper to store the title for the UI
                    req.setJobTitle(rs.getString("title") == null ? "Spontaneous Application" : rs.getString("title"));
                    list.add(req);
                }
            }
        }
        return list;
    }

    // 4. UPDATE (Edit Application)
    public void update(JobRequest req) throws SQLException {
        String sql = "UPDATE job_request SET cv_url=?, cover_letter=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, req.getCvUrl());
            ps.setString(2, req.getCoverLetter());
            ps.setInt(3, req.getId());
            ps.executeUpdate();
        }
    }

    // 5. DELETE
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM job_request WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    private JobRequest mapRow(ResultSet rs) throws SQLException {
        JobRequest req = new JobRequest();
        req.setId(rs.getInt("id"));
        req.setCandidateId(rs.getInt("candidate_id"));
        req.setJobOfferId(rs.getInt("job_offer_id")); // Returns 0 if NULL
        req.setStatus(rs.getString("status"));
        req.setCvUrl(rs.getString("cv_url"));
        req.setCoverLetter(rs.getString("cover_letter"));
        req.setSubmissionDate(rs.getTimestamp("submission_date"));
        return req;
    }
    public List<JobRequest> getForHR(int recruiterId) throws SQLException {
        List<JobRequest> list = new ArrayList<>();

        // LOGIC: Select requests where the job belongs to this recruiter OR the request is Spontaneous (job_offer_id IS NULL)
        // We join with `app_user` to get candidate name.
        // We LEFT JOIN with `job_offer` to get job title (which might be null for spontaneous).

        String sql = "SELECT r.*, j.title, u.first_name, u.last_name " +
                "FROM job_request r " +
                "JOIN app_user u ON r.candidate_id = u.id " +
                "LEFT JOIN job_offer j ON r.job_offer_id = j.id " +
                "WHERE (j.recruiter_id = ? OR r.job_offer_id IS NULL) " +
                "ORDER BY r.submission_date DESC";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, recruiterId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JobRequest req = new JobRequest();
                    req.setId(rs.getInt("id"));
                    req.setCandidateId(rs.getInt("candidate_id"));
                    // Handle NULL Job ID safely
                    int jobId = rs.getInt("job_offer_id");
                    if (rs.wasNull()) req.setJobOfferId(0); // 0 or -1 to indicate null
                    else req.setJobOfferId(jobId);

                    req.setStatus(rs.getString("status"));
                    req.setCvUrl(rs.getString("cv_url"));
                    req.setCoverLetter(rs.getString("cover_letter"));
                    req.setSubmissionDate(rs.getTimestamp("submission_date"));

                    // UI Helpers
                    String jobTitle = rs.getString("title");
                    req.setJobTitle(jobTitle == null ? "⭐ Spontaneous Application" : jobTitle);

                    req.setCandidateName(rs.getString("first_name") + " " + rs.getString("last_name"));

                    list.add(req);
                }
            }
        }
        return list;
    }
    // Get ALL applications (for Admin)
    public List<JobRequest> getAll() throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        String sql = "SELECT r.*, j.title, u.first_name, u.last_name " +
                "FROM job_request r " +
                "JOIN app_user u ON r.candidate_id = u.id " +
                "LEFT JOIN job_offer j ON r.job_offer_id = j.id";

        try (PreparedStatement ps = connection.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {

            while (rs.next()) {
                JobRequest req = new JobRequest();
                req.setId(rs.getInt("id"));
                req.setCandidateId(rs.getInt("candidate_id"));
                req.setJobOfferId(rs.getInt("job_offer_id"));
                req.setStatus(rs.getString("status"));
                req.setCvUrl(rs.getString("cv_url"));
                req.setCoverLetter(rs.getString("cover_letter"));
                req.setSubmissionDate(rs.getTimestamp("submission_date"));

                // UI Helpers
                req.setJobTitle(rs.getString("title") == null ? "Spontaneous" : rs.getString("title"));
                req.setCandidateName(rs.getString("first_name") + " " + rs.getString("last_name"));

                list.add(req);
            }
        }
        return list;
    }
}