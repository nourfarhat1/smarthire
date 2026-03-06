package com.example.smarthire.services;

import com.google.api.client.util.DateTime;
import com.google.api.services.calendar.Calendar;
import com.google.api.services.calendar.model.*;

import java.io.IOException;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.util.Collections;
import java.util.UUID;

public class GoogleCalendarService {

    private Calendar calendarService;

    // --- LE CONSTRUCTEUR VIDE (Celui que tu cherchais) ---
    public GoogleCalendarService() {
        try {
            // Pour l'instant on laisse vide ou on initialise la connexion Google ici
            System.out.println("Google Calendar Service Initialized.");
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // --- LA MÉTHODE POUR CRÉER L'ÉVÉNEMENT ---
    public String createInterviewEvent(String candidateEmail, String jobTitle, LocalDateTime startLDT, boolean isOnline) {
        try {
            // NOTE : Si calendarService est null (pas encore connecté à Google),
            // on retourne un lien de test pour que ton code ne plante pas.
            if (calendarService == null) {
                System.out.println("Simulating Google Meet Link (No API Connection yet)");
                return "https://meet.google.com/abc-mock-link";
            }

            Event event = new Event()
                    .setSummary("Entretien SmartHire : " + jobTitle)
                    .setDescription("Entretien de recrutement via la plateforme SmartHire.");

            // Dates
            DateTime start = new DateTime(startLDT.atZone(ZoneId.systemDefault()).toInstant().toEpochMilli());
            event.setStart(new EventDateTime().setDateTime(start));

            // Fin de l'entretien (+45 min)
            DateTime end = new DateTime(startLDT.plusMinutes(45).atZone(ZoneId.systemDefault()).toInstant().toEpochMilli());
            event.setEnd(new EventDateTime().setDateTime(end));

            // Ajouter le candidat
            EventAttendee attendee = new EventAttendee().setEmail(candidateEmail);
            event.setAttendees(Collections.singletonList(attendee));

            // Meet Link
            if (isOnline) {
                ConferenceData conferenceData = new ConferenceData();
                CreateConferenceRequest createRequest = new CreateConferenceRequest()
                        .setRequestId(UUID.randomUUID().toString())
                        .setConferenceSolutionKey(new ConferenceSolutionKey().setType("hangoutsMeet"));
                conferenceData.setCreateRequest(createRequest);
                event.setConferenceData(conferenceData);
            }

            Event createdEvent = calendarService.events().insert("primary", event)
                    .setConferenceDataVersion(1)
                    .setSendUpdates("all")
                    .execute();

            return createdEvent.getHangoutLink();
        } catch (IOException e) {
            e.printStackTrace();
            return null;
        }
    }
}