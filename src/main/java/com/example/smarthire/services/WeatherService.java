package com.example.smarthire.services;

import org.json.JSONObject;

import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Scanner;

public class WeatherService {

    public String[] getWeather(String location) {
        try {
            // Clean the location string
            String city = location.split(",")[0].trim().replace(" ", "%20");
            String urlString = "https://api.openweathermap.org/data/2.5/weather?q="
                    + city + "&appid=" + API_KEY + "&units=metric";

            // DEBUG: See the exact URL being requested
            System.out.println("--- Weather Debug Start ---");
            System.out.println("Input Location: " + location);
            System.out.println("Requesting URL: " + urlString);

            URL url = new URL(urlString);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");

            if (conn.getResponseCode() != 200) {
                System.out.println("Error: API returned HTTP " + conn.getResponseCode());
                return null;
            }

            Scanner sc = new Scanner(url.openStream());
            StringBuilder sb = new StringBuilder();
            while (sc.hasNext()) sb.append(sc.nextLine());
            sc.close();

            JSONObject data = new JSONObject(sb.toString());

            // DEBUG: Extract info about the location the API actually found
            String resultCity = data.getString("name");
            String resultCountry = data.getJSONObject("sys").getString("country");
            double lat = data.getJSONObject("coord").getDouble("lat");
            double lon = data.getJSONObject("coord").getDouble("lon");

            System.out.println("API Found Location: " + resultCity + ", " + resultCountry);
            System.out.println("Coordinates: Lat " + lat + ", Lon " + lon);
            System.out.println("--- Weather Debug End ---");

            double temp = data.getJSONObject("main").getDouble("temp");
            String desc = data.getJSONArray("weather").getJSONObject(0).getString("description");

            return new String[]{String.valueOf(temp), desc};
        } catch (Exception e) {
            System.out.println("Exception in WeatherService: " + e.getMessage());
            e.printStackTrace();
            return null;
        }
    }
}