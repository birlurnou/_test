document.addEventListener('DOMContentLoaded', function() {
    
    // Переключение видимости списка гостей
    document.querySelectorAll('.room-card-header').forEach(header => {
        header.addEventListener('click', function(e) {
            const roomCard = this.closest('.room-card');
            const guestsList = roomCard.querySelector('.guests-list');
            const isExpanded = guestsList.style.display === 'flex';
            
            guestsList.style.display = isExpanded ? 'none' : 'flex';
            roomCard.classList.toggle('expanded', !isExpanded);
        });
    });
    
    // Обработка отметки гостя
    document.querySelectorAll('.guest-card').forEach(guestCard => {
        guestCard.addEventListener('click', async function(e) {
            e.stopPropagation();
            
            const guestId = this.getAttribute('data-guest-id');
            const roomNumber = this.getAttribute('data-room');
            const currentAttended = this.getAttribute('data-current-attended') === '1';
            const button = this.querySelector('.check-btn');
            
            if (!guestId || !roomNumber) return;
            
            this.style.transform = 'scale(0.99)';
            setTimeout(() => { this.style.transform = ''; }, 120);
            
            button.disabled = true;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="btn-text">...</span>';
            
            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle_attended',
                        guest_id: parseInt(guestId),
                        room_number: roomNumber,
                        current_attended: currentAttended
                    })
                });
                
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const data = await response.json();
                
                if (data.success) {
                    updateGuestCard(this, data.guest);
                    updateRoomCounter(roomNumber, data.attended_count, data.total);
                    this.setAttribute('data-current-attended', data.guest.attended ? '1' : '0');
                } else {
                    button.innerHTML = `<span class="btn-text">Error</span>`;
                    setTimeout(() => { button.innerHTML = originalText; }, 2000);
                }
            } catch (error) {
                console.error(error);
                button.innerHTML = '<span class="btn-text">Error!</span>';
                setTimeout(() => { button.innerHTML = originalText; }, 2000);
            } finally {
                setTimeout(() => { button.disabled = false; }, 1000);
            }
        });
    });
    
    // Поиск по комнатам (работает с фиксированным полем)
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            document.querySelectorAll('.room-card').forEach(card => {
                const roomNumber = card.getAttribute('data-room');
                const matches = roomNumber && roomNumber.toLowerCase().includes(searchTerm);
                card.classList.toggle('hidden-card', !matches);
            });
        });
    }
    
    function updateGuestCard(card, guest) {
        const isAttended = Boolean(guest.attended);
        if (isAttended) card.classList.add('attended');
        else card.classList.remove('attended');
        
        const avatarDiv = card.querySelector('.guest-avatar');
        if (avatarDiv) {
            let icon = '';
            if (guest.guest_type === 'adult') {
                if (guest.gender === 'male') icon = '🧑🏻';
                else if (guest.gender === 'female') icon = '👩🏻';
                else icon = '👤';
            } else {
                if (guest.gender === 'male') icon = '👦🏻';
                else if (guest.gender === 'female') icon = '👧🏻';
                else icon = '👶🏻';
            }
            avatarDiv.innerHTML = icon;
        }
        
        const button = card.querySelector('.check-btn');
        if (button) {
            if (isAttended) {
                button.classList.add('checked');
                button.innerHTML = '<span class="btn-text">Checked In</span>';
            } else {
                button.classList.remove('checked');
                button.innerHTML = '<span class="btn-text">Check In</span>';
            }
        }
        
        const timeElement = card.querySelector('.guest-attended-time');
        const guestInfo = card.querySelector('.guest-info');
        if (isAttended && guest.attended_date) {
            if (!timeElement) {
                const newTime = document.createElement('div');
                newTime.className = 'guest-attended-time';
                const date = new Date(guest.attended_date);
                const timeStr = date.toLocaleTimeString('ru-RU', {hour:'2-digit', minute:'2-digit', second:'2-digit'});
                newTime.innerHTML = `Checked in: ${timeStr}`;
                guestInfo.appendChild(newTime);
            } else {
                const date = new Date(guest.attended_date);
                timeElement.innerHTML = `Checked in: ${date.toLocaleTimeString('ru-RU', {hour:'2-digit', minute:'2-digit', second:'2-digit'})}`;
            }
        } else if (!isAttended && timeElement) {
            timeElement.remove();
        }
        
        card.style.animation = 'none';
        card.offsetHeight;
        card.style.animation = 'slideDown 0.2s ease';
    }
    
    function updateRoomCounter(roomNumber, attendedCount, totalCount) {
        const roomCard = document.querySelector(`.room-card[data-room="${roomNumber}"]`);
        if (!roomCard) return;
        
        const attendedSpan = roomCard.querySelector('.attended-count');
        if (attendedSpan) {
            attendedSpan.textContent = attendedCount;
            attendedSpan.style.transform = 'scale(1.1)';
            setTimeout(() => { attendedSpan.style.transform = ''; }, 200);
        }
        
        const attendanceStat = roomCard.querySelector('.attendance-stat');
        if (attendanceStat) {
            if (attendedCount === totalCount && totalCount > 0) {
                attendanceStat.classList.add('completed');
            } else {
                attendanceStat.classList.remove('completed');
            }
        }
        
        if (attendedCount === totalCount && totalCount > 0) {
            roomCard.style.transition = 'box-shadow 0.2s';
            roomCard.style.boxShadow = '0 0 0 2px #16a34a, 0 4px 12px rgba(0,0,0,0.05)';
            setTimeout(() => { roomCard.style.boxShadow = ''; }, 600);
        }
    }
});