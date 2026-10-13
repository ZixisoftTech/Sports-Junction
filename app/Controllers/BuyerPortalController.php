<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Mailer;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Models\BuyerFavourite;
use App\Models\BuyerRecentlyViewed;
use App\Models\Country;
use App\Models\Otp;
use App\Models\Product;
use App\Models\ProductInquiry;
use App\Models\User;

class BuyerPortalController extends Controller
{
    private function getBuyer(): array
    {
        $user = $this->authUser();
        if (!$user || ($user['role'] ?? '') !== 'BUYER') {
            $this->redirect('/login');
            exit;
        }
        $fullUser = User::findById((int)$user['id']);
        return $fullUser ?: $user;
    }

    public function dashboard(): void
    {
        $buyer = $this->getBuyer();
        $enquiries = ProductInquiry::getBuyerEnquiries((int)$buyer['id'], $buyer['email']);
        $favourites = BuyerFavourite::getBuyerFavourites((int)$buyer['id']);
        $recentViews = BuyerRecentlyViewed::getRecent((int)$buyer['id'], 6);

        $counts = [
            'enquiries'  => count($enquiries),
            'favourites' => count($favourites),
            'recent'     => count($recentViews),
        ];

        $this->render('buyer/dashboard', [
            'pageTitle'   => 'Buyer Dashboard — Sports Junction',
            'buyer'       => $buyer,
            'counts'      => $counts,
            'enquiries'   => array_slice($enquiries, 0, 5),
            'favourites'  => array_slice($favourites, 0, 4),
            'recentViews' => $recentViews,
        ], 'frontend');
    }

    public function enquiries(): void
    {
        $buyer = $this->getBuyer();
        $enquiries = ProductInquiry::getBuyerEnquiries((int)$buyer['id'], $buyer['email']);

        $this->render('buyer/enquiries', [
            'pageTitle' => 'My Product Inquiries — Sports Junction',
            'buyer'     => $buyer,
            'enquiries' => $enquiries,
        ], 'frontend');
    }

    public function enquiryDetail(array $params): void
    {
        $buyer = $this->getBuyer();
        $id = (int)($params['id'] ?? 0);
        $inquiry = ProductInquiry::findById($id);

        if (!$inquiry) {
            Session::flash('error', 'Inquiry not found.');
            $this->redirect('/buyer/enquiries');
            return;
        }

        // Ownership check: Contact Us messages only match if submitted while logged in (buyer_user_id matches)
        $isContactUs = (($inquiry['enquiry_source'] ?? '') === 'CONTACT_US');
        $matchesUser = ((int)($inquiry['buyer_user_id'] ?? 0) === (int)$buyer['id']);
        $matchesEmail = !$isContactUs && (strtolower(trim($inquiry['buyer_email'] ?? '')) === strtolower(trim($buyer['email'] ?? '')));

        if (!$matchesUser && !$matchesEmail) {
            Session::flash('error', 'Unauthorized access to this enquiry.');
            $this->redirect('/buyer/enquiries');
            return;
        }

        $replies = ProductInquiry::getReplies($id);

        $this->render('buyer/enquiry_detail', [
            'pageTitle' => "Inquiry #{$id} Details — Sports Junction",
            'buyer'     => $buyer,
            'inquiry'   => $inquiry,
            'replies'   => $replies,
        ], 'frontend');
    }

    public function replyEnquiry(array $params): void
    {
        $buyer = $this->getBuyer();
        $id = (int)($params['id'] ?? 0);
        $inquiry = ProductInquiry::findById($id);

        if (!$inquiry) {
            $this->json(['success' => false, 'message' => 'Inquiry not found.'], 404);
            return;
        }

        $isContactUs = (($inquiry['enquiry_source'] ?? '') === 'CONTACT_US');
        $matchesUser = ((int)($inquiry['buyer_user_id'] ?? 0) === (int)$buyer['id']);
        $matchesEmail = !$isContactUs && (strtolower(trim($inquiry['buyer_email'] ?? '')) === strtolower(trim($buyer['email'] ?? '')));

        if (!$matchesUser && !$matchesEmail) {
            $this->json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }

        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $message = trim((string)$this->request->input('message', ''));
        if (empty($message)) {
            $this->json(['success' => false, 'message' => 'Reply message cannot be empty.'], 422);
            return;
        }

        ProductInquiry::addReply($id, (int)$buyer['id'], 'BUYER', $message);

        $this->json(['success' => true, 'message' => 'Your reply has been dispatched to the supplier.']);
    }

    public function favourites(): void
    {
        $buyer = $this->getBuyer();
        $favourites = BuyerFavourite::getBuyerFavourites((int)$buyer['id']);

        $this->render('buyer/favourites', [
            'pageTitle'  => 'Saved Favourite Products — Sports Junction',
            'buyer'      => $buyer,
            'favourites' => $favourites,
        ], 'frontend');
    }

