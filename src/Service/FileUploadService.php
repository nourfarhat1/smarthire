<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Smalot\PdfParser\Parser as PdfParser;

class FileUploadService
{
    private string $targetDirectory;
    private SluggerInterface $slugger;
    private PdfParser $pdfParser;
    private AIService $aiService;

    public function __construct(
        ParameterBagInterface $parameterBag, 
        SluggerInterface $slugger,
        AIService $aiService
    ) {
        $this->targetDirectory = $parameterBag->get('kernel.project_dir') . '/public/uploads';
        $this->slugger = $slugger;
        $this->pdfParser = new PdfParser();
        $this->aiService = $aiService;
    }

    public function upload(UploadedFile $file, string $subdirectory = ''): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $filename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $file->guessExtension());

        // Create subdirectory if specified
        $targetPath = $this->targetDirectory;
        if ($subdirectory) {
            $targetPath .= '/' . $subdirectory;
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0777, true);
            }
        }

        try {
            $file->move($targetPath, $filename);
        } catch (FileException $e) {
            throw new FileException('File upload failed: ' . $e->getMessage());
        }

        return $subdirectory ? $subdirectory . '/' . $filename : $filename;
    }

    public function uploadCV(UploadedFile $file, int $userId): string
    {
        $this->validateCV($file);
        return $this->upload($file, 'cvs/' . $userId);
    }

    public function uploadTrainingVideo(UploadedFile $file, int $trainingId): string
    {
        $this->validateTrainingVideo($file);
        return $this->upload($file, 'trainings/' . $trainingId);
    }

    private function validateCV(UploadedFile $file): void
    {
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array(strtolower($file->guessExtension()), $allowedExtensions)) {
            throw new FileException('Only PDF, DOC, and DOCX files are allowed for CVs.');
        }

        if ($file->getSize() > $maxSize) {
            throw new FileException('CV file size must be less than 5MB.');
        }
    }

    private function validateProfilePicture(UploadedFile $file): void
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array(strtolower($file->guessExtension()), $allowedExtensions)) {
            throw new FileException('Only JPG, JPEG, PNG, and GIF files are allowed for profile pictures.');
        }

        if ($file->getSize() > $maxSize) {
            throw new FileException('Profile picture size must be less than 2MB.');
        }
    }

    private function validateTrainingVideo(UploadedFile $file): void
    {
        $allowedExtensions = ['mp4', 'avi', 'mov', 'wmv'];
        $maxSize = 100 * 1024 * 1024; // 100MB

        if (!in_array(strtolower($file->guessExtension()), $allowedExtensions)) {
            throw new FileException('Only MP4, AVI, MOV, and WMV files are allowed for training videos.');
        }

        if ($file->getSize() > $maxSize) {
            throw new FileException('Training video size must be less than 100MB.');
        }
    }

    public function getPublicPath(string $filename): string
    {
        return '/uploads/' . $filename;
    }

    public function getAbsolutePath(string $filename): string
    {
        return $this->targetDirectory . '/' . $filename;
    }

    public function deleteFile(string $filename): bool
    {
        $filepath = $this->getAbsolutePath($filename);
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Extract text from PDF file using Smalot PDF Parser (equivalent to Apache Tika in JavaFX)
     */
    public function extractTextFromPDF(string $filePath): string
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('PDF file not found: ' . $filePath);
            }

            $pdf = $this->pdfParser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Clean up the text
            $text = preg_replace('/\s+/', ' ', $text); // Replace multiple spaces with single space
            $text = trim($text);
            
            return $text;
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from PDF: ' . $e->getMessage());
        }
    }

    /**
     * Parse CV and extract candidate information using AI
     */
    public function parseCV(string $filePath): array
    {
        try {
            // Extract text from PDF
            $text = $this->extractTextFromPDF($filePath);
            
            if (empty($text)) {
                throw new \Exception('No text could be extracted from the PDF');
            }

            // Use AI to analyze the CV text
            $analysis = $this->aiService->analyzeResume($text);
            
            return [
                'success' => true,
                'extracted_text' => $text,
                'analysis' => $analysis,
                'text_length' => strlen($text),
                'word_count' => str_word_count($text)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'extracted_text' => '',
                'analysis' => []
            ];
        }
    }

    /**
     * Upload and parse CV in one operation
     */
    public function uploadAndParseCV(UploadedFile $file): array
    {
        try {
            // Validate file type
            $allowedTypes = ['application/pdf', 'application/x-pdf'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                throw new \Exception('Only PDF files are allowed for CV uploads');
            }

            // Validate file size (max 5MB)
            if ($file->getSize() > 5 * 1024 * 1024) {
                throw new \Exception('CV file size must be less than 5MB');
            }

            // Upload the file
            $filename = $this->upload($file, 'cvs');
            $filePath = $this->getAbsolutePath($filename);

            // Parse the CV
            $parseResult = $this->parseCV($filePath);
            
            if (!$parseResult['success']) {
                // Clean up uploaded file if parsing failed
                $this->deleteFile($filename);
                throw new \Exception($parseResult['error']);
            }

            return [
                'success' => true,
                'filename' => $filename,
                'public_path' => $this->getPublicPath($filename),
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'parsed_data' => $parseResult
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract keywords from CV text using AI
     */
    public function extractKeywords(string $cvText): array
    {
        try {
            $analysis = $this->aiService->analyzeResume($cvText);
            
            return [
                'skills' => $analysis['skills'] ?? [],
                'experience' => $analysis['experience'] ?? [],
                'summary' => $analysis['summary'] ?? '',
                'total_keywords' => count(($analysis['skills'] ?? [])) + count(($analysis['experience'] ?? []))
            ];
            
        } catch (\Exception $e) {
            // Fallback to basic keyword extraction
            return $this->extractBasicKeywords($cvText);
        }
    }

    /**
     * Basic keyword extraction as fallback
     */
    private function extractBasicKeywords(string $text): array
    {
        // Common technical skills to look for
        $technicalSkills = [
            'PHP', 'JavaScript', 'Python', 'Java', 'React', 'Angular', 'Vue.js',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Docker', 'Kubernetes',
            'Git', 'CI/CD', 'AWS', 'Azure', 'GCP',
            'HTML', 'CSS', 'TypeScript', 'Node.js', 'Laravel',
            'Symfony', 'Django', 'Flask', 'Spring Boot'
        ];

        $foundSkills = [];
        foreach ($technicalSkills as $skill) {
            if (stripos($text, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }

        return [
            'skills' => array_unique($foundSkills),
            'experience' => [],
            'summary' => substr($text, 0, 200) . '...',
            'total_keywords' => count($foundSkills)
        ];
    }

    /**
     * Upload profile picture with validation
     */
    public function uploadProfilePicture(string $imagePath, User $user): string
    {
        try {
            // Validate image
            $validation = $this->aiService->validateFaceImage($imagePath);
            if (!$validation['is_valid']) {
                throw new \Exception('Image validation failed: ' . implode(', ', $validation['issues']));
            }

            // Create unique filename
            $filename = 'profile_' . $user->getId() . '_' . uniqid() . '.jpg';
            $targetPath = $this->targetDirectory . '/profiles/' . $filename;

            // Ensure profiles directory exists
            $profilesDir = $this->targetDirectory . '/profiles';
            if (!is_dir($profilesDir)) {
                mkdir($profilesDir, 0755, true);
            }

            // Copy image to profiles directory
            if (!copy($imagePath, $targetPath)) {
                throw new \Exception('Failed to save profile picture');
            }

            return 'profiles/' . $filename;
            
        } catch (\Exception $e) {
            throw new \Exception('Profile picture upload failed: ' . $e->getMessage());
        }
    }
}
