package com.example.smarthire.entities.application;

import java.sql.Timestamp;

public class JobRequest {
    private int id;
    private int candidateId;
    private int jobOfferId;
    private Timestamp submissionDate;
    private String status;
    private String cvUrl;
    private String coverLetter;

    // UI Helpers (Not in DB, but used for TableView display)
    private String jobTitle;
    private String candidateName;

    public JobRequest() {}

    public JobRequest(int candidateId, int jobOfferId, String cvUrl, String coverLetter) {
        this.candidateId = candidateId;
        this.jobOfferId = jobOfferId;
        this.cvUrl = cvUrl;
        this.coverLetter = coverLetter;
        this.status = "PENDING";
    }

    // --- GETTERS AND SETTERS (This fixes the red text) ---

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getCandidateId() { return candidateId; }
    public void setCandidateId(int candidateId) { this.candidateId = candidateId; }

    public int getJobOfferId() { return jobOfferId; }
    public void setJobOfferId(int jobOfferId) { this.jobOfferId = jobOfferId; }

    public Timestamp getSubmissionDate() { return submissionDate; }
    public void setSubmissionDate(Timestamp submissionDate) { this.submissionDate = submissionDate; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }

    public String getCvUrl() { return cvUrl; }
    public void setCvUrl(String cvUrl) { this.cvUrl = cvUrl; }

    public String getCoverLetter() { return coverLetter; }
    public void setCoverLetter(String coverLetter) { this.coverLetter = coverLetter; }

    // --- UI Helper Getters/Setters ---

    public String getJobTitle() { return jobTitle; }
    public void setJobTitle(String jobTitle) { this.jobTitle = jobTitle; }

    public String getCandidateName() { return candidateName; }
    public void setCandidateName(String candidateName) { this.candidateName = candidateName; }
}