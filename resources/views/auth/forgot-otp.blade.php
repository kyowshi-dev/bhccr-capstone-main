@extends('auth.layout')

@section('title', 'Verify Password Reset')

@section('content')
<div class="auth-card animate-in opacity-0">
    <div class="text-center mb-6">
        <div class="flex items-center justify-center gap-3">
            <div class="logo-mark" style="width: 56px; height: 56px; border-radius: 14px;">
                <img src="{{ asset('img/logo.svg') }}" alt="Santa Ana logo">
            </div>
            <div class="text-left">
                <h1 class="font-extrabold auth-title leading-snug mb-0">Barangay Health Center Information System</h1>
                <p class="muted-xs leading-tight">Sta. Ana Health Center</p>
            </div>
        </div>
        <p class="text-xs mt-4 muted-xs leading-relaxed">Isulod ang 6-digit code nga gipadala sa imong email ug paghimo og bag-ong password.</p>

        @if ($maskedEmail)
            <p class="flex items-center justify-center gap-1.5 text-xs mt-3" style="color: var(--ink-muted);">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                <span>Code sent to</span>
                <strong class="font-semibold" style="color: var(--ink);">{{ $maskedEmail }}</strong>
            </p>
        @endif
    </div>

    <form action="{{ route('password.forgot.verify.submit') }}" method="POST" id="verify-form">
        @csrf
        <input type="hidden" name="username" value="{{ $username }}">

        @if (session('status'))
            <div class="mb-4 p-3 text-sm border-l-4 bg-teal-soft" style="border-left-color: var(--primary); color: var(--primary);">
                <p class="font-medium text-sm">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 text-sm border-l-4 bg-danger-soft" style="border-left-color: var(--danger); color: var(--danger);">
                <p class="font-medium text-sm">{{ $errors->first() }}</p>
            </div>
        @endif

        <div class="mb-5">
            <label id="otp-label" class="block text-sm font-medium mb-2.5 text-center" style="color: var(--ink);">Verification code</label>

            <div class="otp-row" role="group" aria-labelledby="otp-label">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" class="otp-digit @error('otp') is-invalid @enderror"
                           id="otp-{{ $i }}" inputmode="numeric" pattern="[0-9]" maxlength="1"
                           autocomplete="one-time-code" aria-label="Verification code digit {{ $i + 1 }}"
                           @if ($i === 0 && ! old('otp')) autofocus @endif>
                @endfor
            </div>
            <input type="hidden" name="otp" id="otp" value="{{ old('otp') }}" autocomplete="off">

            <div class="flex items-center justify-between gap-3 mt-3">
                <p class="otp-hint">Code expires in 15 minutes.</p>
                <button type="button" id="resend-btn" class="resend-btn" disabled>
                    <span id="resend-label">Resend code in 1:00</span>
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">New password</label>
            <div class="pw-wrap">
                <input type="password" name="password" id="password"
                       class="auth-input" placeholder="At least 8 characters" minlength="8" required>
                <button type="button" class="pw-toggle" aria-controls="password" aria-label="Show password">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="mb-5">
            <label for="password_confirmation" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Confirm new password</label>
            <div class="pw-wrap">
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="auth-input" placeholder="Repeat your new password" minlength="8" required>
                <button type="button" class="pw-toggle" aria-controls="password_confirmation" aria-label="Show password">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-btn" id="submit-btn" disabled>Reset password</button>
    </form>

    <form id="resend-form" action="{{ route('password.forgot.submit') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="username" value="{{ $username }}">
    </form>

    <p class="text-center text-xs mt-5" style="color: var(--ink-muted);">
        <a href="{{ route('login') }}" class="font-medium" style="color: var(--primary); text-decoration: underline;">Back to sign in</a>
    </p>

    <p class="text-center text-xs mt-6" style="color: var(--ink-muted);">
        &copy; {{ date('Y') }} | Developed by
        <a href="facebook.com/charlz.chavaria" class="font-medium" style="color: var(--primary);">
            PHINMA COC Students
        </a>
    </p>
</div>

<script>
(function () {
    var boxes = Array.prototype.slice.call(document.querySelectorAll('.otp-digit'));
    var hidden = document.getElementById('otp');
    var submitBtn = document.getElementById('submit-btn');
    var password = document.getElementById('password');
    var passwordConfirmation = document.getElementById('password_confirmation');

    function syncHidden() {
        hidden.value = boxes.map(function (b) { return b.value; }).join('');
    }

    function updateSubmitState() {
        var otpComplete = boxes.every(function (b) { return b.value; });
        submitBtn.disabled = !(otpComplete && password.value && passwordConfirmation.value);
    }

    ['input', 'keyup'].forEach(function (eventName) {
        password.addEventListener(eventName, updateSubmitState);
        passwordConfirmation.addEventListener(eventName, updateSubmitState);
    });

    var old = hidden.value.replace(/\D/g, '').slice(0, boxes.length);
    if (old) {
        old.split('').forEach(function (digit, index) { boxes[index].value = digit; });
        boxes[Math.min(old.length, boxes.length - 1)].focus();
    }

    updateSubmitState();

    boxes.forEach(function (box, index) {
        box.addEventListener('input', function () {
            var digits = box.value.replace(/\D/g, '');
            if (digits.length > 1) {
                digits.split('').forEach(function (digit, offset) {
                    if (index + offset < boxes.length) {
                        boxes[index + offset].value = digit;
                    }
                });
                box.value = box.value.replace(/\D/g, '').charAt(0);
            } else {
                box.value = digits;
            }
            syncHidden();
            if (box.value && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
            updateSubmitState();
        });

        box.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && !box.value && index > 0) {
                boxes[index - 1].focus();
                boxes[index - 1].value = '';
                syncHidden();
                updateSubmitState();
                event.preventDefault();
            } else if (event.key === 'ArrowLeft' && index > 0) {
                boxes[index - 1].focus();
                event.preventDefault();
            } else if (event.key === 'ArrowRight' && index < boxes.length - 1) {
                boxes[index + 1].focus();
                event.preventDefault();
            }
        });

        box.addEventListener('paste', function (event) {
            event.preventDefault();
            var text = (event.clipboardData || window.clipboardData).getData('text') || '';
            var digits = text.replace(/\D/g, '').split('').slice(0, boxes.length);
            if (!digits.length) {
                return;
            }
            digits.forEach(function (digit, offset) { boxes[offset].value = digit; });
            syncHidden();
            updateSubmitState();
            boxes[Math.min(digits.length, boxes.length - 1)].focus();
        });
    });

    var resendBtn = document.getElementById('resend-btn');
    var resendLabel = document.getElementById('resend-label');
    var resendForm = document.getElementById('resend-form');
    var secondsLeft = 60;

    function tick() {
        secondsLeft--;
        if (secondsLeft > 0) {
            resendLabel.textContent = 'Resend code in 0:' + String(secondsLeft).padStart(2, '0');
            window.setTimeout(tick, 1000);
        } else {
            resendBtn.disabled = false;
            resendLabel.textContent = 'Resend code';
        }
    }

    if (resendBtn) {
        resendBtn.addEventListener('click', function () {
            if (secondsLeft > 0) {
                return;
            }
            resendBtn.disabled = true;
            resendLabel.textContent = 'Sending...';
            resendForm.submit();
        });
        window.setTimeout(tick, 1000);
    }
})();
</script>
@endsection
