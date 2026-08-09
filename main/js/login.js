document.documentElement.classList.add("js");

window.requestAnimationFrame(() => {
  document.documentElement.classList.add("page-ready");
});

const loginForm = document.querySelector("#manager-login");
const formMessage = document.querySelector("#form-message");

loginForm?.addEventListener("submit", (event) => {
  event.preventDefault();

  if (!loginForm.reportValidity()) {
    return;
  }

  formMessage.textContent = "Manager authentication will be connected to the database API next.";
  formMessage.classList.add("is-visible");
});
