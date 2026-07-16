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