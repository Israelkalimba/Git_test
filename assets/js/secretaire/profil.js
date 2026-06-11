/**
 * ISTAM Paiement - Profil Secrétaire JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        initTogglePasswords();
        initPasswordStrength();
        console.log('👤 Profil Secrétaire ISTAM - Prêt');
    });

    // ========== TOGGLE PASSWORD ==========
    function initTogglePasswords() {
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;
                
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        });
    }

    // ========== INDICATEUR FORCE MOT DE PASSE ==========
    function initPasswordStrength() {
        const input = document.querySelector('input[name="nouveau_mdp"]');
        if (!input) return;

        const strengthDiv = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');

        input.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            
            let score = 0;
            if (password.length >= 6) score++;
            if (password.length >= 10) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            const levels = [
                { width: '20%', color: '#ef4444', text: 'Très faible' },
                { width: '40%', color: '#f97316', text: 'Faible' },
                { width: '60%', color: '#f59e0b', text: 'Moyen' },
                { width: '80%', color: '#84cc16', text: 'Fort' },
                { width: '100%', color: '#10b981', text: 'Très fort' }
            ];
            
            const level = levels[Math.min(score, 4)];
            strengthFill.style.width = level.width;
            strengthFill.style.background = level.color;
            strengthText.textContent = level.text;
            strengthText.style.color = level.color;
        });
    }

})();
