const signUpButton = document.getElementById("signUpButton");
const signInButton = document.getElementById("signInButton");
const signIn = document.getElementById("signIn");
const signUp = document.getElementById("signUp");
const signUpForm = document.getElementById("signUpForm");
const signInForm = document.getElementById("signInForm");
const allowedDomains = new Set(["belajar.ac.id"]);

globalThis.showError = function (input, message) {
    const group = input.closest(".input-group");
    if (!group) return;
    group.classList.add("input-error");
    const oldError = group.querySelector(".error-message");
    oldError?.remove();
    const error = document.createElement("div");
    error.className = "error-message";
    error.innerHTML = "<i class='fas fa-exclamation-circle'></i> " + message;
    group.appendChild(error);
};

function removeError(input) {
    const group = input.closest(".input-group");
    if (!group) return;
    group.classList.remove("input-error");
    const error = group.querySelector(".error-message");
    error?.remove();
}

function clearAllErrors() {
    document.querySelectorAll(".error-message").forEach((el) => el.remove());
    document.querySelectorAll(".input-error").forEach((el) => el.classList.remove("input-error"));
}

function isValidEmail(email) {
    return /^[^\s@]{1,64}@[^\s@]{1,255}\.[^\s@]{1,255}$/.test(email);
}

function isValidDomain(email) {
    return allowedDomains.has(email.split("@")[1]);
}

function isValidWhatsapp(phone) {
    return /^08\d{8,11}$/.test(phone);
}

function updatePasswordFeedback(val) {
    const box = document.getElementById("passwordFeedback");
    if (!box) return;

    const hasLength = val.length >= 8;
    const hasUppercase = /[A-Z]/.test(val);
    const hasNumber = /\d/.test(val);
    const hasSymbol = /[@$!%*?&]/.test(val);

    updateRequirement("req-length", hasLength);
    updateRequirement("req-uppercase", hasUppercase);
    updateRequirement("req-number", hasNumber);
    updateRequirement("req-symbol", hasSymbol);

    const strength = [hasLength, hasUppercase, hasNumber, hasSymbol].filter(Boolean).length;
    updateStrengthIndicator(strength, val.length);
}

function updateRequirement(id, isValid) {
    const element = document.getElementById(id);
    if (!element) return;

    const icon = element.querySelector("i");
    if (!icon) return;

    if (isValid) {
        element.classList.remove("invalid");
        element.classList.add("valid");
        icon.classList.remove("fa-times-circle");
        icon.classList.add("fa-check-circle");
    } else {
        element.classList.remove("valid");
        element.classList.add("invalid");
        icon.classList.remove("fa-check-circle");
        icon.classList.add("fa-times-circle");
    }
}

function updateStrengthIndicator(strength, length) {
    const progress = document.getElementById("strengthProgress");
    const text = document.getElementById("strengthText");

    if (!progress || !text) return;

    let percentage = 0;
    let strengthText = "";
    let color = "";

    if (length === 0) {
        strengthText = "Masukkan password";
        color = "#e0e0e0";
    } else if (strength <= 1) {
        strengthText = "Sangat Lemah";
        percentage = 25;
        color = "#dc3545";
    } else if (strength === 2) {
        strengthText = "Lemah";
        percentage = 50;
        color = "#fd7e14";
    } else if (strength === 3) {
        strengthText = "Cukup Kuat";
        percentage = 75;
        color = "#ffc107";
    } else {
        strengthText = "Sangat Kuat";
        percentage = 100;
        color = "#28a745";
    }

    progress.style.width = percentage + "%";
    progress.style.background = color;
    text.textContent = "Kekuatan: " + strengthText;
    text.style.color = color;
}

function validateRequiredField(input, message) {
    if (input?.value.trim() === "") {
        globalThis.showError?.(input, message);
        return false;
    }
    return true;
}

function validateEmailField(emailInput) {
    if (!emailInput) { return true; }
    const val = emailInput.value.trim();
    if (val === "") {
        globalThis.showError?.(emailInput, "Email tidak boleh kosong");
        return false;
    }
    if (!isValidEmail(val)) {
        globalThis.showError?.(emailInput, "Format email tidak valid (contoh: nama@email.com)");
        return false;
    }
    if (!isValidDomain(val)) {
        globalThis.showError?.(emailInput, "Email harus menggunakan domain belajar.ac.id");
        return false;
    }
    return true;
}

function validateWhatsappField(whatsappInput) {
    if (!whatsappInput) { return true; }
    const val = whatsappInput.value.trim();
    if (val === "") {
        globalThis.showError?.(whatsappInput, "WhatsApp tidak boleh kosong");
        return false;
    }
    if (!isValidWhatsapp(val)) {
        globalThis.showError?.(whatsappInput, "Format WhatsApp tidak valid (contoh: 08123456789)");
        return false;
    }
    return true;
}

function validatePasswordField(passwordInput) {
    if (!passwordInput) { return true; }
    const val = passwordInput.value;
    if (val.trim() === "") {
        globalThis.showError?.(passwordInput, "Password tidak boleh kosong");
        return false;
    }
    if (val.length < 8 || !/[A-Z]/.test(val) || !/\d/.test(val) || !/[@$!%*?&]/.test(val)) {
        globalThis.showError?.(passwordInput, "Password belum memenuhi syarat keamanan");
        return false;
    }
    return true;
}

