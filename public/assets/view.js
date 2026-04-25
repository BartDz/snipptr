const { content, expiresMs } = window.snipptrData;

document.getElementById('copy-btn').addEventListener('click', function () {
    navigator.clipboard.writeText(content).then(() => {
        this.textContent = 'Copied!';
        this.classList.add('copied');
        setTimeout(() => {
            this.textContent = 'Copy';
            this.classList.remove('copied');
        }, 2000);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = content;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        this.textContent = 'Copied!';
        this.classList.add('copied');
        setTimeout(() => {
            this.textContent = 'Copy';
            this.classList.remove('copied');
        }, 2000);
    });
});

if (expiresMs) {
    const countdown = document.getElementById('countdown');

    function updateCountdown() {
        const diff = expiresMs - Date.now();
        if (diff <= 0) { countdown.textContent = 'Expired'; return; }
        const m = Math.floor(diff / 60000);
        const h = Math.floor(m / 60);
        const d = Math.floor(h / 24);
        countdown.textContent = d > 0 ? `Expires in ${d}d` : h > 0 ? `Expires in ${h}h` : `Expires in ${m}m`;
        setTimeout(updateCountdown, 30000);
    }

    updateCountdown();
}
