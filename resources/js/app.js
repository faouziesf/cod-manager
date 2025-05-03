import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Fonction pour charger les notifications
window.loadNotifications = function() {
    const notificationsContainer = document.getElementById('notifications-list');
    if (notificationsContainer) {
        fetch('/notifications?ajax=1')
            .then(response => response.text())
            .then(html => {
                notificationsContainer.innerHTML = html;
            });
    }
}

// Charger les notifications au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Charger le nombre de notifications non lues
    fetch('/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            const countElement = document.getElementById('notification-count');
            if (countElement) {
                if (data.count > 0) {
                    countElement.textContent = data.count;
                    countElement.style.display = 'inline-flex';
                } else {
                    countElement.style.display = 'none';
                }
            }
        });
        
    // Écouter l'événement de clic sur le bouton de notification
    const notificationButton = document.querySelector('[x-data="{ showNotifications: false }"] button');
    if (notificationButton) {
        notificationButton.addEventListener('click', function() {
            window.loadNotifications();
        });
    }
});