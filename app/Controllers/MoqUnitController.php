<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class MoqUnitController extends Controller
{
    public function index(): void
    {
        $this->render('masters/moq_units', [
            'pageTitle'       => 'MOQ Unit Management',
            'activeMenu'      => 'moq_units',
            'metaDescription' => 'Manage minimum order quantity (MOQ) units (Pieces, Sets, Boxes, Kgs, Tons, Dozens) for bulk inquiries.',
        ]);
    }
}
