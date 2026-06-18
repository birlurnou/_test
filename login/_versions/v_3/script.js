document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const loginInput = document.getElementById('login');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('loginBtn');
    
    // функция для анимации ошибки
    function showError(field) {
        // добавляем класс ошибки
        field.classList.add('error');
        
        // создаем эффект встряски
        field.style.animation = 'shake 0.3s ease';
        
        // через 2 секунды убираем ошибку и очищаем поле
        setTimeout(() => {
            field.classList.remove('error');
            field.style.animation = '';
            // field.value = ''; // Очищаем поле
            field.placeholder = field === loginInput ? 'Enter your login' : 'Enter your password';
        }, 2000);
    }
    
    // функция для очистки всех полей
    function clearFields() {
        loginInput.value = '';
        passwordInput.value = '';
        loginInput.classList.remove('error');
        passwordInput.classList.remove('error');
        loginInput.style.animation = '';
        passwordInput.style.animation = '';
    }
    
    // функция для блокировки/разблокировки кнопки
    function setLoading(isLoading) {
        if (isLoading) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Loading...';
            submitBtn.style.opacity = '0.7';
        } else {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign In';
            submitBtn.style.opacity = '1';
        }
    }
    
    // обработчик отправки формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const login = loginInput.value.trim();
        const password = passwordInput.value.trim();
        
        // проверка на пустые поля
        if (!login || !password) {
            if (!login) {
                showError(loginInput);
                loginInput.placeholder = 'Login is required';
            }
            if (!password) {
                showError(passwordInput);
                passwordInput.placeholder = 'Password is required';
            }
            return;
        }
        
        // блокируем кнопку
        setLoading(true);
        
        try {
            // отправляем запрос на сервер
            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    login: login,
                    password: password
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // успешная авторизация
                submitBtn.textContent = '✓ Success!';
                submitBtn.style.background = '#2e7d32';
                
                setTimeout(() => {
                    window.location.href = data.redirect || 'dashboard.php';
                }, 500);
            } else {
                // ошибка авторизации - показываем ошибку на обоих полях
                setLoading(false);
                
                // очищаем оба поля
                loginInput.value = '';
                passwordInput.value = '';
                
                // показываем ошибку на обоих полях
                showError(loginInput);
                showError(passwordInput);
                
                // меняем placeholder
                loginInput.placeholder = 'Invalid login or password';
                passwordInput.placeholder = 'Invalid login or password';
            }
        } catch (error) {
            console.error('Error:', error);
            setLoading(false);
            showError(loginInput);
            showError(passwordInput);
            loginInput.placeholder = 'Connection error';
            passwordInput.placeholder = 'Connection error';
        }
    });
    
    // очистка ошибок при вводе
    loginInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            this.classList.remove('error');
            this.style.animation = '';
        }
    });
    
    passwordInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            this.classList.remove('error');
            this.style.animation = '';
        }
    });
    
    // добавляем CSS анимации
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .form-input.error {
            border-color: #d94a4a !important;
            background: rgba(217, 74, 74, 0.08) !important;
            box-shadow: 0 0 0 3px rgba(217, 74, 74, 0.15) !important;
            transition: all 0.3s ease;
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .login-btn {
            transition: all 0.3s ease;
        }
        
        .login-btn:disabled {
            cursor: not-allowed;
        }
    `;
    document.head.appendChild(style);
});