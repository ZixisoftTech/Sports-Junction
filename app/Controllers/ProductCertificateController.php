<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class ProductCertificateController extends Controller
{
    public function index(): void
    {
        $this->render('masters/product_certificates', [
            'pageTitle' => 'Product Certificates Master — Sports Junction'
        ], 'admin');
    }
}
