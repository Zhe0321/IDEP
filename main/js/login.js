document.documentElement.classList.add("js");

window.requestAnimationFrame(() => {
  document.documentElement.classList.add("page-ready");
});

const loginForm = document.querySelector("#manager-login");
const formMessage = document.querySelector("#form-message");
const demoManagerUsername = "admin123";
const demoManagerPassword = "admin123";

loginForm?.addEventListener("submit", (event) => {
  event.preventDefault();

  if (!loginForm.reportValidity()) {
    return;
  }

  const formData = new FormData(loginForm);
  const username = String(formData.get("username") ?? "").trim();
  const password = String(formData.get("password") ?? "");

  if (username !== demoManagerUsername || password !== demoManagerPassword) {
    formMessage.textContent = "Incorrect manager username or password.";
    formMessage.classList.add("is-visible", "is-error");
    return;
  }

  formMessage.textContent = "Demo access granted. Opening the administrator dashboard...";
  formMessage.classList.remove("is-error");
  formMessage.classList.add("is-visible");

  window.setTimeout(() => {
    window.location.assign("/main/admin-dashboard.php");
  }, 450);
});
