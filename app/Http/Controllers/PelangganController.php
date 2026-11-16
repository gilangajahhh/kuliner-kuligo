<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PelangganController extends Controller
{
    public function index(string $kode_qr)
    {
        $kodeQrMasuk = rawurldecode($kode_qr);
        $meja = Meja::where('kode_qr', $kodeQrMasuk)->first()
            ?? Meja::whereRaw('LOWER(kode_qr) = ?', [mb_strtolower($kodeQrMasuk)])->first();

        // Accept older printed QR codes that used the table name or table ID.
        if (! $meja) {
            $kodeSlug = Str::slug($kodeQrMasuk);
            if ($kodeSlug !== '') {
                $meja = Meja::all()->first(fn (Meja $table) => Str::slug($table->nomor_meja) === $kodeSlug);
            }
        }
        if (! $meja && ctype_digit($kodeQrMasuk)) {
            $meja = Meja::find((int) $kodeQrMasuk);
        }
        abort_if(! $meja, 404);

        // Normalize legacy links so checkout and payment use the canonical table code.
        if ($kodeQrMasuk !== $meja->kode_qr) {
            return redirect()->route('menu.index', $meja->kode_qr);
        }

        session(['customer_menu_kode_qr' => $meja->kode_qr]);
        $kategori = KategoriMenu::with(['menu' => function ($q) {
            $q->with('varian');
        }])->orderBy('urutan_tampil')->get();

        return view('pelanggan.menu', compact('meja', 'kategori'));
    }

    public function menuAwal()
    {
        $meja = Meja::where('kode_qr', session('customer_menu_kode_qr'))->first();
        $meja ??= Meja::orderBy('nomor_meja')->first();

        abort_if(! $meja, 404, 'Menu pelanggan belum tersedia.');

        return redirect()->route('menu.index', $meja->kode_qr);
    }

    public function cekStok(string $kode_qr, int $id_menu)
    {
        Meja::where('kode_qr', $kode_qr)->firstOrFail();
        $menu = \App\Models\Menu::findOrFail($id_menu);

        return response()->json([
            'stok' => $menu->stok,
            'tersedia' => $menu->status_tersedia && $menu->stok > 0,
            'nama_menu' => $menu->nama_menu,
        ]);
    }

    public function checkout(Request $request, string $kode_qr)
    {
        $meja = Meja::where('kode_qr', $kode_qr)->firstOrFail();

        $data = $request->validate([
            'idempotency_key' => 'required|uuid',
            'nama_pelanggan' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.id_menu' => 'required|exists:menu,id_menu',
            'items.*.id_varian' => 'nullable|exists:varian_menu,id_varian',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string|max:255',
        ]);

        $fingerprint = hash('sha256', json_encode([
            'meja' => $meja->kode_qr,
            'nama_pelanggan' => $data['nama_pelanggan'] ?? null,
            'items' => $data['items'],
        ], JSON_THROW_ON_ERROR));

        try {
            $pesanan = DB::transaction(function () use ($data, $meja, $fingerprint) {
            $existing = Pesanan::where('idempotency_key', $data['idempotency_key'])
                ->where('id_meja', $meja->id_meja)
                ->first();

            if ($existing) {
                if (! hash_equals((string) $existing->idempotency_fingerprint, $fingerprint)) {
                    abort(409, 'Permintaan checkout ini sudah digunakan untuk isi keranjang yang berbeda.');
                }

                return $existing;
            }

            $meja = Meja::whereKey($meja->getKey())->lockForUpdate()->firstOrFail();
            $pesanan = Pesanan::create([
                'id_meja' => $meja->id_meja,
                'no_pesanan' => 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'nama_pelanggan' => $data['nama_pelanggan'] ?? null,
                // Set from Laravel's Asia/Jakarta clock instead of relying on the
                // database server's timezone for the column's CURRENT_TIMESTAMP default.
                'waktu_pesan' => now(),
                'status_pesanan' => 'baru',
                'total_harga' => 0,
                'idempotency_key' => $data['idempotency_key'],
                'idempotency_fingerprint' => $fingerprint,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $menu = \App\Models\Menu::whereKey($item['id_menu'])->lockForUpdate()->first();
                if (! $menu) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => 'Menu ini sudah tidak tersedia. Hapus dari keranjang untuk melanjutkan.',
                    ]);
                }
                if (! $menu->status_tersedia) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => $menu->stok < 1
                            ? "{$menu->nama_menu} sudah habis (stok 0). Hapus menu ini dari keranjang untuk melanjutkan."
                            : "{$menu->nama_menu} sedang tidak tersedia. Hapus menu ini dari keranjang untuk melanjutkan.",
                    ]);
                }
                if ($item['jumlah'] > $menu->stok) {
                    $pesanStok = $menu->stok < 1
                        ? "{$menu->nama_menu} sudah habis (stok 0). Hapus menu ini dari keranjang untuk melanjutkan."
                        : "Stok {$menu->nama_menu} hanya tersisa {$menu->stok}. Kurangi jumlahnya di keranjang untuk melanjutkan.";
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => $pesanStok,
                    ]);
                }
                $hargaTambahan = 0;
                if (! empty($item['id_varian'])) {
                    $varian = \App\Models\VarianMenu::where('id_menu', $menu->id_menu)->findOrFail($item['id_varian']);
                    $hargaTambahan = $varian->harga_tambahan;
                }
                $hargaSatuan = $menu->harga_dasar + $hargaTambahan;
                $subtotal = $hargaSatuan * $item['jumlah'];
                $total += $subtotal;

                DetailPesanan::create([
                    'id_pesanan' => $pesanan->id_pesanan,
                    'id_menu' => $item['id_menu'],
                    'id_varian' => $item['id_varian'] ?? null,
                    'jumlah' => $item['jumlah'],
                    'catatan' => $item['catatan'] ?? null,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $subtotal,
                ]);
                $menu->decrement('stok', $item['jumlah']);
            }

            $pesanan->update(['total_harga' => $total]);
            $meja->update(['status_meja' => 'terisi']);

            return $pesanan;
            });
        } catch (QueryException $exception) {
            if (! in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true)) {
                throw $exception;
            }

            $pesanan = Pesanan::where('idempotency_key', $data['idempotency_key'])->first();
            if (! $pesanan || (int) $pesanan->id_meja !== (int) $meja->id_meja) {
                throw $exception;
            }
            if (! hash_equals((string) $pesanan->idempotency_fingerprint, $fingerprint)) {
                abort(409, 'Permintaan checkout ini sudah digunakan untuk isi keranjang yang berbeda.');
            }
        }

        return response()->json([
            'no_pesanan' => $pesanan->no_pesanan,
            'total_harga' => $pesanan->total_harga,
            'redirect' => route('menu.bayar', ['kode_qr' => $meja->kode_qr]) . '?no_pesanan=' . $pesanan->no_pesanan,
        ]);
    }

    public function bayar(Request $request, string $kode_qr)
    {
        $data = $request->validate([
            'no_pesanan' => 'required|exists:pesanan,no_pesanan',
            'metode_pembayaran' => 'required|in:qris,bank_transfer,tunai',
            'kanal_pembayaran' => 'required_if:metode_pembayaran,bank_transfer|nullable|in:dana,ovo,gopay,bca,bri,mandiri,bjb,seabank',
        ]);

        $pesanan = Pesanan::where('no_pesanan', $data['no_pesanan'])
            ->whereHas('meja', fn ($query) => $query->where('kode_qr', $kode_qr))
            ->firstOrFail();

        if ($data['metode_pembayaran'] === 'qris'
            && (! config('services.midtrans.server_key') || ! config('services.midtrans.client_key'))) {
            return response()->json(['message' => 'Midtrans belum siap. Isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY di .env lalu jalankan php artisan config:clear.'], 503);
        }

        $kanal = match ($data['metode_pembayaran']) {
            'qris' => 'midtrans',
            'bank_transfer' => $data['kanal_pembayaran'],
            default => null,
        };
        $pembayaran = DB::transaction(function () use ($pesanan, $data, $kanal) {
            $pesanan = Pesanan::whereKey($pesanan->getKey())->lockForUpdate()->firstOrFail();
            $existing = Pembayaran::where('id_pesanan', $pesanan->id_pesanan)->first();

            if ($existing) {
                if ($existing->status_pembayaran === 'pending'
                    && ($existing->metode_pembayaran !== $data['metode_pembayaran']
                        || $existing->kanal_pembayaran !== $kanal)) {
                    $paymentChanges = [
                        'metode_pembayaran' => $data['metode_pembayaran'],
                        'kanal_pembayaran' => $kanal,
                        'kode_transaksi_gateway' => null,
                        'qris_string' => null,
                        'qris_expires_at' => null,
                    ];
                    // Older databases can still process cash/manual transfers before
                    // the Snap-token migration is applied.
                    if (Schema::hasColumn('pembayaran', 'snap_token')) {
                        $paymentChanges['snap_token'] = null;
                    }
                    $existing->update($paymentChanges);

                    return $existing->refresh();
                }

                $sameMethod = $existing->metode_pembayaran === $data['metode_pembayaran'];
                $sameChannel = $existing->kanal_pembayaran === $kanal;
                if (! $sameMethod || ! $sameChannel) {
                    abort(409, 'Pesanan ini sudah memiliki pembayaran dengan metode lain.');
                }

                return $existing;
            }

            return Pembayaran::create([
                'id_pesanan' => $pesanan->id_pesanan,
                'metode_pembayaran' => $data['metode_pembayaran'],
                'kanal_pembayaran' => $kanal,
                'jumlah_bayar' => $pesanan->total_harga,
                'status_pembayaran' => 'pending',
                'kode_transaksi_gateway' => null,
            ]);
        });

        if ($pembayaran->metode_pembayaran === 'qris') {
            $snap = $this->midtransSnap($pesanan, $pembayaran);
            if ($snap instanceof \Illuminate\Http\JsonResponse) {
                return $snap;
            }

            return response()->json([
                'status' => $pembayaran->status_pembayaran,
                'metode' => 'qris',
                'instructions' => [
                    'snap_token' => $snap['token'],
                ],
            ]);
        }

        return response()->json([
            'status' => $pembayaran->status_pembayaran,
            'metode' => $pembayaran->metode_pembayaran,
            'instructions' => match ($pembayaran->metode_pembayaran) {
                'bank_transfer' => [
                    'channel' => $pembayaran->kanal_pembayaran,
                ],
                default => null,
            },
        ]);
    }

    private function midtransSnap(Pesanan $pesanan, Pembayaran $pembayaran): array|\Illuminate\Http\JsonResponse
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if ($pembayaran->snap_token) {
            return ['token' => $pembayaran->snap_token];
        }

        $apiUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        try {
            $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->post($apiUrl, [
                'transaction_details' => [
                    'order_id' => $pesanan->no_pesanan,
                    'gross_amount' => (int) round((float) $pembayaran->jumlah_bayar),
                ],
                'customer_details' => [
                    'first_name' => $pesanan->nama_pelanggan ?: 'Pelanggan Kuligo',
                ],
                'enabled_payments' => ['other_qris', 'bank_transfer'],
                'item_details' => $pesanan->detail()->with('menu')->get()->map(fn ($item) => [
                    'id' => (string) $item->id_menu,
                    'price' => (int) round((float) $item->harga_satuan),
                    'quantity' => (int) $item->jumlah,
                    'name' => Str::limit($item->menu?->nama_menu ?? 'Menu Kuligo', 50, ''),
                ])->values()->all(),
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Koneksi ke Midtrans gagal saat menyiapkan Snap.', [
                'order' => $pesanan->no_pesanan,
            ]);

            return response()->json([
                'message' => 'Koneksi ke Midtrans sedang terganggu. Coba buka pembayaran lagi.',
            ], 503);
        }

        if (! $response->successful()) {
            Log::warning('Pembuatan token Midtrans Snap gagal.', [
                'order' => $pesanan->no_pesanan,
                'http_status' => $response->status(),
                'mode' => config('services.midtrans.is_production') ? 'production' : 'sandbox',
                'endpoint' => $apiUrl,
                'server_key_prefix' => substr($serverKey, 0, 8),
                'error_messages' => $response->json('error_messages'),
                'response_body' => Str::limit($response->body(), 500),
            ]);

            $message = $response->status() === 401
                ? 'Midtrans menolak API Key. Untuk uji coba, gunakan Server Key dan Client Key Sandbox (awalan SB-Mid-) dengan MIDTRANS_IS_PRODUCTION=false.'
                : (collect($response->json('error_messages', []))->filter()->implode(' ') ?: 'Midtrans belum berhasil membuat pembayaran. Periksa status akun dan kanal pembayaran.');

            return response()->json([
                'message' => $message,
            ], 502);
        }

        $token = $response->json('token');
        if (! is_string($token) || $token === '') {
            Log::warning('Respons Midtrans tidak berisi Snap Token.', [
                'order' => $pesanan->no_pesanan,
            ]);

            return response()->json(['message' => 'Midtrans belum mengembalikan token pembayaran. Coba lagi beberapa saat.'], 502);
        }

        $pembayaran->update([
            'kanal_pembayaran' => 'midtrans',
            'kode_transaksi_gateway' => $pesanan->no_pesanan,
            'snap_token' => $token,
        ]);

        return ['token' => $token];
    }

    public function midtransWebhook(Request $request)
    {
        $data = $request->all();
        $serverKey = (string) config('services.midtrans.server_key');
        $orderId = $data['order_id'] ?? null;
        $statusCode = $data['status_code'] ?? null;
        $grossAmount = $data['gross_amount'] ?? null;
        $signature = $data['signature_key'] ?? null;

        if ($serverKey === '' || ! is_string($orderId) || ! is_string($statusCode)
            || ! is_string($grossAmount) || ! is_string($signature)) {
            return response()->json(['message' => 'Notifikasi Midtrans tidak lengkap.'], 400);
        }

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Webhook Midtrans ditolak karena signature tidak valid.', ['order_id' => $orderId]);

            return response()->json(['message' => 'Signature tidak valid.'], 401);
        }

        $payment = Pembayaran::query()
            ->where('kanal_pembayaran', 'midtrans')
            ->where('kode_transaksi_gateway', $orderId)
            ->first();

        if (! $payment) {
            Log::warning('Webhook Midtrans diterima untuk pesanan yang tidak ditemukan.', ['order_id' => $orderId]);

            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        if (! is_numeric($grossAmount)
            || abs((float) $grossAmount - (float) $payment->jumlah_bayar) >= 0.01
            || (isset($data['currency']) && $data['currency'] !== 'IDR')) {
            Log::warning('Nominal atau mata uang webhook Midtrans tidak cocok.', ['order_id' => $orderId]);

            return response()->json(['message' => 'Nominal atau mata uang transaksi tidak cocok.'], 422);
        }

        $transactionStatus = $data['transaction_status'] ?? null;
        $fraudStatus = $data['fraud_status'] ?? null;
        $incomingStatus = match ($transactionStatus) {
            'settlement' => 'berhasil',
            'capture' => $fraudStatus === 'accept' ? 'berhasil' : ($fraudStatus === 'deny' ? 'gagal' : 'pending'),
            'pending' => 'pending',
            'deny', 'cancel', 'expire' => 'gagal',
            default => null,
        };

        if ($incomingStatus === null) {
            return response()->json(['status' => 'ignored']);
        }

        $incomingMethod = match ($data['payment_type'] ?? null) {
            'bank_transfer', 'permata_va', 'bca_va', 'bni_va', 'bri_va', 'cimb_va', 'danamon_va', 'bsi_va', 'seabank_va', 'echannel' => 'bank_transfer',
            'qris', 'other_qris' => 'qris',
            default => null,
        };

        DB::transaction(function () use ($payment, $incomingStatus, $incomingMethod) {
            $locked = Pembayaran::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            // Repeated or delayed notifications must not downgrade a confirmed payment.
            if ($locked->status_pembayaran !== 'berhasil' || $incomingStatus === 'berhasil') {
                $changes = [
                    'status_pembayaran' => $incomingStatus,
                    'waktu_pembayaran' => $incomingStatus === 'berhasil' ? ($locked->waktu_pembayaran ?? now()) : null,
                ];
                if ($incomingMethod !== null) {
                    $changes['metode_pembayaran'] = $incomingMethod;
                }
                $locked->update($changes);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function simulasiPembayaranXendit(Request $request, string $kode_qr, string $no_pesanan)
    {
        $secret = (string) config('services.xendit.secret_key');
        if (! str_starts_with($secret, 'xnd_development_')) {
            return response()->json(['message' => 'Simulasi hanya tersedia dengan Secret Key sandbox Xendit.'], 403);
        }

        $pesanan = Pesanan::where('no_pesanan', $no_pesanan)
            ->whereHas('meja', fn ($query) => $query->where('kode_qr', $kode_qr))
            ->firstOrFail();
        $pembayaran = Pembayaran::where('id_pesanan', $pesanan->id_pesanan)
            ->where('metode_pembayaran', 'qris')
            ->where('kanal_pembayaran', 'xendit')
            ->firstOrFail();

        if ($pembayaran->status_pembayaran === 'berhasil') {
            return response()->json(['message' => 'Pembayaran ini sudah terverifikasi.', 'status' => 'berhasil']);
        }
        if (! $pembayaran->kode_transaksi_gateway) {
            return response()->json(['message' => 'Transaksi Xendit belum terbentuk. Buat ulang QRIS terlebih dahulu.'], 409);
        }

        return $this->kirimSimulasiXendit($pesanan, $pembayaran);
    }

    public function simulatorQris()
    {
        abort_unless(str_starts_with((string) config('services.xendit.secret_key'), 'xnd_development_'), 404);

        return view('pelanggan.simulator-qris');
    }

    public function simulatorQrisScan(Request $request)
    {
        $secret = (string) config('services.xendit.secret_key');
        abort_unless(str_starts_with($secret, 'xnd_development_'), 404);

        $data = $request->validate([
            'qris_string' => 'required|string|max:1500',
        ]);

        $pembayaran = Pembayaran::with('pesanan')
            ->where('metode_pembayaran', 'qris')
            ->where('kanal_pembayaran', 'xendit')
            ->where('qris_string', $data['qris_string'])
            ->latest('id_pembayaran')
            ->first();

        if (! $pembayaran) {
            return response()->json(['message' => 'QR tidak cocok dengan transaksi sandbox yang dibuat aplikasi ini.'], 404);
        }
        if ($pembayaran->status_pembayaran === 'berhasil') {
            return response()->json(['message' => 'Pembayaran bohongan ini sudah berhasil sebelumnya.', 'status' => 'berhasil']);
        }
        if ($pembayaran->status_pembayaran !== 'pending' || ! $pembayaran->kode_transaksi_gateway) {
            return response()->json(['message' => 'Transaksi ini tidak lagi menunggu pembayaran.'], 409);
        }

        return $this->kirimSimulasiXendit($pembayaran->pesanan, $pembayaran);
    }

    private function kirimSimulasiXendit(Pesanan $pesanan, Pembayaran $pembayaran)
    {
        $secret = (string) config('services.xendit.secret_key');
        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->withHeaders(['api-version' => '2024-11-11'])
            ->timeout(20)
            ->post('https://api.xendit.co/v3/payment_requests/' . urlencode($pembayaran->kode_transaksi_gateway) . '/simulate', [
                'amount' => (float) $pembayaran->jumlah_bayar,
            ]);

        if (! $response->successful()) {
            Log::warning('Simulasi pembayaran QRIS Xendit gagal.', [
                'order' => $pesanan->no_pesanan,
                'http_status' => $response->status(),
                'error_code' => $response->json('error_code'),
            ]);

            return response()->json([
                'message' => $response->json('message') ?: 'Xendit belum dapat menyimulasikan pembayaran. Pastikan transaksi masih pending di Test Mode.',
            ], 502);
        }

        return response()->json([
            'status' => $response->json('status', 'PENDING'),
            'message' => 'Simulasi pembayaran bohongan dikirim. Tunggu webhook Xendit; halaman status pesanan akan berubah setelah notifikasi diterima.',
        ]);
    }

    public function xenditWebhook(Request $request)
    {
        $webhookToken = (string) config('services.xendit.webhook_token');
        $providedToken = (string) $request->header('x-callback-token', '');

        if ($webhookToken === '' || $providedToken === '' || ! hash_equals($webhookToken, $providedToken)) {
            Log::warning('Webhook Xendit ditolak karena token callback tidak valid.');

            return response()->json(['message' => 'Webhook tidak terautentikasi.'], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');
        if (! is_string($event) || ! is_array($data)) {
            return response()->json(['message' => 'Payload webhook tidak valid.'], 400);
        }

        $incomingStatus = match ($event) {
            'payment.capture' => ($data['status'] ?? null) === 'SUCCEEDED' ? 'berhasil' : null,
            'payment.failure', 'payment_request.expiry' => 'gagal',
            default => null,
        };

        // Acknowledge unrelated Xendit events without changing payment records.
        if ($incomingStatus === null) {
            return response()->json(['status' => 'ignored']);
        }

        $paymentRequestId = $data['payment_request_id'] ?? $data['id'] ?? null;
        $referenceId = $data['reference_id'] ?? null;
        $hasPaymentRequestId = is_string($paymentRequestId) && $paymentRequestId !== '';
        $hasReferenceId = is_string($referenceId) && $referenceId !== '';
        if (! $hasPaymentRequestId && ! $hasReferenceId) {
            return response()->json(['message' => 'ID transaksi tidak ditemukan di payload.'], 400);
        }

        $query = Pembayaran::query()
            ->where('metode_pembayaran', 'qris')
            ->where('kanal_pembayaran', 'xendit')
            ->where(function ($query) use ($paymentRequestId, $referenceId, $hasPaymentRequestId, $hasReferenceId) {
                if ($hasPaymentRequestId) {
                    $query->where('kode_transaksi_gateway', $paymentRequestId);
                }
                if ($hasReferenceId) {
                    $method = $hasPaymentRequestId ? 'orWhereHas' : 'whereHas';
                    $query->{$method}('pesanan', fn ($order) => $order->where('no_pesanan', $referenceId));
                }
            });

        $payment = $query->first();
        if (! $payment) {
            Log::warning('Webhook Xendit diterima untuk transaksi yang tidak ditemukan.', [
                'payment_request_id' => is_string($paymentRequestId) ? $paymentRequestId : null,
                'reference_id' => is_string($referenceId) ? $referenceId : null,
            ]);

            return response()->json(['message' => 'Transaksi belum ditemukan.'], 404);
        }

        $payment->loadMissing('pesanan');
        if (is_string($referenceId) && $referenceId !== ''
            && (string) $payment->pesanan?->no_pesanan !== $referenceId) {
            Log::warning('Reference ID webhook Xendit tidak cocok dengan pesanan.', [
                'payment_id' => $payment->getKey(),
            ]);

            return response()->json(['message' => 'Reference transaksi tidak cocok.'], 422);
        }

        if (array_key_exists('currency', $data) && $data['currency'] !== 'IDR') {
            return response()->json(['message' => 'Mata uang transaksi tidak cocok.'], 422);
        }
        if (isset($data['request_amount'])
            && (! is_numeric($data['request_amount'])
                || abs((float) $data['request_amount'] - (float) $payment->jumlah_bayar) >= 0.01)) {
            Log::warning('Nominal webhook Xendit berbeda dari nominal pesanan.', [
                'payment_id' => $payment->getKey(),
            ]);

            return response()->json(['message' => 'Nominal transaksi tidak cocok.'], 422);
        }
        if ($event === 'payment.capture' && ! isset($data['request_amount'])) {
            return response()->json(['message' => 'Nominal transaksi tidak tersedia di webhook.'], 422);
        }

        DB::transaction(function () use ($payment, $incomingStatus) {
            $locked = Pembayaran::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            // A delayed expiry/failure event must never downgrade a captured payment.
            if ($locked->status_pembayaran !== 'berhasil' || $incomingStatus === 'berhasil') {
                $locked->update([
                    'status_pembayaran' => $incomingStatus,
                    'waktu_pembayaran' => $incomingStatus === 'berhasil' ? ($locked->waktu_pembayaran ?? now()) : null,
                ]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function status(string $kode_qr, string $no_pesanan)
    {
        $pesanan = Pesanan::with('detail.menu', 'detail.varian', 'pembayaran')
            ->where('no_pesanan', $no_pesanan)
            ->whereHas('meja', fn ($query) => $query->where('kode_qr', $kode_qr))
            ->firstOrFail();

        return view('pelanggan.status', compact('pesanan'));
    }

    public function strukPelanggan(string $kode_qr, string $no_pesanan)
    {
        $pesanan = Pesanan::with('meja', 'detail.menu', 'detail.varian', 'pembayaran')
            ->where('no_pesanan', $no_pesanan)
            ->whereHas('meja', fn ($query) => $query->where('kode_qr', $kode_qr))
            ->firstOrFail();

        abort_unless($pesanan->pembayaran?->status_pembayaran === 'berhasil', 404);

        return view('pelanggan.struk', compact('pesanan'));
    }
}
