// ============================================================
// script.js — Client-Side Logic (v3)
// Hostel Maintenance & Complaint Tracker
// Course: NCSM301 Web Development Laboratory
// Labs 6-8: Event Handling, Regex Validation, Async Fetch, DOM
// ============================================================

// Admission number regex: starts with 24JE followed by 4 digits
var ADMISSION_REGEX = /^24JE\d{4}$/i;

// Staff options for assignment
var STAFF_OPTIONS = [
    'Rajesh - Plumber',
    'Suresh - Electrician',
    'Anil - Network Admin',
    'Mukesh - Housekeeping',
    'Ramesh - Mess Supervisor'
];

// ---- Global Toast Notification System (Replaces alert) ----
function showToast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;

    container.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(function () {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

// ---- Utility Helpers ----

function statusClass(status) {
    if (status === 'Submitted')   return 'status-submitted';
    if (status === 'In Progress') return 'status-inprogress';
    if (status === 'Resolved')    return 'status-resolved';
    if (status === 'Cancelled')   return 'status-cancelled';
    return '';
}

function categoryClass(cat) {
    if (cat === 'Electrical')      return 'cat-electrical';
    if (cat === 'Plumbing')        return 'cat-plumbing';
    if (cat === 'Network')         return 'cat-network';
    if (cat === 'Housekeeping')    return 'cat-housekeeping';
    if (cat === 'Mess & Hygiene')  return 'cat-messhygiene';
    return '';
}

function formatDate(dtStr) {
    if (!dtStr) return '—';
    var d     = new Date(dtStr);
    var day   = String(d.getDate()).padStart(2, '0');
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var year  = d.getFullYear();
    var hours = String(d.getHours()).padStart(2, '0');
    var mins  = String(d.getMinutes()).padStart(2, '0');
    return day + '-' + month + '-' + year + ' ' + hours + ':' + mins;
}

function renderStars(rating) {
    if (!rating) return '<span style="color:#aaa;">—</span>';
    var filled = '&#9733;';  // ★
    var empty  = '&#9734;';  // ☆
    var html   = '';
    for (var i = 1; i <= 5; i++) {
        html += (i <= rating) ? filled : empty;
    }
    return '<span class="rating-display">' + html + '</span>';
}

// ============================================================
// LOGIN PAGE — index.php
// ============================================================

(function initLoginPage() {
    var tabStudent = document.getElementById('tab-student');
    var tabAdmin   = document.getElementById('tab-admin');

    if (!tabStudent || !tabAdmin) return; // not on login page

    var panelStudent = document.getElementById('form-student');
    var panelAdmin   = document.getElementById('form-admin');

    tabStudent.addEventListener('click', function () {
        tabStudent.classList.add('tab-active');
        tabAdmin.classList.remove('tab-active');
        panelStudent.classList.remove('hidden');
        panelAdmin.classList.add('hidden');
    });

    tabAdmin.addEventListener('click', function () {
        tabAdmin.classList.add('tab-active');
        tabStudent.classList.remove('tab-active');
        panelAdmin.classList.remove('hidden');
        panelStudent.classList.add('hidden');
    });

    // Client-side admission number regex validation
    var studentLoginForm = document.getElementById('student-login-form');
    if (studentLoginForm) {
        studentLoginForm.addEventListener('submit', function (event) {
            var msgEl       = document.getElementById('login-form-message');
            var admissionNo = document.getElementById('admission_no').value.trim();

            msgEl.textContent = '';
            msgEl.className   = '';

            if (admissionNo === '') {
                event.preventDefault();
                msgEl.textContent = 'Admission number is required.';
                msgEl.className   = 'msg-error';
                return;
            }

            if (!ADMISSION_REGEX.test(admissionNo)) {
                event.preventDefault();
                msgEl.textContent = 'Admission number must match format: 24JE followed by 4 digits (e.g. 24JE0001).';
                msgEl.className   = 'msg-error';
                return;
            }
        });
    }
})();

// ============================================================
// STUDENT DASHBOARD — student_dashboard.php
// ============================================================

(function initStudentDashboard() {
    var complaintForm = document.getElementById('complaint-form');
    if (!complaintForm) return; // not on student dashboard

    var currentStudentPage = 1;

    // ---- Load Notices ----
    function loadNotices() {
        fetch('api_fetch.php?action=notices')
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (data) {
                var container = document.getElementById('notices-list');

                if (!Array.isArray(data) || data.length === 0) {
                    container.innerHTML = '<p class="no-notices">No notices at this time.</p>';
                    return;
                }

                var html = '';
                data.forEach(function (n) {
                    html += '<div class="notice-card">' +
                        '<h3>' + n.title + '</h3>' +
                        '<p>' + n.message + '</p>' +
                        '<p class="notice-meta">Posted by ' + n.posted_by + ' on ' + formatDate(n.created_at) + '</p>' +
                        '</div>';
                });
                container.innerHTML = html;
            })
            .catch(function () {
                document.getElementById('notices-list').innerHTML =
                    '<p class="msg-error">Failed to load notices.</p>';
            });
    }

    // ---- Load Student Complaints (Paginated) ----
    function loadStudentComplaints(page) {
        page = page || currentStudentPage;
        var msgEl = document.getElementById('history-message');
        msgEl.textContent = 'Loading your complaints...';

        fetch('api_fetch.php?action=student_complaints&page=' + page)
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (res) {
                var tbody = $('#student-complaints-table tbody');
                tbody.empty();

                var list = res.complaints || [];
                currentStudentPage = res.page || 1;
                var totalPages = res.total_pages || 1;

                if (list.length === 0) {
                    msgEl.textContent = 'You have not submitted any complaints yet.';
                    $('#student-pagination').hide();
                    return;
                }

                msgEl.textContent = '';

                list.forEach(function (c) {
                    var actionCell = buildStudentAction(c);

                    var row = '<tr id="srow-' + c.id + '">' +
                        '<td>' + c.id + '</td>' +
                        '<td><span class="badge ' + categoryClass(c.category) + '">' + c.category + '</span></td>' +
                        '<td>' + c.description + '</td>' +
                        '<td><span class="badge ' + statusClass(c.status) + '">' + c.status + '</span></td>' +
                        '<td>' + formatDate(c.created_at) + '</td>' +
                        '<td class="action-cell">' + actionCell + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });

                // Update pagination controls
                $('#student-pagination').show();
                $('#stu-page-info').text('Page ' + currentStudentPage + ' of ' + totalPages);
                $('#stu-btn-prev').prop('disabled', currentStudentPage <= 1);
                $('#stu-btn-next').prop('disabled', currentStudentPage >= totalPages);
            })
            .catch(function (err) {
                msgEl.textContent = 'Error loading complaints: ' + err.message;
            });
    }

    function buildStudentAction(c) {
        // Cancel button: only if status is 'Submitted'
        if (c.status === 'Submitted') {
            return '<button class="btn btn-cancel" data-id="' + c.id + '" data-action="cancel">Cancel</button>';
        }

        // Rating: if status is 'Resolved'
        if (c.status === 'Resolved') {
            if (c.rating !== null && c.rating > 0) {
                return renderStars(c.rating);
            }
            return '<select class="rating-select" data-id="' + c.id + '">' +
                '<option value="">Rate</option>' +
                '<option value="1">1 &#9733;</option>' +
                '<option value="2">2 &#9733;</option>' +
                '<option value="3">3 &#9733;</option>' +
                '<option value="4">4 &#9733;</option>' +
                '<option value="5">5 &#9733;</option>' +
                '</select>' +
                '<button class="btn btn-rate" data-id="' + c.id + '" data-action="rate">Rate</button>';
        }

        if (c.status === 'In Progress') return '<em>In progress</em>';
        if (c.status === 'Cancelled')   return '<em>Cancelled</em>';
        return '—';
    }

    // ---- Pagination Button Clicks ----
    $('#stu-btn-prev').on('click', function () {
        if (currentStudentPage > 1) {
            loadStudentComplaints(currentStudentPage - 1);
        }
    });

    $('#stu-btn-next').on('click', function () {
        loadStudentComplaints(currentStudentPage + 1);
    });

    // ---- Form Validation & Async Submit ----
    complaintForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var msgEl = document.getElementById('form-message');
        msgEl.textContent = '';
        msgEl.className   = '';

        var admissionNo = document.getElementById('admission_no').value.trim();
        var category    = document.getElementById('category').value.trim();
        var description = document.getElementById('description').value.trim();

        // Regex check
        if (!ADMISSION_REGEX.test(admissionNo)) {
            showToast('Invalid admission number format (expected: 24JE####).', 'error');
            return;
        }

        if (category === '') {
            showToast('Please select a category.', 'error');
            return;
        }

        if (description === '') {
            showToast('Please describe the issue.', 'error');
            return;
        }

        var submitBtn = document.getElementById('btn-submit-complaint');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch('api_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action:      'submit_complaint',
                category:    category,
                description: description
            })
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Server error');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                showToast(data.message || 'Complaint submitted successfully!', 'success');
                document.getElementById('category').value    = '';
                document.getElementById('description').value = '';
                loadStudentComplaints(1); // refresh table to page 1
            } else {
                showToast(data.error || 'Submission failed.', 'error');
            }
        })
        .catch(function (err) {
            showToast('Request failed: ' + err.message, 'error');
        })
        .finally(function () {
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Submit Complaint';
        });
    });

    // ---- Delegated Click: Cancel Ticket ----
    $('#student-complaints-table').on('click', '.btn-cancel', function () {
        var btn = $(this);
        var id  = btn.data('id');

        btn.prop('disabled', true).text('Cancelling...');

        fetch('api_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'cancel_complaint', id: id })
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Server error');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                showToast(data.message || 'Complaint cancelled.', 'success');
                var row = $('#srow-' + id);
                row.find('td').eq(3).html('<span class="badge status-cancelled">Cancelled</span>');
                row.find('.action-cell').html('<em>Cancelled</em>');
            } else {
                showToast(data.error || 'Cancel failed.', 'error');
                btn.prop('disabled', false).text('Cancel');
            }
        })
        .catch(function (err) {
            showToast('Request failed: ' + err.message, 'error');
            btn.prop('disabled', false).text('Cancel');
        });
    });

    // ---- Delegated Click: Rate Resolved Ticket ----
    $('#student-complaints-table').on('click', '.btn-rate', function () {
        var btn    = $(this);
        var id     = btn.data('id');
        var select = $('select.rating-select[data-id="' + id + '"]');
        var rating = Number(select.val());

        if (!rating || rating < 1 || rating > 5) {
            showToast('Please select a rating between 1 and 5.', 'error');
            return;
        }

        btn.prop('disabled', true).text('Saving...');

        fetch('api_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'submit_rating', id: id, rating: rating })
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Server error');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                showToast(data.message || 'Rating submitted!', 'success');
                var row = $('#srow-' + id);
                row.find('.action-cell').html(renderStars(data.rating));
            } else {
                showToast(data.error || 'Rating failed.', 'error');
                btn.prop('disabled', false).text('Rate');
            }
        })
        .catch(function (err) {
            showToast('Request failed: ' + err.message, 'error');
            btn.prop('disabled', false).text('Rate');
        });
    });

    // ---- Initial Load ----
    loadNotices();
    loadStudentComplaints(1);
})();

