<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class JobApplication extends Model
{
    public const VALID_STATUSES = [
        'NEW',
        'SHORTLISTED',
        'INTERVIEW_SCHEDULED',
        'SELECTED',
        'REJECTED',
    ];

    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `job_applications` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `application_no` VARCHAR(50) NOT NULL UNIQUE,
                    `job_opening_id` INT NULL,
                    `position_applied` VARCHAR(200) NOT NULL,
                    `full_name` VARCHAR(150) NOT NULL,
                    `email` VARCHAR(191) NOT NULL,
                    `phone` VARCHAR(50) NOT NULL,
                    `city` VARCHAR(100) NOT NULL,
                    `country_id` INT NULL,
                    `country_name` VARCHAR(100) NOT NULL,
                    `total_experience` VARCHAR(50) NOT NULL,
                    `current_company` VARCHAR(150) NULL,
                    `current_job_title` VARCHAR(150) NULL,
                    `expected_salary` VARCHAR(100) NULL,
                    `notice_period` VARCHAR(50) NOT NULL,
                    `linkedin_url` VARCHAR(255) NULL,
                    `portfolio_url` VARCHAR(255) NULL,
                    `resume_path` VARCHAR(255) NOT NULL,
                    `resume_name` VARCHAR(255) NOT NULL,
                    `resume_size` INT NOT NULL,
                    `resume_mime` VARCHAR(100) NOT NULL,
                    `cover_letter` TEXT NULL,
                    `consent_privacy` TINYINT(1) NOT NULL DEFAULT 1,
                    `status` ENUM('NEW', 'SHORTLISTED', 'INTERVIEW_SCHEDULED', 'SELECTED', 'REJECTED') NOT NULL DEFAULT 'NEW',
                    `admin_notes` TEXT NULL,
                    `ip_address` VARCHAR(45) NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_app_status (`status`),
                    INDEX idx_app_job (`job_opening_id`),
                    INDEX idx_app_created (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Throwable $e) {
            @error_log("JobApplication::ensureSchema error: " . $e->getMessage());
        }
    }

    public static function create(array $data): int
    {
        self::ensureSchema();
        try {
            $jobOpeningId = !empty($data['job_opening_id']) ? (int)$data['job_opening_id'] : null;
            $position     = trim((string)($data['position_applied'] ?? 'General Application'));
            $fullName     = trim((string)($data['full_name'] ?? $data['name'] ?? ''));
            $email        = trim((string)($data['email'] ?? ''));
            $phone        = trim((string)($data['phone'] ?? ''));
            $city         = trim((string)($data['city'] ?? ''));
            $countryId    = !empty($data['country_id']) ? (int)$data['country_id'] : null;
            $countryName  = trim((string)($data['country_name'] ?? 'India'));
            $experience   = trim((string)($data['total_experience'] ?? ''));
            $currentCo    = !empty($data['current_company']) ? trim((string)$data['current_company']) : null;
            $currentTitle = !empty($data['current_job_title']) ? trim((string)$data['current_job_title']) : (!empty($data['current_title']) ? trim((string)$data['current_title']) : null);
            $salary       = !empty($data['expected_salary']) ? trim((string)$data['expected_salary']) : null;
            $notice       = trim((string)($data['notice_period'] ?? '30 Days'));
            $linkedin     = !empty($data['linkedin_url']) ? trim((string)$data['linkedin_url']) : (!empty($data['linkedin_profile']) ? trim((string)$data['linkedin_profile']) : null);
            $portfolio    = !empty($data['portfolio_url']) ? trim((string)$data['portfolio_url']) : null;
            $resumePath   = trim((string)($data['resume_path'] ?? ''));
            $resumeName   = trim((string)($data['resume_name'] ?? 'Resume.pdf'));
            $resumeSize   = (int)($data['resume_size'] ?? 0);
            $resumeMime   = trim((string)($data['resume_mime'] ?? 'application/pdf'));
            $coverLetter  = !empty($data['cover_letter']) ? trim((string)$data['cover_letter']) : null;
            $consent      = !empty($data['consent_privacy']) ? 1 : 0;
            $ip           = (string)($data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

            // Temporary placeholder application_no for insert
            $tempNo = 'TMP-' . microtime(true);

            self::execute("
                INSERT INTO `job_applications` (
                    `application_no`, `job_opening_id`, `position_applied`, `full_name`,
                    `email`, `phone`, `city`, `country_id`, `country_name`,
                    `total_experience`, `current_company`, `current_job_title`,
                    `expected_salary`, `notice_period`, `linkedin_url`, `portfolio_url`,
                    `resume_path`, `resume_name`, `resume_size`, `resume_mime`,
                    `cover_letter`, `consent_privacy`, `status`, `ip_address`, `created_at`
                ) VALUES (
                    :app_no, :job_id, :position, :name,
                    :email, :phone, :city, :country_id, :country_name,
                    :exp, :curr_co, :curr_title,
                    :salary, :notice, :linkedin, :portfolio,
                    :r_path, :r_name, :r_size, :r_mime,
                    :cover, :consent, 'NEW', :ip, NOW()
                )
            ", [
                ':app_no'       => $tempNo,
                ':job_id'       => $jobOpeningId,
                ':position'     => $position,
                ':name'         => $fullName,
                ':email'        => $email,
                ':phone'        => $phone,
                ':city'         => $city,
                ':country_id'   => $countryId,
                ':country_name' => $countryName,
                ':exp'          => $experience,
                ':curr_co'      => $currentCo,
                ':curr_title'   => $currentTitle,
                ':salary'       => $salary,
                ':notice'       => $notice,
                ':linkedin'     => $linkedin,
                ':portfolio'    => $portfolio,
                ':r_path'       => $resumePath,
                ':r_name'       => $resumeName,
                ':r_size'       => $resumeSize,
                ':r_mime'       => $resumeMime,
                ':cover'        => $coverLetter,
                ':consent'      => $consent,
                ':ip'           => $ip,
            ]);

            $id = (int) self::lastInsertId();
            $appNo = 'APP-' . date('Ym') . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
            self::execute("UPDATE `job_applications` SET `application_no` = :no WHERE `id` = :id", [
                ':no' => $appNo,
                ':id' => $id,
            ]);

            // Notify Super Admin
            try {
                Notification::notifySuperAdmins(
                    'JOB_APPLICATION_RECEIVED',
                    "New Candidate Application: {$fullName} ({$position})",
                    "Application {$appNo} submitted from {$city}, {$countryName}. Experience: {$experience}.",
                    '/admin/careers',
                    'CAREERS',
                    $id
                );
            } catch (Throwable $notifErr) {
                @error_log("JobApplication notification error: " . $notifErr->getMessage());
            }

            return $id;
        } catch (Throwable $e) {
            error_log("JobApplication::create error: " . $e->getMessage());
            return 0;
        }
    }

    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        $row = self::fetch("
            SELECT a.*, j.title AS job_title, j.department AS job_department
            FROM `job_applications` a
            LEFT JOIN `job_openings` j ON a.job_opening_id = j.id
            WHERE a.`id` = :id
            LIMIT 1
        ", [':id' => $id]);
        return $row ?: null;
    }

    public static function getAdminDataTable(array $params): array
    {
        self::ensureSchema();

        $draw   = (int) ($params['draw'] ?? 1);
        $start  = max(0, (int) ($params['start'] ?? 0));
        $length = max(1, min(100, (int) ($params['length'] ?? 15)));

        $where = ['1=1'];
        $bindings = [];

        // Position / Job filter
        if (!empty($params['position']) && $params['position'] !== 'ALL') {
            $where[] = "a.`position_applied` = :f_pos";
            $bindings[':f_pos'] = $params['position'];
        }
        if (!empty($params['job_opening_id']) && $params['job_opening_id'] !== 'ALL') {
            if ($params['job_opening_id'] === 'GENERAL') {
                $where[] = "a.`job_opening_id` IS NULL";
            } else {
                $where[] = "a.`job_opening_id` = :f_jid";
                $bindings[':f_jid'] = (int) $params['job_opening_id'];
            }
        }

        // Status filter
        if (!empty($params['status']) && $params['status'] !== 'ALL') {
            $where[] = "a.`status` = :f_status";
            $bindings[':f_status'] = strtoupper(trim($params['status']));
        }

        // Experience filter
        if (!empty($params['experience']) && $params['experience'] !== 'ALL') {
            $where[] = "a.`total_experience` = :f_exp";
            $bindings[':f_exp'] = $params['experience'];
        }

        // Location / City filter
        if (!empty($params['city']) && $params['city'] !== 'ALL') {
            $where[] = "a.`city` LIKE :f_city";
            $bindings[':f_city'] = '%' . trim($params['city']) . '%';
        }

        // Date Range
        if (!empty($params['start_date'])) {
            $where[] = "a.`created_at` >= :start_date";
            $bindings[':start_date'] = $params['start_date'] . ' 00:00:00';
        }
        if (!empty($params['end_date'])) {
            $where[] = "a.`created_at` <= :end_date";
            $bindings[':end_date'] = $params['end_date'] . ' 23:59:59';
        }

        // Global Search
        $searchVal = trim((string)($params['search']['value'] ?? $params['search_query'] ?? ''));
        if ($searchVal !== '') {
            $where[] = "(
                a.`application_no` LIKE :s1 OR
                a.`full_name` LIKE :s2 OR
                a.`email` LIKE :s3 OR
                a.`phone` LIKE :s4 OR
                a.`city` LIKE :s5 OR
                a.`country_name` LIKE :s6 OR
                a.`position_applied` LIKE :s7 OR
                a.`current_company` LIKE :s8 OR
                a.`current_job_title` LIKE :s9
            )";
            $term = '%' . $searchVal . '%';
            for ($i = 1; $i <= 9; $i++) {
                $bindings[":s{$i}"] = $term;
            }
        }

        $whereSql = implode(' AND ', $where);

        $totalRecords = (int)(self::fetch("SELECT COUNT(*) AS cnt FROM `job_applications`")['cnt'] ?? 0);
        $filteredRecords = (int)(self::fetch("SELECT COUNT(*) AS cnt FROM `job_applications` a WHERE {$whereSql}", $bindings)['cnt'] ?? 0);

        $columns = [
            0 => 'a.id',
            1 => 'a.created_at',
            2 => 'a.full_name',
            3 => 'a.position_applied',
            4 => 'a.total_experience',
            5 => 'a.city',
            6 => 'a.status',
        ];

        $orderColIdx = (int) ($params['order'][0]['column'] ?? 1);
        $orderCol    = $columns[$orderColIdx] ?? 'a.created_at';
        $orderDir    = strtoupper($params['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $rows = self::fetchAll("
            SELECT a.*, j.title AS job_title, j.department AS job_department
            FROM `job_applications` a
            LEFT JOIN `job_openings` j ON a.job_opening_id = j.id
            WHERE {$whereSql}
            ORDER BY {$orderCol} {$orderDir}
            LIMIT {$length} OFFSET {$start}
        ", $bindings);

        return [
            'draw'            => $draw,
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $rows,
        ];
    }

    public static function getAdminMetrics(): array
    {
        self::ensureSchema();
        $row = self::fetch("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'NEW' THEN 1 ELSE 0 END) AS status_new,
                SUM(CASE WHEN status = 'SHORTLISTED' THEN 1 ELSE 0 END) AS status_shortlisted,
                SUM(CASE WHEN status = 'INTERVIEW_SCHEDULED' THEN 1 ELSE 0 END) AS status_interview,
                SUM(CASE WHEN status = 'SELECTED' THEN 1 ELSE 0 END) AS status_selected,
                SUM(CASE WHEN status = 'REJECTED' THEN 1 ELSE 0 END) AS status_rejected
            FROM `job_applications`
        ");

        return [
            'total'               => (int) ($row['total'] ?? 0),
            'new'                 => (int) ($row['status_new'] ?? 0),
            'shortlisted'         => (int) ($row['status_shortlisted'] ?? 0),
            'interview_scheduled' => (int) ($row['status_interview'] ?? 0),
            'selected'            => (int) ($row['status_selected'] ?? 0),
            'rejected'            => (int) ($row['status_rejected'] ?? 0),
        ];
    }

    public static function getAdminExportData(array $params): array
    {
        self::ensureSchema();

        $where = ['1=1'];
        $bindings = [];

        if (!empty($params['position']) && $params['position'] !== 'ALL') {
            $where[] = "a.`position_applied` = :f_pos";
            $bindings[':f_pos'] = $params['position'];
        }
        if (!empty($params['status']) && $params['status'] !== 'ALL') {
            $where[] = "a.`status` = :f_status";
            $bindings[':f_status'] = strtoupper(trim($params['status']));
        }
        if (!empty($params['start_date'])) {
            $where[] = "a.`created_at` >= :start_date";
            $bindings[':start_date'] = $params['start_date'] . ' 00:00:00';
        }
        if (!empty($params['end_date'])) {
            $where[] = "a.`created_at` <= :end_date";
            $bindings[':end_date'] = $params['end_date'] . ' 23:59:59';
        }
        $searchVal = trim((string)($params['search_query'] ?? ''));
        if ($searchVal !== '') {
            $where[] = "(
                a.`application_no` LIKE :es1 OR
                a.`full_name` LIKE :es2 OR
                a.`email` LIKE :es3 OR
                a.`phone` LIKE :es4 OR
                a.`city` LIKE :es5 OR
                a.`position_applied` LIKE :es6
            )";
            $term = '%' . $searchVal . '%';
            for ($i = 1; $i <= 6; $i++) {
                $bindings[":es{$i}"] = $term;
            }
        }

        $whereSql = implode(' AND ', $where);

        return self::fetchAll("
            SELECT 
                a.application_no AS 'Application No',
                a.position_applied AS 'Position Applied',
                a.full_name AS 'Full Name',
                a.email AS 'Email Address',
                a.phone AS 'Phone Number',
                a.city AS 'City / Location',
                a.country_name AS 'Country',
                a.total_experience AS 'Total Experience',
                COALESCE(a.current_company, 'N/A') AS 'Current Company',
                COALESCE(a.current_job_title, 'N/A') AS 'Current Title',
                COALESCE(a.expected_salary, 'N/A') AS 'Expected CTC',
                a.notice_period AS 'Notice Period',
                COALESCE(a.linkedin_url, 'N/A') AS 'LinkedIn Profile',
                COALESCE(a.portfolio_url, 'N/A') AS 'Portfolio URL',
                a.status AS 'Application Status',
                a.created_at AS 'Applied Date'
            FROM `job_applications` a
            WHERE {$whereSql}
            ORDER BY a.created_at DESC
        ", $bindings);
    }

    public static function updateStatus(int $id, string $status): bool
    {
        self::ensureSchema();
        $status = strtoupper(trim($status));
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }
        self::execute("UPDATE `job_applications` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id", [
            ':status' => $status,
            ':id'     => $id
        ]);
        return true;
    }

    public static function updateAdminNotes(int $id, string $notes): bool
    {
        self::ensureSchema();
        self::execute("UPDATE `job_applications` SET `admin_notes` = :notes, `updated_at` = NOW() WHERE `id` = :id", [
            ':notes' => trim($notes),
            ':id'    => $id
        ]);
        return true;
    }
}
