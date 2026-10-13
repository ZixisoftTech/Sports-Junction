<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class CountryController extends Controller
{
    public function index(): void
    {
        $this->render('masters/countries', [
            'pageTitle'       => 'Country Management',
            'activeMenu'      => 'countries',
            'metaDescription' => 'Manage countries, ISO codes, international dial codes and status for the sports marketplace.',
        ]);
    }
}
