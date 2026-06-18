<?php
require_once 'config/config.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="layout">
        <div class="area">

            <!-- верхний блок -->
            <header class="top-block">
                <div class="top-block-content">
                    <button class="add-btn">Create New User</button>
                    <div class="center-text">
                        <h2>Admin Panel</h2>
                        <p>User Management</p>
                    </div>
                    <button class="go-btn">Restaurant Accounting</button>
                </div>
            </header>

            <!-- основной контент -->
            <main class="content">

                <!-- строка поиска -->
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search users...">
                </div>

                <!-- строка с заголовками -->
                <div class="table-header">
                    <div class="col-name">Login</div>
                    <div class="col-role">Role</div>
                    <div class="col-actions">Actions</div>
                </div>

                <!-- список пользователей -->
                <div class="users-list">
                </div>

            </main>
        </div>
    </div>

    <!-- модальное окно создания пользователя -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <h2 class="modal-title">Create New User</h2>
            
            <div class="modal-form">
                <div class="form-group">
                    <label class="form-label">Login</label>
                    <input type="text" class="form-input" id="userLogin" placeholder="Enter login">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-input" id="userPassword" placeholder="Enter password">
                        <button class="password-toggle" id="togglePassword" type="button">👁</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select class="form-select" id="userRole">
                        <option value="user">user</option>
                        <option value="manager">manager</option>
                        <option value="admin">admin</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button class="modal-btn save-btn" id="saveUserBtn">Save</button>
                    <button class="modal-btn cancel-btn" id="cancelUserBtn">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>