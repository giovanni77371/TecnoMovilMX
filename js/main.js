document.addEventListener('DOMContentLoaded', () => {
  const openLogin = document.getElementById('openLogin');
  const loginModal = document.getElementById('loginModal');
  const closeLogin = document.getElementById('closeLogin');

  if (openLogin && loginModal) {
    openLogin.addEventListener('click', (e) => {
      e.preventDefault();
      loginModal.style.display = 'flex';
    });
  }

  if (closeLogin && loginModal) {
    closeLogin.addEventListener('click', (e) => {
      e.preventDefault();
      loginModal.style.display = 'none';
    });
  }

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const fd = new FormData(loginForm);
      const res = await fetch('login.php', { method: 'POST', body: fd });
      const json = await res.json();

      if (json.ok) {
        window.location = 'panel.php';
        return;
      }

      const errorEl = document.getElementById('loginError');
      if (errorEl) {
        errorEl.innerText = json.msg;
        errorEl.style.display = 'block';
      }
    });
  }

  document.body.addEventListener('click', async (e) => {
    const btn = e.target.closest('.add-to-cart');
    if (!btn) return;

    const id = btn.dataset.id;
    const res = await fetch('carrito.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=add&id=' + encodeURIComponent(id)
    });

    const json = await res.json();
    if (json.ok) {
      const countEl = document.getElementById('cartCount');
      if (countEl) {
        const current = parseInt(countEl.textContent, 10) || 0;
        countEl.textContent = current + 1;
      }
    }
  });

  const deleteModal = document.getElementById('deleteModal');
  const confirmDelete = document.getElementById('confirmDelete');
  const cancelDelete = document.getElementById('cancelDelete');
  let deleteUrl = '';

  document.body.addEventListener('click', (e) => {
    const link = e.target.closest('.btn-delete');
    if (link && deleteModal) {
      e.preventDefault();
      deleteUrl = link.getAttribute('href');
      deleteModal.style.display = 'flex';
    }
  });

  if (confirmDelete) {
    confirmDelete.addEventListener('click', () => {
      window.location.href = deleteUrl;
    });
  }

  if (cancelDelete && deleteModal) {
    cancelDelete.addEventListener('click', () => {
      deleteModal.style.display = 'none';
      deleteUrl = '';
    });
  }

  const searchWidgets = document.querySelectorAll('.header-search');

  searchWidgets.forEach((widget) => {
    const toggle = widget.querySelector('.search-toggle');
    const input = widget.querySelector('input[name="q"]');

    if (!toggle || !input) return;

    toggle.addEventListener('click', () => {
      const isOpen = widget.classList.toggle('is-open');
      if (isOpen) {
        setTimeout(() => input.focus(), 60);
      } else {
        input.blur();
      }
    });

    widget.addEventListener('submit', (event) => {
      if (!input.value.trim()) {
        event.preventDefault();
        widget.classList.remove('is-open');
      }
    });
  });

  document.addEventListener('click', (event) => {
    searchWidgets.forEach((widget) => {
      if (!widget.contains(event.target)) {
        widget.classList.remove('is-open');
      }
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      searchWidgets.forEach((widget) => widget.classList.remove('is-open'));
    }
  });
});
