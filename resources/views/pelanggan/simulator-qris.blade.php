<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Simulator QRIS · Kuligo Resto</title>
    <link rel="stylesheet" href="/css/customer.css?v=qris-actions-2">
    <style>
        .simulator-card{max-width:560px;margin:40px auto;padding:28px;background:#fff;border:1px solid #e5e7eb;border-radius:20px;box-shadow:0 18px 48px #10182814}
        .simulator-card h1{margin:0 0 10px}.simulator-card p{color:#667085;line-height:1.6}.simulator-label{display:inline-block;margin-bottom:14px;padding:6px 10px;border-radius:999px;background:#fff4d6;color:#875d00;font-size:12px;font-weight:800;letter-spacing:.08em}
        #reader{width:100%;max-width:420px;margin:22px auto;overflow:hidden;border:1px solid #e5e7eb;border-radius:14px}#reader video{object-fit:cover}
        .simulator-status{min-height:44px;margin-top:14px;padding:12px 14px;border:1px solid #e5e9f0;border-radius:10px;background:#f4f6f8;color:#344054;line-height:1.5}.simulator-status.error{border-color:#f0c8c8;background:#fff0f0;color:#a33434}.simulator-status.success{border-color:#c9ead5;background:#eaf8ee;color:#16703a}
        .simulator-actions{display:grid;grid-template-columns:1fr;gap:10px;margin-top:18px}.simulator-actions button{width:100%;min-height:50px;border:1px solid #192235;border-radius:11px;padding:12px 18px;background:#192235;color:white;font:inherit;font-weight:700;cursor:pointer;transition:background .18s,transform .18s,box-shadow .18s}.simulator-actions button:hover:not(:disabled){background:#2b3545;box-shadow:0 5px 14px #19223526;transform:translateY(-1px)}.simulator-actions button:focus-visible{outline:3px solid #9ba9c2;outline-offset:3px}.simulator-actions button:disabled{opacity:.62;cursor:wait;transform:none;box-shadow:none}.simulator-actions button#scan-again{background:#fff;color:#192235}.simulator-actions button#scan-again:hover:not(:disabled){background:#f2f4f8}
    </style>
</head>
<body class="customer-body">
    <main class="simulator-card" data-scan-url="{{ route('qris.simulator.scan') }}">
        <span class="simulator-label">MODE DEMO · TANPA UANG SUNGGUHAN</span>
        <h1>Simulator scan QRIS</h1>
        <p>Buka halaman ini di HP kedua, lalu arahkan kamera ke QR sandbox yang tampil di checkout. Jika QR cocok dengan transaksi demo, simulator akan meminta Xendit menandai pembayaran melalui webhook.</p>
        <div id="reader"></div>
        <div class="simulator-actions"><button id="start-scan" type="button">Mulai scan QR</button><button id="scan-again" type="button" hidden>Scan QR lain</button></div>
        <div id="simulator-status" class="simulator-status" role="status">Tekan “Mulai scan” dan izinkan akses kamera.</div>
    </main>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (() => {
            const card = document.querySelector('.simulator-card');
            const statusBox = document.querySelector('#simulator-status');
            const startButton = document.querySelector('#start-scan');
            const againButton = document.querySelector('#scan-again');
            const scanner = new Html5Qrcode('reader');
            let busy = false;

            function setStatus(message, kind = '') {
                statusBox.textContent = message;
                statusBox.className = `simulator-status ${kind}`;
            }

            async function onScan(decodedText) {
                if (busy) return;
                busy = true;
                startButton.disabled = true;
                setStatus('QR terbaca. Mengirim simulasi pembayaran bohongan…');
                try { await scanner.stop(); } catch (_) {}
                try {
                    const response = await fetch(card.dataset.scanUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ qris_string: decodedText }),
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'QR tidak dapat disimulasikan.');
                    setStatus(`${result.message} Cek halaman pesanan pelanggan untuk melihat hasilnya.`, 'success');
                } catch (error) {
                    setStatus(error.message, 'error');
                }
                startButton.hidden = true;
                againButton.hidden = false;
                busy = false;
            }

            async function startScanner() {
                if (typeof Html5Qrcode === 'undefined') {
                    setStatus('Komponen scanner gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.', 'error');
                    return;
                }
                startButton.disabled = true;
                setStatus('Meminta izin kamera…');
                try {
                    await scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 250, height: 250 } }, onScan, () => {});
                    startButton.hidden = true;
                    againButton.hidden = true;
                    setStatus('Arahkan kamera ke QR sandbox pesanan.');
                } catch (error) {
                    startButton.disabled = false;
                    setStatus('Kamera tidak bisa dibuka. Izinkan akses kamera dan buka simulator melalui HTTPS ngrok.', 'error');
                }
            }

            startButton.addEventListener('click', startScanner);
            againButton.addEventListener('click', () => {
                startButton.hidden = false;
                startButton.disabled = false;
                againButton.hidden = true;
                startScanner();
            });
        })();
    </script>
</body>
</html>
