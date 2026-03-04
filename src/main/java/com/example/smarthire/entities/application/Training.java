package com.example.smarthire.entities.application;



import java.sql.Timestamp;

public class Training {

    private int id;
    private String title;
    private String category;
    private String description;
    private String videoUrl;
    private int likes;
    private int dislikes;
    private Timestamp createdAt;
    private Integer adminId;
    private String adminName;


    public Training() {}

    public Training(String title, String category, String description,
                    String videoUrl, Integer adminId) {
        this.title = title;
        this.category = category;
        this.description = description;
        this.videoUrl = videoUrl;
        this.adminId = adminId;
    }

    // -------- GETTERS / SETTERS --------

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public String getTitle() { return title; }
    public void setTitle(String title) { this.title = title; }

    public String getCategory() { return category; }
    public void setCategory(String category) { this.category = category; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    public String getUrl() { return videoUrl; }
    public void setVideoUrl(String videoUrl) { this.videoUrl = videoUrl; }

    public int getLikes() { return likes; }
    public void setLikes(int likes) { this.likes = likes; }

    public int getDislikes() { return dislikes; }
    public void setDislikes(int dislikes) { this.dislikes = dislikes; }

    public Timestamp getCreatedAt() { return createdAt; }
    public void setCreatedAt(Timestamp createdAt) { this.createdAt = createdAt; }

    public Integer getAdminId() { return adminId; }
    public void setAdminId(Integer adminId) { this.adminId = adminId; }

    public String getAdminName() {
        return adminName;
    }

    public void setAdminName(String adminName) {
        this.adminName = adminName;
    }
    public void setUrl(String videoUrl) {
        this.videoUrl = videoUrl;
    }
}