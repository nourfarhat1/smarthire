package com.example.smarthire.entities.user;

public class Role {
    private int id;
    private String roleName;
    private String description;

    // Constructors
    public Role() {}

    public Role(int id, String roleName, String description) {
        this.id = id;
        this.roleName = roleName;
        this.description = description;
    }

    // Getters and Setters
    public int getId() { return id; }
    public void setId(int id) { this.id = id; }

    public String getRoleName() { return roleName; }
    public void setRoleName(String roleName) { this.roleName = roleName; }

    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }

    // toString helps with debugging and ComboBoxes
    @Override
    public String toString() {
        return roleName;
    }
}