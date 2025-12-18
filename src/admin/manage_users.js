
const apiUrl = 'index.php'; 

// Fetch and display students
async function loadStudents() {
    try {
        const res = await fetch(apiUrl);
        const data = await res.json();
        const tbody = document.querySelector('#student-table tbody');
        tbody.innerHTML = '';

        if(data.success && data.data) {
            data.data.forEach(student => {
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
                tbody.appendChild(tr);
            });
        }
    } catch(err) {
        console.error(err);
    }
}

// Add new student
document.getElementById('add-student-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const name = document.getElementById('student-name').value;
    const email = document.getElementById('student-email').value;
    const password = document.getElementById('default-password').value;

    try {
        const res = await fetch(`${apiUrl}`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name, email, password})
        });
        const data = await res.json();
        document.getElementById('add-msg').textContent = data.message;
        if(data.success) {
            loadStudents();
            e.target.reset();
        }
    } catch(err) {
        console.error(err);
    }
});


async function editStudent(student_id) {
    try {
        const res = await fetch(`${apiUrl}?id=${student_id}`);
        const data = await res.json();
        if (!data.success) {
            alert(data.message);
            return;
        }

        const student = data.data;
        document.getElementById('edit-student-id').value = student_id;
        document.getElementById('edit-name').value = student.name;
        document.getElementById('edit-email').value = student.email;
        document.getElementById('edit-msg').textContent = '';

        document.getElementById('edit-popup').style.display = 'flex';
    } catch(err) {
        console.error(err);
    }
}

// Hide popup
document.getElementById('cancel-edit').addEventListener('click', () => {
    document.getElementById('edit-popup').style.display = 'none';
});

document.getElementById('edit-student-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('edit-student-id').value;
    const name = document.getElementById('edit-name').value.trim();
    const email = document.getElementById('edit-email').value.trim();
    const password = document.getElementById('edit-password')?.value.trim(); // optional password field
    const msgEl = document.getElementById('edit-msg');

    if (!name || !email) {
        msgEl.textContent = "All fields are required!";
        msgEl.style.color = "red";
        return;
    }

    try {
        const res = await fetch(apiUrl, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, name, email})
        });

        const data = await res.json();
        msgEl.textContent = data.message;
        msgEl.style.color = data.success ? 'green' : 'red';

        if (data.success && password) {
            const passRes = await fetch(`${apiUrl}?action=change_password`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id, current_password: '', new_password: password })
            });
            const passData = await passRes.json();
            if(passData.success) {
                msgEl.textContent += " & Password updated successfully!";
                msgEl.style.color = "green";
            } else {
                msgEl.textContent += " & Password update failed: " + passData.message;
                msgEl.style.color = "orange";
            }
        }

        if(data.success) {
            loadStudents(); 
            setTimeout(() => {
                document.getElementById('edit-popup').style.display = 'none';
            }, 1500);
        }

    } catch(err) {
        console.error(err);
        msgEl.textContent = "Server error!";
        msgEl.style.color = "red";
    }
});



// Delete student
async function deleteStudent(student_id) {
    if(!confirm("Are you sure you want to delete this student?")) return;
    try {
        const res = await fetch(`${apiUrl}?id=${student_id}`, { method: 'DELETE' });
        const data = await res.json();
        alert(data.message);
        if(data.success) loadStudents();
    } catch(err) {
        console.error(err);
    }
}
// Change password (Teacher)
document.getElementById('password-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const newPass = document.getElementById('new-password').value.trim();
    const confirmPass = document.getElementById('confirm-password').value.trim();
    const teacher = JSON.parse(localStorage.getItem('teacher'));
    const msgEl = document.getElementById('password-msg');

    if(newPass !== confirmPass) {
        msgEl.textContent = "Passwords do not match!";
        msgEl.style.color = "red";
        return;
    }

    if(newPass.length < 8){
        msgEl.textContent = "Password must be at least 8 characters!";
        msgEl.style.color = "red";
        return;
    }

    try {
        const res = await fetch('index.php?action=change_teacher_password', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ teacher_id: teacher.id, new_password: newPass })
        });

        const data = await res.json();
        msgEl.textContent = data.message;
        msgEl.style.color = data.success ? 'green' : 'red';
    } catch(err){
        console.error(err);
        msgEl.textContent = "Server error!";
        msgEl.style.color = "red";
    }
});



// Load students on page load
loadStudents();