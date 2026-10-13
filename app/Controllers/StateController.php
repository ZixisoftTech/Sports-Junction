<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Country;

class StateController extends Controller
{
    public function index(): void
    {
        $countries = Country::allActive();

        $this->render('masters/states', [
            'pageTitle'       => 'State Management',
            'activeMenu'      => 'states',
            'metaDescription' => 'Manage states, state codes, and country mappings across sports manufacturing hubs.',
            'countries'       => $countries,
        ]);
    }
}
