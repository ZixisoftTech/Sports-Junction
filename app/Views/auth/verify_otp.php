<?php
declare(strict_types=1);

use App\Core\Response;
use App\Helpers\CSRF;
use App\Helpers\Session;

$baseUrl = Response::baseUrl();
$email = $email ?? Session::get('otp_email') ?? '';
$purpose = $purpose ?? Session::get('otp_purpose') ?? 'REGISTRATION';
$error = Session::flash('error');
$success = Session::flash('success');
?>

<div class="w-full max-w-xl mx-auto">
    <!-- Top Utility Bar -->
    <div class="flex items-center justify-between mb-4 px-2">
        <a href="<?= htmlspecialchars($baseUrl) ?>/register" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-blue-900 transition-colors">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to Registration</span>
        </a>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/60 text-emerald-800 text-[11px] font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>256-Bit SSL Secured</span>
        </span>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 border border-slate-100 p-6 sm:p-10 text-center">
        <!-- Top Icon -->
        <div class="w-16 h-16 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center mx-auto mb-5 relative text-blue-900 shadow-sm">
            <span class="material-symbols-outlined text-[32px]">mark_email_read</span>
            <span class="material-symbols-outlined text-[14px] text-teal-600 absolute bottom-2 right-2 bg-white rounded-full p-0.5 shadow-xs">verified</span>
        </div>

        <!-- Heading -->
        <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-slate-900 tracking-tight">Verify Your Email</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-sm mx-auto">
            We've sent a 6-digit verification code to authenticate your enterprise account.
        </p>

        <!-- Email Badge Pill with Change Link -->
        <div class="inline-flex items-center gap-2 mt-4 px-4 py-1.5 rounded-full bg-blue-50/80 border border-blue-100 text-xs font-semibold text-blue-950">
            <span class="material-symbols-outlined text-[16px] text-blue-700">mail</span>
            <span><?= htmlspecialchars($email ?: 'your business email') ?></span>
            <a href="<?= htmlspecialchars($baseUrl) ?>/register" class="text-blue-700 hover:underline font-bold ml-1">Change</a>
        </div>

        <?php if (empty(\App\Helpers\Env::get('MAIL_PASSWORD')) && \App\Helpers\Env::get('APP_DEBUG')): ?>
            <div class="mt-4 p-3 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-950 text-xs flex items-center justify-between gap-3 text-left">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600 text-[20px] shrink-0">terminal</span>
                    <div>
                        <span class="font-bold text-amber-900">Static Testing OTP:</span> 
                        Passcode is <strong class="font-mono text-sm text-blue-900 bg-amber-100/90 px-2 py-0.5 rounded">123456</strong> 
                        <span class="text-slate-500 block text-[11px] mt-0.5">Static OTP active for all accounts and verifications</span>
                    </div>
                </div>
                <button type="button" onclick="fillOtp('123456')" class="shrink-0 text-xs bg-blue-900 hover:bg-blue-950 text-white font-bold px-3 py-1.5 rounded-lg transition-colors shadow-xs">Auto-Fill</button>
            </div>
        <?php endif; ?>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="mt-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm text-left flex items-start gap-3">
                <span class="material-symbols-outlined text-red-600 text-[20px] shrink-0 mt-0.5">error</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mt-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm text-left flex items-start gap-3">
                <span class="material-symbols-outlined text-emerald-600 text-[20px] shrink-0 mt-0.5">check_circle</span>
                <div id="successMsg"><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- OTP Verification Form -->
        <form action="<?= htmlspecialchars($baseUrl) ?>/verify-email" method="POST" class="mt-8 space-y-6" id="otpForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CSRF::token()) ?>">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="purpose" value="<?= htmlspecialchars($purpose) ?>">
            <input type="hidden" name="otp" id="combinedOtp" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-widest mb-3">ENTER 6-DIGIT PASSCODE</label>
                
                <!-- 6 Square Digits Inputs -->
                <div class="flex items-center justify-center gap-2 sm:gap-3" id="otpInputGroup">
                    <input type="text" inputmode="numeric" maxlength="1" autofocus class="otp-box w-11 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold font-mono bg-slate-50 border-2 border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:border-blue-900 focus:ring-4 focus:ring-blue-100 outline-none transition-all shadow-sm">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-box w-11 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold font-mono bg-slate-50 border-2 border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:border-blue-900 focus:ring-4 focus:ring-blue-100 outline-none transition-all shadow-sm">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-box w-11 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold font-mono bg-slate-50 border-2 border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:border-blue-900 focus:ring-4 focus:ring-blue-100 outline-none transition-all shadow-sm">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-box w-11 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold font-mono bg-slate-50 border-2 border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:border-blue-900 focus:ring-4 focus:ring-blue-100 outline-none transition-all shadow-sm">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-box w-11 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold font-mono bg-slate-50 border-2 border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:border-blue-900 focus:ring-4 focus:ring-blue-100 outline-none transition-all shadow-sm">
                    <input type="text" inputmode="numeric" maxlength="1" class="otp-box w-11 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold font-mono bg-slate-50 border-2 border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:border-blue-900 focus:ring-4 focus:ring-blue-100 outline-none transition-all shadow-sm">
                </div>
            </div>

            <!-- Resend Row & Timer -->
            <div class="flex items-center justify-between text-xs px-2 pt-1">
                <div class="text-slate-600">
                    Didn't receive the code? 
                    <button type="button" id="resendBtn" onclick="resendOtp()" disabled class="font-bold text-slate-400 hover:text-blue-900 transition-colors disabled:cursor-not-allowed">Resend OTP</button>
                </div>
                <div id="countdownPill" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 font-mono font-bold text-slate-700 text-[11px]">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    <span>Resend in <span id="timerText">00:60</span></span>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="verifyBtn" class="w-full py-3.5 px-6 rounded-xl bg-blue-900 hover:bg-blue-950 text-white font-headline font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group">
                <span>Verify Email</span>
                <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </button>
        </form>

        <!-- Security Advisory Box -->
        <div class="mt-8 p-4 rounded-2xl bg-blue-50/70 border border-blue-100 text-left flex items-start gap-3">
            <span class="material-symbols-outlined text-blue-900 text-[20px] shrink-0 mt-0.5">security</span>
            <div class="text-xs text-slate-700 leading-relaxed">
                <span class="font-bold text-blue-950">Security Advisory:</span> Never share your verification code with anyone. Sports Junction procurement specialists will never request OTPs via phone, email, or marketplace chat.
            </div>
        </div>
    </div>
