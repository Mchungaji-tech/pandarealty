/**
 * Panda Realty - Admin Dashboard JavaScript
 * Designed & Developed by TekTrend
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebarToggle();
    initTaskCheckboxes();
    initCalendarWidget();
});

function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }
}

function initTaskCheckboxes() {
    document.querySelectorAll('.task-checkbox').forEach(chk => {
        chk.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const isCompleted = this.checked;
            const parent = this.closest('.task-item');

            if (parent) {
                parent.classList.toggle('completed', isCompleted);
            }

            // Optional AJAX request to update task status
            const formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('status', isCompleted ? 'completed' : 'pending');

            fetch('tasks.php?action=quick_status', {
                method: 'POST',
                body: formData
            }).catch(e => console.error('Task update error', e));
        });
    });
}

function initCalendarWidget() {
    // Simple calendar day highlight and interaction
    const days = document.querySelectorAll('.cal-day');
    days.forEach(day => {
        day.addEventListener('click', () => {
            days.forEach(d => d.classList.remove('selected'));
            day.classList.add('selected');
        });
    });
}

function copyToClipboard(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    navigator.clipboard.writeText(el.value || el.textContent).then(() => {
        alert('Copied to clipboard!');
    });
}

/* =======================================================
   UNIVERSAL ADMIN MODAL CONTROLS & ESCAPE / BACKDROP DISMISS
   ======================================================= */
window.openModal = function(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
};

window.closeModal = function(idOrElem) {
    let modal = null;
    if (typeof idOrElem === 'string') {
        modal = document.getElementById(idOrElem);
    } else if (idOrElem instanceof HTMLElement) {
        modal = idOrElem.closest('.modal') || idOrElem;
    }

    if (modal) {
        // Stop any iframe videos
        modal.querySelectorAll('iframe').forEach((frame) => {
            const currentSrc = frame.getAttribute('src') || '';
            if (currentSrc) {
                frame.setAttribute('data-src', currentSrc);
                frame.setAttribute('src', '');
            }
        });
        modal.classList.remove('active');
        modal.style.display = 'none';
        
        if (!document.querySelector('.modal.active') && !document.querySelector('.modal[style*="display: flex"]')) {
            document.body.style.overflow = 'auto';
        }
    }
};

// Global click listener for backdrop closing and close buttons
document.addEventListener('click', (e) => {
    // Click outside modal content (on backdrop)
    if (e.target.classList.contains('modal')) {
        window.closeModal(e.target);
    }

    // Click on any close button
    const closeBtn = e.target.closest('.modal-close, [data-close-modal], .close-modal-btn');
    if (closeBtn) {
        const modal = closeBtn.closest('.modal') || document.querySelector('.modal.active');
        if (modal) {
            window.closeModal(modal);
        }
    }
});

// Global Escape Key Listener
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' || e.keyCode === 27) {
        document.querySelectorAll('.modal.active, .modal[style*="display: flex"], .modal[style*="display:flex"]').forEach((m) => {
            window.closeModal(m);
        });
    }
});

