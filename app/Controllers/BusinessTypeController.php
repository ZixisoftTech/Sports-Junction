<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class BusinessTypeController extends Controller
{
    public function index(): void
    {
        $this->render('masters/business_types', [
            'pageTitle'       => 'Business Type Management',
            'activeMenu'      => 'business_types',
            'metaDescription' => 'Manage business types (Manufacturer, Trader, Wholesaler, Exporter) used for vendor onboarding.',
        ]);
    }
}
