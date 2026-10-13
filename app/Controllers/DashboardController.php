<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditLog;

class DashboardController extends Controller
{
    public function index(): void
    {
        $counts = AuditLog::getCounts();
        $recentAudits = AuditLog::getRecent(10);

        $this->render('dashboard/index', [
            'pageTitle'    => 'Dashboard — Sports Junction Super Admin',
            'counts'       => $counts,
            'recentAudits' => $recentAudits,
            'user'         => $this->authUser(),
        ]);
    }
}
