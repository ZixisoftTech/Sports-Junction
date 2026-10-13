<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class CategoryController extends Controller
{
    public function index(): void
    {
        $this->render('masters/categories', [
            'pageTitle'       => 'Category Management',
            'activeMenu'      => 'categories',
            'metaDescription' => 'Manage sports product categories, display order, visual banners and status.',
        ]);
    }
}
