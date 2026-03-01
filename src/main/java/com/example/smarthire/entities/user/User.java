package com.example.smarthire.entities.user;

import java.sql.Timestamp;

public class User {
    private int id;
    private int roleId; // Links to USER_ROLE table
    private String email;
    private String passwordHash;
    private String firstName;
    private String lastName;
    private String phoneNumber;
    private boolean isVerified;
    private boolean isBanned;
    private Timestamp createdAt;

    // Constructors
    public User() {}

    public User(int roleId, String email, String passwordHash, String firstName, String lastName, String phoneNumber) {
        this.roleId = roleId;
        this.email = email;
        this.passwordHash = passwordHash;
        this.firstName = firstName;
        this.lastName = lastName;
        this.phoneNumber = phoneNumber;
    }

    // Getters and Setters (Generate these in your IDE: Right Click -> Generate -> Getters & Setters)
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getRoleId() { return roleId; }
    public void setRoleId(int roleId) { this.roleId = roleId; }

    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }

    public String getPasswordHash() { return passwordHash; }
    public void setPasswordHash(String passwordHash) { this.passwordHash = passwordHash; }

    public String getFirstName() { return firstName; }
    public void setFirstName(String firstName) { this.firstName = firstName; }

    public String getLastName() { return lastName; }
    public void setLastName(String lastName) { this.lastName = lastName; }

    public String getPhoneNumber() { return phoneNumber; }
    public void setPhoneNumber(String phoneNumber) { this.phoneNumber = phoneNumber; }

    public boolean isVerified() { return isVerified; }
    public void setVerified(boolean verified) { isVerified = verified; }

    public boolean isBanned() { return isBanned; }
    public void setBanned(boolean banned) { isBanned = banned; }

    public Timestamp getCreatedAt() { return createdAt; }
    public void setCreatedAt(Timestamp createdAt) { this.createdAt = createdAt; }
    public String getRoleName() {
        switch (this.roleId) {
            case 1: return "CANDIDATE"; // Assuming ID 1 is Candidate
            case 2: return "HR";        // Assuming ID 2 is HR
            case 3: return "ADMIN";     // Assuming ID 3 is Admin
            default: return "GUEST";
        }
}}