package com.example.smarthire.entities.reclamation;

public class ReclaimType {
    private int id;
    private String name;
    private String urgencyLevel; // Changed from description

    public ReclaimType() {}

    public ReclaimType(int id, String name, String urgencyLevel) {
        this.id = id;
        this.name = name;
        this.urgencyLevel = urgencyLevel;
    }

    public ReclaimType(String name, String urgencyLevel) {
        this.name = name;
        this.urgencyLevel = urgencyLevel;
    }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public String getUrgencyLevel() { return urgencyLevel; }
    public void setUrgencyLevel(String urgencyLevel) { this.urgencyLevel = urgencyLevel; }

    @Override
    public String toString() {
        return name + " (" + urgencyLevel + ")"; // Useful for ListView display
    }
}