// ============================================================
// ADMIN DASHBOARD — admin_dashboard.php
// ============================================================

(function initAdminDashboard() {
    var adminTable = document.getElementById('admin-complaints-table');
    if (!adminTable) return; // not on admin dashboard

    var currentAdminPage = 1;

    // ---- Load Stats ----
    function loadStats() {
        fetch('api_fetch.php?action=stats')
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (data) {
                $('#stat-open').text(data.total_open !== undefined ? data.total_open : '0');
                $('#stat-resolved-today').text(data.resolved_today !== undefined ? data.resolved_today : '0');
                $('#stat-pending').text(data.pending_48h !== undefined ? data.pending_48h : '0');
            })
            .catch(function () {
                $('#stat-open, #stat-resolved-today, #stat-pending').text('?');
            });
    }

    // ---- Helper to Build Staff Dropdown HTML ----
    function buildStaffSelect(id) {
        var html = '<select class="staff-select" data-id="' + id + '">';
        html += '<option value="">-- Assign Staff --</option>';
        STAFF_OPTIONS.forEach(function (staff) {
            html += '<option value="' + staff + '">' + staff + '</option>';
        });
        html += '</select>';
        return html;
    }

    // ---- Load Admin Complaints (Paginated & Filtered) ----
    function loadAdminComplaints(page) {
        page = page || currentAdminPage;
        var block    = $('#admin-filter-block').val();
        var category = $('#admin-filter-category').val();
        var status   = $('#admin-filter-status').val();

        var url = 'api_fetch.php?action=admin_complaints' +
            '&page='     + page +
            '&block='    + encodeURIComponent(block) +
            '&category=' + encodeURIComponent(category) +
            '&status='   + encodeURIComponent(status);

        var msgEl = document.getElementById('admin-table-message');
        msgEl.textContent = 'Loading complaints...';

        fetch(url)
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (res) {
                var tbody = $('#admin-complaints-table tbody');
                tbody.empty();

                var list = res.complaints || [];
                currentAdminPage = res.page || 1;
                var totalPages   = res.total_pages || 1;

                if (list.length === 0) {
                    msgEl.textContent = 'No complaints found for the selected filters.';
                    $('#admin-pagination').hide();
                    return;
                }

                msgEl.textContent = '';

                list.forEach(function (c) {
                    var actionCell = '';

                    if (c.status === 'Submitted') {
                        // Dropdown for staff assignment + In Progress button
                        actionCell = buildStaffSelect(c.id) +
                            '<button class="btn btn-advance" data-id="' + c.id + '" data-action="in_progress">Assign &amp; In Progress</button>';
                    } else if (c.status === 'In Progress') {
                        // Mark Resolved button
                        actionCell = '<button class="btn btn-advance" data-id="' + c.id + '" data-action="resolved">Mark Resolved</button>';
                    } else if (c.status === 'Resolved') {
                        actionCell = '<em>Resolved</em>';
                    } else if (c.status === 'Cancelled') {
                        actionCell = '<em>Cancelled</em>';
                    } else {
                        actionCell = '—';
                    }

                    var assignedStaffCell = c.assigned_staff ? '<strong>' + c.assigned_staff + '</strong>' : '<span style="color:#aaa;">Unassigned</span>';
                    var ratingCell        = renderStars(c.rating);

                    var row = '<tr id="arow-' + c.id + '">' +
                        '<td>' + c.id + '</td>' +
                        '<td>' + c.student_name + '</td>' +
                        '<td>' + c.admission_no + '</td>' +
                        '<td>' + c.room_number + '</td>' +
                        '<td>' + c.block + '</td>' +
                        '<td><span class="badge ' + categoryClass(c.category) + '">' + c.category + '</span></td>' +
                        '<td>' + c.description + '</td>' +
                        '<td><span class="badge ' + statusClass(c.status) + '">' + c.status + '</span></td>' +
                        '<td class="staff-cell">' + assignedStaffCell + '</td>' +
                        '<td>' + ratingCell + '</td>' +
                        '<td>' + formatDate(c.created_at) + '</td>' +
                        '<td class="action-cell">' + actionCell + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });

                // Update pagination controls
                $('#admin-pagination').show();
                $('#adm-page-info').text('Page ' + currentAdminPage + ' of ' + totalPages);
                $('#adm-btn-prev').prop('disabled', currentAdminPage <= 1);
                $('#adm-btn-next').prop('disabled', currentAdminPage >= totalPages);
            })
            .catch(function (err) {
                msgEl.textContent = 'Error loading complaints: ' + err.message;
            });
    }

    // ---- Pagination Button Clicks ----
    $('#adm-btn-prev').on('click', function () {
        if (currentAdminPage > 1) {
            loadAdminComplaints(currentAdminPage - 1);
        }
    });

    $('#adm-btn-next').on('click', function () {
        loadAdminComplaints(currentAdminPage + 1);
    });

    // ---- Advance Status & Staff Assignment (delegated click) ----
    $('#admin-complaints-table').on('click', '.btn-advance', function () {
        var btn    = $(this);
        var id     = btn.data('id');
        var action = btn.data('action');

        var payload = {
            action: 'update_status',
            id:     id
        };

        if (action === 'in_progress') {
            var staffSelect = $('select.staff-select[data-id="' + id + '"]');
            var selectedStaff = staffSelect.val() ? staffSelect.val().trim() : '';

            if (selectedStaff === '') {
                showToast('Please select a staff member to assign from the dropdown.', 'error');
                staffSelect.focus();
                return;
            }
            payload.assigned_staff = selectedStaff;
        }

        btn.prop('disabled', true).text('Updating...');

        fetch('api_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Server error');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                showToast(data.message || 'Status updated successfully!', 'success');

                var row      = $('#arow-' + id);
                var statusTd = row.find('td').eq(7);
                statusTd.html('<span class="badge ' + statusClass(data.status) + '">' + data.status + '</span>');

                if (data.status === 'In Progress') {
                    // Update assigned staff column
                    row.find('.staff-cell').html('<strong>' + data.assigned_staff + '</strong>');
                    // Change action button to Mark Resolved
                    row.find('.action-cell').html(
                        '<button class="btn btn-advance" data-id="' + id + '" data-action="resolved">Mark Resolved</button>'
                    );
                } else if (data.status === 'Resolved') {
                    row.find('.action-cell').html('<em>Resolved</em>');
                }

                loadStats(); // refresh dashboard stats
            } else {
                showToast(data.error || 'Update failed.', 'error');
                btn.prop('disabled', false);
                if (action === 'in_progress') {
                    btn.html('Assign &amp; In Progress');
                } else {
                    btn.text('Mark Resolved');
                }
            }
        })
        .catch(function (err) {
            showToast('Request failed: ' + err.message, 'error');
            btn.prop('disabled', false);
        });
    });

    // ---- Post Notice Form Submission ----
    var noticeForm = document.getElementById('notice-form');
    if (noticeForm) {
        noticeForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var title   = document.getElementById('notice-title').value.trim();
            var message = document.getElementById('notice-message').value.trim();

            if (title === '' || message === '') {
                showToast('Both notice title and message are required.', 'error');
                return;
            }

            var postBtn = document.getElementById('btn-post-notice');
            postBtn.disabled = true;
            postBtn.textContent = 'Posting...';

            fetch('api_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action:  'post_notice',
                    title:   title,
                    message: message
                })
            })
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (data) {
                if (data.success) {
                    showToast(data.message || 'Notice posted successfully!', 'success');
                    document.getElementById('notice-title').value   = '';
                    document.getElementById('notice-message').value = '';
                } else {
                    showToast(data.error || 'Failed to post notice.', 'error');
                }
            })
            .catch(function (err) {
                showToast('Request failed: ' + err.message, 'error');
            })
            .finally(function () {
                postBtn.disabled    = false;
                postBtn.textContent = 'Post Notice';
            });
        });
    }

    // ---- Filter Event Listeners (Resets to page 1) ----
    $('#admin-filter-block').on('change', function () {
        currentAdminPage = 1;
        loadAdminComplaints(1);
    });
    $('#admin-filter-category').on('change', function () {
        currentAdminPage = 1;
        loadAdminComplaints(1);
    });
    $('#admin-filter-status').on('change', function () {
        currentAdminPage = 1;
        loadAdminComplaints(1);
    });
    $('#admin-btn-refresh').on('click', function () {
        loadStats();
        loadAdminComplaints(currentAdminPage);
    });

    // ---- Initial Load ----
    loadStats();
    loadAdminComplaints(1);
})();
