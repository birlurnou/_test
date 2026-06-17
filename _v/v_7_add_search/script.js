document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.querySelector('.add-btn');
    const modal = document.getElementById('userModal');
    const cancelBtn = document.getElementById('cancelUserBtn');
    const saveBtn = document.getElementById('saveUserBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('userPassword');
    const loginInput = document.getElementById('userLogin');
    const roleSelect = document.getElementById('userRole');
    const searchInput = document.querySelector('.search-input');
    const usersList = document.querySelector('.users-list');

    // Функция для загрузки пользователей с фильтром
    function loadUsers(searchTerm = '') {
        const formData = new FormData();
        formData.append('search', searchTerm);
        
        fetch('get_users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderUsers(data.users);
            } else {
                console.error('Error loading users:', data.error);
            }
        })
        .catch(error => {
            console.error('Connection error:', error);
        });
    }

    // Функция для отрисовки пользователей
    function renderUsers(users) {
        if (!usersList) return;
        
        if (users.length === 0) {
            usersList.innerHTML = `
                <div style="text-align: center; padding: 40px 20px; color: #9D9AAE; font-size: 1rem;">
                    No users found
                </div>
            `;
            return;
        }
        
        let html = '';
        users.forEach(user => {
            html += `
                <div class="user-row">
                    <div class="col-name">${escapeHtml(user.login)}</div>
                    <div class="col-role">${escapeHtml(user.role)}</div>
                    <div class="col-actions">
                        <button class="action-btn edit-btn" data-userid="${user.user_id}">Change</button>
                        <button class="action-btn copy-btn" data-userid="${user.user_id}">Copy</button>
                        <button class="action-btn delete-btn" data-userid="${user.user_id}">Delete</button>
                    </div>
                </div>
            `;
        });
        usersList.innerHTML = html;
    }

    // Функция для экранирования HTML
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Поиск с задержкой (debounce)
    let searchTimeout = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            searchTimeout = setTimeout(() => {
                loadUsers(searchTerm);
            }, 300); // Задержка 300ms после окончания ввода
        });
    }

    // Функции для модального окна
    function openModal() {
        modal.classList.add('active');
        loginInput.value = '';
        passwordInput.value = '';
        roleSelect.value = 'user';
        passwordInput.type = 'password';
        togglePassword.textContent = '👁';
        document.body.style.overflow = 'hidden';
        
        loginInput.classList.remove('error');
        passwordInput.classList.remove('error');
        document.querySelectorAll('.error-message').forEach(el => el.classList.remove('visible'));
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        loadUsers(searchInput ? searchInput.value.trim() : '');
    }

    if (addBtn) {
        addBtn.addEventListener('click', openModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            closeModal();
        }
    });

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.style.color = type === 'password' ? '#9D9AAE' : '#5B5778';
        });
    }

    function showError(inputElement, message) {
        inputElement.classList.add('error');
        const errorDiv = inputElement.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.add('visible');
        } else {
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

    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            clearErrors();
            
            const login = loginInput.value.trim();
            const password = passwordInput.value;
            const role = roleSelect.value;
            
            let isValid = true;
            
            if (!login) {
                showError(loginInput, 'Login is required');
                isValid = false;
            } else if (login.length < 3) {
                showError(loginInput, 'Login must be at least 3 characters');
                isValid = false;
            }
            
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

    // Загрузка пользователей при старте
    loadUsers();
});