function validateConfirmPasswordField(confirmPasswordInput, passwordInput) {
    if (!confirmPasswordInput) { return true; }
    if (confirmPasswordInput.value.trim() === "") {
        globalThis.showError?.(confirmPasswordInput, "Konfirmasi password tidak boleh kosong");
        return false;
    }
    if (passwordInput && confirmPasswordInput.value !== passwordInput.value) {
        globalThis.showError?.(confirmPasswordInput, "Password tidak cocok");
        return false;
    }
    return true;
}

function validateAgreeCheckbox(agreeCheckbox) {
    if (!agreeCheckbox?.checked) {
        const group = agreeCheckbox?.closest(".checkbox-group");
        group?.classList.add("input-error");
        group?.querySelector(".error-message")?.remove();
        const error = document.createElement("div");
        error.className = "error-message";
        error.style.width = "100%";
        error.innerHTML = "<i class='fas fa-exclamation-circle'></i> Anda harus menyetujui syarat dan ketentuan";
        group?.appendChild(error);
        return false;
    }
    return true;
}

function validateSignUpForm() {
    const fNameInput = document.getElementById("fName");
    const lNameInput = document.getElementById("lName");
    const emailInput = document.getElementById("signupEmail");
    const whatsappInput = document.getElementById("signupWhatsapp");
    const passwordInput = document.getElementById("signupPassword");
    const confirmPasswordInput = document.getElementById("signupConfirmPassword");
    const agreeCheckbox = document.getElementById("agreeTerms");

    const results = [
        validateRequiredField(fNameInput, "First Name tidak boleh kosong"),
        validateRequiredField(lNameInput, "Last Name tidak boleh kosong"),
        validateEmailField(emailInput),
        validateWhatsappField(whatsappInput),
        validatePasswordField(passwordInput),
        validateConfirmPasswordField(confirmPasswordInput, passwordInput),
        validateAgreeCheckbox(agreeCheckbox),
    ];

    return results.every(Boolean);
}

function validateSignInForm() {
    let isValid = true;
    const emailInput = document.getElementById("signinEmail");
    if (emailInput?.value.trim() === "") {
        globalThis.showError?.(emailInput, "Email tidak boleh kosong");
        isValid = false;
    }
    const passwordInput = document.getElementById("signinPassword");
    if (passwordInput?.value.trim() === "") {
        globalThis.showError?.(passwordInput, "Password tidak boleh kosong");
        isValid = false;
    }
    return isValid;
}

document.addEventListener("DOMContentLoaded", function () {
    if (signUpForm) {
        signUpForm.addEventListener("submit", function (e) {
            if (!validateSignUpForm()) e.preventDefault();
        });
    }
    if (signInForm) {
        signInForm.addEventListener("submit", function (e) {
            if (!validateSignInForm()) e.preventDefault();
        });
    }

    if (signUpButton) {
        signUpButton.onclick = function () {
            signIn.style.display = "none";
            signUp.style.display = "block";
            clearAllErrors();
        };
    }
    if (signInButton) {
        signInButton.onclick = function () {
            signUp.style.display = "none";
            signIn.style.display = "block";
            clearAllErrors();
        };
    }

    document.querySelectorAll("input").forEach((input) => {
        input.addEventListener("input", function () {
            removeError(this);
        });
    });

    document.querySelectorAll(".toggle-password").forEach((icon) => {
        icon.addEventListener("click", function () {
            const group = this.closest(".input-group");
            const input = group?.querySelector("input[type='password'], input[type='text']");
            if (!input) return;
            if (input.type === "password") {
                input.type = "text";
                this.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                input.type = "password";
                this.classList.replace("fa-eye-slash", "fa-eye");
            }
        });
    });

    const passwordInput = document.getElementById("signupPassword");
    const feedbackBox = document.getElementById("passwordFeedback");

    if (passwordInput && feedbackBox) {
        passwordInput.addEventListener("focus", function () {
            feedbackBox.classList.add("show");
            if (this.value.length > 0) {
                updatePasswordFeedback(this.value);
            }
        });

        passwordInput.addEventListener("input", function () {
            if (this.value.length > 0 || feedbackBox.classList.contains("show")) {
                updatePasswordFeedback(this.value);
                feedbackBox.classList.add("show");
            }
        });

        passwordInput.addEventListener("blur", function () {
            setTimeout(() => {
                feedbackBox.classList.remove("show");
            }, 200);
        });

        feedbackBox.addEventListener("mouseenter", function () {
            this.classList.add("show");
        });

        feedbackBox.addEventListener("mouseleave", function () {
            if (document.activeElement !== passwordInput) {
                this.classList.remove("show");
            }
        });

        document.addEventListener("click", function (e) {
            if (!passwordInput.contains(e.target) && !feedbackBox.contains(e.target)) {
                feedbackBox.classList.remove("show");
            }
        });
    }

    const agreeCheckbox = document.getElementById("agreeTerms");
    if (agreeCheckbox) {
        agreeCheckbox.addEventListener("change", function () {
            const group = this.closest(".checkbox-group");
            group?.classList.remove("input-error");
            const error = group?.querySelector(".error-message");
            error?.remove();
        });
    }
});