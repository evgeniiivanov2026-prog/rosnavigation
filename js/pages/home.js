import { api } from '../utils/api.js';
import { startPolling } from '../utils/poll.js';

export function init(boot) {
  const count = document.getElementById('ether-count');
  if (!count) return;
  startPolling(async () => {
    const data = await api('poll.php');
    if (typeof data.count_online === 'number') {
      count.textContent = String(data.count_online);
    }
  }, boot.pollMs || 30000);
}
