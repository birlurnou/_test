// script.js
let loginTime = window.loginTime;
const sessionLifetime = window.sessionLifetime;
let timerInterval;

function checkTime() {
    const now = Math.floor(Date.now() / 1000);
    const timeLeft = sessionLifetime - (now - loginTime);
    
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        window.location.href = '../login/index.php?expired=1';
        return true;
    }
    return false;
}

function resetTimer() {
    fetch('../auth/reset_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loginTime = data.login_time;
            if (!timerInterval) {
                timerInterval = setInterval(checkTime, 1000);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function checkSession() {
    fetch('../auth/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.active) {
                clearInterval(timerInterval);
                window.location.href = '../login/index.php?expired=1';
            } else if (data.login_time) {
                loginTime = data.login_time;
            }
        })
        .catch(() => {
            window.location.href = '../login/index.php?expired=1';
        });
}

function handleUserActivity() {
    resetTimer();
}

document.addEventListener('click', handleUserActivity);
document.addEventListener('mousemove', handleUserActivity);
document.addEventListener('keydown', handleUserActivity);
document.addEventListener('scroll', handleUserActivity);
document.addEventListener('touchstart', handleUserActivity);
document.addEventListener('touchmove', handleUserActivity);
document.addEventListener('wheel', handleUserActivity);
document.addEventListener('input', handleUserActivity);

timerInterval = setInterval(checkTime, 1000);
setInterval(checkSession, 5000);

function confirmLogout() {
    if (confirm('Вы уверены, что хотите выйти?')) {
        window.location.href = '../auth/logout.php';
    }
}

function toggleRoom(header) {
    const card = header.closest('.room-card');
    const guests = card.querySelector('.room-guests');
    const icon = header.querySelector('.toggle-icon');
    
    if (guests.style.display === 'flex') {
        guests.style.display = 'none';
        icon.textContent = '▼';
    } else {
        guests.style.display = 'flex';
        icon.textContent = '▲';
    }
}

function guestClick(element, guestName) {
    alert('Вы выбрали: ' + guestName);
}

function toggleGuest(element) {
    const wrapper = element.closest('.guest-wrapper');
    const dropdown = wrapper.querySelector('.guest-dropdown');
    const toggle = element.querySelector('.guest-toggle'); // .закрыть все другие
    
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    } else {
        // закрываем все другие
        document.querySelectorAll('.guest-dropdown').forEach(d => { // .закрыть все другие
            d.style.display = 'none'; // .закрыть все другие
            const t = d.closest('.guest-wrapper').querySelector('.guest-toggle'); // .закрыть все другие
        }); // .закрыть все другие
        dropdown.style.display = 'block';
    }
}

















// Функции для модального окна
let currentGuestElement = null;
let currentCommentContainer = null;

function openModal(buttonElement) {
    const modal = document.getElementById('commentModal');
    const textarea = document.getElementById('commentText');
    
    // Находим родительский контейнер комментариев
    const guestWrapper = buttonElement.closest('.guest-wrapper');
    currentCommentContainer = guestWrapper.querySelector('.guest-dropdown');
    currentGuestElement = guestWrapper;
    
    // Очищаем текстовое поле
    textarea.value = '';
    textarea.focus();
    
    // Показываем модальное окно
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Запрещаем скролл страницы
}

function closeModal() {
    const modal = document.getElementById('commentModal');
    modal.style.display = 'none';
    document.body.style.overflow = ''; // Возвращаем скролл
    currentGuestElement = null;
    currentCommentContainer = null;
}

function addComment() {
    const textarea = document.getElementById('commentText');
    const commentText = textarea.value.trim();
    
    if (!commentText) {
        alert('Please enter your comment text.');
        textarea.focus();
        return;
    }
    
    if (!currentCommentContainer) {
        alert('Error: Comment container not found.');
        closeModal();
        return;
    }
    
    // Создаем новый комментарий
    const commentItem = document.createElement('div');
    commentItem.className = 'comment-item';
    
    // Получаем текущую дату и время
    const now = new Date();
    const dateStr = now.toLocaleDateString('ru-RU') + ' ' + 
                    now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    
    commentItem.innerHTML = `
        <div class="comment-header">
            <div class="comment-info">
                <span class="comment-time">${dateStr}</span>
                <span class="comment-creator">${document.querySelector('.username')?.textContent || 'Пользователь'}</span>
            </div>
            <div class="comment-actions">
                <button class="comment-btn edit-btn" onclick="editComment(this)">Change</button>
                <button class="comment-btn delete-btn" onclick="deleteComment(this)">Delete</button>
            </div>
        </div>
        <div class="comment-text">${escapeHtml(commentText)}</div>
    `;
    
    // Находим кнопку "Add Comment" и вставляем перед ней
    const addButton = currentCommentContainer.querySelector('.add-comment-btn');
    if (addButton) {
        currentCommentContainer.insertBefore(commentItem, addButton.nextSibling);
    } else {
        currentCommentContainer.appendChild(commentItem);
    }
    
    // Закрываем модальное окно
    closeModal();
    
    // Показываем сообщение об успехе
    showNotification('Comment successfully added!');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function deleteComment(button) {
    if (confirm('Are you sure you want to delete this comment?')) {
        const commentItem = button.closest('.comment-item');
        if (commentItem) {
            commentItem.remove();
            showNotification('Comment deleted.');
        }
    }
}

function editComment(button) {
    const commentItem = button.closest('.comment-item');
    const commentTextDiv = commentItem.querySelector('.comment-text');
    const currentText = commentTextDiv.textContent;
    
    // Открываем модальное окно с текущим текстом
    const modal = document.getElementById('commentModal');
    const textarea = document.getElementById('commentText');
    textarea.value = currentText;
    textarea.focus();
    
    // Сохраняем ссылку на комментарий для редактирования
    currentCommentContainer = commentItem.closest('.guest-dropdown');
    currentGuestElement = commentItem.closest('.guest-wrapper');
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Временно переопределяем функцию addComment для редактирования
    const originalAddComment = window.addComment;
    window.addComment = function() {
        const newText = textarea.value.trim();
        if (!newText) {
            alert('Please enter your comment text.');
            return;
        }
        commentTextDiv.textContent = newText;
        closeModal();
        showNotification('Comment updated.');
        window.addComment = originalAddComment; // Восстанавливаем функцию
    };
    
    // Сохраняем оригинальную функцию для восстановления при закрытии
    const originalClose = window.closeModal;
    window.closeModal = function() {
        closeModal();
        window.addComment = originalAddComment;
        window.closeModal = originalClose;
    };
}

function showNotification(message) {
    // Создаем уведомление
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: #5B5778;
        color: white;
        padding: 12px 24px;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        z-index: 2000;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Анимация появления
    setTimeout(() => {
        notification.style.opacity = '1';
    }, 10);
    
    // Автоматическое скрытие через 3 секунды
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Закрытие модального окна по клику вне его
window.onclick = function(event) {
    const modal = document.getElementById('commentModal');
    if (event.target === modal) {
        closeModal();
    }
};

// Закрытие по клавише Esc
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});