package com.example.smarthire.entities.application;

import java.sql.Timestamp;
import java.text.SimpleDateFormat;

public class JobRequest {

    private int id;
    private int candidateId;
    private Integer jobOfferId; // Nullable for spontaneous apps
    private Timestamp submissionDate;
    private String status;
    private String cvUrl;
    private String coverLetter;
    private String jobTitle;          // REQUIRED for spontaneous apps
    private String location;          // OPTIONAL
    private Double suggestedSalary;   // OPTIONAL, from price API
    private String categorie;
    // UI Helper
    private String candidateName;
    private String candidateEmail;    // Added from second file

    public JobRequest() {}

    public JobRequest(int candidateId, Integer jobOfferId, String jobTitle, String location,
                      String cvUrl, String coverLetter, Double suggestedSalary, String categorie) {
        this.candidateId = candidateId;
        this.jobOfferId = jobOfferId;
        this.jobTitle = jobTitle;
        this.location = location;
        this.cvUrl = cvUrl;
        this.coverLetter = coverLetter;
        this.suggestedSalary = suggestedSalary;
        this.categorie = categorie;
        this.status = "PENDING";
    }

    // --- GETTERS / SETTERS ---
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getCandidateId() { return candidateId; }
    public void setCandidateId(int candidateId) { this.candidateId = candidateId; }

    public Integer getJobOfferId() { return jobOfferId; }
    public void setJobOfferId(Integer jobOfferId) { this.jobOfferId = jobOfferId; }

    public Timestamp getSubmissionDate() { return submissionDate; }
    public void setSubmissionDate(Timestamp submissionDate) { this.submissionDate = submissionDate; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }

    public String getCvUrl() { return cvUrl; }
    public void setCvUrl(String cvUrl) { this.cvUrl = cvUrl; }

    public String getCoverLetter() { return coverLetter; }
    public void setCoverLetter(String coverLetter) { this.coverLetter = coverLetter; }

    public String getJobTitle() { return jobTitle; }
    public void setJobTitle(String jobTitle) { this.jobTitle = jobTitle; }

    public String getLocation() { return location; }
    public void setLocation(String location) { this.location = location; }

    public Double getSuggestedSalary() { return suggestedSalary; }
    public void setSuggestedSalary(Double suggestedSalary) { this.suggestedSalary = suggestedSalary; }

    public String getCandidateName() { return candidateName; }
    public void setCandidateName(String candidateName) { this.candidateName = candidateName; }

    public String getCandidateEmail() { return candidateEmail; }
    public void setCandidateEmail(String candidateEmail) { this.candidateEmail = candidateEmail; }

    public String getSubmissionDateString() {
        if (submissionDate == null) return "";
        return new SimpleDateFormat("dd/MM/yyyy HH:mm").format(submissionDate);
    }
    public String getCategorie() { return categorie; }
    public void setCategorie(String categorie) { this.categorie = categorie; }
}