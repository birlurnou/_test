document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.querySelector('.add-btn');
    const modal = document.getElementById('userModal');
    const cancelBtn = document.getElementById('cancelUserBtn');
    const saveBtn = document.getElementById('saveUserBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('userPassword');
    const loginInput = document.getElementById('userLogin');
    const roleSelect = document.getElementById('userRole');

    function openModal() {
        modal.classList.add('active');
        loginInput.value = '';
        passwordInput.value = '';
        roleSelect.value = 'user';
        passwordInput.type = 'password';
        togglePassword.textContent = '👁';
        document.body.style.overflow = 'hidden';
        
        // Убираем ошибки при открытии
        loginInput.classList.remove('error');
        passwordInput.classList.remove('error');
        document.querySelectorAll('.error-message').forEach(el => el.classList.remove('visible'));
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Открытие модального окна
    if (addBtn) {
        addBtn.addEventListener('click', openModal);
    }

    // Закрытие по кнопке Close
    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    // Закрытие по клику на фон
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Показать/скрыть пароль
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.style.color = type === 'password' ? '#9D9AAE' : '#5B5778';
        });
    }

    // Функция для отображения ошибки
    function showError(inputElement, message) {
        inputElement.classList.add('error');
        const errorDiv = inputElement.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.add('visible');
        } else {
            // Если нет элемента для ошибки, создаем его
            const newError = document.createElement('div');
            newError.className = 'error-message visible';
            newError.textContent = message;
            inputElement.parentElement.appendChild(newError);
        }
    }

    function clearErrors() {
        document.querySelectorAll('.form-input.error').forEach(el => el.classList.remove('error'));
        document.querySelectorAll('.error-message').forEach(el => el.classList.remove('visible'));
    }

    // Сохранение пользователя
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            clearErrors();
            
            const login = loginInput.value.trim();
            const password = passwordInput.value;
            const role = roleSelect.value;
            
            let isValid = true;
            
            // Проверка логина
            if (!login) {
                showError(loginInput, 'Login is required');
                isValid = false;
            } else if (login.length < 3) {
                showError(loginInput, 'Login must be at least 3 characters');
                isValid = false;
            }
            
            // Проверка пароля
            if (!password) {
                showError(passwordInput, 'Password is required');
                isValid = false;
            } else if (password.length < 4) {
                showError(passwordInput, 'Password must be at least 4 characters');
                isValid = false;
            }
            
            if (!isValid) {
                return;
            }
            
            // Отправка данных на сервер
            const formData = new FormData();
            formData.append('login', login);
            formData.append('password', password);
            formData.append('role', role);
            
            fetch('create_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User created successfully!');
                    closeModal();
                    location.reload(); // Перезагружаем страницу для обновления списка
                } else {
                    alert('Error: ' + data.error);
                    if (data.error === 'User already exists') {
                        showError(loginInput, 'User with this login already exists');
                    }
                }
            })
            .catch(error => {
                alert('Connection error: ' + error.message);
            });
        });
    }
});