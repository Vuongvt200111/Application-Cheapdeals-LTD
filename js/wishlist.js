/* wishlist.js — handle AJAX saving to wishlist and clear recommendations history (BUG-008) */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      const code = btn.dataset.code;
      const formData = new FormData();
      formData.append('code', code);
      
      fetch('index.php?action=toggle_wishlist', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          toast(data.error);
        } else {
          // Toggle all heart buttons on the page with the same code (BUG-008)
          const allBtns = document.querySelectorAll(`.wishlist-btn[data-code="${code}"]`);
          if (data.status === 'added') {
            allBtns.forEach(b => {
              b.classList.add('active');
              b.textContent = '❤️';
            });
            toast('Deal saved to Wishlist ❤️');
          } else if (data.status === 'removed') {
            allBtns.forEach(b => {
              b.classList.remove('active');
              b.textContent = '🤍';
            });
            toast('Deal removed from Wishlist');
          }
        }
      })
      .catch(err => {
        console.error('Wishlist error:', err);
        toast('Failed to update Wishlist.');
      });
    });
  });

  const clearBtn = document.getElementById('clearHistoryBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      fetch('index.php?action=clear_history')
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          const block = document.getElementById('recommendationsBlock');
          if (block) block.style.display = 'none';
          toast('View history cleared');
        }
      });
    });
  }
});

function logView(code) {
  fetch('index.php?view_code=' + encodeURIComponent(code));
}
