<?php
include_once "connect.php";
$errorCode = isset($_GET['error']) ? $_GET['error'] : '';
$successCode = isset($_GET['success']) ? $_GET['success'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="./style.css">
</head>
<body>

    <div class="container" id="signUp" style="display:none;">
        <h1 class="form-title">Register</h1>
        <form method="post" action="./register.php" novalidate id="signUpForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fName" id="fName" placeholder="First Name">
                <label for="fName">First Name</label>
            </div>
            
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lName" id="lName" placeholder="Last Name">
                <label for="lName">Last Name</label>
            </div>
            
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="signupEmail" placeholder="Email">
                <label for="signupEmail">Email</label>
            </div>
            
            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="text" name="whatsapp" id="signupWhatsapp" placeholder="WhatsApp (08xxxxxxxxxx)">
                <label for="signupWhatsapp">WhatsApp</label>
            </div>

            <div class="password-wrapper">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="signupPassword" placeholder="Password">
                    <label for="signupPassword">Password</label>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                
                <div class="password-feedback" id="passwordFeedback">
                    <ul class="requirements">
                        <li class="invalid" id="req-length">
                            <i class="fas fa-times-circle"></i>
                            <span>Minimal 8 karakter</span>
                        </li>
                        <li class="invalid" id="req-uppercase">
                            <i class="fas fa-times-circle"></i>
                            <span>Minimal 1 huruf kapital</span>
                        </li>
                        <li class="invalid" id="req-number">
                            <i class="fas fa-times-circle"></i>
                            <span>Minimal 1 angka</span>
                        </li>
                        <li class="invalid" id="req-symbol">
                            <i class="fas fa-times-circle"></i>
                            <span>Minimal 1 simbol (@$!%*?&)</span>
                        </li>
                    </ul>
                    <div class="strength-indicator">
                        <div class="strength-bar">
                            <div class="strength-progress" id="strengthProgress"></div>
                        </div>
                        <div class="strength-text" id="strengthText">Kekuatan: Sangat Lemah</div>
                    </div>
                </div>
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" id="signupConfirmPassword" placeholder="Confirm Password">
                <label for="signupConfirmPassword">Confirm Password</label>
                <i class="fas fa-eye toggle-password"></i>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="agree" id="agreeTerms" required>
                <label for="agreeTerms">
                    Saya setuju mengenai <a href="#">Syarat & Ketentuan</a> dan benar akan data yang saya masukkan ril not fake :)
                </label>
            </div>

            <input type="submit" class="btn" value="Sign Up" name="signUp">
        </form>
        <div class="links">
            <p>Apakah sudah punya akun?</p>
            <button id="signInButton">Sign In</button>
        </div>
    </div>

    <div class="container" id="signIn">
        <h1 class="form-title">Sign In</h1>
        <form method="post" action="./register.php" novalidate id="signInForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" id="signinEmail" placeholder="Email">
                <label for="signinEmail">Email</label>
            </div>
            
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="signinPassword" placeholder="Password">
                <label for="signinPassword">Password</label>
                <i class="fas fa-eye toggle-password"></i>
            </div>

            <input type="submit" class="btn" value="Sign In" name="signIn">
        </form>
        <div class="links">
            <p>Apakah belum punya akun?</p>
            <button id="signUpButton">Sign Up</button>
        </div>
    </div>

    <script src="./script.js"></script>

    <?php if($errorCode !== ''): ?>
    <script>
    setTimeout(function() {
        if(<?php echo json_encode(in_array($errorCode, ['email_exists', 'register_failed', 'weak_password', 'invalid_email', 'invalid_domain', 'invalid_whatsapp', 'terms_not_accepted', 'password_mismatch', 'empty_confirm_password'], true)); ?>) {
            document.getElementById('signIn').style.display = "none";
            document.getElementById('signUp').style.display = "block";
        } else {
            document.getElementById('signUp').style.display = "none";
            document.getElementById('signIn').style.display = "block";
        }

        if(<?php echo json_encode($errorCode === 'csrf_failed'); ?>) {
            alert('Permintaan tidak sah (CSRF Token Invalid). Silakan muat ulang halaman.');
        }

        if(<?php echo json_encode($errorCode === 'invalid_login'); ?>) {
            const emailInput = document.getElementById('signinEmail');
            const passwordInput = document.getElementById('signinPassword');
            if(window.showError) {
                showError(emailInput, 'Email atau Password salah');
                showError(passwordInput, 'Email atau Password salah');
            }
        }

        if(<?php echo json_encode($errorCode === 'email_exists'); ?>) {
            const emailInput = document.getElementById('signupEmail');
            if(window.showError) showError(emailInput, 'Email sudah terdaftar, silakan login');
        }

        if(<?php echo json_encode($errorCode === 'invalid_email'); ?>) {
            const emailInput = document.getElementById('signupEmail');
            if(window.showError) showError(emailInput, 'Format email tidak valid (contoh: nama@email.com)');
        }

        if(<?php echo json_encode($errorCode === 'invalid_domain'); ?>) {
            const emailInput = document.getElementById('signupEmail');
            if(window.showError) showError(emailInput, 'Email harus menggunakan domain belajar.ac.id');
        }

        if(<?php echo json_encode($errorCode === 'invalid_whatsapp'); ?>) {
            const whatsappInput = document.getElementById('signupWhatsapp');
            if(window.showError) showError(whatsappInput, 'Format WhatsApp tidak valid (contoh: 08123456789)');
        }

        if(<?php echo json_encode($errorCode === 'weak_password'); ?>) {
            const passwordInput = document.getElementById('signupPassword');
            if(window.showError) showError(passwordInput, 'Password tidak memenuhi syarat keamanan');
        }

        if(<?php echo json_encode($errorCode === 'terms_not_accepted'); ?>) {
            const agreeCheckbox = document.getElementById('agreeTerms');
            const group = agreeCheckbox.closest('.checkbox-group');
            group.classList.add('input-error');
            let oldError = group.querySelector('.error-message');
            if(oldError) oldError.remove();
            const error = document.createElement("div");
            error.className = "error-message";
            error.innerHTML = "<i class='fas fa-exclamation-circle'></i> Anda harus menyetujui syarat dan ketentuan";
            group.appendChild(error);
        }

        if(<?php echo json_encode($errorCode === 'password_mismatch'); ?>) {
            const confirmInput = document.getElementById('signupConfirmPassword');
            if(window.showError) showError(confirmInput, 'Password tidak cocok');
        }

        if(<?php echo json_encode($errorCode === 'empty_confirm_password'); ?>) {
            const confirmInput = document.getElementById('signupConfirmPassword');
            if(window.showError) showError(confirmInput, 'Konfirmasi password tidak boleh kosong');
        }

        if(<?php echo json_encode($errorCode === 'register_failed'); ?>) {
            alert('Registrasi gagal. Silakan coba lagi.');
        }
    }, 100);
    </script>
    <?php endif; ?>

    <?php if($successCode !== ''): ?>
    <script>
    setTimeout(function() {
        if(<?php echo json_encode($successCode === 'registered'); ?>) {
            alert('Registrasi berhasil! Silakan login.');
        }
    }, 100);
    </script>
    <?php endif; ?>

</body>
</html>
