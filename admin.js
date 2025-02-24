document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.getElementById("loginForm");
    const errorMsg = document.getElementById("errorMsg");
  
    // Exemplu de date de autentificare statice
    const validUsername = "admin";
    const validPassword = "admin123";
  
    loginForm.addEventListener("submit", function(e) {
      e.preventDefault(); // prevenim comportamentul default de submit
  
      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value.trim();
  
      if (username === validUsername && password === validPassword) {
        // Autentificare reușită
        errorMsg.textContent = "";
        // Redirect sau afișare dashboard; aici vom afișa un mesaj simplu
        alert("Autentificare reușită! Bine ai venit, " + username + ".");
        // De exemplu, poți redirecționa:
        // window.location.href = "admin-dashboard.html";
      } else {
        // Autentificare eșuată
        errorMsg.textContent = "Utilizator sau parolă incorectă.";
      }
    });
  });
  