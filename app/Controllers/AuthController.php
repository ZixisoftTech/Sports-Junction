<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\CSRF;
use App\Helpers\Mailer;
use App\Helpers\Session;
use App\Helpers\Validator;
use App\Middleware\RateLimiter;
use App\Models\BusinessType;
use App\Models\City;
use App\Models\Country;
use App\Models\Otp;
use App\Models\State;
use App\Models\Supplier;
use App\Models\User;

class AuthController extends Controller
{
    // =========================================================================
    // 1. SUPER ADMIN LOGIN
    // =========================================================================

    public function showAdminLogin(): void
    {
        $user = $this->authUser();
        if ($user && ($user['role'] ?? '') === 'SUPER_ADMIN') {
            $this->redirect('/admin/dashboard');
            return;
        }

        $this->render('auth/login', [
            'pageTitle' => 'Super Admin Login — Sports Junction',
        ], 'auth');
    }

    public function adminLogin(): void
    {
        $this->handleLogin(true);
    }

    // =========================================================================
    // 2. UNIFIED / BUYER / SUPPLIER LOGIN (Stitch Screen 4)
    // =========================================================================

    public function showLogin(): void
    {
        $user = $this->authUser();
        if ($user) {
            $role = $user['role'] ?? '';
            if ($role === 'SUPER_ADMIN') {
                $this->redirect('/admin/dashboard');
                return;
            } elseif ($role === 'SUPPLIER') {
                $supplier = Supplier::findByUserId((int)$user['id']);
                if ($supplier && ($supplier['approval_status'] ?? '') === 'APPROVED') {
                    $this->redirect('/supplier/dashboard');
                } else {
                    $this->redirect('/supplier/pending-approval');
                }
                return;
            } elseif ($role === 'BUYER') {
                $this->redirect('/buyer/dashboard');
                return;
            }
        }

        $this->render('auth/login', [
            'pageTitle' => 'Sign In — Sports Junction B2B Marketplace',
        ], 'auth');
    }

    public function login(): void
    {
        $this->handleLogin(false, false);
    }

    public function showSellerLogin(): void
    {
        $user = $this->authUser();
        if ($user) {
            $role = $user['role'] ?? '';
            if ($role === 'SUPPLIER') {
                $supplier = Supplier::findByUserId((int)$user['id']);
                if ($supplier && ($supplier['approval_status'] ?? '') === 'APPROVED') {
                    $this->redirect('/supplier/dashboard');
                } else {
                    $this->redirect('/supplier/pending-approval');
                }
                return;
            } elseif ($role === 'SUPER_ADMIN') {
                $this->redirect('/admin/dashboard');
                return;
            } else {
                $this->redirect('/buyer/dashboard');
                return;
            }
        }

        $this->render('auth/seller_login', [
            'pageTitle' => 'Seller Sign In — Sports Junction B2B Marketplace',
        ], 'auth');
    }

    public function sellerLogin(): void
    {
        $this->handleLogin(false, true);
    }

    private function handleLogin(bool $adminOnly = false, bool $sellerOnly = false): void
    {
        $ip = $this->request->getIp();
        $email = strtolower(trim((string)$this->request->post('email', '')));
        $password = (string)$this->request->post('password', '');
        $remember = (bool)$this->request->post('remember', false);
        $csrfToken = (string)$this->request->post('csrf_token', '');
        $redirectBack = $adminOnly ? '/admin/login' : ($sellerOnly ? '/seller/login' : '/login');

        // 1. Rate Limiting Check
        if (RateLimiter::isLocked($ip, $email)) {
            $rem = RateLimiter::getRemainingLockoutSeconds($ip, $email);
            $minutes = max(1, (int) ceil($rem / 60));
            Session::flash('error', "Too many failed attempts. Access temporarily locked. Please try again in {$minutes} minute(s).");
            $this->redirect($redirectBack);
            return;
        }

        // 2. CSRF Token Validation
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired or invalid. Please refresh and try again.');
            $this->redirect($redirectBack);
            return;
        }

        // 3. Input Validation
        $validator = Validator::make([
            'email'    => $email,
            'password' => $password,
        ]);

