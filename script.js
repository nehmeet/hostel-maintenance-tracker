// ============================================================
// script.js — Client-Side Logic (v2)
// Hostel Maintenance & Complaint Tracker
// Course: NCSM301 Web Development Laboratory
// Labs 6-8: Event Handling, Regex Validation, Async Fetch
// ============================================================

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
    var d     = new Date(dtStr);
    var day   = String(d.getDate()).padStart(2, '0');
    var month = String(d.getMonth() + 1).padStart(2, '0');
    var year  = d.getFullYear();
    var hours = String(d.getHours()).padStart(2, '0');
    var mins  = String(d.getMinutes()).padStart(2, '0');
    return day + '-' + month + '-' + year + ' ' + hours + ':' + mins;
}

function nextStatusLabel(currentStatus) {
    if (currentStatus === 'Submitted')   return 'Mark In Progress';
    if (currentStatus === 'In Progress') return 'Mark Resolved';
    return '';
}

function renderStars(rating) {
    var filled = '&#9733;';  // ★
    var empty  = '&#9734;';  // ☆
    var html   = '';
    for (var i = 1; i <= 5; i++) {
        html += (i <= rating) ? filled : empty;
    }
    return '<span class="rating-display">' + html + '</span>';
}

// Admission number regex: starts with 24JE followed by 4 digits
var ADMISSION_REGEX = /^24JE\d{4}$/i;

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

    // Client-side admission number validation on student login form
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

    // ---- Load Notices ----
    function loadNotices() {
        fetch('api_fetch.php?action=notices')
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (data) {
                var container = document.getElementById('notices-list');

                if (data.length === 0) {
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

    // ---- Load Student Complaints ----
    function loadStudentComplaints() {
        var msgEl = document.getElementById('history-message');
        msgEl.textContent = 'Loading your complaints...';

        fetch('api_fetch.php?action=student_complaints')
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (data) {
                var tbody = $('#student-complaints-table tbody');
                tbody.empty();

                if (data.length === 0) {
                    msgEl.textContent = 'You have not submitted any complaints yet.';
                    return;
                }

                msgEl.textContent = '';

                data.forEach(function (c) {
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
            if (c.rating !== null) {
                return renderStars(c.rating);
            }
            // Show rating dropdown + Rate button
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

        // In Progress or Cancelled — no action
        if (c.status === 'In Progress') return '<em>In progress</em>';
        if (c.status === 'Cancelled')   return '<em>Cancelled</em>';
        return '';
    }

    // ---- Form Validation & Async Submit ----
    complaintForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var msgEl = document.getElementById('form-message');
        msgEl.textContent = '';
        msgEl.className   = '';

        var admissionNo = document.getElementById('admission_no').value.trim();
        var roomNumber  = document.getElementById('room_number').value.trim();
        var category    = document.getElementById('category').value.trim();
        var description = document.getElementById('description').value.trim();

        // Validate admission number format
        if (!ADMISSION_REGEX.test(admissionNo)) {
            msgEl.textContent = 'Invalid admission number format (expected: 24JE followed by 4 digits).';
            msgEl.className   = 'msg-error';
            return;
        }

        // Validate room number
        var roomNum = Number(roomNumber);
        if (!Number.isInteger(roomNum) || roomNum <= 0) {
            msgEl.textContent = 'Room number must be a positive integer.';
            msgEl.className   = 'msg-error';
            return;
        }

        // Validate category
        if (category === '') {
            msgEl.textContent = 'Please select a category.';
            msgEl.className   = 'msg-error';
            return;
        }

        // Validate description
        if (description === '') {
            msgEl.textContent = 'Please enter a description.';
            msgEl.className   = 'msg-error';
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
                msgEl.textContent = 'Complaint #' + data.id + ' submitted successfully!';
                msgEl.className   = 'msg-success';
                document.getElementById('category').value    = '';
                document.getElementById('description').value = '';
                loadStudentComplaints(); // refresh table
            } else {
                msgEl.textContent = data.error || 'Submission failed.';
                msgEl.className   = 'msg-error';
            }
        })
        .catch(function (err) {
            msgEl.textContent = 'Request failed: ' + err.message;
            msgEl.className   = 'msg-error';
        })
        .finally(function () {
            submitBtn.disabled    = false;
            submitBtn.textContent = 'Submit Complaint';
        });
    });

    // ---- Delegated Click: Cancel & Rate ----
    $('#student-complaints-table').on('click', '.btn-cancel', function () {
        var btn = $(this);
        var id  = btn.data('id');

        if (!confirm('Are you sure you want to cancel complaint #' + id + '?')) return;

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
                // Update row in-place
                var row = $('#srow-' + id);
                row.find('td').eq(3).html('<span class="badge status-cancelled">Cancelled</span>');
                row.find('.action-cell').html('<em>Cancelled</em>');
            } else {
                alert(data.error || 'Cancel failed.');
                btn.prop('disabled', false).text('Cancel');
            }
        })
        .catch(function (err) {
            alert('Request failed: ' + err.message);
        })
        .finally(function () {
            if (btn.parent().length) btn.prop('disabled', false);
        });
    });

    $('#student-complaints-table').on('click', '.btn-rate', function () {
        var btn    = $(this);
        var id     = btn.data('id');
        var select = $('select.rating-select[data-id="' + id + '"]');
        var rating = Number(select.val());

        if (!rating || rating < 1 || rating > 5) {
            alert('Please select a rating between 1 and 5.');
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
                var row = $('#srow-' + id);
                row.find('.action-cell').html(renderStars(data.rating));
            } else {
                alert(data.error || 'Rating failed.');
                btn.prop('disabled', false).text('Rate');
            }
        })
        .catch(function (err) {
            alert('Request failed: ' + err.message);
        })
        .finally(function () {
            if (btn.parent().length) btn.prop('disabled', false);
        });
    });

    // ---- Initial Load ----
    loadNotices();
    loadStudentComplaints();
})();