</div>

<script>
// OTP Input Logic: Auto-focus next, backspace, and paste support
const boxes = document.querySelectorAll('.otp-box');
const combinedInput = document.getElementById('combinedOtp');
const form = document.getElementById('otpForm');

boxes.forEach((box, index) => {
    box.addEventListener('input', (e) => {
        const val = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = val;
        if (val && index < boxes.length - 1) {
            boxes[index + 1].focus();
        }
        updateCombined();
    });

    box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && index > 0) {
            boxes[index - 1].focus();
        }
    });

    box.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        if (text.length > 0) {
            for (let i = 0; i < boxes.length; i++) {
                boxes[i].value = text[i] || '';
            }
            const nextIdx = Math.min(text.length, boxes.length - 1);
            boxes[nextIdx].focus();
            updateCombined();
        }
    });
});

function updateCombined() {
    let code = '';
    boxes.forEach(b => code += b.value);
    combinedInput.value = code;
}

function fillOtp(code) {
    if (!code) return;
    const str = String(code).trim();
    for (let i = 0; i < boxes.length; i++) {
        boxes[i].value = str[i] || '';
    }
    updateCombined();
}

form.addEventListener('submit', (e) => {
    updateCombined();
    if (combinedInput.value.length < 6) {
        e.preventDefault();
        alert('Please enter all 6 digits of the verification code.');
        boxes[0].focus();
    }
});

// Resend Countdown Timer (60s)
let countdown = 60;
const timerText = document.getElementById('timerText');
const resendBtn = document.getElementById('resendBtn');
const countdownPill = document.getElementById('countdownPill');

const timerInterval = setInterval(() => {
    countdown--;
    if (countdown > 0) {
        const secs = countdown < 10 ? '0' + countdown : countdown;
        timerText.textContent = '00:' + secs;
    } else {
        clearInterval(timerInterval);
        resendBtn.disabled = false;
        resendBtn.classList.remove('text-slate-400');
        resendBtn.classList.add('text-blue-900', 'underline');
        countdownPill.classList.add('hidden');
    }
}, 1000);

// Resend Ajax Call
function resendOtp() {
    resendBtn.disabled = true;
    resendBtn.textContent = 'Sending...';

    fetch('<?= htmlspecialchars($baseUrl) ?>/api/otp/resend', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= htmlspecialchars(CSRF::token()) ?>'
        },
        body: JSON.stringify({
            email: '<?= htmlspecialchars($email) ?>',
            purpose: '<?= htmlspecialchars($purpose) ?>'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('A fresh 6-digit passcode has been sent to your email.');
            location.reload();
        } else {
            alert(data.message || 'Could not resend code. Please try again shortly.');
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend OTP';
        }
    })
    .catch(() => {
        alert('Connection error. Please try again.');
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend OTP';
    });
}
</script>
