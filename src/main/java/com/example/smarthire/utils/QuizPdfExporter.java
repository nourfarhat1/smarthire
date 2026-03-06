package com.example.smarthire.utils;

import com.example.smarthire.entities.test.Question;
import com.example.smarthire.entities.test.Quiz;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.pdmodel.PDPage;
import org.apache.pdfbox.pdmodel.PDPageContentStream;
import org.apache.pdfbox.pdmodel.common.PDRectangle;
import org.apache.pdfbox.pdmodel.font.PDType1Font;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Map;

public class QuizPdfExporter {

    // ── Page geometry ─────────────────────────────────────────────────────────
    private static final float PW = PDRectangle.A4.getWidth();
    private static final float PH = PDRectangle.A4.getHeight();
    private static final float ML = 45f;
    private static final float CW = PW - 2 * ML;

    // ── Palette ───────────────────────────────────────────────────────────────
    private static final float[] C_NAVY   = {0.102f, 0.180f, 0.412f};
    private static final float[] C_INDIGO = {0.220f, 0.286f, 0.671f};
    private static final float[] C_ACCENT = {0.380f, 0.447f, 0.941f};
    private static final float[] C_GREEN  = {0.129f, 0.529f, 0.200f};
    private static final float[] C_LGREEN = {0.902f, 0.976f, 0.914f};
    private static final float[] C_DGREEN = {0.067f, 0.392f, 0.137f};
    private static final float[] C_RED    = {0.780f, 0.086f, 0.235f};
    private static final float[] C_LRED   = {0.996f, 0.918f, 0.929f};
    private static final float[] C_DRED   = {0.600f, 0.047f, 0.145f};
    private static final float[] C_ORANGE = {0.800f, 0.400f, 0.000f};
    private static final float[] C_LORANGE= {1.000f, 0.961f, 0.914f};
    private static final float[] C_WHITE  = {1f, 1f, 1f};
    private static final float[] C_BG     = {0.969f, 0.973f, 0.984f};
    private static final float[] C_BORDER = {0.863f, 0.871f, 0.918f};
    private static final float[] C_TEXT   = {0.133f, 0.149f, 0.192f};
    private static final float[] C_MUTED  = {0.482f, 0.498f, 0.561f};
    private static final float[] C_LIGHT  = {0.827f, 0.835f, 0.882f};

    // ── Font aliases ──────────────────────────────────────────────────────────
    private static final PDType1Font FB  = PDType1Font.HELVETICA_BOLD;
    private static final PDType1Font FR  = PDType1Font.HELVETICA;
    private static final PDType1Font FI  = PDType1Font.HELVETICA_OBLIQUE;
    private static final PDType1Font FBI = PDType1Font.HELVETICA_BOLD_OBLIQUE;

    // ── State ─────────────────────────────────────────────────────────────────
    private PDDocument          doc;
    private PDPageContentStream cs;
    private float               y;

