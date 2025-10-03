
    function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const icon = document.querySelector('.toggle-password i');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('uil-eye-slash');
        icon.classList.add('uil-eye');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('uil-eye');
        icon.classList.add('uil-eye-slash');
    }
}