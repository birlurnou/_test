// script.js
let loginTime = window.loginTime;
const sessionLifetime = window.sessionLifetime;
let timerInterval;
let currentGuestId = null;
let currentCommentId = null;
let currentGuestIdForComment = null;

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

/* function confirmLogout() {
    if (confirm('Вы уверены, что хотите выйти?')) {
        window.location.href = '../auth/logout.php';
    }
} */

function confirmLogout() {
    openConfirmModal(
        'Logout',
        'Are you sure you want to exit?',
        function() {
            window.location.href = '../auth/logout.php';
        }
    );
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

function toggleGuest(element) {
    const wrapper = element.closest('.guest-wrapper');
    const dropdown = wrapper.querySelector('.guest-dropdown');
    
    // закрываем все другие
    document.querySelectorAll('.guest-dropdown').forEach(d => {
        if (d !== dropdown) {
            d.style.display = 'none';
        }
    });
    
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    } else {
        dropdown.style.display = 'block';
    }
}

// функция для переключения Check In/Unmark Check In

function performCheckIn(element, guestId, roomNumber) {
    fetch('../processing/checkin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            guest_id: guestId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Меняем внешний вид кнопки
            element.classList.add('checked-in');
            element.textContent = 'Unmark\nCheck In';

            // Добавляем плашку Attention с датой и временем
            const guestItem = element.closest('.guest-item');
            const bottomRow = guestItem.querySelector('.guest-row-bottom');
            if (bottomRow && data.attended_at) {
                let attentionBadge = guestItem.querySelector('.guest-attention-badge');
                if (!attentionBadge) {
                    attentionBadge = document.createElement('span');
                    attentionBadge.className = 'guest-badge guest-attention-badge';
                    attentionBadge.style.cssText = 'background: rgba(210, 140, 44, 0.8); color: white;';
                    bottomRow.appendChild(attentionBadge);
                }
                const attendedDate = new Date(data.attended_at);
                const dateStr = attendedDate.toLocaleDateString('ru-RU');
                const timeStr = attendedDate.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
                attentionBadge.textContent = `${dateStr} ${timeStr}`;
            }

            // Обновляем счётчик Attended
            updateAttendedCount(roomNumber);
        } else {
            alert('Error: ' + (data.error || 'Operation failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

// Выполняет снятие отметки Check In (без подтверждения)
function performUncheck(element, guestId, roomNumber) {
    fetch('../processing/unmark_checkin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            guest_id: guestId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Меняем внешний вид кнопки
            element.classList.remove('checked-in');
            element.textContent = 'Check In';

            // Удаляем плашку Attention
            const guestItem = element.closest('.guest-item');
            const attentionBadge = guestItem.querySelector('.guest-attention-badge');
            if (attentionBadge) {
                attentionBadge.remove();
            }

            // Обновляем счётчик Attended
            updateAttendedCount(roomNumber);
        } else {
            alert('Error: ' + (data.error || 'Operation failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

// Основная функция переключения (использует кастомную модалку для снятия)
function toggleCheckIn(element, guestId, roomNumber) {
    const isCheckedIn = element.classList.contains('checked-in');

    if (isCheckedIn) {
        // Для снятия отметки — показываем модалку подтверждения
        openConfirmModal(
            'Unmark Check In',
            'Are you sure you want to uncheck Check In?',
            function() {
                performUncheck(element, guestId, roomNumber);
            }
        );
    } else {
        // Для отметки — сразу выполняем без подтверждения
        performCheckIn(element, guestId, roomNumber);
    }
}

/* function toggleCheckIn(element, guestId, roomNumber) {
    const isCheckedIn = element.classList.contains('checked-in');
    if (isCheckedIn) {
        if (!confirm('Are you sure you want to uncheck Check In?')) {
            return;
        }
    }
    const url = isCheckedIn ? '../processing/unmark_checkin.php' : '../processing/checkin.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            guest_id: guestId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isCheckedIn) {
                // Unmark
                element.classList.remove('checked-in');
                element.textContent = 'Check In';
                // удаляем плашку Attention
                const guestItem = element.closest('.guest-item');
                const attentionBadge = guestItem.querySelector('.guest-attention-badge');
                if (attentionBadge) {
                    attentionBadge.remove();
                }
                // обновляем счетчик Attended
                updateAttendedCount(roomNumber);
            } else {
                // Check In
                element.classList.add('checked-in');
                element.textContent = 'Unmark\nCheck In';
                // добавляем плашку Attention с датой и временем
                const guestItem = element.closest('.guest-item');
                const bottomRow = guestItem.querySelector('.guest-row-bottom');
                if (bottomRow && data.attended_at) {
                    // проверяем, есть ли уже плашка Attention
                    let attentionBadge = guestItem.querySelector('.guest-attention-badge');
                    if (!attentionBadge) {
                        attentionBadge = document.createElement('span');
                        attentionBadge.className = 'guest-badge guest-attention-badge';
                        attentionBadge.style.cssText = 'background: rgba(210, 140, 44, 0.8); color: white;';
                        bottomRow.appendChild(attentionBadge);
                    }
                    // форматируем дату и время
                    const attendedDate = new Date(data.attended_at);
                    const dateStr = attendedDate.toLocaleDateString('ru-RU');
                    const timeStr = attendedDate.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
                    attentionBadge.textContent = `Attention: `${dateStr} ${timeStr}`;
                }
                // обновляем счетчик Attended
                updateAttendedCount(roomNumber);
            }
        } else {
            alert('Error: ' + (data.error || 'Operation failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}
*/

function updateAttendedCount(roomNumber) {
    const roomCard = document.querySelector(`.room-card[data-room="${roomNumber}"]`);
    if (!roomCard) return;
    
    const guests = roomCard.querySelectorAll('.guest-item');
    let attendedCount = 0;
    guests.forEach(guest => {
        const clickArea = guest.querySelector('.guest-click-area');
        if (clickArea && clickArea.classList.contains('checked-in')) {
            attendedCount++;
        }
    });
    
    const badge = roomCard.querySelector('.room-guests-badge');
    if (badge) {
        const total = guests.length;
        badge.textContent = `Attended ${attendedCount} / ${total}`;

        badge.classList.remove('all-attended', 'path-attended');
        
        if (total > 0) {
            if (attendedCount > 0 && attendedCount < total) {
                badge.classList.add('path-attended');
            } else if (attendedCount === total) {
                badge.classList.add('all-attended');
            }
        }
    }
}

// функции для модального окна
function openModal(buttonElement, guestId) {
    const modal = document.getElementById('commentModal');
    const textarea = document.getElementById('commentText');
    
    currentGuestIdForComment = guestId;
    currentCommentId = null;
    
    // очищаем текстовое поле
    textarea.value = '';
    textarea.focus();
    
    // меняем заголовок
    modal.querySelector('h2').textContent = 'New Comment';
    modal.querySelector('.add-btn').textContent = 'Add Comment';
    
    // показываем модальное окно
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('commentModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    currentGuestIdForComment = null;
    currentCommentId = null;
}

function saveComment() {
    const textarea = document.getElementById('commentText');
    const commentText = textarea.value.trim();
    
    if (!commentText) {
        alert('Please enter your comment text.');
        textarea.focus();
        return;
    }
    
    const url = currentCommentId ? '../processing/update_comment.php' : '../processing/add_comment.php';
    const data = currentCommentId ? 
        { comment_id: currentCommentId, comment: commentText } :
        { guest_id: currentGuestIdForComment, comment: commentText, created_by: window.currentUser };
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (currentCommentId) {
                // обновляем существующий комментарий
                const commentItem = document.querySelector(`.comment-item[data-comment-id="${currentCommentId}"]`);
                if (commentItem) {
                    const textDiv = commentItem.querySelector('.comment-text');
                    if (textDiv) {
                        textDiv.textContent = commentText;
                    }
                }
                showNotification('Comment updated successfully!');
            } else {
                // добавляем новый комментарий
                const dropdown = document.querySelector(`.guest-dropdown[data-guest-id="${currentGuestIdForComment}"]`);
                if (dropdown) {
                    const newComment = createCommentElement(result.comment_id, commentText);
                    const addButton = dropdown.querySelector('.add-comment-btn');
                    if (addButton) {
                        dropdown.insertBefore(newComment, addButton.nextSibling);
                    } else {
                        dropdown.appendChild(newComment);
                    }
                    
                    // убираем класс no-comments
                    dropdown.classList.remove('no-comments');
                    
                    // обновляем счетчик комментариев
                    updateCommentCount(currentGuestIdForComment);
                }
                showNotification('Comment added successfully!');
            }
            closeModal();
        } else {
            alert('Error: ' + (result.error || 'Operation failed'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    });
}

function createCommentElement(commentId, text) {
    const now = new Date();
    const dateStr = now.toLocaleDateString('ru-RU') + ' ' + now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    
    const div = document.createElement('div');
    div.className = 'comment-item';
    div.setAttribute('data-comment-id', commentId);
    div.innerHTML = `
        <div class="comment-header">
            <div class="comment-info">
                <span class="comment-time">${dateStr}</span>
                <span class="comment-creator">${window.currentUser || 'Unknown'}</span>
            </div>
            <div class="comment-actions">
                <button class="comment-btn edit-btn" onclick="editComment(this, ${commentId})">Change</button>
                <button class="comment-btn delete-btn" onclick="deleteComment(this, ${commentId})">Delete</button>
            </div>
        </div>
        <div class="comment-text">${escapeHtml(text)}</div>
    `;
    return div;
}

function editComment(button, commentId) {
    const commentItem = button.closest('.comment-item');
    const commentTextDiv = commentItem.querySelector('.comment-text');
    const currentText = commentTextDiv.textContent;
    
    const modal = document.getElementById('commentModal');
    const textarea = document.getElementById('commentText');
    textarea.value = currentText;
    textarea.focus();
    
    currentCommentId = commentId;
    currentGuestIdForComment = null;
    
    modal.querySelector('h2').textContent = 'Edit Comment';
    modal.querySelector('.add-btn').textContent = 'Update Comment';
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function deleteComment(button, commentId) {
    /* if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    } */
    
    openConfirmModal(
        'Deleting a comment',
        'Are you sure you want to delete this comment?',
        function() {

        fetch('../processing/delete_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ comment_id: commentId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const commentItem = button.closest('.comment-item');
                if (commentItem) {
                    const dropdown = commentItem.closest('.guest-dropdown');
                    const guestId = dropdown.dataset.guestId;
                    commentItem.remove();
                    
                    // Проверяем, остались ли комментарии
                    const remainingComments = dropdown.querySelectorAll('.comment-item');
                    if (remainingComments.length === 0) {
                        dropdown.classList.add('no-comments');
                    }
                    
                    updateCommentCount(guestId);
                    showNotification('Comment deleted.');
                }
            } else {
                alert('Error: ' + (result.error || 'Operation failed'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    })
}

function updateCommentCount(guestId) {
    const dropdown = document.querySelector(`.guest-dropdown[data-guest-id="${guestId}"]`);
    if (!dropdown) return;
    
    const comments = dropdown.querySelectorAll('.comment-item');
    const count = comments.length;
    
    const guestItem = dropdown.closest('.guest-wrapper').querySelector('.guest-item');
    if (guestItem) {
        let commentBadge = guestItem.querySelector('.guest-comment-badge');
        if (count === 0) {
            if (commentBadge) {
                commentBadge.style.display = 'none';
            }
        } else {
            if (!commentBadge) {
                // создаем новый badge, если его нет
                const middleRow = guestItem.querySelector('.guest-row-middle');
                if (middleRow) {
                    commentBadge = document.createElement('span');
                    commentBadge.className = 'guest-badge guest-comment-badge';
                    middleRow.appendChild(commentBadge);
                }
            }
            if (commentBadge) {
                commentBadge.style.display = 'inline-flex';
                commentBadge.textContent = `${count} comment${count > 1 ? 's' : ''}`;
            }
        }
    }

    // находим карточку комнаты
    const roomCard = dropdown.closest('.room-card');
    if (roomCard) {
        // считаем все комментарии в этой комнате
        const allComments = roomCard.querySelectorAll('.comment-item');
        const totalCount = allComments.length;
        
        // находим бейдж комнаты для комментариев
        const roomCommentsBadge = roomCard.querySelector('.room-comments-badge');
        if (roomCommentsBadge) {
            roomCommentsBadge.textContent = `${totalCount} comment${totalCount > 1 ? 's' : ''}`;
        }
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message) {
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
    
    setTimeout(() => {
        notification.style.opacity = '1';
    }, 10);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}



// закрытие модального окна по клику вне его
window.onclick = function(event) {
    const modal = document.getElementById('commentModal');
    if (event.target === modal) {
        closeModal();
    }
};

// закрытие по клавише Esc
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// поиск комнат
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchRoom');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const roomCards = document.querySelectorAll('.room-card');
            
            roomCards.forEach(card => {
                const roomNumber = card.dataset.room || '';
                if (roomNumber.includes(searchTerm) || searchTerm === '') {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});





// переменная для хранения функции, которая выполнится при подтверждении
let confirmCallback = null;

function openConfirmModal(title, message, callback) {
    const modal = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmModalTitle');
    const messageEl = document.getElementById('confirmModalMessage');
    const confirmBtn = document.getElementById('confirmModalConfirmBtn');

    titleEl.textContent = title || 'Подтверждение';
    messageEl.textContent = message || 'Вы уверены?';
    confirmCallback = callback || null;

    // сбрасываем предыдущие обработчики (чтобы не накапливались)
    confirmBtn.onclick = null;
    if (confirmCallback) {
        confirmBtn.onclick = function() {
            confirmCallback();
            closeConfirmModal();
        };
    }

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
    confirmCallback = null;
}

// закрытие по клику на фон
window.onclick = function(event) {
    const modal = document.getElementById('confirmModal');
    if (event.target === modal) {
        closeConfirmModal();
    }
};

// закрытие по Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeConfirmModal();
    }
});