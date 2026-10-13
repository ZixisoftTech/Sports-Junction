<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Country;

class CityController extends Controller
{
    public function index(): void
    {
        $countries = Country::allActive();

        $this->render('masters/cities', [
            'pageTitle'       => 'City Management',
            'activeMenu'      => 'cities',
            'metaDescription' => 'Manage cities, industrial zones, and manufacturing clusters linked to states and countries.',
            'countries'       => $countries,
        ]);
    }
}
