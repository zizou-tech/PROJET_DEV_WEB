// js/script.js

document.addEventListener('DOMContentLoaded', () => {
    console.log("Le script JavaScript est chargé.");

    // Exemple de validation côté client pour le formulaire d'inscription
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', (event) => {
            const password = registerForm.password.value;
            const confirmPassword = registerForm.confirm_password.value;
            const email = registerForm.email.value;

            if (password !== confirmPassword) {
                alert("Les mots de passe ne correspondent pas !");
                event.preventDefault(); // Empêche l'envoi du formulaire
            }

            if (password.length < 6) {
                alert("Le mot de passe doit contenir au moins 6 caractères.");
                event.preventDefault();
            }

            // Validation simple de l'email avec une regex
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert("Veuillez entrer une adresse email valide.");
                event.preventDefault();
            }
        });
    }

    // Vous pouvez ajouter d'autres interactions JavaScript ici
    // Par exemple, des animations, des messages de confirmation non bloquants, etc.
});