package com.example.smarthire.entities.job;

public class JobCategory {
    private int id;
    private String name;

    public JobCategory(int id, String name) {
        this.id = id;
        this.name = name;
    }

    public int getId() { return id; }
    public String getName() { return name; }

    @Override
    public String toString() { return name; } // Important for ComboBox display
}