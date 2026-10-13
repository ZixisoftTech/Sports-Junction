<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;

class SubcategoryController extends Controller
{
    public function index(): void
    {
        $categories = Category::allActive();
        $this->render('masters/subcategories', [
            'pageTitle'       => 'Subcategory Management',
            'activeMenu'      => 'subcategories',
            'categories'      => $categories,
            'metaDescription' => 'Manage sports product subcategories linked to parent categories.',
        ]);
    }
}
