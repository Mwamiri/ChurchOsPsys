<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\AuditLogRepository;
use App\Repository\CentreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class InsightService
{
    public EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly CentreRepository $centres,
        private readonly AuditLogRepository $auditLogs,
    ) {
        $this->em = $em;
    }

    public function dashboardStats(?User $viewer = null): array
    {
        $conn = $this->em->getConnection();

        $stats = [
            'users_active'  => $this->users->countActive(),
            'centres_active' => $this->centres->countActive(),
            'users_by_role' => $this->users->countByRole(),
            'recent_activity' => $this->auditLogs->recentActivity(30),
        ];

        // Members count (if ministry tables exist)
        try {
            $stats['members_total'] = (int) $conn->fetchOne('SELECT COUNT(*) FROM members WHERE deleted_at IS NULL');
        } catch (\Exception) {
            $stats['members_total'] = null;
        }

        // Giving this month
        try {
            $stats['giving_month'] = (float) $conn->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) FROM giving_entries WHERE MONTH(gift_date) = MONTH(NOW()) AND YEAR(gift_date) = YEAR(NOW()) AND status = 'approved'"
            );
        } catch (\Exception) {
            $stats['giving_month'] = null;
        }

        // Attendance last service
        try {
            $stats['attendance_last'] = (int) $conn->fetchOne(
                "SELECT SUM(total) FROM attendance_services WHERE service_date = (SELECT MAX(service_date) FROM attendance_services) AND status = 'approved'"
            );
        } catch (\Exception) {
            $stats['attendance_last'] = null;
        }

        // Visitors this month
        try {
            $stats['visitors_month'] = (int) $conn->fetchOne(
                "SELECT COUNT(*) FROM visitors WHERE MONTH(visit_date) = MONTH(NOW()) AND YEAR(visit_date) = YEAR(NOW())"
            );
        } catch (\Exception) {
            $stats['visitors_month'] = null;
        }

        return $stats;
    }

    public function signInTrend(int $days = 30): array
    {
        $conn = $this->em->getConnection();
        try {
            $rows = $conn->fetchAllAssociative(
                "SELECT DATE(created_at) as day, COUNT(*) as cnt, SUM(success) as ok
                 FROM login_history
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL :d DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY day",
                ['d' => $days]
            );
        } catch (\Exception) {
            $rows = [];
        }
        return $rows;
    }

    public function topActions(int $days = 30): array
    {
        return $this->auditLogs->recentActivity($days);
    }

    public function ministryReport(int $year, array $centreIds = []): array
    {
        $conn = $this->em->getConnection();
        $data = ['year' => $year, 'centres' => $centreIds];

        // Attendance by month
        try {
            $inClause = $centreIds ? 'AND centre_id IN (' . implode(',', array_map('intval', $centreIds)) . ')' : '';
            $data['attendance'] = $conn->fetchAllAssociative(
                "SELECT MONTH(service_date) as mo, SUM(total) as total
                 FROM attendance_services
                 WHERE YEAR(service_date) = :y AND status = 'approved' $inClause
                 GROUP BY MONTH(service_date) ORDER BY mo",
                ['y' => $year]
            );
        } catch (\Exception) {
            $data['attendance'] = [];
        }

        // Giving by month
        try {
            $inClause = $centreIds ? 'AND centre_id IN (' . implode(',', array_map('intval', $centreIds)) . ')' : '';
            $data['giving'] = $conn->fetchAllAssociative(
                "SELECT MONTH(gift_date) as mo, SUM(amount) as total
                 FROM giving_entries
                 WHERE YEAR(gift_date) = :y AND status = 'approved' $inClause
                 GROUP BY MONTH(gift_date) ORDER BY mo",
                ['y' => $year]
            );
        } catch (\Exception) {
            $data['giving'] = [];
        }

        // New members by month
        try {
            $inClause = $centreIds ? 'AND centre_id IN (' . implode(',', array_map('intval', $centreIds)) . ')' : '';
            $data['new_members'] = $conn->fetchAllAssociative(
                "SELECT MONTH(born_again_date) as mo, COUNT(*) as cnt
                 FROM members
                 WHERE YEAR(born_again_date) = :y AND deleted_at IS NULL $inClause
                 GROUP BY MONTH(born_again_date) ORDER BY mo",
                ['y' => $year]
            );
        } catch (\Exception) {
            $data['new_members'] = [];
        }

        return $data;
    }

    public function annualSummary(int $year): array
    {
        $monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $report = $this->ministryReport($year);
        $report['month_labels'] = $monthLabels;
        return $report;
    }
}
