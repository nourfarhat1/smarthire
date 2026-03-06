package com.example.smarthire.entities.event;

import java.sql.Timestamp;

public class AppEvent {
    private int id;
    private int organizerId;
    private String organizerName; // Champ pour le nom (utilisé pour l'affichage Admin)
    private String name;
    private String description;
    private Timestamp eventDate;
    private String location;
    private int maxParticipants;

    // 1. Constructeur par défaut (Requis par JDBC et certains frameworks)
    public AppEvent() {}

    // 2. Constructeur avec paramètres (Celui qui manque dans ton erreur)
    // Note : On ne met pas l'ID ici car il est souvent AUTO_INCREMENT dans la DB
    public AppEvent(int organizerId, String name, String description, Timestamp eventDate, String location, int maxParticipants) {
        this.organizerId = organizerId;
        this.name = name;
        this.description = description;
        this.eventDate = eventDate;
        this.location = location;
        this.maxParticipants = maxParticipants;
    }

    // --- Getters et Setters ---

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public int getOrganizerId() { return organizerId; }
    public void setOrganizerId(int organizerId) { this.organizerId = organizerId; }

    public String getOrganizerName() { return organizerName; }
    public void setOrganizerName(String organizerName) { this.organizerName = organizerName; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    public Timestamp getEventDate() { return eventDate; }
    public void setEventDate(Timestamp eventDate) { this.eventDate = eventDate; }

    public String getLocation() { return location; }
    public void setLocation(String location) { this.location = location; }

    public int getMaxParticipants() { return maxParticipants; }
    public void setMaxParticipants(int maxParticipants) { this.maxParticipants = maxParticipants; }
}