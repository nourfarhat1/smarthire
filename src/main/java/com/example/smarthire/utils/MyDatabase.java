package com.example.smarthire.utils;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class MyDatabase {

    private final String URL = "jdbc:mysql://localhost:3306/smarthire";
    private final String USER = "root";
    private final String PASSWORD = "";

    // 2. Singleton Instance
    private static MyDatabase instance;
    private Connection connection;

    // 3. Private Constructor (Connects to DB)
    private MyDatabase() {
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
            connection = DriverManager.getConnection(URL, USER, PASSWORD);
            System.out.println("✅ Database Connected Successfully!");

        } catch (ClassNotFoundException | SQLException e) {
            System.err.println("❌ Database Connection Failed: " + e.getMessage());
        }
    }

    public static MyDatabase getInstance() {
        if (instance == null) {
            instance = new MyDatabase();
        }
        return instance;
    }
    public Connection getConnection() {
        return connection;
    }
}
