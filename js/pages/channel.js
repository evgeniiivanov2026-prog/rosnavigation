import { api } from '../utils/api.js';
import { toast } from '../components/toast.js';

export function init(boot) {
  document.querySelectorAll('form input[name="action"][value="like"]').forEach((input) => {
    const form = input.form;
    if (!form) return;
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const id = form.querySelector('input[name="id"]')?.value;
      try {
        const data = await api('channel.php?action=like', {
          method: 'POST',
          csrf: boot.csrf,
          body: { id: Number(id) },
        });
        const button = form.querySelector('button');
        if (button && data.result != null) {
          button.textContent = 'Пятюня стоит · ' + data.result;
          button.disabled = true;
        }
      } catch (err) {
        if (err.status === 401) {
          window.location.href = '/auth.php?next=' + encodeURIComponent('/channel.php');
          return;
        }
        toast(err.message || 'Не поставили пятюню.');
      }
    });
  });
}