    public function toggleFavourite(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $productId = (int)$this->request->input('product_id', 0);
        if ($productId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid product.'], 422);
            return;
        }

        $isFav = BuyerFavourite::isFavourite((int)$buyer['id'], $productId);
        if ($isFav) {
            BuyerFavourite::remove((int)$buyer['id'], $productId);
            $this->json(['success' => true, 'favourited' => false, 'message' => 'Removed from favourites.']);
        } else {
            $ok = BuyerFavourite::add((int)$buyer['id'], $productId);
            if ($ok) {
                $this->json(['success' => true, 'favourited' => true, 'message' => 'Added to favourites!']);
            } else {
                $this->json(['success' => false, 'message' => 'This product is currently unavailable.'], 400);
            }
        }
    }

    public function removeFavourite(array $params): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $productId = (int)($params['id'] ?? 0);
        BuyerFavourite::remove((int)$buyer['id'], $productId);

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'message' => 'Removed from favourites.']);
        } else {
            Session::flash('success', 'Product removed from your saved favourites.');
            $this->redirect('/buyer/favourites');
        }
    }

    public function recentlyViewed(): void
    {
        $buyer = $this->getBuyer();
        $recent = BuyerRecentlyViewed::getRecent((int)$buyer['id'], 20);

        $this->render('buyer/recently_viewed', [
            'pageTitle' => 'Recently Viewed Equipment — Sports Junction',
            'buyer'     => $buyer,
            'products'  => $recent,
        ], 'frontend');
    }

    public function clearRecentViews(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        BuyerRecentlyViewed::clearHistory((int)$buyer['id']);

        if ($this->request->isAjax()) {
            $this->json(['success' => true, 'message' => 'Browsing history cleared.']);
        } else {
            Session::flash('success', 'Browsing history cleared successfully.');
            $this->redirect('/buyer/recently-viewed');
        }
    }

    public function profile(): void
    {
        $buyer = $this->getBuyer();
        $countries = Country::allActive();

        $this->render('buyer/profile', [
            'pageTitle' => 'My Account & Profile — Sports Junction',
            'buyer'     => $buyer,
            'countries' => $countries,
        ], 'frontend');
    }

    public function updateProfile(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'name'         => 'required|max:150',
            'company_name' => 'required|max:150',
            'email'        => 'required|email|max:191',
            'phone'        => 'required|max:30',
            'country_id'   => 'required|integer',
        ];

        if (!$validator->validate($rules)) {
            Session::flash('error', $validator->firstError() ?? 'Validation failed.');
            $this->redirect('/buyer/profile');
            return;
        }

        $newEmail = strtolower(trim((string)$input['email']));
        if (User::emailExists($newEmail, (int)$buyer['id'])) {
            Session::flash('error', 'This email address is already registered to another account.');
            $this->redirect('/buyer/profile');
            return;
        }

        $emailChanged = strtolower(trim($buyer['email'])) !== $newEmail;

        User::updateProfile((int)$buyer['id'], [
            'name'         => $input['name'],
            'company_name' => $input['company_name'],
            'email'        => $newEmail,
            'phone'        => $input['phone'],
            'country_code' => $input['country_code'] ?? '+91',
            'country_id'   => $input['country_id'],
        ]);

        if ($emailChanged) {
            // Re-verify email flow
            $otp = Otp::generate($newEmail, 'REGISTRATION', $this->request->getIp());
            Mailer::sendBuyerOtp($newEmail, (string)$input['name'], $otp);
            Session::set('otp_email', $newEmail);
            Session::set('otp_purpose', 'REGISTRATION');
            Session::remove('auth_user');

            Session::flash('info', 'Your email address was updated. Please enter the verification passcode sent to your new email.');
            $this->redirect('/verify-email');
            return;
        }

        // Update session
        Session::set('auth_user', array_merge(Session::get('auth_user'), [
            'name'  => $input['name'],
            'email' => $newEmail,
        ]));

        Session::flash('success', 'Your profile details have been saved successfully.');
        $this->redirect('/buyer/profile');
    }

    public function changePassword(): void
    {
        $buyer = $this->getBuyer();
        $token = $this->request->input('_csrf') ?? $this->request->input('csrf_token') ?? $this->request->header('X-CSRF-Token');
        CSRF::validateOrFail($token);

        $currentPass = (string)$this->request->post('current_password', '');
        $newPass = (string)$this->request->post('new_password', '');
        $confPass = (string)$this->request->post('password_confirmation', '');

        if (!User::verifyPassword($currentPass, $buyer['password'])) {
            Session::flash('error', 'Current password is incorrect.');
            $this->redirect('/buyer/profile#security');
            return;
        }

        if (strlen($newPass) < 8) {
            Session::flash('error', 'New password must be at least 8 characters in length.');
            $this->redirect('/buyer/profile#security');
            return;
        }

        if ($newPass !== $confPass) {
            Session::flash('error', 'New password and confirmation do not match.');
            $this->redirect('/buyer/profile#security');
            return;
        }

        User::updatePassword((int)$buyer['id'], $newPass);
        Session::flash('success', 'Your password has been changed securely.');
        $this->redirect('/buyer/profile');
    }
}
