<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getStatusColor', [$this, 'getStatusColor']),
            new TwigFunction('getPriorityColor', [$this, 'getPriorityColor']),
            new TwigFunction('getSentimentColor', [$this, 'getSentimentColor']),
            new TwigFunction('getUrgencyColor', [$this, 'getUrgencyColor']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('values', [$this, 'arrayValues']),
        ];
    }

    public function arrayValues(array $array): array
    {
        return array_values($array);
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'OPEN' => 'danger',
            'IN_PROGRESS' => 'warning',
            'RESOLVED' => 'success',
            'CLOSED' => 'secondary',
            default => 'primary',
        };
    }

    public function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'HIGH' => 'danger',
            'MEDIUM' => 'warning',
            'LOW' => 'info',
            default => 'secondary',
        };
    }

    public function getSentimentColor(string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'success',
            'negative' => 'danger',
            'neutral' => 'secondary',
            default => 'info',
        };
    }

    public function getUrgencyColor(int $score): string
    {
        return match (true) {
            $score >= 8 => 'danger',
            $score >= 5 => 'warning',
            $score >= 3 => 'info',
            default => 'secondary',
        };
    }
}
