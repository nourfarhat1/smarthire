package com.example.smarthire.entities.test;

public class Quiz {
    private int id;
    private int relatedJobId;
    private String title;
    private String description;
    private int durationMinutes;
    private int passingScore;

    public Quiz() {}

    public Quiz(int relatedJobId, String title, String description, int durationMinutes, int passingScore) {
        this.relatedJobId = relatedJobId;
        this.title = title;
        this.description = description;
        this.durationMinutes = durationMinutes;
        this.passingScore = passingScore;
    }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getRelatedJobId() { return relatedJobId; }
    public void setRelatedJobId(int relatedJobId) { this.relatedJobId = relatedJobId; }
    public String getTitle() { return title; }
    public void setTitle(String title) { this.title = title; }
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    public int getDurationMinutes() { return durationMinutes; }
    public void setDurationMinutes(int durationMinutes) { this.durationMinutes = durationMinutes; }
    public int getPassingScore() { return passingScore; }
    public void setPassingScore(int passingScore) { this.passingScore = passingScore; }

    @Override
    public String toString() { return title; }
}