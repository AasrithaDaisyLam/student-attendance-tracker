// logout.js

document.addEventListener("DOMContentLoaded", function () {
  const logoutBtn = document.getElementById('logoutBtn');
  const logoutModal = document.getElementById('logoutModal');
  const confirmLogout = document.getElementById('confirmLogout');
  const cancelLogout = document.getElementById('cancelLogout');

  if (logoutBtn) {
    logoutBtn.onclick = () => {
      logoutModal.style.display = 'flex';
    };
  }

  if (cancelLogout) {
    cancelLogout.onclick = () => {
      logoutModal.style.display = 'none';
    };
  }

  if (confirmLogout) {
    confirmLogout.onclick = () => {
      window.location.href = '../loginstuff/login.php';
    };
  }

  window.onclick = (event) => {
    if (event.target === logoutModal) {
      logoutModal.style.display = 'none';
    }
  };
});
