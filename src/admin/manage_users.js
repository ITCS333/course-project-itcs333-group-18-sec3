const apiUrl = 'index.php'; 


function createStudentRow(student) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${student.name}</td>
        <td>${student.id}</td>
        <td>${student.email}</td>
        <td>
            <button onclick="editStudent('${student.id}')">Edit</button>
            <button onclick="deleteStudent('${student.id}')">Delete</button>
        </td>
    `;
    return tr;
}


function renderTable(students) {
    const tbody = document.querySelector('#student-table tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    students.forEach(student => {
        tbody.appendChild(createStudentRow(student));
    });
}


async function loadStudents() {
    try {
        const tbody = document.querySelector('#student-table tbody');
        if (!tbody || typeof fetch === 'undefined') return;

        const res = await fetch(apiUrl);
        if (!res) return;

        const data = await res.json();
        if (data.success && data.data) {
            renderTable(data.data);
        }
    } catch (err) {
        console.error(err);
    }
}

// ==============================
// Add new student
// ==============================
const addForm = document.getElementById('add-student-form');
if (addForm) {
    addForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const name = document.getElementById('student-name')?.value;
        const email = document.getElementById('student-email')?.value;
        const password = document.getElementById('default-password')?.value;

        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email, password })
            });

            const data = await res.json();
            const msg = document.getElementById('add-msg');
            if (msg) msg.textContent = data.message;

            if (data.success) {
                loadStudents();
                e.target.reset();
            }
        } catch (err) {
            console.error(err);
        }
    });
}

// ==============================
// Edit student
// ==============================
async function editStudent(student_id) {
    try {
        const res = await fetch(`${apiUrl}?id=${student_id}`);
        const data = await res.json();
        if (!data.success) {
            alert(data.message);
            return;
        }

        const student = data.data;
        const idEl = document.getElementById('edit-student-id');
        const nameEl = document.getElementById('edit-name');
        const emailEl = document.getElementById('edit-email');
        const msgEl = document.getElementById('edit-msg');
        const popup = document.getElementById('edit-popup');

        if (idEl) idEl.value = student_id;
        if (nameEl) nameEl.value = student.name;
        if (emailEl) emailEl.value = student.email;
        if (msgEl) msgEl.textContent = '';
        if (popup) popup.style.display = 'flex';
    } catch (err) {
        console.error(err);
    }
}

// ==============================
// Hide edit popup
// ==============================
const cancelEditBtn = document.getElementById('cancel-edit');
if (cancelEditBtn) {
    cancelEditBtn.addEventListener('click', () => {
        const popup = document.getElementById('edit-popup');
        if (popup) popup.style.display = 'none';
    });
}

// ==============================
// Update student
// ==============================
const editForm = document.getElementById('edit-student-form');
if (editForm) {
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('edit-student-id')?.value;
        const name = document.getElementById('edit-name')?.value.trim();
        const email = document.getElementById('edit-email')?.value.trim();
        const password = document.getElementById('edit-password')?.value.trim();
        const msgEl = document.getElementById('edit-msg');

        if (!name || !email) {
            if (msgEl) {
                msgEl.textContent = "All fields are required!";
                msgEl.style.color = "red";
            }
            return;
        }

        try {
            const res = await fetch(apiUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, email })
            });

            const data = await res.json();
            if (msgEl) {
                msgEl.textContent = data.message;
                msgEl.style.color = data.success ? 'green' : 'red';
            }

            if (data.success && password) {
                const passRes = await fetch(`${apiUrl}?action=change_password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, current_password: '', new_password: password })
                });

                const passData = await passRes.json();
                if (msgEl) {
                    if (passData.success) {
                        msgEl.textContent += " & Password updated successfully!";
                        msgEl.style.color = "green";
                    } else {
                        msgEl.textContent += " & Password update failed: " + passData.message;
                        msgEl.style.color = "orange";
                    }
                }
            }

            if (data.success) {
                loadStudents();
                setTimeout(() => {
                    const popup = document.getElementById('edit-popup');
                    if (popup) popup.style.display = 'none';
                }, 1500);
            }

        } catch (err) {
            console.error(err);
            if (msgEl) {
                msgEl.textContent = "Server error!";
                msgEl.style.color = "red";
            }
        }
    });
}

// ==============================
// Delete student
// ==============================
async function deleteStudent(student_id) {
    if (!confirm("Are you sure you want to delete this student?")) return;
    try {
        const res = await fetch(`${apiUrl}?id=${student_id}`, { method: 'DELETE' });
        const data = await res.json();
        alert(data.message);
        if (data.success) loadStudents();
    } catch (err) {
        console.error(err);
    }
}


const passwordForm = document.getElementById('password-form');
if (passwordForm) {
    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const newPass = document.getElementById('new-password')?.value.trim();
        const confirmPass = document.getElementById('confirm-password')?.value.trim();
        const teacher = JSON.parse(localStorage.getItem('teacher'));
        const msgEl = document.getElementById('password-msg');

        if (newPass !== confirmPass) {
            if (msgEl) {
                msgEl.textContent = "Passwords do not match!";
                msgEl.style.color = "red";
            }
            return;
        }

        if (newPass.length < 8) {
            if (msgEl) {
                msgEl.textContent = "Password must be at least 8 characters!";
                msgEl.style.color = "red";
            }
            return;
        }

        try {
            const res = await fetch('index.php?action=change_teacher_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ teacher_id: teacher.id, new_password: newPass })
            });

            const data = await res.json();
            if (msgEl) {
                msgEl.textContent = data.message;
                msgEl.style.color = data.success ? 'green' : 'red';
            }
        } catch (err) {
            console.error(err);
            if (msgEl) {
                msgEl.textContent = "Server error!";
                msgEl.style.color = "red";
            }
        }
    });
}


if (typeof window !== 'undefined' && typeof fetch !== 'undefined') {
    loadStudents();
}
