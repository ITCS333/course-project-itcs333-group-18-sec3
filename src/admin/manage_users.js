/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
let students = [];

// --- Element Selections ---
const studentTableBody = document.querySelector("#student-table tbody"); // TODO

const addStudentForm = document.getElementById("add-student-form"); // TODO

const changePasswordForm = document.getElementById("password-form"); // TODO

const searchInput = document.getElementById("search-input"); // TODO

const tableHeaders = document.querySelectorAll("thead th"); // TODO

// --- Functions ---

function createStudentRow(student) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${student.name}</td>
    <td>${student.id}</td>
    <td>${student.email}</td>
    <td>
        <button class="edit-btn" data-id="${student.id}">Edit</button>
        <button class="delete-btn" data-id="${student.id}">Delete</button>
    </td>
  `;

  return tr;
}

function renderTable(studentArray) {
  studentTableBody.innerHTML = "";

  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}

function handleChangePassword(event) {
  event.preventDefault();

  const current = document.getElementById("current-password").value;
  const newPass = document.getElementById("new-password").value;
  const confirmPass = document.getElementById("confirm-password").value;

  if (newPass.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  if (newPass !== confirmPass) {
    alert("Passwords do not match.");
    return;
  }

  alert("Password updated successfully!");

  document.getElementById("current-password").value = "";
  document.getElementById("new-password").value = "";
  document.getElementById("confirm-password").value = "";
}

function handleAddStudent(event) {
  event.preventDefault();

  const name = document.getElementById("student-name").value;
  const id = document.getElementById("student-id").value;
  const email = document.getElementById("student-email").value;
  const defaultPass = document.getElementById("default-password");

  if (!name || !id || !email) {
    alert("Please fill out all required fields.");
    return;
  }

  const exists = students.some(s => s.id === id);
  if (exists) {
    alert("A student with this ID already exists.");
    return;
  }

  const newStudent = { name, id, email };
  students.push(newStudent);

  renderTable(students);

  document.getElementById("student-name").value = "";
  document.getElementById("student-id").value = "";
  document.getElementById("student-email").value = "";
  if (defaultPass) defaultPass.value = "";
}

function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.getAttribute("data-id");
    students = students.filter(s => s.id !== id);
    renderTable(students);
  }

  if (target.classList.contains("edit-btn")) {
    alert("Edit feature not implemented yet.");
  }
}

function handleSearch(event) {
  const term = event.target.value.toLowerCase();

  if (term === "") {
    renderTable(students);
    return;
  }

  const filtered = students.filter(s =>
    s.name.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

function handleSort(event) {
  const th = event.currentTarget;
  const index = th.cellIndex;

  const map = {
    0: "name",
    1: "id",
    2: "email"
  };

  const key = map[index];
  if (!key) return;

  let dir = th.getAttribute("data-sort-dir") || "asc";
  const newDir = dir === "asc" ? "desc" : "asc";
  th.setAttribute("data-sort-dir", newDir);

  students.sort((a, b) => {
    if (key === "id") {
      return newDir === "asc" ? a.id - b.id : b.id - a.id;
    } else {
      return newDir === "asc"
        ? a[key].localeCompare(b[key])
        : b[key].localeCompare(a[key]);
    }
  });

  renderTable(students);
}

async function loadStudentsAndInitialize() {
  try {
    const response = await fetch("students.json");
    if (!response.ok) {
      console.error("Error loading students.json");
      return;
    }

    students = await response.json();
    renderTable(students);

  } catch (err) {
    console.error("Failed to load students:", err);
  }

  changePasswordForm.addEventListener("submit", handleChangePassword);
  addStudentForm.addEventListener("submit", handleAddStudent);
  studentTableBody.addEventListener("click", handleTableClick);
  searchInput.addEventListener("input", handleSearch);

  tableHeaders.forEach(th => {
    th.addEventListener("click", handleSort);
  });
}

loadStudentsAndInitialize();
