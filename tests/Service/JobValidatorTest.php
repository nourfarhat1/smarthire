<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\JobValidator;
use PHPUnit\Framework\TestCase;

class JobValidatorTest extends TestCase
{
    private JobValidator $jobValidator;

    protected function setUp(): void
    {
        $this->jobValidator = new JobValidator();
    }

    /**
     * Tests that a valid positive salary returns true.
     */
    public function testValidSalary(): void
    {
        $result = $this->jobValidator->isValidSalary(1500);

        $this->assertTrue($result);
    }

    /**
     * Tests that invalid salaries (zero or negative) return false.
     */
    public function testInvalidSalary(): void
    {
        $zeroResult = $this->jobValidator->isValidSalary(0);
        $negativeResult = $this->jobValidator->isValidSalary(-100);

        $this->assertFalse($zeroResult);
        $this->assertFalse($negativeResult);
    }
}
