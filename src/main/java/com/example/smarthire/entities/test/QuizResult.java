package com.example.smarthire.entities.test;

import java.sql.Timestamp;

public class QuizResult {
    private int id;
    private int quizId;
    private int candidateId;
    private int score;
    private Timestamp attemptDate;
    private boolean isPassed;
    private String quizTitle;
    private String candidateName;

    public QuizResult() {}

    public QuizResult(int quizId, int candidateId, int score, boolean isPassed) {
        this.quizId = quizId;
        this.candidateId = candidateId;
        this.score = score;
        this.isPassed = isPassed;
    }

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getQuizId() { return quizId; }
    public void setQuizId(int quizId) { this.quizId = quizId; }
    public int getCandidateId() { return candidateId; }
    public void setCandidateId(int candidateId) { this.candidateId = candidateId; }
    public int getScore() { return score; }
    public void setScore(int score) { this.score = score; }
    public Timestamp getAttemptDate() { return attemptDate; }
    public void setAttemptDate(Timestamp attemptDate) { this.attemptDate = attemptDate; }
    public boolean isPassed() { return isPassed; }
    public void setPassed(boolean passed) { isPassed = passed; }
    public String getQuizTitle() { return quizTitle; }
    public void setQuizTitle(String quizTitle) { this.quizTitle = quizTitle; }
    public String getCandidateName() { return candidateName; }
    public void setCandidateName(String candidateName) { this.candidateName = candidateName; }

    @Override
    public String toString() {
        return "Result: " + score + " - " + (isPassed ? "PASSED" : "FAILED");
    }
}
