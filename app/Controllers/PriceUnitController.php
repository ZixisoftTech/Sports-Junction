<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class PriceUnitController extends Controller
{
    public function index(): void
    {
        $this->render('masters/price_units', [
            'pageTitle'       => 'Price Unit Management',
            'activeMenu'      => 'price_units',
            'metaDescription' => 'Manage product pricing units (Piece, Set, Box, Kg, Ton, Meter) across sports catalogs.',
        ]);
    }
}
