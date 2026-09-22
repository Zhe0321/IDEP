document.documentElement.classList.add("js");

window.requestAnimationFrame(() => {
  document.documentElement.classList.add("page-ready");
});

const loginForm = document.querySelector("#manager-login");
const formMessage = document.querySelector("#form-message");
loginForm?.addEventListener("submit", async (event) => {
  event.preventDefault();

  if (!loginForm.reportValidity()) {
    return;
  }

  formMessage.textContent = "Checking your account...";
  formMessage.classList.remove("is-error");
  formMessage.classList.add("is-visible");

  try {
    const response = await fetch(loginForm.action, {
      method: "POST",
      body: new FormData(loginForm),
      headers: { Accept: "application/json" },
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message ?? "Unable to sign in.");
    }
    formMessage.textContent = result.message;
    window.location.assign(result.redirect ?? "/main/admin-dashboard.php");
  } catch (error) {
    formMessage.textContent = error instanceof Error ? error.message : "Unable to sign in.";
    formMessage.classList.add("is-error");
  }
});
