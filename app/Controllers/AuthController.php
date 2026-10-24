<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
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
use App\Helpers\AuditLogger;

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
            'pageTitle'   => 'Super Admin Login — Sports Junction',
            'isAdminView' => true,
        ], 'auth');
    }

    public function adminLogin(): void
    {
        $this->handleLogin(true);
    }

    // =========================================================================
    // 2. UNIFIED / BUYER / SUPPLIER LOGIN (Stitch Screen 4)
    // =========================================================================

    private function normalizeRedirectTarget(string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return '';
        }

        if (str_starts_with($target, 'http://') || str_starts_with($target, 'https://')) {
            $parsed = parse_url($target);
            $target = ($parsed['path'] ?? '/') . (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
        }

        $base = Response::baseUrl();
        if ($base !== '' && $base !== '/' && str_starts_with($target, $base)) {
            $target = substr($target, strlen($base)) ?: '/';
        }

        if (!str_starts_with($target, '/') || str_starts_with($target, '//') || str_starts_with($target, '/login') || str_starts_with($target, '/logout')) {
            return '';
        }

        return $target;
    }

    public function showLogin(): void
    {
        $redirectParam = (string)$this->request->get('redirect', '');
        $normalizedParam = $this->normalizeRedirectTarget($redirectParam);
        if (!empty($normalizedParam)) {
            Session::set('auth_redirect', $normalizedParam);
        }

        $user = $this->authUser();
        if ($user) {
            $rawTarget = (string)($this->request->get('redirect') ?? Session::get('auth_redirect') ?? '');
            Session::remove('auth_redirect');
            $redirectTarget = $this->normalizeRedirectTarget($rawTarget);
            $role = $user['role'] ?? '';

            if (!empty($redirectTarget) && $redirectTarget !== '/') {
                if ($role === 'SUPER_ADMIN' && (str_starts_with($redirectTarget, '/buyer') || str_starts_with($redirectTarget, '/supplier'))) {
                    if (str_contains($redirectTarget, '/messages')) {
                        preg_match('/(?:conversation_id|conv)[=\/](\d+)/i', $redirectTarget, $m);
                        $this->redirect(!empty($m[1]) ? "/admin/communication/{$m[1]}" : "/admin/communication");
                        return;
                    }
                    $this->redirect('/admin/dashboard');
                    return;
                }
                $this->redirect($redirectTarget);
                return;
            }

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
            AuditLogger::log('LOGIN', 'User', null, $email, null, "Failed login attempt: email not found ({$email})", 'FAILURE');
            Session::flash('error', $genericError);
            $this->redirect($redirectBack);
            return;
        }

        if (!User::verifyPassword($password, $user['password'])) {
            RateLimiter::recordFailedAttempt($ip, $email);
            AuditLogger::log('LOGIN', 'User', (int)$user['id'], $email, null, "Failed login attempt: invalid password for {$email}", 'FAILURE', (int)$user['id']);
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

        // If supplier, check supplier approval status & suspension (Strictly scoped to SUPPLIER role)
        $supplierRecord = null;
        if (($user['role'] ?? '') === 'SUPPLIER') {
            $supplierRecord = Supplier::findByUserId((int)$user['id']);
            if ($supplierRecord) {
                if (($supplierRecord['approval_status'] ?? '') === 'SUSPENDED' || ($supplierRecord['status'] ?? '') === 'INACTIVE') {
                    Session::flash('error', 'Your manufacturer facility account has been suspended or disabled. Please contact administration.');
                    $this->redirect($redirectBack);
                    return;
                }
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

        if (($user['role'] ?? '') === 'BUYER') {
            try {
                \App\Models\BuyerFavourite::mergeGuestSortIntoBuyer((int)$user['id']);
            } catch (\Throwable $e) {}
        }

        if ($supplierRecord && ($user['role'] ?? '') === 'SUPPLIER') {
            Session::set('auth_supplier', $supplierRecord);
        }

        User::updateLastLogin((int)$user['id']);

        AuditLogger::log(
            'LOGIN',
            'User',
            (int)$user['id'],
            $user['name'] . ' (' . $user['email'] . ')',
            null,
            "Successfully signed in as {$user['role']}",
            'SUCCESS',
            (int)$user['id']
        );

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
            $rawTarget = (string)($this->request->get('redirect') ?? $this->request->post('redirect') ?? Session::get('auth_redirect') ?? '');
            Session::remove('auth_redirect');
            $redirectTarget = $this->normalizeRedirectTarget($rawTarget);

            if (!empty($redirectTarget) && $redirectTarget !== '/') {
                $this->redirect($redirectTarget);
                return;
            }
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
        $pendingUserId = (int)Session::get('pending_edit_user_id');
        $pendingUser = $pendingUserId ? User::findById($pendingUserId) : null;

        if (User::emailExists($email)) {
            if (!$pendingUser || strtolower($pendingUser['email']) !== $email) {
                Session::flash('error', 'An account with this email address already exists. Please log in.');
                Session::flash('old_input', $input);
                $this->redirect('/register');
                return;
            }
        }

        try {
            if ($pendingUser && $pendingUser['status'] === 'PENDING_VERIFICATION') {
                $userId = $pendingUserId;
                User::updateProfile($userId, [
                    'name'         => $input['name'],
                    'company_name' => $input['company_name'],
                    'email'        => $email,
                    'phone'        => $input['phone'],
                    'country_code' => $input['country_code'] ?? '+91',
                    'country_id'   => (int)$input['country_id'],
                    'state'        => !empty($input['state']) ? trim($input['state']) : null,
                    'city'         => !empty($input['city']) ? trim($input['city']) : null,
                ]);
                if (!empty($input['password'])) {
                    User::updatePassword($userId, $input['password']);
                }
                // Invalidate previous OTP
                Otp::invalidate($pendingUser['email'], 'REGISTRATION');
                Session::remove('pending_edit_user_id');
            } else {
                // Create pending buyer user
                $userId = User::createBuyer([
                    'name'         => $input['name'],
                    'company_name' => $input['company_name'],
                    'email'        => $email,
                    'password'     => $input['password'],
                    'phone'        => $input['phone'],
                    'country_code' => $input['country_code'] ?? '+91',
                    'country_id'   => (int)$input['country_id'],
                    'state'        => !empty($input['state']) ? trim($input['state']) : null,
                    'city'         => !empty($input['city']) ? trim($input['city']) : null,
                ]);
            }

            // Generate 6-digit OTP
            $otp = Otp::generate($email, 'REGISTRATION', $this->request->getIp());

            // Send transactional email
            $sent = Mailer::sendBuyerOtp($email, (string)$input['name'], $otp);

            // Set session for OTP verification
            Session::set('otp_email', $email);
            Session::set('otp_purpose', 'REGISTRATION');
            Session::set('otp_user_id', $userId);

            if ($sent) {
                Session::flash('success', "A 6-digit verification code has been dispatched to {$email}.");
            } else {
                Session::flash('warning', "Account created, but we could not deliver the verification email to {$email}. Please verify your email or click Resend OTP.");
            }
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

    public function changeRegistrationEmail(): void
    {
        $userId = (int)Session::get('otp_user_id');
        $email = (string)Session::get('otp_email');
        $purpose = (string)(Session::get('otp_purpose') ?? 'REGISTRATION');

        if (!$userId && !empty($email)) {
            $user = User::findByEmail($email);
            if ($user) {
                $userId = (int)$user['id'];
            }
        }

        if (!$userId) {
            $this->redirect('/register');
            return;
        }

        $user = User::findById($userId);
        if (!$user) {
            $this->redirect('/register');
            return;
        }

        Session::set('pending_edit_user_id', $userId);

        if ($purpose === 'SUPPLIER_REGISTRATION' || ($user['role'] ?? '') === 'SUPPLIER') {
            $supplier = Supplier::findByUserId($userId);
            $oldInput = [
                'company_name'     => $supplier['company_name'] ?? $user['company_name'] ?? '',
                'contact_person'   => $supplier['contact_person'] ?? $user['name'] ?? '',
                'email'            => $user['email'] ?? '',
                'phone'            => $supplier['phone'] ?? $user['phone'] ?? '',
                'country_code'     => $supplier['country_code'] ?? $user['country_code'] ?? '+91',
                'country_id'       => $supplier['country_id'] ?? $user['country_id'] ?? '',
                'business_type_id' => $supplier['business_type_id'] ?? '',
                'address'          => $supplier['address'] ?? '',
                'state'            => $supplier['state'] ?? $user['state'] ?? '',
                'city'             => $supplier['city'] ?? $user['city'] ?? '',
                'postal_code'      => $supplier['postal_code'] ?? '',
            ];
            Session::flash('old_input', $oldInput);
            Session::flash('info', 'You can update your email address below.');
            $this->redirect('/supplier/register');
            return;
        }

        $oldInput = [
            'name'         => $user['name'] ?? '',
            'company_name' => $user['company_name'] ?? '',
            'email'        => $user['email'] ?? '',
            'phone'        => $user['phone'] ?? '',
            'country_code' => $user['country_code'] ?? '+91',
            'country_id'   => $user['country_id'] ?? '',
            'state'        => $user['state'] ?? '',
            'city'         => $user['city'] ?? '',
        ];
        Session::flash('old_input', $oldInput);
        Session::flash('info', 'You can update your email address below.');
        $this->redirect('/register');
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

            try {
                \App\Models\BuyerFavourite::mergeGuestSortIntoBuyer((int)$user['id']);
            } catch (\Throwable $e) {}

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

        $sent = false;
        if ($purpose === 'SUPPLIER_REGISTRATION') {
            $sent = Mailer::sendOtpResend($email, $name, $otp, true);
        } elseif ($purpose === 'PASSWORD_RESET') {
            $isSupplier = ($user && ($user['role'] ?? '') === 'SUPPLIER');
            if ($isSupplier) {
                $sent = Mailer::sendSupplierPasswordResetOtp($email, $name, $otp);
            } else {
                $sent = Mailer::sendBuyerPasswordResetOtp($email, $name, $otp);
            }
        } else {
            $sent = Mailer::sendOtpResend($email, $name, $otp, false);
        }

        if (!$sent) {
            $this->json([
                'success' => false,
                'message' => 'Unable to deliver the verification passcode at this time. Please check your email configuration.',
            ], 500);
            return;
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

        if (!$user) {
            Session::remove('reset_email');
            Session::flash('error', 'No account found with this email address. Please check your email or register.');
            $this->redirect('/forgot-password');
            return;
        }

        if (($user['status'] ?? '') !== 'ACTIVE') {
            Session::remove('reset_email');
            Session::flash('error', 'Your account is currently inactive or pending approval. Please contact support.');
            $this->redirect('/forgot-password');
            return;
        }

        $otp = Otp::generate($email, 'PASSWORD_RESET', $this->request->getIp());
        if (($user['role'] ?? '') === 'SUPPLIER') {
            $sent = Mailer::sendSupplierPasswordResetOtp($email, $user['name'] ?? 'Manufacturer', $otp);
        } else {
            $sent = Mailer::sendBuyerPasswordResetOtp($email, $user['name'] ?? 'Valued Buyer', $otp);
        }

        if (!$sent) {
            Session::remove('reset_email');
            Session::flash('error', 'Unable to deliver the password recovery email at this time. Please check your email or contact support.');
            $this->redirect('/forgot-password');
            return;
        }

        Session::set('reset_email', $email);
        Session::flash('success', "A 6-digit recovery passcode has been dispatched to {$email}.");
        $this->redirect('/reset-password');
    }

    public function showResetPassword(): void
    {
        $email = Session::get('reset_email') ?? '';
        if (empty($email)) {
            Session::flash('error', 'Please submit your registered email to request a password reset.');
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

        $sessionEmail = Session::get('reset_email');
        if (empty($sessionEmail)) {
            Session::flash('error', 'Password reset session expired or unauthorized. Please start over.');
            $this->redirect('/forgot-password');
            return;
        }

        $email = strtolower(trim((string)$this->request->post('email', '')));
        if (empty($email) || $email !== strtolower($sessionEmail)) {
            Session::flash('error', 'Password reset session does not match this email address.');
            $this->redirect('/reset-password');
            return;
        }
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
            if (($user['role'] ?? '') === 'SUPPLIER') {
                Mailer::sendSupplierPasswordUpdated($email, $user['name'] ?? 'Manufacturer');
            } else {
                Mailer::sendBuyerPasswordUpdated($email, $user['name'] ?? 'Valued Buyer');
            }
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
            'state'                 => 'required|max:100',
            'city'                  => 'required|max:100',
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
        $pendingUserId = (int)Session::get('pending_edit_user_id');
        $pendingUser = $pendingUserId ? User::findById($pendingUserId) : null;

        if (User::emailExists($email)) {
            if (!$pendingUser || strtolower($pendingUser['email']) !== $email) {
                Session::flash('error', 'An account with this email address already exists. Please log in.');
                Session::flash('old_input', $input);
                $this->redirect('/supplier/register');
                return;
            }
        }

        try {
            if ($pendingUser && $pendingUser['status'] === 'PENDING_VERIFICATION') {
                $userId = $pendingUserId;
                User::updateProfile($userId, [
                    'name'         => $input['contact_person'],
                    'company_name' => $input['company_name'],
                    'email'        => $email,
                    'phone'        => $input['phone'],
                    'country_code' => $input['country_code'] ?? '+91',
                    'country_id'   => (int)$input['country_id'],
                    'state'        => trim($input['state']),
                    'city'         => trim($input['city']),
                ]);
                if (!empty($input['password'])) {
                    User::updatePassword($userId, $input['password']);
                }

                $supplier = Supplier::findByUserId($userId);
                if ($supplier) {
                    Supplier::updateProfile((int)$supplier['id'], [
                        'business_type_id' => (int)$input['business_type_id'],
                        'country_id'       => (int)$input['country_id'],
                        'state_id'         => !empty($input['state_id']) ? (int)$input['state_id'] : null,
                        'city_id'          => !empty($input['city_id']) ? (int)$input['city_id'] : null,
                        'state'            => trim($input['state']),
                        'city'             => trim($input['city']),
                        'company_name'     => $input['company_name'],
                        'contact_person'   => $input['contact_person'],
                        'email'            => $email,
                        'phone'            => $input['phone'],
                        'country_code'     => $input['country_code'] ?? '+91',
                        'address'          => $input['address'],
                        'postal_code'      => $input['postal_code'],
                    ]);
                    $supplierId = (int)$supplier['id'];
                } else {
                    $supplierId = Supplier::create([
                        'user_id'          => $userId,
                        'business_type_id' => (int)$input['business_type_id'],
                        'country_id'       => (int)$input['country_id'],
                        'state_id'         => !empty($input['state_id']) ? (int)$input['state_id'] : null,
                        'city_id'          => !empty($input['city_id']) ? (int)$input['city_id'] : null,
                        'state'            => trim($input['state']),
                        'city'             => trim($input['city']),
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
                }

                // Invalidate old OTP
                Otp::invalidate($pendingUser['email'], 'SUPPLIER_REGISTRATION');
                Session::remove('pending_edit_user_id');
            } else {
                // 1. Create User with role SUPPLIER and PENDING_VERIFICATION
                $userId = User::createSupplierUser([
                    'name'         => $input['contact_person'],
                    'company_name' => $input['company_name'],
                    'email'        => $email,
                    'password'     => $input['password'],
                    'phone'        => $input['phone'],
                    'country_code' => $input['country_code'] ?? '+91',
                    'country_id'   => (int)$input['country_id'],
                    'state'        => trim($input['state']),
                    'city'         => trim($input['city']),
                ]);

                // 2. Create Supplier Profile
                $supplierId = Supplier::create([
                    'user_id'          => $userId,
                    'business_type_id' => (int)$input['business_type_id'],
                    'country_id'       => (int)$input['country_id'],
                    'state_id'         => !empty($input['state_id']) ? (int)$input['state_id'] : null,
                    'city_id'          => !empty($input['city_id']) ? (int)$input['city_id'] : null,
                    'state'            => trim($input['state']),
                    'city'             => trim($input['city']),
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
            }

            // 3. Generate & Dispatch OTP
            $otp = Otp::generate($email, 'SUPPLIER_REGISTRATION', $this->request->getIp());
            $sent = Mailer::sendSupplierOtp($email, (string)$input['company_name'], $otp);

            // Super Admin Notification Dispatch
            try {
                \App\Models\Notification::notifySuperAdmins(
                    'SUPPLIER_REGISTRATION',
                    "New Supplier Registration: {$input['company_name']}",
                    "Contact: {$input['contact_person']} ({$email}). Awaiting admin verification.",
                    "/admin/suppliers",
                    'SUPPLIER',
                    $supplierId
                );
            } catch (\Throwable $ne) {
                error_log("Failed to notify admin of supplier #{$supplierId}: " . $ne->getMessage());
            }

            Session::set('otp_email', $email);
            Session::set('otp_purpose', 'SUPPLIER_REGISTRATION');
            Session::set('otp_user_id', $userId);
            Session::set('otp_supplier_id', $supplierId);

            if ($sent) {
                Session::flash('success', "A 6-digit verification code has been dispatched to {$email}.");
            } else {
                Session::flash('warning', "Account created, but we could not deliver the verification email to {$email}. Please verify your email or click Resend OTP.");
            }
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
        $currentUser = Session::get('auth_user');
        if ($currentUser && !empty($currentUser['id'])) {
            AuditLogger::log(
                'LOGOUT',
                'User',
                (int)$currentUser['id'],
                ($currentUser['name'] ?? 'User') . ' (' . ($currentUser['email'] ?? '') . ')',
                null,
                "User signed out",
                'SUCCESS',
                (int)$currentUser['id']
            );
        }

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

    /**
     * Profile Email Change: Step 1 — Request email change with OTP
     */
    public function requestEmailChange(): void
    {
        $user = $this->authUser();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Unauthorized. Please sign in.'], 401);
            return;
        }

        $input = $this->request->all();
        $newEmail = strtolower(trim((string)($input['new_email'] ?? '')));

        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Please enter a valid new email address.'], 422);
            return;
        }

        $currentEmail = strtolower(trim((string)$user['email']));
        if ($newEmail === $currentEmail) {
            $this->json(['success' => false, 'message' => 'New email must be different from your current email address.'], 422);
            return;
        }

        if (User::emailExists($newEmail)) {
            $this->json(['success' => false, 'message' => 'This email address is already in use by another account.'], 422);
            return;
        }

        // Store pending_email without modifying existing verified email
        User::execute("UPDATE users SET pending_email = :newEmail WHERE id = :id", [
            ':newEmail' => $newEmail,
            ':id'       => (int)$user['id']
        ]);

        // Generate and send OTP to new email
        $otp = Otp::generate($newEmail, 'PROFILE_EMAIL_CHANGE', $this->request->getIp());
        $sent = Mailer::sendProfileEmailChangeOtp($newEmail, $user['name'] ?? 'User', $otp);

        // Security notification to old email
        Mailer::sendSecurityNoticeEmail(
            $currentEmail,
            $user['name'] ?? 'User',
            'Email Change Requested',
            "A request was submitted to change your Sports Junction account email to {$newEmail}. A single-use verification code was sent to the new address. If you did not authorize this, please contact support immediately."
        );

        $this->json([
            'success' => true,
            'message' => "A 6-digit verification code has been dispatched to {$newEmail}. Please confirm to complete the update.",
            'email'   => $newEmail,
            'sent'    => $sent
        ]);
    }

    /**
     * Profile Email Change: Step 2 — Verify OTP and update email
     */
    public function verifyEmailChange(): void
    {
        $user = $this->authUser();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Unauthorized. Please sign in.'], 401);
            return;
        }

        $dbUser = User::findById((int)$user['id']);
        $pendingEmail = strtolower(trim((string)($dbUser['pending_email'] ?? '')));

        if (empty($pendingEmail)) {
            $this->json(['success' => false, 'message' => 'No pending email change request found. Please request a new code.'], 400);
            return;
        }

        $input = $this->request->all();
        $otp = trim((string)($input['otp'] ?? ''));

        if (empty($otp)) {
            $this->json(['success' => false, 'message' => 'Verification code is required.'], 422);
            return;
        }

        $verify = Otp::verify($pendingEmail, $otp, 'PROFILE_EMAIL_CHANGE');
        if (!$verify['success']) {
            $this->json(['success' => false, 'message' => $verify['message']], 400);
            return;
        }

        $oldEmail = $dbUser['email'];

        // Update email, set email_verified_at = NOW(), and clear pending_email
        User::execute("
            UPDATE users 
            SET email = :email, email_verified_at = NOW(), pending_email = NULL, updated_at = NOW() 
            WHERE id = :id
        ", [
            ':email' => $pendingEmail,
            ':id'    => (int)$user['id']
        ]);

        // If supplier, keep supplier profile in sync
        Supplier::execute("
            UPDATE suppliers 
            SET email = :email, updated_at = NOW() 
            WHERE user_id = :userId
        ", [
            ':email'  => $pendingEmail,
            ':userId' => (int)$user['id']
        ]);

        // Update session
        $authUser = Session::get('auth_user');
        if ($authUser && (int)$authUser['id'] === (int)$user['id']) {
            $authUser['email'] = $pendingEmail;
            Session::set('auth_user', $authUser);
        }

        // Notify old email that change is complete
        Mailer::sendSecurityNoticeEmail(
            $oldEmail,
            $user['name'] ?? 'User',
            'Email Address Updated',
            "Your Sports Junction account email has been successfully updated to {$pendingEmail}. If you did not make this change, please contact administration immediately."
        );

        $this->json([
            'success' => true,
            'message' => 'Your email address has been successfully updated and verified.',
            'email'   => $pendingEmail
        ]);
    }
}
