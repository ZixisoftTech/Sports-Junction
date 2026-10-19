<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Uploader;
use App\Helpers\Validator;
use App\Models\SitePopup;

class AdminPopupController extends Controller
{
    public function index(): void
    {
        SitePopup::ensureSchema();

        $this->render('admin/popups', [
            'pageTitle'  => 'Autoload Popup Management — Sports Junction',
            'activeMenu' => 'popups',
        ]);
    }

    public function data(): void
    {
        $draw = (int)($this->request->get('draw') ?? 1);
        try {
            $result = SitePopup::getDataTable($this->request->all());
            $this->json($result);
        } catch (\Throwable $e) {
            @error_log("SitePopup DataTable error: " . $e->getMessage());
            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Failed to load popups: ' . $e->getMessage()
            ]);
        }
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $popup = SitePopup::findById($id);
        if (!$popup) {
            $this->json(['success' => false, 'message' => 'Popup not found.'], 404);
            return;
        }

        $this->json(['success' => true, 'data' => $popup]);
    }

    public function store(): void
    {
        $input = $this->request->all();

        // 1. Handle uploaded image file if present
        $uploadedPath = null;
        if (!empty($_FILES['image_file']['name']) && empty($_FILES['image_file']['error'])) {
            try {
                $uploadedPath = Uploader::uploadImage($_FILES['image_file'], 'popups');
                $input['image'] = $uploadedPath;
            } catch (\Throwable $e) {
                $this->json([
                    'success' => false,
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                    'errors'  => ['image_file' => [$e->getMessage()]]
                ], 422);
                return;
            }
        }

        $validator = Validator::make($input);
        $rules = [
            'title' => 'required|max:255',
            'image' => 'required|max:500',
        ];

        if (!$validator->validate($rules)) {
            $this->json([
                'success' => false,
                'message' => 'Please provide a valid popup title and image.',
                'errors'  => $validator->errors()
            ], 422);
            return;
        }

        try {
            $id = SitePopup::create($input);
            $this->json([
                'success' => true,
                'message' => 'Site popup created successfully.',
                'id'      => $id
            ], 201);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Database error while saving popup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? $this->request->input('id') ?? 0);
        $existing = SitePopup::findById($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Site popup not found.'], 404);
            return;
        }

        $input = $this->request->all();

        // Handle file upload if supplied
        if (!empty($_FILES['image_file']['name']) && empty($_FILES['image_file']['error'])) {
            try {
                $uploadedPath = Uploader::uploadImage($_FILES['image_file'], 'popups');
                $input['image'] = $uploadedPath;
            } catch (\Throwable $e) {
                $this->json([
                    'success' => false,
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                    'errors'  => ['image_file' => [$e->getMessage()]]
                ], 422);
                return;
            }
        } elseif (empty($input['image'])) {
            $input['image'] = $existing['image'];
        }

        $validator = Validator::make($input);
        $rules = [
            'title' => 'required|max:255',
            'image' => 'required|max:500',
        ];

        if (!$validator->validate($rules)) {
            $this->json([
                'success' => false,
                'message' => 'Please provide a valid popup title and image.',
                'errors'  => $validator->errors()
            ], 422);
            return;
        }

        try {
            $success = SitePopup::update($id, $input);
            $this->json([
                'success' => $success,
                'message' => $success ? 'Site popup updated successfully.' : 'No changes were saved.'
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to update popup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(array $params): void
    {
        $id = (int)($params['id'] ?? $this->request->input('id') ?? 0);
        $existing = SitePopup::findById($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Site popup not found.'], 404);
            return;
        }

        try {
            $success = SitePopup::delete($id);
            $this->json([
                'success' => $success,
                'message' => $success ? 'Site popup permanently deleted.' : 'Deletion failed.'
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to delete popup: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(array $params): void
    {
        $id = (int)($params['id'] ?? $this->request->input('id') ?? 0);
        $existing = SitePopup::findById($id);
        if (!$existing) {
            $this->json(['success' => false, 'message' => 'Site popup not found.'], 404);
            return;
        }

        $requestedStatus = $this->request->input('status');
        if (in_array($requestedStatus, ['ACTIVE', 'INACTIVE'], true)) {
            $newStatus = $requestedStatus;
        } else {
            $newStatus = ($existing['status'] === 'ACTIVE') ? 'INACTIVE' : 'ACTIVE';
        }

        try {
            $success = SitePopup::setStatus($id, $newStatus);
            $this->json([
                'success' => $success,
                'message' => "Popup marked as {$newStatus}.",
                'status'  => $newStatus
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }
}
