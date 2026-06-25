document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.querySelector('.add-btn');
    const modal = document.getElementById('userModal');
    const cancelBtn = document.getElementById('cancelUserBtn');
    const saveBtn = document.getElementById('saveUserBtn');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('userPassword');
    const loginInput = document.getElementById('userLogin');
    const roleSelect = document.getElementById('userRole');
    const usernameInput = document.getElementById('userUsername');
    const searchInput = document.querySelector('.search-input');
    const usersList = document.querySelector('.users-list');
    const goBtn = document.querySelector('.go-btn');
    if (goBtn) {
        goBtn.addEventListener('click', function() {
            window.location.href = '../main/index.php';
        });
    }
    
    let currentAction = null; // 'edit' или 'copy'
    let currentUserId = null;

    // функция для загрузки пользователей с фильтром
    function loadUsers(searchTerm = '') {
        const formData = new FormData();
        formData.append('search', searchTerm);
        
        fetch('core/get_users.php', {
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

    // функция для отрисовки пользователей
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
                <div class="user-row" data-userid="${user.user_id}">
                    <div class="col-name">${escapeHtml(user.login)}</div>
                    <div class="col-role">${escapeHtml(user.role)}</div>
                    <div class="col-actions">
                        <button class="action-btn edit-btn" data-action="edit" data-userid="${user.user_id}">Change</button>
                        <button class="action-btn copy-btn" data-action="copy" data-userid="${user.user_id}">Copy</button>
                        <button class="action-btn delete-btn" data-action="delete" data-userid="${user.user_id}">Delete</button>
                    </div>
                </div>
            `;
        });
        usersList.innerHTML = html;
        
        // добавляем обработчики для кнопок действий
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const action = this.dataset.action;
                const userId = this.dataset.userid;
                
                if (action === 'edit') {
                    openEditModal(userId);
                } else if (action === 'copy') {
                    openCopyModal(userId);
                } else if (action === 'delete') {
                    deleteUser(userId);
                }
            });
        });
    }

    // функция для экранирования HTML
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // поиск с задержкой (debounce)
    let searchTimeout = null;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            searchTimeout = setTimeout(() => {
                loadUsers(searchTerm);
            }, 300);
        });
    }

    // функция для открытия модального окна с данными пользователя (Change)
    function openEditModal(userId) {
        currentAction = 'edit';
        currentUserId = userId;
        
        // загружаем данные пользователя
        const formData = new FormData();
        formData.append('user_id', userId);
        
        fetch('core/get_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                loginInput.value = user.login;
                passwordInput.value = user.password;
                roleSelect.value = user.role;
                usernameInput.value = user.username;
                loginInput.disabled = false; // блокируем изменение логина
                document.querySelector('.modal-title').textContent = 'Edit User';
                openModal();
            } else {
                alert('Error loading user data');
            }
        })
        .catch(error => {
            alert('Connection error: ' + error.message);
        });
    }

    // функция для открытия модального окна с копированием роли (Copy)
    function openCopyModal(userId) {
        currentAction = 'copy';
        currentUserId = userId;
        
        // загружаем данные пользователя
        const formData = new FormData();
        formData.append('user_id', userId);
        
        fetch('core/get_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                loginInput.value = '';
                passwordInput.value = '';
                roleSelect.value = user.role;
                usernameInput.value = '';
                loginInput.disabled = false;
                usernameInput.disabled = false;
                document.querySelector('.modal-title').textContent = 'Copy User';
                openModal();
            } else {
                alert('Error loading user data');
            }
        })
        .catch(error => {
            alert('Connection error: ' + error.message);
        });
    }

    // функция для удаления пользователя
    function deleteUser(userId) {
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('user_id', userId);
        
        fetch('core/delete_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // alert('User deleted successfully!');
                loadUsers(searchInput ? searchInput.value.trim() : '');
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Connection error: ' + error.message);
        });
    }

    // функции для модального окна
    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        loginInput.classList.remove('error');
        passwordInput.classList.remove('error');
        document.querySelectorAll('.error-message').forEach(el => el.classList.remove('visible'));
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';

        loginInput.value = '';
        loginInput.disabled = false;
        passwordInput.value = '';
        roleSelect.value = 'user';
        usernameInput.value = '';
        usernameInput.disabled = false;

        loginInput.disabled = false;
        document.querySelector('.modal-title').textContent = 'Create New User';
        currentAction = null;
        currentUserId = null;

        clearErrors();

        loadUsers(searchInput ? searchInput.value.trim() : '');
    }

    if (addBtn) {
        addBtn.addEventListener('click', function() {
            currentAction = 'add';
            currentUserId = null;
            loginInput.value = '';
            passwordInput.value = '';
            roleSelect.value = 'user';
            usernameInput.value = '';
            loginInput.disabled = false;
            usernameInput.disabled = false;
            document.querySelector('.modal-title').textContent = 'Create New User';
            openModal();
        });
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
            const username = usernameInput.value.trim();
            
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
            } else if (password.length < 3) {
                showError(passwordInput, 'Password must be at least 3 characters');
                isValid = false;
            }
            
            if (!username) {
                showError(usernameInput, 'Display name is required');
                isValid = false;
            } else if (username.length < 2) {
                showError(usernameInput, 'Display name must be at least 2 characters');
                isValid = false;
            }

            if (!isValid) {
                return;
            }
            
            let url = 'core/create_user.php';
            const formData = new FormData();
            formData.append('login', login);
            formData.append('password', password);
            formData.append('role', role);
            formData.append('username', username);
            
            if (currentAction === 'edit' && currentUserId) {
                url = 'core/update_user.php';
                formData.append('user_id', currentUserId);
            } else if (currentAction === 'copy') {
                // для copy используем create_user.php с проверкой на существование
                url = 'core/create_user.php';
            }
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // alert(currentAction === 'edit' ? 'User updated successfully!' : 'User created successfully!');
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

    // загрузка пользователей при старте
    loadUsers();
});