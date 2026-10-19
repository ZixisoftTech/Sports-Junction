<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index(): void
    {
        $categories = Category::allActive();
        $homepageCategories = Category::getHomepageCategories();
        $featuredProducts = Product::featuredProducts(8);

        $this->render('frontend/home', [
            'pageTitle'          => 'Sports Junction — India\'s Sports Manufacturing Marketplace | Factory-Direct B2B Sourcing',
            'metaDescription'    => 'Direct B2B sourcing from verified sports and fitness equipment manufacturers in India. Handcrafted cricket bats, FIFA standard footballs, agility gear, and gym weights at ex-factory wholesale pricing.',
            'categories'         => $categories,
            'homepageCategories' => $homepageCategories,
            'featuredProducts'   => $featuredProducts,
        ], 'frontend');
    }
}
