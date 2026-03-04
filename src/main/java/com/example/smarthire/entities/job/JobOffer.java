package com.example.smarthire.entities.job;

import java.sql.Timestamp;

public class JobOffer {
    private int id;
    private int recruiterId; // Matches 'recruiter_id'
    private int categoryId;
    private String categoryName; // For UI
    private String title;
    private String description;
    private String location;
    private String salaryRange; // Matches 'salary_range' VARCHAR
    private String jobType;     // Matches 'job_type'
    private Timestamp postedDate;

    public JobOffer() {}

    public JobOffer(int recruiterId, int categoryId, String title, String description, String location, String salaryRange, String jobType) {
        this.recruiterId = recruiterId;
        this.categoryId = categoryId;
        this.title = title;
        this.description = description;
        this.location = location;
        this.salaryRange = salaryRange;
        this.jobType = jobType;
    }

    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getRecruiterId() { return recruiterId; }
    public void setRecruiterId(int recruiterId) { this.recruiterId = recruiterId; }
    public int getCategoryId() { return categoryId; }
    public void setCategoryId(int categoryId) { this.categoryId = categoryId; }
    public String getCategoryName() { return categoryName; }
    public void setCategoryName(String categoryName) { this.categoryName = categoryName; }
    public String getTitle() { return title; }
    public void setTitle(String title) { this.title = title; }
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    public String getLocation() { return location; }
    public void setLocation(String location) { this.location = location; }
    public String getSalaryRange() { return salaryRange; }
    public void setSalaryRange(String salaryRange) { this.salaryRange = salaryRange; }
    public String getJobType() { return jobType; }
    public void setJobType(String jobType) { this.jobType = jobType; }
    public Timestamp getPostedDate() { return postedDate; }
    public void setPostedDate(Timestamp postedDate) { this.postedDate = postedDate; }

    // toString for debugging
    @Override
    public String toString() { return title + " (" + location + ")"; }
}