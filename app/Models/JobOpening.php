<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

class JobOpening extends Model
{
    public const VALID_STATUSES = ['ACTIVE', 'DRAFT', 'CLOSED'];

    public static function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            self::execute("
                CREATE TABLE IF NOT EXISTS `job_openings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(200) NOT NULL,
                    `department` VARCHAR(100) NOT NULL,
                    `location` VARCHAR(100) NOT NULL,
                    `employment_type` VARCHAR(50) NOT NULL DEFAULT 'Full-time',
                    `experience_level` VARCHAR(100) NOT NULL,
                    `overview` TEXT NULL,
                    `responsibilities` TEXT NULL,
                    `requirements` TEXT NULL,
                    `benefits` TEXT NULL,
                    `status` ENUM('ACTIVE', 'DRAFT', 'CLOSED') NOT NULL DEFAULT 'ACTIVE',
                    `sort_order` INT NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_job_status (`status`),
                    INDEX idx_job_dept (`department`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed initial 3 jobs if table is empty
            $cnt = (int)(self::fetch("SELECT COUNT(*) AS cnt FROM `job_openings`")['cnt'] ?? 0);
            if ($cnt === 0) {
                self::execute("
                    INSERT INTO `job_openings` (
                        `title`, `department`, `location`, `employment_type`, `experience_level`,
                        `overview`, `responsibilities`, `requirements`, `benefits`, `status`, `sort_order`
                    ) VALUES 
                    (
                        'Senior Full-Stack Engineer (PHP & Modern Web)',
                        'Engineering',
                        'New Delhi / Hybrid',
                        'Full-time',
                        '4+ Years',
                        'Lead full-stack feature development across our core marketplace, messaging engines, RFQ workflows, and Super Admin command center.',
                        '• Design, develop, and deploy scalable B2B marketplace features in PHP, MySQL, and modern JavaScript.\n• Optimize high-volume database queries, indexing, and transactional integrity across catalog and enquiry engines.\n• Collaborate with UX/UI designers and category leaders to build responsive buyer and supplier workflows.\n• Champion clean architecture, automated testing, and secure coding practices.',
                        '• 4+ years of professional full-stack development experience.\n• Strong mastery of modern PHP 8+, MySQL/PDO, and relational database design.\n• Proficiency in Tailwind CSS, responsive frontends, and RESTful API integrations.\n• Solid understanding of server security, authentication, and session management.',
                        '• Market-competitive CTC with performance incentives\n• Comprehensive health and medical insurance\n• Hybrid flexibility with modern workstation equipment\n• Annual learning and conference allowance',
                        'ACTIVE',
                        1
                    ),
                    (
                        'Vendor Acquisition & Category Manager — Field Sports',
                        'Operations & Sourcing',
                        'Jalandhar / Meerut Hubs',
                        'Full-time',
                        '3+ Years',
                        'Identify, onboard, and audit tier-1 sporting goods manufacturers in northern industrial manufacturing belts. Ensure catalog excellence and supplier responsiveness.',
                        '• Prospect and onboard verified sports equipment manufacturers in cricket, football, and athletic gear clusters.\n• Conduct factory audits, verify GST/export certifications, and review production capacities.\n• Guide manufacturers through catalog onboarding, MOQ pricing setups, and digital quote responsiveness.\n• Analyze market procurement trends and expand supplier coverage for fast-growing sport categories.',
                        '• 3+ years in sports manufacturing, industrial procurement, or B2B category management.\n• Existing relationships with manufacturing units in Jalandhar, Meerut, or Delhi NCR.\n• Strong negotiation, commercial acumen, and vendor relationship management skills.\n• Willingness to travel across regional manufacturing clusters.',
                        '• Competitive base salary plus vendor acquisition bonuses\n• Travel allowances and field expense coverage\n• Health coverage and wellness benefits\n• Rapid career progression in a high-growth marketplace',
                        'ACTIVE',
                        2
                    ),
                    (
                        'B2B Customer Success Specialist',
                        'Customer Success',
                        'Remote / Delhi NCR',
                        'Full-time',
                        '2+ Years',
                        'Assist institutional buyers, international schools, sports academies, and distributors with RFQ formulation, sample ordering, and supplier coordination.',
                        '• Guide buyers through the custom manufacturing RFQ and prototype sampling lifecycle.\n• Act as a liaison between buyers and factories to clarify technical specifications and dispatch milestones.\n• Monitor enquiry response times and support dispute resolution or order mediation.\n• Gather buyer feedback to propose continuous enhancements to our sourcing tools.',
                        '• 2+ years of experience in B2B customer support, account management, or export coordination.\n• Exceptional written and verbal communication in English and Hindi.\n• High empathy, detail orientation, and problem-solving mindset.\n• Familiarity with sports goods, procurement, or trade operations is a plus.',
                        '• Competitive CTC with monthly customer success milestones\n• Remote work flexibility\n• Comprehensive group health coverage\n• Dynamic, supportive team environment',
                        'ACTIVE',
                        3
                    )
                ");
            }
        } catch (Throwable $e) {
            @error_log("JobOpening::ensureSchema error: " . $e->getMessage());
        }
    }

    public static function allActive(): array
    {
        self::ensureSchema();
        try {
            return self::fetchAll("
                SELECT j.*, 
                       (SELECT COUNT(*) FROM `job_applications` a WHERE a.job_opening_id = j.id) AS applications_count
                FROM `job_openings` j 
                WHERE j.`status` = 'ACTIVE' 
                ORDER BY j.`sort_order` ASC, j.`id` DESC
            ");
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function allForAdmin(array $params = []): array
    {
        self::ensureSchema();
        $where = ['1=1'];
        $bindings = [];

        if (!empty($params['status']) && $params['status'] !== 'ALL') {
            $where[] = "j.`status` = :st";
            $bindings[':st'] = strtoupper(trim($params['status']));
        }
        if (!empty($params['department']) && $params['department'] !== 'ALL') {
            $where[] = "j.`department` = :dept";
            $bindings[':dept'] = trim($params['department']);
        }
        $search = trim((string)($params['search'] ?? ''));
        if ($search !== '') {
            $where[] = "(j.`title` LIKE :s1 OR j.`location` LIKE :s2 OR j.`overview` LIKE :s3)";
            $term = '%' . $search . '%';
            $bindings[':s1'] = $term;
            $bindings[':s2'] = $term;
            $bindings[':s3'] = $term;
        }

        $whereSql = implode(' AND ', $where);

        return self::fetchAll("
            SELECT j.*,
                   (SELECT COUNT(*) FROM `job_applications` a WHERE a.job_opening_id = j.id) AS applications_count
            FROM `job_openings` j
            WHERE {$whereSql}
            ORDER BY j.`sort_order` ASC, j.`id` DESC
        ", $bindings);
    }

    public static function findById(int $id): ?array
    {
        self::ensureSchema();
        $row = self::fetch("
            SELECT j.*,
                   (SELECT COUNT(*) FROM `job_applications` a WHERE a.job_opening_id = j.id) AS applications_count
            FROM `job_openings` j 
            WHERE j.`id` = :id 
            LIMIT 1
        ", [':id' => $id]);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        self::ensureSchema();
        $title          = trim((string)($data['title'] ?? ''));
        $department     = trim((string)($data['department'] ?? 'General'));
        $location       = trim((string)($data['location'] ?? 'Remote'));
        $employmentType = trim((string)($data['employment_type'] ?? 'Full-time'));
        $expLevel       = trim((string)($data['experience_level'] ?? '1-3 Years'));
        $overview       = trim((string)($data['overview'] ?? ''));
        $resp           = trim((string)($data['responsibilities'] ?? ''));
        $req            = trim((string)($data['requirements'] ?? ''));
        $benefits       = trim((string)($data['benefits'] ?? ''));
        $status         = strtoupper(trim((string)($data['status'] ?? 'ACTIVE')));
        if (!in_array($status, self::VALID_STATUSES, true)) $status = 'ACTIVE';
        $sortOrder      = (int)($data['sort_order'] ?? 0);

        self::execute("
            INSERT INTO `job_openings` (
                `title`, `department`, `location`, `employment_type`, `experience_level`,
                `overview`, `responsibilities`, `requirements`, `benefits`, `status`, `sort_order`
            ) VALUES (
                :title, :dept, :loc, :etype, :exp,
                :overview, :resp, :req, :benefits, :st, :sort
            )
        ", [
            ':title'    => $title,
            ':dept'     => $department,
            ':loc'      => $location,
            ':etype'    => $employmentType,
            ':exp'      => $expLevel,
            ':overview' => $overview,
            ':resp'     => $resp,
            ':req'      => $req,
            ':benefits' => $benefits,
            ':st'       => $status,
            ':sort'     => $sortOrder,
        ]);

        return (int) self::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        self::ensureSchema();
        $status = strtoupper(trim((string)($data['status'] ?? 'ACTIVE')));
        if (!in_array($status, self::VALID_STATUSES, true)) $status = 'ACTIVE';

        self::execute("
            UPDATE `job_openings` SET 
                `title` = :title,
                `department` = :dept,
                `location` = :loc,
                `employment_type` = :etype,
                `experience_level` = :exp,
                `overview` = :overview,
                `responsibilities` = :resp,
                `requirements` = :req,
                `benefits` = :benefits,
                `status` = :st,
                `sort_order` = :sort,
                `updated_at` = NOW()
            WHERE `id` = :id
        ", [
            ':id'       => $id,
            ':title'    => trim((string)($data['title'] ?? '')),
            ':dept'     => trim((string)($data['department'] ?? 'General')),
            ':loc'      => trim((string)($data['location'] ?? 'Remote')),
            ':etype'    => trim((string)($data['employment_type'] ?? 'Full-time')),
            ':exp'      => trim((string)($data['experience_level'] ?? '1-3 Years')),
            ':overview' => trim((string)($data['overview'] ?? '')),
            ':resp'     => trim((string)($data['responsibilities'] ?? '')),
            ':req'      => trim((string)($data['requirements'] ?? '')),
            ':benefits' => trim((string)($data['benefits'] ?? '')),
            ':st'       => $status,
            ':sort'     => (int)($data['sort_order'] ?? 0),
        ]);

        return true;
    }

    public static function delete(int $id): bool
    {
        self::ensureSchema();
        // Remove or nullify candidate applications
        self::execute("UPDATE `job_applications` SET `job_opening_id` = NULL WHERE `job_opening_id` = :id", [':id' => $id]);
        self::execute("DELETE FROM `job_openings` WHERE `id` = :id", [':id' => $id]);
        return true;
    }

    public static function toggleStatus(int $id): bool
    {
        self::ensureSchema();
        $job = self::findById($id);
        if (!$job) return false;
        $newStatus = ($job['status'] === 'ACTIVE') ? 'CLOSED' : 'ACTIVE';
        self::execute("UPDATE `job_openings` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id", [
            ':st' => $newStatus,
            ':id' => $id
        ]);
        return true;
    }
}
