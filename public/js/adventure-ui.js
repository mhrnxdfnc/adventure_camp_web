/**
 * Adventure Camp Rental — Core UI Interactivity
 * Handles mobile sidebar offcanvas, alerts, and modal helpers.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('acSidebarToggle');
    const sidebar = document.getElementById('acSidebar');
    const backdrop = document.getElementById('acSidebarBackdrop');

    if (sidebarToggle && sidebar && backdrop) {
        function openSidebar() {
            sidebar.classList.add('show');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }

        sidebarToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        backdrop.addEventListener('click', closeSidebar);

        // Close sidebar on escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                closeSidebar();
            }
        });
    }

    // 2. Auto-dismiss alerts after 5 seconds
    const autoAlerts = document.querySelectorAll('.ac-alert-autoclose');
    autoAlerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });
});
