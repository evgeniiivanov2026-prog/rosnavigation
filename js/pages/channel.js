import { api } from '../utils/api.js';

export function init({ toast }) {
  const dialog = document.getElementById('report-dialog');
  const reportId = document.getElementById('report-id');
  document.getElementById('feed')?.addEventListener('click', async (event) => {
    const like = event.target.closest('[data-like]');
    const report = event.target.closest('[data-report]');
    if (like) {
      event.preventDefault();
      try {
        const data = await api('channel.php?action=like', {
          method: 'POST',
          body: { post_id: Number(like.dataset.like) },
        });
        like.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
        const count = like.querySelector('.salute__count');
        if (count) count.textContent = String(data.likes);
      } catch (error) {
        if (error.status === 401) {
          window.location.href = '/auth?next=/channel';
          return;
        }
        toast(error.message);
      }
    }
    if (report && dialog && reportId) {
      reportId.value = report.dataset.report || '';
      if (typeof dialog.showModal === 'function') dialog.showModal();
    }
  });
  document.getElementById('report-cancel')?.addEventListener('click', () => dialog?.close());

  const form = document.getElementById('channel-form');
  form?.addEventListener('submit', async (event) => {
    if (form.querySelector('input[type="file"]')?.files?.length) return;
    event.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());
    try {
      await api('channel.php?action=create', { method: 'POST', body: data });
      window.location.reload();
    } catch (error) {
      toast(error.message);
    }
  });
}
