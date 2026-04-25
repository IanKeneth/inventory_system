 const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    const isMobile = () => window.innerWidth <= 768;

    toggleBtn.addEventListener('click', function () {
        if (isMobile()) {
            // Mobile: slide in/out as drawer
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        } else {
            // Desktop: collapse/expand
            sidebar.classList.toggle('collapsed');
        }
    });

    // Close sidebar when overlay is clicked (mobile)
    overlay.addEventListener('click', function () {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    });

    // On resize: clean up classes to avoid stuck states
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        } else {
            sidebar.classList.remove('collapsed');
        }
    });