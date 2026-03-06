package com.example.smarthire.services;

import com.example.smarthire.entities.application.JobRequest;
import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.utils.MyDatabase;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ApplicationService {

    private final Connection connection;

    public ApplicationService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    // --- SAVED JOBS FEATURE ---

    public void saveJob(int userId, int jobId) throws SQLException {
        String sql = "INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            ps.setInt(2, jobId);
            ps.executeUpdate();
        } catch (SQLException e) {
            if (e.getErrorCode() == 1062) { // Duplicate entry
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

    // --- CREATE APPLICATION (Normal or Spontaneous) ---
    public void apply(JobRequest request) throws SQLException {
        String sql = "INSERT INTO job_request " +
                "(candidate_id, job_offer_id, job_title, location, status, cv_url, cover_letter, suggested_salary, categorie) " +
                "VALUES (?, ?, ?, ?, 'PENDING', ?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, request.getCandidateId());

            if (request.getJobOfferId() == null || request.getJobOfferId() == 0) {
                ps.setNull(2, Types.INTEGER); // spontaneous
            } else {
                ps.setInt(2, request.getJobOfferId());
            }

            ps.setString(3, request.getJobTitle());
            ps.setString(4, request.getLocation());
            ps.setString(5, request.getCvUrl());
            ps.setString(6, request.getCoverLetter());

            if (request.getSuggestedSalary() == null) ps.setNull(7, Types.DOUBLE);
            else ps.setDouble(7, request.getSuggestedSalary());
            ps.setString(8, request.getCategorie());

            ps.executeUpdate();
        }
    }

    // --- GET ALL APPLICATIONS FOR A CANDIDATE ---
    public List<JobRequest> getByCandidate(int candidateId) throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        String sql = "SELECT r.*, j.title AS offer_title, u.first_name, u.last_name, u.email " +
                "FROM job_request r " +
                "LEFT JOIN job_offer j ON r.job_offer_id = j.id " +
                "JOIN app_user u ON r.candidate_id = u.id " +
                "WHERE r.candidate_id = ? " +
                "ORDER BY r.submission_date DESC";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, candidateId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JobRequest req = mapRow(rs);
                    fallbackSpontaneousTitle(req, rs);
                    req.setCandidateName(rs.getString("first_name") + " " + rs.getString("last_name"));
                    req.setCandidateEmail(rs.getString("email"));
                    list.add(req);
                }
            }
        }
        return list;
    }

    // --- GET APPLICATIONS FOR HR ---
    public List<JobRequest> getForHR(int recruiterId) throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        String sql = "SELECT r.*, j.title AS offer_title, u.first_name, u.last_name, u.email " +
                "FROM job_request r " +
                "LEFT JOIN job_offer j ON r.job_offer_id = j.id " +
                "JOIN app_user u ON r.candidate_id = u.id " +
                "WHERE j.recruiter_id=? OR r.job_offer_id IS NULL " +
                "ORDER BY r.submission_date DESC";

        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, recruiterId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    JobRequest req = mapRow(rs);
                    fallbackSpontaneousTitle(req, rs);
                    req.setCandidateName(rs.getString("first_name") + " " + rs.getString("last_name"));
                    req.setCandidateEmail(rs.getString("email"));
                    list.add(req);
                }
            }
        }
        return list;
    }

    // --- GET ALL APPLICATIONS (ADMIN) ---
    public List<JobRequest> getAll() throws SQLException {
        List<JobRequest> list = new ArrayList<>();
        String sql = "SELECT r.*, j.title AS offer_title, u.first_name, u.last_name, u.email " +
                "FROM job_request r " +
                "LEFT JOIN job_offer j ON r.job_offer_id = j.id " +
                "JOIN app_user u ON r.candidate_id = u.id " +
                "ORDER BY r.submission_date DESC";

        try (PreparedStatement ps = connection.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                JobRequest req = mapRow(rs);
                fallbackSpontaneousTitle(req, rs);
                req.setCandidateName(rs.getString("first_name") + " " + rs.getString("last_name"));
                req.setCandidateEmail(rs.getString("email"));
                list.add(req);
            }
        }
        return list;
    }

    // --- UPDATE APPLICATION ---
    public void update(JobRequest req) throws SQLException {
        String sql = "UPDATE job_request SET cv_url=?, cover_letter=?, job_title=?, location=?, suggested_salary=?, categorie=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, req.getCvUrl());
            ps.setString(2, req.getCoverLetter());
            ps.setString(3, req.getJobTitle());
            ps.setString(4, req.getLocation());

            if (req.getSuggestedSalary() == null)
                ps.setNull(5, Types.DOUBLE);
            else
                ps.setDouble(5, req.getSuggestedSalary());

            ps.setString(6, req.getCategorie());
            ps.setInt(7, req.getId());
            ps.executeUpdate();
        }
    }

    // --- DELETE APPLICATION ---
    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM job_request WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    // --- HELPERS ---

    /** Map ResultSet to JobRequest */
    private JobRequest mapRow(ResultSet rs) throws SQLException {
        JobRequest req = new JobRequest();
        req.setId(rs.getInt("id"));
        req.setCandidateId(rs.getInt("candidate_id"));

        Integer jobId = rs.getObject("job_offer_id", Integer.class);
        req.setJobOfferId(jobId);

        req.setJobTitle(rs.getString("job_title"));
        req.setLocation(rs.getString("location"));
        req.setCategorie(rs.getString("categorie"));
        Double salary = rs.getObject("suggested_salary", Double.class);
        req.setSuggestedSalary(salary);

        req.setStatus(rs.getString("status"));
        req.setCvUrl(rs.getString("cv_url"));
        req.setCoverLetter(rs.getString("cover_letter"));
        req.setSubmissionDate(rs.getTimestamp("submission_date"));
        return req;
    }

    /** Fallback for spontaneous apps: ensure jobTitle is set */
    private void fallbackSpontaneousTitle(JobRequest req, ResultSet rs) throws SQLException {
        if (req.getJobTitle() == null || req.getJobTitle().isEmpty()) {
            String title = rs.getString("offer_title");
            req.setJobTitle(title == null ? "⭐ Spontaneous Application" : title);
        }
    }

    public boolean isSaved(int userId, int jobId) throws SQLException {
        String sql = "SELECT COUNT(*) FROM saved_jobs WHERE user_id = ? AND job_id = ?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, userId);
            ps.setInt(2, jobId);
            ResultSet rs = ps.executeQuery();
            if (rs.next()) {
                return rs.getInt(1) > 0;
            }
        }
        return false;
    }
}