<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class CertificationTagController extends Controller
{
    public function index(): void
    {
        $this->render('masters/certification_tags', [
            'pageTitle' => 'Supplier Certification Tags Master — Sports Junction'
        ], 'admin');
    }
}
