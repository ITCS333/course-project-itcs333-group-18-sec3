const loginForm = document.getElementById("login-form");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const messageContainer = document.getElementById("message-container");
const userTypeSelect = document.getElementById("user-type");

// --- Functions ---

function displayMessage(message, type) {
  if (!messageContainer) return;
  messageContainer.textContent = message;
  messageContainer.className = type;
}

function isValidEmail(email) {
  const regex = /\S+@\S+\.\S+/;
  return regex.test(email);
}

function isValidPassword(password) {
  return password.length >= 8;
}

/**
 * Handle login submit
 */
async function handleLogin(event) {
  event.preventDefault();

  const email = emailInput?.value.trim();
  const password = passwordInput?.value.trim();
  const type = userTypeSelect?.value;

  // --- Client-side validation ---
  if (!isValidEmail(email)) {
    displayMessage("Invalid email format.", "error");
    return;
  }

  if (!isValidPassword(password)) {
    displayMessage("Password must be at least 8 characters.", "error");
    return;
  }

  displayMessage("Logging in...", "success");

  // --- Backend request ---
  try {
    const response = await fetch("login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password, type })
    });

    const data = await response.json();

    if (!response.ok || !data.success) {
      displayMessage(data.message || "Login failed.", "error");
      return;
    }

    if (type === "teacher") {
      localStorage.setItem(
        "teacher",
        JSON.stringify({
          id: data.user.id,
          name: data.user.name,
          email: data.user.email,
          type: data.user.type
        })
      );
    } else if (type === "student") {
      localStorage.setItem(
        "student",
        JSON.stringify({
          id: data.user.id,
          name: data.user.name,
          email: data.user.email,
          type: data.user.type
        })
      );
    }

    displayMessage("Login successful! Redirecting...", "success");

    setTimeout(() => {
      window.location.href = "../../index.html";
    }, 800);

  } catch (error) {
    displayMessage("Server error. Please try again.", "error");
  }
}


function setupLoginForm() {
  if (!loginForm) return;
  loginForm.addEventListener("submit", handleLogin);
}

// Run only in browser (safe for tests)
if (typeof document !== "undefined") {
  setupLoginForm();
}
