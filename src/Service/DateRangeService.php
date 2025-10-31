<?php

namespace App\Service;

class DateRangeService
{
    /**
     * Get date range based on period string
     * 
     * @param string $period One of: 'today', 'week', 'month', 'year'
     * @return array{0: \DateTime, 1: \DateTime} Array with start and end date
     */
    public function getDateRange(string $period): array
    {
        $endDate = new \DateTime();
        $endDate->setTime(23, 59, 59);

        $startDate = match ($period) {
            'today' => (new \DateTime())->setTime(0, 0, 0),
            'week' => (new \DateTime())->modify('-7 days')->setTime(0, 0, 0),
            'month' => (new \DateTime())->modify('-30 days')->setTime(0, 0, 0),
            'year' => (new \DateTime())->modify('-365 days')->setTime(0, 0, 0),
            default => (new \DateTime())->setTime(0, 0, 0)
        };

        return [$startDate, $endDate];
    }

    /**
     * Get start of today
     * 
     * @return \DateTime
     */
    public function getToday(): \DateTime
    {
        return (new \DateTime())->setTime(0, 0, 0);
    }

    /**
     * Get start of current week (Monday)
     * 
     * @return \DateTime
     */
    public function getWeekStart(): \DateTime
    {
        return (new \DateTime('monday this week'))->setTime(0, 0, 0);
    }

    /**
     * Get start of current month
     * 
     * @return \DateTime
     */
    public function getMonthStart(): \DateTime
    {
        return (new \DateTime('first day of this month'))->setTime(0, 0, 0);
    }

    /**
     * Get group format for SQL queries based on period
     * 
     * @param string $period One of: 'week', 'month', 'year'
     * @return string SQL DATE_FORMAT expression
     */
    public function getGroupFormat(string $period): string
    {
        return match ($period) {
            'week', 'month' => "DATE_FORMAT(o.deliveredAt, '%Y-%m-%d')",
            'year' => "DATE_FORMAT(o.deliveredAt, '%Y-%m')",
            default => "DATE_FORMAT(o.deliveredAt, '%Y-%m-%d')"
        };
    }
}

