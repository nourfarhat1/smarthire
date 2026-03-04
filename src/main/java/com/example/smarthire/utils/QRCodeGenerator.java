package com.example.smarthire.utils;

import com.google.zxing.BarcodeFormat;
import com.google.zxing.client.j2se.MatrixToImageWriter;
import com.google.zxing.common.BitMatrix;
import com.google.zxing.qrcode.QRCodeWriter;
import javafx.scene.image.Image;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;

public class QRCodeGenerator {
    public static Image generateQRCode(String text) {
        try {
            QRCodeWriter qrCodeWriter = new QRCodeWriter();
            BitMatrix bitMatrix = qrCodeWriter.encode(text, BarcodeFormat.QR_CODE, 200, 200);

            ByteArrayOutputStream out = new ByteArrayOutputStream();
            MatrixToImageWriter.writeToStream(bitMatrix, "PNG", out);
            return new Image(new ByteArrayInputStream(out.toByteArray()));
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }
}