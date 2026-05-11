<?php

declare(strict_types=1);

namespace App\Service;

class JobValidator
{
    /**
     * Validates that a job salary is greater than zero.
     *
     * @param int $salary The salary amount to validate
     * @return bool True if salary is valid (> 0), false otherwise
     */
    public function isValidSalary(int $salary): bool
    {
        return $salary > 0;
    }
}
