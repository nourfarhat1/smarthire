<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\JobOffer;
use App\Entity\JobRequest;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    // ========== Basic Properties Tests ==========

    public function testDefaultRoleIsCandidate(): void
    {
        $this->assertEquals(1, $this->user->getRoleId());
        $this->assertEquals('CANDIDATE', $this->user->getRoleName());
    }

    public function testSetAndGetEmail(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);

        $this->assertEquals($email, $this->user->getEmail());
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testSetAndGetPassword(): void
    {
        $password = 'hashedPassword123';
        $this->user->setPassword($password);

        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testSetAndGetFirstName(): void
    {
        $this->user->setFirstName('John');
        $this->assertEquals('John', $this->user->getFirstName());
    }

    public function testSetAndGetLastName(): void
    {
        $this->user->setLastName('Doe');
        $this->assertEquals('Doe', $this->user->getLastName());
    }

    public function testGetFullName(): void
    {
        $this->user->setFirstName('John');
        $this->user->setLastName('Doe');

        $this->assertEquals('John Doe', $this->user->getFullName());
    }

    public function testGetFullNameWithEmptyLastName(): void
    {
        $this->user->setFirstName('John');
        $this->user->setLastName('');

        $this->assertEquals('John', $this->user->getFullName());
    }

    // ========== Role Management Tests ==========

    public function testRoleMappingCandidate(): void
    {
        $this->user->setRoleId(1);

        $this->assertEquals('CANDIDATE', $this->user->getRoleName());
        $this->assertContains('ROLE_CANDIDATE', $this->user->getRoles());
    }

    public function testRoleMappingHr(): void
    {
        $this->user->setRoleId(2);

        $this->assertEquals('HR', $this->user->getRoleName());
        $this->assertContains('ROLE_HR', $this->user->getRoles());
    }

    public function testRoleMappingAdmin(): void
    {
        $this->user->setRoleId(3);

        $this->assertEquals('ADMIN', $this->user->getRoleName());
        $this->assertContains('ROLE_ADMIN', $this->user->getRoles());
    }

    public function testRoleMappingUnknown(): void
    {
        $this->user->setRoleId(999);

        $this->assertEquals('USER', $this->user->getRoleName());
    }

    public function testRolesAlwaysIncludeRoleUser(): void
    {
        $roles = $this->user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    // ========== Status Tests ==========

    public function testDefaultVerificationStatus(): void
    {
        $this->assertFalse($this->user->isVerified());
    }

    public function testSetVerified(): void
    {
        $this->user->setVerified(true);
        $this->assertTrue($this->user->isVerified());
    }

    public function testDefaultBannedStatus(): void
    {
        $this->assertFalse($this->user->isBanned());
    }

    public function testSetBanned(): void
    {
        $this->user->setBanned(true);
        $this->assertTrue($this->user->isBanned());
    }

    // ========== Phone Number Tests ==========

    public function testSetAndGetPhoneNumber(): void
    {
        $phone = '+1234567890';
        $this->user->setPhoneNumber($phone);

        $this->assertEquals($phone, $this->user->getPhoneNumber());
    }

    public function testPhoneNumberCanBeNull(): void
    {
        $this->user->setPhoneNumber(null);
        $this->assertNull($this->user->getPhoneNumber());
    }

    // ========== Verification Token Tests ==========

    public function testSetAndGetVerificationToken(): void
    {
        $token = 'abc123xyz';
        $this->user->setVerificationToken($token);

        $this->assertEquals($token, $this->user->getVerificationToken());
    }

    // ========== Profile Picture Tests ==========

    public function testSetAndGetProfilePicture(): void
    {
        $picture = 'profile.jpg';
        $this->user->setProfilePicture($picture);

        $this->assertEquals($picture, $this->user->getProfilePicture());
    }

    // ========== Face Login Tests ==========

    public function testDefaultFaceLoginStatus(): void
    {
        $this->assertFalse($this->user->isFaceLoginEnabled());
    }

    public function testEnableFaceLogin(): void
    {
        $this->user->setFaceLoginEnabled(true);
        $this->assertTrue($this->user->isFaceLoginEnabled());
    }

    public function testSetAndGetFaceFeatures(): void
    {
        $features = 'face_features_data';
        $this->user->setFaceFeatures($features);

        $this->assertEquals($features, $this->user->getFaceFeatures());
    }

    public function testAddAndGetFaceTokens(): void
    {
        $token1 = 'token123';
        $token2 = 'token456';

        $this->user->addFaceToken($token1);
        $this->user->addFaceToken($token2);

        $this->assertContains($token1, $this->user->getFaceTokens());
        $this->assertContains($token2, $this->user->getFaceTokens());
    }

    public function testAddDuplicateFaceToken(): void
    {
        $token = 'token123';

        $this->user->addFaceToken($token);
        $this->user->addFaceToken($token);

        $tokens = $this->user->getFaceTokens();
        $this->assertEquals(1, count(array_filter($tokens, fn($t) => $t === $token)));
    }

    public function testRemoveFaceToken(): void
    {
        $token1 = 'token123';
        $token2 = 'token456';

        $this->user->addFaceToken($token1);
        $this->user->addFaceToken($token2);
        $this->user->removeFaceToken($token1);

        $this->assertNotContains($token1, $this->user->getFaceTokens());
        $this->assertContains($token2, $this->user->getFaceTokens());
    }

    // ========== Skills Tests ==========

    public function testSetAndGetSkills(): void
    {
        $skills = json_encode(['PHP', 'Symfony', 'MySQL']);
        $this->user->setSkills($skills);

        $this->assertEquals($skills, $this->user->getSkills());
    }

    public function testGetSkillsArray(): void
    {
        $skillsArray = ['PHP', 'Symfony', 'MySQL'];
        $this->user->setSkills(json_encode($skillsArray));

        $this->assertEquals($skillsArray, $this->user->getSkillsArray());
    }

    public function testGetSkillsArrayWithEmptySkills(): void
    {
        $this->user->setSkills(null);

        $this->assertEquals([], $this->user->getSkillsArray());
    }

    public function testGetSkillsString(): void
    {
        $this->user->setSkills(json_encode(['PHP', 'Symfony']));

        $this->assertEquals('PHP, Symfony', $this->user->getSkillsString());
    }

    public function testHasSkills(): void
    {
        $this->user->setSkills(json_encode(['PHP']));

        $this->assertTrue($this->user->hasSkills());
    }

    public function testHasSkillsWithEmpty(): void
    {
        $this->user->setSkills(null);

        $this->assertFalse($this->user->hasSkills());
    }

    // ========== CV Tests ==========

    public function testSetAndGetCvFilename(): void
    {
        $filename = 'cv.pdf';
        $this->user->setCvFilename($filename);

        $this->assertEquals($filename, $this->user->getCvFilename());
    }

    public function testHasCv(): void
    {
        $this->user->setCvFilename('cv.pdf');

        $this->assertTrue($this->user->hasCv());
    }

    public function testHasCvWithNoCv(): void
    {
        $this->assertFalse($this->user->hasCv());
    }

    public function testGetCvUrl(): void
    {
        $filename = 'cv.pdf';
        $this->user->setCvFilename($filename);

        $this->assertEquals('/uploads/cvs/' . $filename, $this->user->getCvUrl());
    }

    public function testGetCvUrlWithNoCv(): void
    {
        $this->assertNull($this->user->getCvUrl());
    }

    // ========== Google OAuth Tests ==========

    public function testSetAndGetGoogleId(): void
    {
        $googleId = 'google123';
        $this->user->setGoogleId($googleId);

        $this->assertEquals($googleId, $this->user->getGoogleId());
    }

    public function testIsGoogleUser(): void
    {
        $this->assertFalse($this->user->isGoogleUser());

        $this->user->setGoogleId('google123');
        $this->assertTrue($this->user->isGoogleUser());
    }

    public function testSetAndGetGoogleAccessToken(): void
    {
        $token = 'access_token_123';
        $this->user->setGoogleAccessToken($token);

        $this->assertEquals($token, $this->user->getGoogleAccessToken());
    }

    public function testSetAndGetGoogleRefreshToken(): void
    {
        $token = 'refresh_token_123';
        $this->user->setGoogleRefreshToken($token);

        $this->assertEquals($token, $this->user->getGoogleRefreshToken());
    }

    // ========== OTP Tests ==========

    public function testSetAndGetOtpCode(): void
    {
        $otp = '123456';
        $this->user->setOtpCode($otp);

        $this->assertEquals($otp, $this->user->getOtpCode());
    }

    public function testSetAndGetOtpExpiry(): void
    {
        $expiry = new \DateTime('+5 minutes');
        $this->user->setOtpExpiry($expiry);

        $this->assertEquals($expiry, $this->user->getOtpExpiry());
    }

    // ========== Created At Tests ==========

    public function testCreatedAtIsSetOnConstruct(): void
    {
        $this->assertInstanceOf(\DateTimeInterface::class, $this->user->getCreatedAt());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $createdAt = new \DateTime('2024-01-15');
        $this->user->setCreatedAt($createdAt);

        $this->assertEquals($createdAt, $this->user->getCreatedAt());
    }

    // ========== Updated At Tests ==========

    public function testSetAndGetUpdatedAt(): void
    {
        $updatedAt = new \DateTime('2024-01-20');
        $this->user->setUpdatedAt($updatedAt);

        $this->assertEquals($updatedAt, $this->user->getUpdatedAt());
    }

    // ========== Collections Tests ==========

    public function testGetJobOffersCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->user->getJobOffers());
        $this->assertCount(0, $this->user->getJobOffers());
    }

    public function testAddAndRemoveSavedJob(): void
    {
        $jobOffer = $this->createMock(JobOffer::class);

        $this->user->addSavedJob($jobOffer);
        $this->assertTrue($this->user->getSavedJobs()->contains($jobOffer));

        $this->user->removeSavedJob($jobOffer);
        $this->assertFalse($this->user->getSavedJobs()->contains($jobOffer));
    }

    public function testAddAndRemoveJobApplication(): void
    {
        $jobRequest = $this->createMock(JobRequest::class);

        $this->user->addJobApplication($jobRequest);
        $this->assertTrue($this->user->getJobApplications()->contains($jobRequest));

        $this->user->removeJobApplication($jobRequest);
        $this->assertFalse($this->user->getJobApplications()->contains($jobRequest));
    }

    // ========== Training Votes Tests ==========

    public function testGetTrainingVotesCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->user->getTrainingVotes());
        $this->assertCount(0, $this->user->getTrainingVotes());
    }

    // ========== Security Interface Tests ==========

    public function testEraseCredentials(): void
    {
        // eraseCredentials should not throw an exception
        $this->user->eraseCredentials();
        $this->addToAssertionCount(1);
    }
}