// ============================================================
// ADMIN DASHBOARD — admin_dashboard.php
// ============================================================

(function initAdminDashboard() {
    var adminTable = document.getElementById('admin-complaints-table');
    if (!adminTable) return; // not on admin dashboard

    // ---- Load Stats ----
    function loadStats() {
        fetch('api_fetch.php?action=stats')
            .then(function (response) {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(function (data) {
                $('#stat-open').text(data.total_open);
                $('#stat-resolved-today').text(data.resolved_today);
                $('#stat-pending').text(data.pending);
            })
            .catch(function () {
                $('#stat-open, #stat-resolved-today, #stat-pending').text('?');
            });
    }

    // ---- Load Admin Complaints ----
    function loadAdminComplaints() {
        var block    = $('#admin-filter-block').val();
        var category = $('#admin-filter-category').val();
        var status   = $('#admin-filter-status').val();

        var url = 'api_fetch.php?action=admin_complaints' +
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
            .then(function (data) {
                var tbody = $('#admin-complaints-table tbody');
                tbody.empty();

                if (data.length === 0) {
                    msgEl.textContent = 'No complaints found for the selected filters.';
                    return;
                }

                msgEl.textContent = '';

                data.forEach(function (c) {
                    var actionCell = '';
                    var label = nextStatusLabel(c.status);

                    if (label !== '') {
                        actionCell = '<button class="btn btn-advance" data-id="' + c.id + '">' + label + '</button>';
                    } else if (c.status === 'Resolved') {
                        actionCell = '<em>Resolved</em>';
                    } else {
                        actionCell = '<em>—</em>';
                    }

                    // Rating column
                    var ratingCell = '';
                    if (c.rating !== null) {
                        ratingCell = renderStars(c.rating);
                    } else {
                        ratingCell = '<span style="color:#aaa;">—</span>';
                    }

                    var row = '<tr id="arow-' + c.id + '">' +
                        '<td>' + c.id + '</td>' +
                        '<td>' + c.student_name + '</td>' +
                        '<td>' + c.admission_no + '</td>' +
                        '<td>' + c.room_number + '</td>' +
                        '<td>' + c.block + '</td>' +
                        '<td><span class="badge ' + categoryClass(c.category) + '">' + c.category + '</span></td>' +
                        '<td>' + c.description + '</td>' +
                        '<td><span class="badge ' + statusClass(c.status) + '">' + c.status + '</span></td>' +
                        '<td>' + ratingCell + '</td>' +
                        '<td>' + formatDate(c.created_at) + '</td>' +
                        '<td class="action-cell">' + actionCell + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });
            })
            .catch(function (err) {
                msgEl.textContent = 'Error loading complaints: ' + err.message;
            });
    }

    // ---- Advance Status (delegated click) ----
    $('#admin-complaints-table').on('click', '.btn-advance', function () {
        var btn = $(this);
        var id  = btn.data('id');

        btn.prop('disabled', true).text('Updating...');

        fetch('api_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_status', id: id })
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Server error');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                var row      = $('#arow-' + id);
                var statusTd = row.find('td').eq(7);
                statusTd.html('<span class="badge ' + statusClass(data.status) + '">' + data.status + '</span>');

                var newLabel = nextStatusLabel(data.status);
                if (newLabel !== '') {
                    btn.prop('disabled', false).text(newLabel);
                } else {
                    btn.replaceWith('<em>Resolved</em>');
                }

                loadStats(); // refresh stat cards
            } else {
                alert(data.error || 'Update failed.');
                btn.prop('disabled', false);
            }
        })
        .catch(function (err) {
            alert('Request failed: ' + err.message);
        })
        .finally(function () {
            if (btn.parent().length) btn.prop('disabled', false);
        });
    });

    // ---- Post Notice ----
    var noticeForm = document.getElementById('notice-form');
    noticeForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var msgEl   = document.getElementById('notice-form-message');
        var title   = document.getElementById('notice-title').value.trim();
        var message = document.getElementById('notice-message').value.trim();

        msgEl.textContent = '';
        msgEl.className   = '';

        if (title === '' || message === '') {
            msgEl.textContent = 'Both title and message are required.';
            msgEl.className   = 'msg-error';
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
                msgEl.textContent = 'Notice posted successfully!';
                msgEl.className   = 'msg-success';
                document.getElementById('notice-title').value   = '';
                document.getElementById('notice-message').value = '';
            } else {
                msgEl.textContent = data.error || 'Failed to post notice.';
                msgEl.className   = 'msg-error';
            }
        })
        .catch(function (err) {
            msgEl.textContent = 'Request failed: ' + err.message;
            msgEl.className   = 'msg-error';
        })
        .finally(function () {
            postBtn.disabled    = false;
            postBtn.textContent = 'Post Notice';
        });
    });

    // ---- Bind Filters ----
    $('#admin-filter-block').on('change', loadAdminComplaints);
    $('#admin-filter-category').on('change', loadAdminComplaints);
    $('#admin-filter-status').on('change', loadAdminComplaints);
    $('#admin-btn-refresh').on('click', function () {
        loadStats();
        loadAdminComplaints();
    });

    // ---- Initial Load ----
    loadStats();
    loadAdminComplaints();
})();
