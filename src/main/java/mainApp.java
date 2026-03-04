package com.example.smarthire;

import com.example.smarthire.entities.user.User;
import com.example.smarthire.services.UserService;
import com.example.smarthire.utils.MyDatabase;

import java.sql.SQLException;
import java.util.List;

public class mainApp {
    public static void main(String[] args) {
        System.out.println("--- STARTING QUICK DATABASE TEST ---");
            if (MyDatabase.getInstance().getConnection() != null) {
                System.out.println("✅ Database Connection: OK");
        System.out.println("------------------------------------------");
        System.out.println("--- END OF TEST - LAUNCHING APP ---");
        System.out.println("------------------------------------------");
        // Continue to launch the UI normally
        HelloApplication.main(args);
    }}}