        if (!$validator->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ])) {
            Session::flash('error', $validator->firstError() ?? 'Invalid credentials.');
            $this->redirect($redirectBack);
            return;
        }

        // 4. Authenticate User
        $user = User::findByEmail($email);
        $genericError = 'Invalid email address or password.';

        if (!$user) {
            RateLimiter::recordFailedAttempt($ip, $email);
            Session::flash('error', $genericError);
            $this->redirect($redirectBack);
            return;
        }

        if (!User::verifyPassword($password, $user['password'])) {
            RateLimiter::recordFailedAttempt($ip, $email);
            Session::flash('error', $genericError);
            $this->redirect($redirectBack);
            return;
        }

        // Check active / verification status
        $userStatus = $user['status'] ?? 'ACTIVE';
        if ($userStatus === 'INACTIVE') {
            Session::flash('error', 'This account has been deactivated or suspended. Please contact support.');
            $this->redirect($redirectBack);
            return;
        }

        // If admin-only route requested, enforce SUPER_ADMIN
        if ($adminOnly && ($user['role'] ?? '') !== 'SUPER_ADMIN') {
            RateLimiter::recordFailedAttempt($ip, $email);
            Session::flash('error', 'Access restricted. Only authorized Super Administrators may sign in.');
            $this->redirect($redirectBack);
            return;
        }

        // Support Dual Login: If seller-only route requested and user is BUYER, ensure supplier profile exists
        if ($sellerOnly) {
            $supplierRecord = Supplier::findByUserId((int)$user['id']);
            if (!$supplierRecord) {
                // Auto-provision supplier profile so the account can act as both Buyer and Supplier
                try {
                    $supplierId = Supplier::create([
                        'user_id'          => (int)$user['id'],
                        'business_type_id' => 1,
                        'country_id'       => (int)($user['country_id'] ?? 1),
                        'state_id'         => 1,
                        'city_id'          => 1,
                        'company_name'     => !empty($user['company_name']) ? $user['company_name'] : ($user['name'] . ' Enterprises'),
                        'contact_person'   => $user['name'],
                        'email'            => $user['email'],
                        'phone'            => $user['phone'] ?? '+919876543210',
                        'address'          => 'Sports Manufacturing Hub',
                        'postal_code'      => '250001',
                        'status'           => 'ACTIVE',
                        'approval_status'  => 'APPROVED',
                    ]);
                    // Update user role if needed to allow supplier access
                    $db = \App\Core\Database::getConnection();
                    $db->prepare("UPDATE users SET role = 'SUPPLIER' WHERE id = :id AND role = 'BUYER'")
                       ->execute([':id' => $user['id']]);
                    $user['role'] = 'SUPPLIER';
                } catch (\Throwable $e) {}
            }
        }

        // If supplier, check supplier approval status & suspension
        $supplierRecord = Supplier::findByUserId((int)$user['id']);
        if ($supplierRecord) {
            if (($supplierRecord['approval_status'] ?? '') === 'SUSPENDED' || ($supplierRecord['status'] ?? '') === 'INACTIVE') {
                Session::flash('error', 'Your manufacturer facility account has been suspended or disabled. Please contact administration.');
                $this->redirect($redirectBack);
                return;
            }
        }

        if ($userStatus === 'PENDING_VERIFICATION' && empty($user['email_verified_at'])) {
            Session::set('otp_email', $user['email']);
            Session::set('otp_purpose', $user['role'] === 'SUPPLIER' ? 'SUPPLIER_REGISTRATION' : 'REGISTRATION');
            Session::flash('error', 'Please verify your business email address before continuing.');
            $this->redirect('/verify-email');
            return;
        }

        // Clear attempts & regenerate session
        RateLimiter::clearAttempts($ip, $email);
        Session::regenerate(true);

        Session::set('auth_user', [
            'id'     => (int)$user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'role'   => $user['role'],
            'status' => $user['status'] ?? 'ACTIVE',
        ]);

        if ($supplierRecord) {
            Session::set('auth_supplier', $supplierRecord);
        }

        User::updateLastLogin((int)$user['id']);

        // Remember Me: set secure cookie token for 30 days
        if ($remember) {
            $rememberToken = bin2hex(random_bytes(32));
            User::setRememberToken((int)$user['id'], $rememberToken);
            setcookie('sj_remember', $rememberToken, [
                'expires'  => time() + (86400 * 30),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        // Redirect based on portal route and role
        if ($adminOnly || ($user['role'] ?? '') === 'SUPER_ADMIN') {
            Session::flash('success', "Welcome back, {$user['name']}! Signed in as Super Administrator.");
            $this->redirect('/admin/dashboard');
            return;
        }

        if ($sellerOnly) {
            $this->redirect('/supplier/dashboard');
            return;
        }

        // Standard /login route
        if (($user['role'] ?? '') === 'SUPPLIER' && !$this->request->get('buyer_portal')) {
            $supplier = Supplier::findByUserId((int)$user['id']);
            if ($supplier && ($supplier['approval_status'] ?? '') === 'APPROVED') {
                $this->redirect('/supplier/dashboard');
            } else {
                $this->redirect('/supplier/pending-approval');
            }
        } else {
            Session::flash('success', "Welcome back, {$user['name']}!");
            $this->redirect('/buyer/dashboard');
        }
    }

    // =========================================================================
    // 3. BUYER REGISTRATION (Stitch Screen 1)
    // =========================================================================

    public function showRegister(): void
    {
        $countries = Country::allActive();
        $this->render('auth/register', [
            'pageTitle' => 'Create Your Buyer Account — Sports Junction',
            'countries' => $countries,
        ], 'auth');
    }

    public function register(): void
    {
        $csrfToken = (string)$this->request->post('csrf_token', '');
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired. Please refresh and try again.');
            $this->redirect('/register');
            return;
        }

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'name'                  => 'required|max:150',
            'company_name'          => 'required|max:150',
            'email'                 => 'required|email|max:191',
            'phone'                 => 'required|max:30',
            'country_id'            => 'required|integer',
            'password'              => 'required|min:8',
            'password_confirmation' => 'required',
            'terms'                 => 'required',
        ];

        if (!$validator->validate($rules)) {
            Session::flash('error', $validator->firstError() ?? 'Validation failed.');
            Session::flash('old_input', $input);
            $this->redirect('/register');
            return;
        }

        if ($input['password'] !== $input['password_confirmation']) {
            Session::flash('error', 'The password and confirm password fields do not match.');
            Session::flash('old_input', $input);
            $this->redirect('/register');
            return;
        }

        $email = strtolower(trim((string)$input['email']));
        if (User::emailExists($email)) {
            Session::flash('error', 'An account with this email address already exists. Please log in.');
            Session::flash('old_input', $input);
            $this->redirect('/register');
            return;
        }

        try {
            // Create pending buyer user
            $userId = User::createBuyer([
                'name'         => $input['name'],
                'company_name' => $input['company_name'],
                'email'        => $email,
                'password'     => $input['password'],
                'phone'        => $input['phone'],
                'country_code' => $input['country_code'] ?? '+91',
                'country_id'   => (int)$input['country_id'],
            ]);

            // Generate 6-digit OTP
            $otp = Otp::generate($email, 'REGISTRATION', $this->request->getIp());

            // Send transactional email
            Mailer::sendBuyerOtp($email, (string)$input['name'], $otp);

            // Set session for OTP verification
            Session::set('otp_email', $email);
            Session::set('otp_purpose', 'REGISTRATION');
            Session::set('otp_user_id', $userId);

            Session::flash('success', "A 6-digit verification code has been dispatched to {$email}.");
            $this->redirect('/verify-email');
        } catch (\Throwable $e) {
            Session::flash('error', 'Registration error: ' . $e->getMessage());
            Session::flash('old_input', $input);
            $this->redirect('/register');
        }
    }

    // =========================================================================
    // 4. EMAIL OTP VERIFICATION (Stitch Screen 2)
    // =========================================================================

    public function showVerifyOtp(): void
    {
        $email = Session::get('otp_email');
        $purpose = Session::get('otp_purpose') ?? 'REGISTRATION';

        if (empty($email)) {
            $this->redirect('/register');
            return;
        }

        $this->render('auth/verify_otp', [
            'pageTitle' => 'Verify Your Email — Sports Junction',
            'email'     => $email,
            'purpose'   => $purpose,
        ], 'auth');
    }

    public function verifyOtp(): void
    {
        $csrfToken = (string)$this->request->post('csrf_token', '');
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired. Please try again.');
            $this->redirect('/verify-email');
            return;
        }

        $email = strtolower(trim((string)$this->request->post('email', '')));
        $otp = trim((string)$this->request->post('otp', ''));
        $purpose = trim((string)$this->request->post('purpose', 'REGISTRATION'));

        if (empty($email) || empty($otp)) {
            Session::flash('error', 'Please enter the 6-digit passcode.');
            $this->redirect('/verify-email');
            return;
        }

        $result = Otp::verify($email, $otp, $purpose);

        if (!$result['success']) {
            Session::flash('error', $result['message']);
            $this->redirect('/verify-email');
            return;
        }

        // Success handling by purpose
        $user = User::findByEmail($email);
        if (!$user) {
            Session::flash('error', 'Account record not found.');
            $this->redirect('/login');
            return;
        }

        if ($purpose === 'REGISTRATION') {
            User::markEmailVerified((int)$user['id'], 'BUYER');
            Mailer::sendBuyerVerified($email, $user['name']);

            // Auto-login buyer
            Session::regenerate(true);
            Session::set('auth_user', [
                'id'     => (int)$user['id'],
                'name'   => $user['name'],
                'email'  => $user['email'],
                'role'   => 'BUYER',
                'status' => 'ACTIVE',
            ]);

            Session::set('verified_role', 'BUYER');
            $this->redirect('/email-verified');
            return;
        } elseif ($purpose === 'SUPPLIER_REGISTRATION') {
            User::markEmailVerified((int)$user['id'], 'SUPPLIER');
            // Update supplier status to PENDING_APPROVAL
            $supplier = Supplier::findByUserId((int)$user['id']);
            if ($supplier) {
                Supplier::updateApprovalStatus((int)$supplier['id'], 'PENDING_APPROVAL');
                Mailer::sendSupplierApplicationReceived($email, $supplier['company_name']);
            }

            // Auto-login session for status checking
            Session::regenerate(true);
            Session::set('auth_user', [
                'id'     => (int)$user['id'],
                'name'   => $user['name'],
                'email'  => $user['email'],
                'role'   => 'SUPPLIER',
                'status' => 'PENDING_VERIFICATION',
            ]);

            Session::set('verified_role', 'SUPPLIER');
            $this->redirect('/email-verified');
            return;
        }

        $this->redirect('/login');
    }

    public function resendOtp(): void
    {
        $input = $this->request->all();
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $purpose = trim((string)($input['purpose'] ?? 'REGISTRATION'));

        if (empty($email)) {
            $this->json(['success' => false, 'message' => 'Email address is required.'], 400);
            return;
        }

        $canResend = Otp::canResend($email, $purpose, 60);
        if (!$canResend['allowed']) {
            $this->json([
                'success'   => false,
                'message'   => "Please wait {$canResend['remaining']} second(s) before requesting a new passcode.",
                'remaining' => $canResend['remaining'],
            ], 429);
            return;
        }

        $user = User::findByEmail($email);
        $name = $user['name'] ?? 'User';

        $otp = Otp::generate($email, $purpose, $this->request->getIp());

        if ($purpose === 'SUPPLIER_REGISTRATION') {
            Mailer::sendSupplierOtp($email, $name, $otp);
        } elseif ($purpose === 'PASSWORD_RESET') {
            Mailer::sendPasswordResetOtp($email, $name, $otp);
        } else {
            Mailer::sendBuyerOtp($email, $name, $otp);
        }

        $this->json([
            'success' => true,
            'message' => 'A fresh verification passcode has been dispatched.',
        ]);
    }

    // =========================================================================
    // 5. EMAIL VERIFIED SUCCESS SCREEN (Stitch Screen 3)
    // =========================================================================

    public function showVerified(): void
    {
        $role = Session::get('verified_role') ?? 'BUYER';
        $this->render('auth/verification_success', [
            'pageTitle' => 'Email Verified Successfully — Sports Junction',
            'role'      => $role,
        ], 'auth');
    }

    // =========================================================================
    // 6. FORGOT & RESET PASSWORD WORKFLOW
    // =========================================================================

    public function showForgotPassword(): void
    {
        $this->render('auth/forgot_password', [
            'pageTitle' => 'Forgot Password — Sports Junction',
        ], 'auth');
    }

    public function forgotPassword(): void
    {
        $csrfToken = (string)$this->request->post('csrf_token', '');
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired. Please refresh and try again.');
            $this->redirect('/forgot-password');
            return;
        }

        $email = strtolower(trim((string)$this->request->post('email', '')));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid business email address.');
            $this->redirect('/forgot-password');
            return;
        }

        $user = User::findByEmail($email);

        // Always show generic response to prevent account enumeration
        if ($user && ($user['status'] ?? '') === 'ACTIVE') {
            $otp = Otp::generate($email, 'PASSWORD_RESET', $this->request->getIp());
            Mailer::sendPasswordResetOtp($email, $user['name'], $otp);
            Session::set('reset_email', $email);
        } else {
            Session::set('reset_email', $email);
        }

        Session::flash('success', "If an active account exists for {$email}, a 6-digit recovery passcode has been dispatched.");
        $this->redirect('/reset-password');
    }

    public function showResetPassword(): void
    {
        $email = Session::get('reset_email') ?? '';
        if (empty($email)) {
            $this->redirect('/forgot-password');
            return;
        }

        $this->render('auth/reset_password', [
            'pageTitle' => 'Set New Password — Sports Junction',
            'email'     => $email,
        ], 'auth');
    }

    public function resetPassword(): void
    {
        $csrfToken = (string)$this->request->post('csrf_token', '');
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired. Please try again.');
            $this->redirect('/reset-password');
            return;
        }

        $email = strtolower(trim((string)$this->request->post('email', '')));
        $otp = trim((string)$this->request->post('otp', ''));
        $password = (string)$this->request->post('password', '');
        $conf = (string)$this->request->post('password_confirmation', '');

        if (empty($password) || strlen($password) < 8) {
            Session::flash('error', 'Password must be at least 8 characters in length.');
            $this->redirect('/reset-password');
            return;
        }

        if ($password !== $conf) {
            Session::flash('error', 'Passwords do not match.');
            $this->redirect('/reset-password');
            return;
        }

        $verify = Otp::verify($email, $otp, 'PASSWORD_RESET');
        if (!$verify['success']) {
            Session::flash('error', $verify['message']);
            $this->redirect('/reset-password');
            return;
        }

        $user = User::findByEmail($email);
        if ($user) {
            User::updatePassword((int)$user['id'], $password);
            Session::remove('reset_email');
            Session::flash('success', 'Your password has been successfully reset! Please sign in with your new credentials.');
            $this->redirect('/login');
        } else {
            Session::flash('error', 'Account not found.');
            $this->redirect('/login');
        }
    }

    // =========================================================================
    // 7. SUPPLIER REGISTRATION
    // =========================================================================

    public function showSupplierRegister(): void
    {
        $countries = Country::allActive();
        $businessTypes = BusinessType::allActive();

        $this->render('auth/supplier_register', [
            'pageTitle'     => 'Supplier & Factory Onboarding — Sports Junction',
            'countries'     => $countries,
            'businessTypes' => $businessTypes,
        ], 'auth');
    }

    public function supplierRegister(): void
    {
        $csrfToken = (string)$this->request->post('csrf_token', '');
        if (!CSRF::validate($csrfToken)) {
            Session::flash('error', 'Security token expired. Please refresh and try again.');
            $this->redirect('/supplier/register');
            return;
        }

        $input = $this->request->all();
        $validator = Validator::make($input);

        $rules = [
            'company_name'          => 'required|max:150',
            'contact_person'        => 'required|max:100',
            'email'                 => 'required|email|max:191',
            'phone'                 => 'required|max:30',
            'business_type_id'      => 'required|integer',
            'address'               => 'required|max:255',
            'country_id'            => 'required|integer',
            'state_id'              => 'required|integer',
            'city_id'               => 'required|integer',
            'postal_code'           => 'required|max:20',
            'password'              => 'required|min:8',
            'password_confirmation' => 'required',
            'terms'                 => 'required',
        ];

        if (!$validator->validate($rules)) {
            Session::flash('error', $validator->firstError() ?? 'Validation failed.');
            Session::flash('old_input', $input);
            $this->redirect('/supplier/register');
            return;
        }

        if ($input['password'] !== $input['password_confirmation']) {
            Session::flash('error', 'Passwords do not match.');
            Session::flash('old_input', $input);
            $this->redirect('/supplier/register');
            return;
        }

        $email = strtolower(trim((string)$input['email']));
        if (User::emailExists($email)) {
            Session::flash('error', 'An account with this email address already exists. Please log in.');
            Session::flash('old_input', $input);
            $this->redirect('/supplier/register');
            return;
        }

        try {
            // 1. Create User with role SUPPLIER and PENDING_VERIFICATION
            $userId = User::createSupplierUser([
                'name'         => $input['contact_person'],
                'company_name' => $input['company_name'],
                'email'        => $email,
                'password'     => $input['password'],
                'phone'        => $input['phone'],
                'country_code' => $input['country_code'] ?? '+91',
                'country_id'   => (int)$input['country_id'],
            ]);

            // 2. Create Supplier Profile
            $supplierId = Supplier::create([
                'user_id'          => $userId,
                'business_type_id' => (int)$input['business_type_id'],
                'country_id'       => (int)$input['country_id'],
                'state_id'         => (int)$input['state_id'],
                'city_id'          => (int)$input['city_id'],
                'company_name'     => $input['company_name'],
                'contact_person'   => $input['contact_person'],
                'email'            => $email,
                'phone'            => $input['phone'],
                'country_code'     => $input['country_code'] ?? '+91',
                'address'          => $input['address'],
                'postal_code'      => $input['postal_code'],
                'status'           => 'ACTIVE',
                'approval_status'  => 'PENDING_VERIFICATION',
            ]);

            // 3. Generate & Dispatch OTP
            $otp = Otp::generate($email, 'SUPPLIER_REGISTRATION', $this->request->getIp());
            Mailer::sendSupplierOtp($email, (string)$input['company_name'], $otp);

            Session::set('otp_email', $email);
            Session::set('otp_purpose', 'SUPPLIER_REGISTRATION');
            Session::set('otp_user_id', $userId);
            Session::set('otp_supplier_id', $supplierId);

            Session::flash('success', "A 6-digit verification code has been dispatched to {$email}.");
            $this->redirect('/verify-email');
        } catch (\Throwable $e) {
            Session::flash('error', 'Supplier registration error: ' . $e->getMessage());
            Session::flash('old_input', $input);
            $this->redirect('/supplier/register');
        }
    }

    public function supplierPendingApproval(): void
    {
        $user = $this->authUser();
        if (!$user || ($user['role'] ?? '') !== 'SUPPLIER') {
            $this->redirect('/login');
            return;
        }

        $supplier = Supplier::findByUserId((int)$user['id']);
        if (!$supplier) {
            Session::flash('error', 'Supplier profile not found.');
            $this->redirect('/login');
            return;
        }

        // If already approved, jump to dashboard!
        if (($supplier['approval_status'] ?? '') === 'APPROVED') {
            $this->redirect('/supplier/dashboard');
            return;
        }

        $this->render('supplier/pending_approval', [
            'pageTitle' => 'Manufacturer Application Review — Sports Junction',
            'supplier'  => $supplier,
        ], 'auth');
    }

    // =========================================================================
    // 8. LOGOUT
    // =========================================================================

    public function logout(): void
    {
        // Clear remember cookie if present
        if (isset($_COOKIE['sj_remember'])) {
            setcookie('sj_remember', '', time() - 3600, '/');
        }

        Session::destroy();
        Session::start();
        Session::flash('info', 'You have been successfully signed out.');
        $this->redirect('/login');
    }

    public function publicStatesByCountry(array $params): void
    {
        $countryId = (int)($params['countryId'] ?? 0);
        if ($countryId <= 0) {
            $this->json(['success' => true, 'data' => []]);
            return;
        }

        $states = State::getByCountry($countryId, true);
        $this->json(['success' => true, 'data' => $states]);
    }

    public function publicCitiesByState(array $params): void
    {
        $stateId = (int)($params['stateId'] ?? 0);
        if ($stateId <= 0) {
            $this->json(['success' => true, 'data' => []]);
            return;
        }

        $cities = City::getByState($stateId, true);
        $this->json(['success' => true, 'data' => $cities]);
    }
}
