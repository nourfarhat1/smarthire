package com.example.smarthire.entities.application;

import java.sql.Timestamp;

public class Interview {
    private int id;
    private int jobRequestId;
    private Timestamp dateTime;
    private String location;
    private String notes;
    private String status; // SCHEDULED, COMPLETED

    public Interview() {}

    public Interview(int jobRequestId, Timestamp dateTime, String location) {
        this.jobRequestId = jobRequestId;
        this.dateTime = dateTime;
        this.location = location;
        this.status = "SCHEDULED";
    }

    // Getters and Setters...
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getJobRequestId() { return jobRequestId; }
    public void setJobRequestId(int jobRequestId) { this.jobRequestId = jobRequestId; }
    public Timestamp getDateTime() { return dateTime; }
    public void setDateTime(Timestamp dateTime) { this.dateTime = dateTime; }
    public String getLocation() { return location; }
    public void setLocation(String location) { this.location = location; }
    public String getNotes() { return notes; }
    public void setNotes(String notes) { this.notes = notes; }
    public String getStatus() { return status; }
    public void setStatus(String status) { this.status = status; }
}