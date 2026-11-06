<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PelangganController extends Controller
{
    public function index(string $kode_qr)
    {
        $meja = Meja::where('kode_qr', $kode_qr)->firstOrFail();
        $kategori = KategoriMenu::with(['menu' => function ($q) {
            $q->where('status_tersedia', true)->where('stok', '>', 0)->with('varian');
        }])->orderBy('urutan_tampil')->get();

        return view('pelanggan.menu', compact('meja', 'kategori'));
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
                'status_pesanan' => 'baru',
                'total_harga' => 0,
                'idempotency_key' => $data['idempotency_key'],
                'idempotency_fingerprint' => $fingerprint,
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $menu = \App\Models\Menu::where('status_tersedia', true)->whereKey($item['id_menu'])->lockForUpdate()->firstOrFail();
                if ($item['jumlah'] > $menu->stok) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Stok {$menu->nama_menu} tersisa {$menu->stok}, sedangkan jumlah pesanan {$item['jumlah']}.",
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
        ]);

        $pesanan = Pesanan::where('no_pesanan', $data['no_pesanan'])
            ->whereHas('meja', fn ($query) => $query->where('kode_qr', $kode_qr))
            ->firstOrFail();

        if ($data['metode_pembayaran'] === 'bank_transfer'
            && (! config('payments.bank.name') || ! config('payments.bank.account') || ! config('payments.bank.account_name'))) {
            return response()->json(['message' => 'Rekening tujuan transfer belum diatur di .env (PAYMENT_BANK_NAME, PAYMENT_BANK_ACCOUNT, PAYMENT_BANK_ACCOUNT_NAME).'], 503);
        }
        if ($data['metode_pembayaran'] === 'qris' && ! config('services.xendit.secret_key')) {
            return response()->json(['message' => 'Xendit belum siap. Isi XENDIT_SECRET_KEY di .env lalu jalankan php artisan config:clear.'], 503);
        }

        $kanal = $data['metode_pembayaran'] === 'qris' ? 'xendit' : null;
        $pembayaran = DB::transaction(function () use ($pesanan, $data, $kanal) {
            $pesanan = Pesanan::whereKey($pesanan->getKey())->lockForUpdate()->firstOrFail();
            $existing = Pembayaran::where('id_pesanan', $pesanan->id_pesanan)->first();

            if ($existing) {
                if ($existing->status_pembayaran === 'pending'
                    && ($existing->metode_pembayaran !== $data['metode_pembayaran']
                        || $existing->kanal_pembayaran !== $kanal)) {
                    $existing->update([
                        'metode_pembayaran' => $data['metode_pembayaran'],
                        'kanal_pembayaran' => $kanal,
                        'kode_transaksi_gateway' => null,
                        'qris_string' => null,
                        'qris_expires_at' => null,
                    ]);

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
            $qris = $this->xenditQris($pesanan, $pembayaran);
            if ($qris instanceof \Illuminate\Http\JsonResponse) {
                return $qris;
            }

            return response()->json([
                'status' => $pembayaran->status_pembayaran,
                'metode' => 'qris',
                'sandbox' => str_starts_with((string) config('services.xendit.secret_key'), 'xnd_development_'),
                'instructions' => [
                    'image' => $this->qrisImageUrl($qris['string']),
                    'download' => $this->qrisImageUrl($qris['string']),
                    'expires_at' => $qris['expires_at']?->toIso8601String(),
                    'payment_request_id' => $pembayaran->kode_transaksi_gateway,
                ],
            ]);
        }

        return response()->json([
            'status' => $pembayaran->status_pembayaran,
            'metode' => $pembayaran->metode_pembayaran,
            'instructions' => match ($pembayaran->metode_pembayaran) {
                'bank_transfer' => [
                    'bank' => config('payments.bank.name'),
                    'account' => config('payments.bank.account'),
                    'account_name' => config('payments.bank.account_name'),
                ],
                default => null,
            },
        ]);
    }

    private function xenditQris(Pesanan $pesanan, Pembayaran $pembayaran): array|\Illuminate\Http\JsonResponse
    {
        $secret = (string) config('services.xendit.secret_key');
        $baseUrl = 'https://api.xendit.co/v3/payment_requests';
        $expiresAt = now()->addMinutes(15)->utc();

        if ($pembayaran->qris_string) {
            return [
                'string' => $pembayaran->qris_string,
                'expires_at' => $pembayaran->qris_expires_at,
            ];
        }

        $request = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->withHeaders(['api-version' => '2024-11-11'])
            ->timeout(20);

        if ($pembayaran->kode_transaksi_gateway) {
            $response = $request->get($baseUrl . '/' . urlencode($pembayaran->kode_transaksi_gateway));
        } else {
            $response = $request->withHeaders([
                'Idempotency-Key' => 'qris-' . $pesanan->no_pesanan,
            ])->post($baseUrl, [
                'reference_id' => $pesanan->no_pesanan,
                'type' => 'PAY',
                'country' => 'ID',
                'currency' => 'IDR',
                'request_amount' => (float) $pembayaran->jumlah_bayar,
                'channel_code' => 'QRIS',
                'channel_properties' => [
                    'expires_at' => $expiresAt->toIso8601String(),
                    'qr_string_type' => 'DYNAMIC',
                ],
                'description' => 'Pembayaran pesanan ' . $pesanan->no_pesanan,
            ]);
        }

        if (! $response->successful()) {
            Log::warning('Pembuatan QRIS Xendit gagal.', [
                'order' => $pesanan->no_pesanan,
                'http_status' => $response->status(),
                'error_code' => $response->json('error_code'),
            ]);

            $message = $response->json('message');
            return response()->json([
                'message' => is_string($message) && $message !== ''
                    ? 'Xendit menolak pembuatan QRIS: ' . $message
                    : 'Xendit belum berhasil membuat QRIS. Periksa status akun dan API key sandbox.',
            ], 502);
        }

        $body = $response->json();
        $paymentRequestId = $body['payment_request_id'] ?? null;
        $qrString = collect($body['actions'] ?? [])->first(fn ($action) =>
            ($action['descriptor'] ?? null) === 'QR_STRING' && is_string($action['value'] ?? null)
        )['value'] ?? null;

        if (! is_string($paymentRequestId) || ! is_string($qrString) || $qrString === '') {
            Log::warning('Respons QRIS Xendit tidak berisi payment request ID atau QR string.', [
                'order' => $pesanan->no_pesanan,
            ]);

            return response()->json(['message' => 'Xendit menerima transaksi, tetapi data QRIS belum ada pada respons. Coba lagi beberapa saat.'], 502);
        }

        $actualExpiry = data_get($body, 'channel_properties.expires_at');
        $pembayaran->update([
            'kanal_pembayaran' => 'xendit',
            'kode_transaksi_gateway' => $paymentRequestId,
            'qris_string' => $qrString,
            'qris_expires_at' => $actualExpiry ? \Illuminate\Support\Carbon::parse($actualExpiry) : $expiresAt,
        ]);

        return [
            'string' => $qrString,
            'expires_at' => $pembayaran->fresh()->qris_expires_at,
        ];
    }

    private function qrisImageUrl(string $qrString): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&format=png&data=' . urlencode($qrString);
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
            'message' => 'Simulasi dikirim. Tunggu webhook Xendit, lalu halaman status akan memperbarui pembayaran.',
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
}