    // ═════════════════════════════════════════════════════════════════════════
    public byte[] generate(Quiz quiz,
                           List<Question> questions,
                           Map<Integer, String> userAnswers,
                           int score, boolean passed,
                           String candidateName) throws IOException {
        doc = new PDDocument();
        startPage();

        renderHeader(quiz, candidateName);
        renderScoreBanner(score, passed, quiz.getPassingScore(), questions, userAnswers);
        renderInfoRow(quiz, questions.size());
        renderSectionTitle("Detailed Results");

        for (int i = 0; i < questions.size(); i++)
            renderQuestionCard(i + 1, questions.size(),
                               questions.get(i), userAnswers.get(questions.get(i).getId()));

        closePage();
        addFooters();

        ByteArrayOutputStream baos = new ByteArrayOutputStream();
        doc.save(baos);
        doc.close();
        return baos.toByteArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void renderHeader(Quiz quiz, String candidateName) throws IOException {
        fillRect(0, PH - 62, PW, 62, C_NAVY);
        fillRect(0, PH - 68, PW, 6, C_ACCENT);

        text("SmartHire", FB, 22, ML, PH - 36, C_WHITE);
        float logoW = tw(FB, 22, "SmartHire");
        fillRect(ML + logoW + 10, PH - 54, 1, 26, blend(C_WHITE, C_NAVY, 0.55f));
        text("Quiz Result Report", FR, 11, ML + logoW + 18, PH - 38, blend(C_WHITE, C_ACCENT, 0.6f));
        text("Confidential - For candidate record only", FI, 8,
             ML + logoW + 18, PH - 54, blend(C_WHITE, C_NAVY, 0.45f));

        String dateStr = new SimpleDateFormat("dd MMM yyyy  |  HH:mm").format(new Date());
        text(dateStr, FR, 9, PW - ML - tw(FR, 9, dateStr), PH - 36, blend(C_WHITE, C_NAVY, 0.4f));
        String cand = "Candidate: " + candidateName;
        text(cand, FB, 9, PW - ML - tw(FB, 9, cand), PH - 52, blend(C_WHITE, C_ACCENT, 0.55f));

        y = PH - 68 - 18;
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void renderScoreBanner(int score, boolean passed, int passingScore,
                                   List<Question> questions,
                                   Map<Integer, String> userAnswers) throws IOException {
        int correct  = countCorrect(questions, userAnswers);
        int answered = countAnswered(userAnswers);
        int total    = questions.size();

        float[] bg   = passed ? C_LGREEN  : C_LRED;
        float[] fg   = passed ? C_GREEN   : C_RED;
        float[] side = passed ? C_DGREEN  : C_DRED;
        float bh = 96;

        fillRect(ML - 8, y - bh, CW + 16, bh, bg);
        strokeRect(ML - 8, y - bh, CW + 16, bh, C_BORDER, 0.5f);
        fillRect(ML - 8, y - bh, 5, bh, side);

        // large score
        String scoreStr = score + "%";
        float sw = tw(FB, 40, scoreStr);
        text(scoreStr, FB, 40, ML + 16, y - 34, fg);

        // badge
        String badge  = passed ? "PASSED" : "FAILED";
        float badgeX  = ML + 16 + sw + 14;
        float badgeW  = tw(FB, 11, badge) + 18;
        fillRect(badgeX, y - 50, badgeW, 24, fg);
        text(badge, FB, 11, badgeX + 9, y - 35, C_WHITE);

        // stats
        float col2 = badgeX + badgeW + 24;
        text("Correct Answers",  FR, 9, col2,       y - 22, C_MUTED);
        text(correct + " / " + total, FB, 14, col2, y - 40, C_TEXT);
        float col3 = col2 + 120;
        text("Pass Threshold",   FR, 9, col3,       y - 22, C_MUTED);
        text(passingScore + "%", FB, 14, col3,      y - 40, C_TEXT);
        float col4 = col3 + 100;
        text("Questions Answered", FR, 9, col4,     y - 22, C_MUTED);
        text(answered + " / " + total, FB, 14, col4, y - 40, C_TEXT);

        // progress bar
        float barY = y - bh + 14;
        float barW = CW - 18;
        float barX = ML + 1;
        fillRect(barX, barY, barW, 9, C_LIGHT);
        fillRect(barX, barY, Math.min(1f, score / 100f) * barW, 9, fg);

        // pass-mark tick
        float markX = barX + barW * Math.min(1f, passingScore / 100f);
        fillRect(markX - 1, barY - 1, 2, 11, side);
        String passLabel = "Pass: " + passingScore + "%";
        text(passLabel, FR, 7, markX - tw(FR, 7, passLabel) / 2, barY - 4, side);
        text("0%", FR, 7, barX, barY - 4, C_MUTED);
        text("100%", FR, 7, barX + barW - tw(FR, 7, "100%"), barY - 4, C_MUTED);

        if (answered == 0)
            text("(No per-question answer data stored - showing correct answers only)",
                 FI, 8, ML + 4, barY - 15, C_MUTED);

        y -= bh + 16;
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void renderInfoRow(Quiz quiz, int qCount) throws IOException {
        float rh = 44;
        fillRect(ML - 8, y - rh, CW + 16, rh, C_BG);
        strokeRect(ML - 8, y - rh, CW + 16, rh, C_BORDER, 0.4f);

        float x0 = ML + 4;
        text("QUIZ", FR, 8, x0, y - 12, C_MUTED);
        text(ellipsis(quiz.getTitle(), 38), FB, 11, x0, y - 28, C_NAVY);

        float x1 = x0 + 210;
        text("DURATION", FR, 8, x1, y - 12, C_MUTED);
        text(quiz.getDurationMinutes() + " min", FB, 11, x1, y - 28, C_TEXT);

        float x2 = x1 + 100;
        text("QUESTIONS", FR, 8, x2, y - 12, C_MUTED);
        text(String.valueOf(qCount), FB, 11, x2, y - 28, C_TEXT);

        String desc = quiz.getDescription();
        if (desc != null && !desc.isEmpty()) {
            float x3 = x2 + 70;
            text("DESCRIPTION", FR, 8, x3, y - 12, C_MUTED);
            text(ellipsis(desc, 30), FI, 10, x3, y - 28, C_MUTED);
        }
        y -= rh + 18;
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void renderSectionTitle(String title) throws IOException {
        ensureSpace(26);
        fillRect(ML - 8, y - 22, 4, 22, C_ACCENT);
        text(title.toUpperCase(), FB, 11, ML + 4, y - 14, C_INDIGO);
        float lineX = ML + 4 + tw(FB, 11, title.toUpperCase()) + 8;
        fillRect(lineX, y - 10, CW - lineX + ML + 8, 0.75f, C_BORDER);
        y -= 28;
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void renderQuestionCard(int num, int total,
                                    Question q, String userAnswer) throws IOException {
        boolean answered  = userAnswer != null && !userAnswer.isEmpty();
        boolean isCorrect = answered && userAnswer.equals(q.getCorrectAnswer());

        String[] opts    = {q.getOptionA(), q.getOptionB(), q.getOptionC()};
        String[] letters = {"A", "B", "C"};
        List<String> qLines = wrap(q.getQuestionText(), FB, 11, CW - 36);

        // estimate height
        float optH = 0;
        for (String o : opts) optH += 14 + (wrap(o, FR, 10, CW - 70).size() - 1) * 13f + 5;
        float summaryH = answered ? 24 : 0;
        float cardH = 22 + qLines.size() * 14f + 10 + optH + summaryH + 14;
        ensureSpace(cardH + 10);

        // ── card shell ──
        fillRect(ML - 8, y - cardH, CW + 16, cardH, C_WHITE);
        strokeRect(ML - 8, y - cardH, CW + 16, cardH, C_BORDER, 0.4f);

        float[] accentColor = !answered ? C_ORANGE : (isCorrect ? C_GREEN : C_RED);
        fillRect(ML - 8, y - cardH, 4, cardH, accentColor);

        // ── status chip (top-right) ──
        String chipText  = !answered ? "SKIPPED" : (isCorrect ? "CORRECT" : "WRONG");
        float[] chipBg   = !answered ? C_LORANGE : (isCorrect ? C_LGREEN : C_LRED);
        float[] chipFg   = !answered ? C_ORANGE  : (isCorrect ? C_DGREEN : C_DRED);
        float chipW = tw(FB, 8, chipText) + 14;
        float chipX = ML + CW + 8 - chipW - 8;
        fillRect(chipX, y - 19, chipW, 16, chipBg);
        strokeRect(chipX, y - 19, chipW, 16, chipFg, 0.5f);
        text(chipText, FB, 8, chipX + 7, y - 10, chipFg);

        // ── question number badge ──
        String numStr = "Q" + num;
        float nbW = tw(FB, 9, numStr) + 12;
        fillRect(ML + 4, y - 19, nbW, 16, C_INDIGO);
        text(numStr, FB, 9, ML + 10, y - 10, C_WHITE);
        text("of " + total, FR, 8, ML + 4 + nbW + 4, y - 11, C_MUTED);

        y -= 22;

        // ── question text ──
        for (String line : qLines) {
            text(line, FB, 11, ML + 4, y - 12, C_TEXT);
            y -= 14;
        }
        y -= 8;

        // ── options ──
        for (int i = 0; i < 3; i++) {
            String  letter    = letters[i];
            String  optText   = opts[i];
            boolean optCorrect = letter.equals(q.getCorrectAnswer());
            boolean optChosen  = letter.equals(userAnswer);
            List<String> optLines = wrap(optText, FR, 10, CW - 70);
            float rowH = 14 + (optLines.size() - 1) * 13f;

            ensureSpace(rowH + 6);

            // row background
            if (optCorrect) fillRect(ML + 2, y - rowH - 3, CW - 2, rowH + 3, C_LGREEN);
            else if (optChosen) fillRect(ML + 2, y - rowH - 3, CW - 2, rowH + 3, C_LRED);

            // letter badge
            float[] badgeFg = optCorrect ? C_DGREEN : (optChosen ? C_DRED : C_MUTED);
            fillRect(ML + 6, y - rowH + 1, 16, 14, badgeFg);
            text(letter, FB, 8, ML + 6 + (16 - tw(FB, 8, letter)) / 2, y - rowH + 6, C_WHITE);

            // text
            float[] tc = optCorrect ? C_DGREEN : (optChosen ? C_DRED : C_TEXT);
            PDType1Font tf = (optCorrect || optChosen) ? FB : FR;
            float textX = ML + 28;
            for (int j = 0; j < optLines.size(); j++)
                text(optLines.get(j), tf, 10, textX, y - rowH + 6 - j * 13f, tc);

            // right-side tag
            String tag = "";
            float[] tagC = C_DGREEN;
            if (optCorrect && optChosen) { tag = "Your answer  -  CORRECT"; tagC = C_DGREEN; }
            else if (optCorrect)         { tag = "Correct answer";           tagC = C_DGREEN; }
            else if (optChosen)          { tag = "Your answer";              tagC = C_DRED; }
            if (!tag.isEmpty())
                text(tag, FBI, 8, PW - ML - tw(FBI, 8, tag) - 8, y - rowH + 6, tagC);

            y -= rowH + 5;
        }

        // ── summary row ──
        if (answered) {
            y -= 2;
            ensureSpace(22);
            float[] sumBg = isCorrect ? blend(C_LGREEN, C_WHITE, 0.4f) : blend(C_LRED, C_WHITE, 0.4f);
            fillRect(ML + 2, y - 20, CW - 2, 20, sumBg);

            String yourLabel = "Your answer:";
            String corrLabel = "Correct answer:";
            String yourVal   = getOptionText(userAnswer, q);
            String corrVal   = getOptionText(q.getCorrectAnswer(), q);
            float[] yourColor = isCorrect ? C_DGREEN : C_DRED;

            text(yourLabel, FR, 9, ML + 8, y - 8, C_MUTED);
            text(userAnswer + ")  " + ellipsis(yourVal, 26),
                 FB, 9, ML + 8 + tw(FR, 9, yourLabel) + 4, y - 8, yourColor);

            float midX = ML + CW / 2f;
            text(corrLabel, FR, 9, midX, y - 8, C_MUTED);
            text(q.getCorrectAnswer() + ")  " + ellipsis(corrVal, 26),
                 FB, 9, midX + tw(FR, 9, corrLabel) + 4, y - 8, C_DGREEN);

            y -= 20;
        }

        y -= 12;
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void addFooters() throws IOException {
        int total = doc.getNumberOfPages();
        for (int i = 0; i < total; i++) {
            PDPage p = doc.getPage(i);
            try (PDPageContentStream fcs = new PDPageContentStream(
                    doc, p, PDPageContentStream.AppendMode.APPEND, true)) {
                fillRect(fcs, 0, 0, PW, 30, C_NAVY);
                fillRect(fcs, 0, 30, PW, 1, C_ACCENT);
                text(fcs, "SmartHire  |  Confidential Quiz Result Report",
                     FI, 8, ML, 11, blend(C_WHITE, C_NAVY, 0.35f));
                String pg = "Page " + (i + 1) + " of " + total;
                text(fcs, pg, FR, 9, (PW - tw(FR, 9, pg)) / 2, 11, blend(C_WHITE, C_NAVY, 0.4f));
                String ds = new SimpleDateFormat("dd MMM yyyy").format(new Date());
                text(fcs, ds, FR, 8, PW - ML - tw(FR, 8, ds), 11, blend(C_WHITE, C_NAVY, 0.35f));
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void startPage() throws IOException {
        PDPage page = new PDPage(PDRectangle.A4);
        doc.addPage(page);
        cs = new PDPageContentStream(doc, page);
        fillRect(0, 0, PW, PH, C_BG);
        y = PH;
    }

    private void closePage() throws IOException {
        if (cs != null) { cs.close(); cs = null; }
    }

    private void ensureSpace(float needed) throws IOException {
        if (y - needed < 42) { closePage(); startPage(); y = PH - 18; }
    }

    // ─────────────────────────────────────────────────────────────────────────
    private void text(String t, PDType1Font font, float size,
                      float x, float fy, float[] rgb) throws IOException {
        if (t == null || t.isEmpty()) return;
        cs.beginText();
        cs.setNonStrokingColor(rgb[0], rgb[1], rgb[2]);
        cs.setFont(font, size);
        cs.newLineAtOffset(x, fy);
        cs.showText(sanitise(t));
        cs.endText();
    }

    private void text(PDPageContentStream s, String t, PDType1Font font, float size,
                      float x, float fy, float[] rgb) throws IOException {
        if (t == null || t.isEmpty()) return;
        s.beginText();
        s.setNonStrokingColor(rgb[0], rgb[1], rgb[2]);
        s.setFont(font, size);
        s.newLineAtOffset(x, fy);
        s.showText(sanitise(t));
        s.endText();
    }

    private void fillRect(float x, float fy, float w, float h, float[] rgb) throws IOException {
        cs.setNonStrokingColor(rgb[0], rgb[1], rgb[2]);
        cs.addRect(x, fy, w, h);
        cs.fill();
    }

    private void fillRect(PDPageContentStream s,
                          float x, float fy, float w, float h, float[] rgb) throws IOException {
        s.setNonStrokingColor(rgb[0], rgb[1], rgb[2]);
        s.addRect(x, fy, w, h);
        s.fill();
    }

    private void strokeRect(float x, float fy, float w, float h,
                             float[] rgb, float lineW) throws IOException {
        cs.setStrokingColor(rgb[0], rgb[1], rgb[2]);
        cs.setLineWidth(lineW);
        cs.addRect(x, fy, w, h);
        cs.stroke();
    }

    private float tw(PDType1Font font, float size, String text) throws IOException {
        return font.getStringWidth(sanitise(text)) / 1000f * size;
    }

    private List<String> wrap(String text, PDType1Font font,
                              float size, float maxW) throws IOException {
        List<String> lines = new ArrayList<>();
        if (text == null || text.isEmpty()) { lines.add(""); return lines; }
        String[] words = text.split("\\s+");
        StringBuilder cur = new StringBuilder();
        for (String w : words) {
            String candidate = cur.isEmpty() ? w : cur + " " + w;
            if (tw(font, size, candidate) > maxW && !cur.isEmpty()) {
                lines.add(cur.toString()); cur = new StringBuilder(w);
            } else { if (!cur.isEmpty()) cur.append(' '); cur.append(w); }
        }
        if (!cur.isEmpty()) lines.add(cur.toString());
        if (lines.isEmpty()) lines.add("");
        return lines;
    }

    private int countCorrect(List<Question> questions, Map<Integer, String> answers) {
        int n = 0;
        for (Question q : questions) {
            String a = answers.get(q.getId());
            if (a != null && a.equals(q.getCorrectAnswer())) n++;
        }
        return n;
    }

    private int countAnswered(Map<Integer, String> answers) {
        int n = 0;
        for (String v : answers.values()) if (v != null && !v.isEmpty()) n++;
        return n;
    }

    private String getOptionText(String letter, Question q) {
        if (letter == null) return "-";
        switch (letter) {
            case "A": return q.getOptionA();
            case "B": return q.getOptionB();
            case "C": return q.getOptionC();
            default:  return "-";
        }
    }

    private String sanitise(String s) {
        if (s == null) return "";
        StringBuilder sb = new StringBuilder(s.length());
        for (char c : s.toCharArray())
            sb.append((c >= 32 && c <= 126) || (c >= 160 && c <= 255) ? c : ' ');
        return sb.toString().trim();
    }

    private String ellipsis(String s, int maxLen) {
        if (s == null) return "";
        return s.length() <= maxLen ? s : s.substring(0, maxLen - 3) + "...";
    }

    private float[] arr(float r, float g, float b) { return new float[]{r, g, b}; }

    private float[] blend(float[] a, float[] b, float t) {
        return new float[]{
            a[0] * (1 - t) + b[0] * t,
            a[1] * (1 - t) + b[1] * t,
            a[2] * (1 - t) + b[2] * t
        };
    }
}
