const { content, slug, expiresMs, url } = window.snipptrData;

document.getElementById('fork-btn').addEventListener('click', function (e) {
    e.preventDefault();
    this.disabled = true;
    this.textContent = 'Forking...';

    fetch(`/api/fork/${slug}`, { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            if (d.url) {
                window.location = d.url;
            } else {
                this.textContent = 'Fork';
                this.disabled = false;
                alert('Fork failed: ' + (d.error || 'Unknown error'));
            }
        })
        .catch(err => {
            this.textContent = 'Fork';
            this.disabled = false;
            alert('Fork failed: ' + err.message);
        });
});

function copyToClipboard(text, btn, originalText) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('copied');
        }, 2000);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = originalText;
            btn.classList.remove('copied');
        }, 2000);
    });
}

document.getElementById('copy-code-btn').addEventListener('click', function () {
    copyToClipboard(content, this, 'Copy code');
});

document.getElementById('copy-url-btn').addEventListener('click', function () {
    copyToClipboard(url, this, 'Copy URL');
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

const qrToggle = document.getElementById('qr-toggle');
const qrContainer = document.getElementById('qr-container');
if (qrToggle && qrContainer && url) {
    qrToggle.addEventListener('click', function () {
        if (qrContainer.style.display === 'none') {
            qrContainer.style.display = 'inline-block';
            this.textContent = 'Hide QR';
            if (!qrContainer.hasChildNodes()) {
                new QRCode(qrContainer, {
                    text: url,
                    width: 180,
                    height: 180,
                });
            }
        } else {
            qrContainer.style.display = 'none';
            this.textContent = 'Show QR';
        }
    });
}
