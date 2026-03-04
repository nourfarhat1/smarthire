package com.example.smarthire.entities.reclamation;

import java.sql.Timestamp;

public class Complaint {
    private int id;
    private int userId;
    private int typeId;
    private String subject;
    private String description;
    private String status;
    private Timestamp submissionDate;
    private String firstName;
    private String lastName;
    public Complaint() {}

    // Constructor used by Controller (Auto-sets defaults)
    public Complaint(int userId, int typeId, String subject, String description) {
        this.userId = userId;
        this.typeId = typeId;
        this.subject = subject;
        this.description = description;
        this.status = "OPEN";
        // We set the current time here so Java passes it to DB
        this.submissionDate = new Timestamp(System.currentTimeMillis());
    }

    // Full Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getUserId() { return userId; }
    public void setUserId(int userId) { this.userId = userId; }

    public String getFirstName() {return firstName;}

    public void setFirstName(String firstName) {this.firstName = firstName;}

    public String getLastName() {return lastName;}

    public void setLastName(String lastName) {this.lastName = lastName;}

    public int getTypeId() { return typeId; }
    public void setTypeId(int typeId) { this.typeId = typeId; }

    public String getSubject() { return subject; }
    public void setSubject(String subject) { this.subject = subject; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }

    public Timestamp getSubmissionDate() { return submissionDate; }
    public void setSubmissionDate(Timestamp submissionDate) { this.submissionDate = submissionDate; }
}