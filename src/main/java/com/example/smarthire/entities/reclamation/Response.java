package com.example.smarthire.entities.reclamation;

import java.sql.Timestamp;

public class Response {
    private int id;
    private int complaintId;
    private String message;
    private Timestamp responseDate;
    private String adminName;

    public Response() {}

    public Response(int complaintId, String message, String adminName) {
        this.complaintId = complaintId;
        this.message = message;
        this.adminName = adminName;
        this.responseDate = new Timestamp(System.currentTimeMillis());
    }

    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getComplaintId() { return complaintId; }
    public void setComplaintId(int complaintId) { this.complaintId = complaintId; }
    public String getMessage() { return message; }
    public void setMessage(String message) { this.message = message; }
    public Timestamp getResponseDate() { return responseDate; }
    public void setResponseDate(Timestamp responseDate) { this.responseDate = responseDate; }
    public String getAdminName() { return adminName; }
    public void setAdminName(String adminName) { this.adminName = adminName; }
}