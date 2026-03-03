package com.example.smarthire.services;

import com.example.smarthire.entities.job.JobCategory;
import com.example.smarthire.entities.job.JobOffer;
import com.example.smarthire.utils.MyDatabase;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class JobService {
    private Connection connection;

    public JobService() {
        connection = MyDatabase.getInstance().getConnection();
    }

    // Fetch Categories for the Dropdown
    public List<JobCategory> getCategories() throws SQLException {
        List<JobCategory> list = new ArrayList<>();
        String sql = "SELECT * FROM job_category";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                list.add(new JobCategory(rs.getInt("id"), rs.getString("name")));
            }
        }
        return list;
    }

    public void add(JobOffer offer) throws SQLException {
        String sql = "INSERT INTO job_offer (recruiter_id, category_id, title, description, location, salary_range, job_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, offer.getRecruiterId());
            ps.setInt(2, offer.getCategoryId());
            ps.setString(3, offer.getTitle());
            ps.setString(4, offer.getDescription());
            ps.setString(5, offer.getLocation());
            ps.setString(6, offer.getSalaryRange());
            ps.setString(7, offer.getJobType());
            ps.executeUpdate();
        }
    }

    public List<JobOffer> getAll() throws SQLException {
        List<JobOffer> offers = new ArrayList<>();
        // JOIN to get Category Name
        String sql = "SELECT j.*, c.name as cat_name FROM job_offer j LEFT JOIN job_category c ON j.category_id = c.id ORDER BY j.posted_date DESC";
        try (Statement st = connection.createStatement(); ResultSet rs = st.executeQuery(sql)) {
            while (rs.next()) {
                JobOffer o = new JobOffer();
                o.setId(rs.getInt("id"));
                o.setRecruiterId(rs.getInt("recruiter_id"));
                o.setCategoryId(rs.getInt("category_id"));
                o.setCategoryName(rs.getString("cat_name")); // Display Name
                o.setTitle(rs.getString("title"));
                o.setDescription(rs.getString("description"));
                o.setLocation(rs.getString("location"));
                o.setSalaryRange(rs.getString("salary_range"));
                o.setJobType(rs.getString("job_type"));
                o.setPostedDate(rs.getTimestamp("posted_date"));
                offers.add(o);
            }
        }
        return offers;
    }
    public void update(JobOffer offer) throws SQLException {
        String sql = "UPDATE job_offer SET title=?, description=?, location=?, salary_range=?, job_type=?, category_id=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, offer.getTitle());
            ps.setString(2, offer.getDescription());
            ps.setString(3, offer.getLocation());
            ps.setString(4, offer.getSalaryRange());
            ps.setString(5, offer.getJobType());
            ps.setInt(6, offer.getCategoryId());
            ps.setInt(7, offer.getId());
            ps.executeUpdate();
        }
    }

    public void delete(int id) throws SQLException {
        String sql = "DELETE FROM job_offer WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }
    public void addCategory(String categoryName) throws SQLException {
        String sql = "INSERT INTO job_category (name) VALUES (?)";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            ps.setString(1, categoryName);
            ps.executeUpdate();
        }
    }
}