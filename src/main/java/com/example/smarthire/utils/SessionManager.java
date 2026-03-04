package com.example.smarthire.utils;

import com.example.smarthire.entities.user.User;

public class SessionManager {
    private static SessionManager instance;
    private static User currentUser;

    // 1. Set User (Login)
    public static void setUser(User user) {
        currentUser = user;
    }

    // 2. Get User (To access ID, Name, etc. in other controllers)
    public static User getUser() {
        return currentUser;
    }
    public static SessionManager getInstance() {
        if (instance == null) {
            instance = new SessionManager();
        }
        return instance;
    }
    public static void saveUser(User user) {
        getInstance().currentUser = user;
    }
    // 3. Clear Session (Logout) - THIS WAS MISSING
    public static void clearSession() {
        currentUser = null;
    }

    // 4. Get Role Helper
    public static String getRole() {
        // Safety check to prevent NullPointerException
        if (currentUser == null) return "";

        // Return Role Name based on ID (Matches your DB logic: 1=Candidate, 2=HR, 3=Admin)
        return switch (currentUser.getRoleId()) {
            case 1 -> "CANDIDATE";
            case 2 -> "HR";
            case 3 -> "ADMIN";
            default -> "UNKNOWN";
        };
    }
}