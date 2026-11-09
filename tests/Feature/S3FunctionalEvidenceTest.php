<?php

namespace Tests\Feature;

use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class S3FunctionalEvidenceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kasir;
    private Meja $mejaSatu;
    private Meja $mejaDua;
    private KategoriMenu $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->user('admin');
        $this->kasir = $this->user('kasir');
        $this->mejaSatu = Meja::create(['nomor_meja' => 'Meja 01', 'kode_qr' => 'meja-01']);
        $this->mejaDua = Meja::create(['nomor_meja' => 'Meja 02', 'kode_qr' => 'meja-02']);
        $this->kategori = KategoriMenu::create(['nama_kategori' => 'Makanan', 'urutan_tampil' => 1]);
    }

    public function test_guest_home_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('HALAMAN LOGIN');
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $this->post('/login', ['role' => 'kasir', 'username' => 'missing', 'password' => 'wrong'])
            ->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_guest_cannot_open_admin_menu(): void
    {
        $this->get('/admin/menu')->assertRedirect(route('login'));
    }

    public function test_guest_cannot_open_cashier_dashboard(): void
    {
        $this->get('/kasir/dashboard')->assertRedirect(route('login'));
    }

    public function test_admin_can_open_admin_menu(): void
    {
        $this->actingAs($this->admin)->get('/admin/menu')->assertOk()->assertSee('KATALOG PRODUK');
    }

    public function test_cashier_is_denied_from_admin_menu_with_custom_403(): void
    {
        $this->actingAs($this->kasir)->get('/admin/menu')->assertForbidden()->assertSee('Akses ditolak');
    }

    public function test_admin_can_open_cashier_dashboard(): void
    {
        $this->actingAs($this->admin)->get('/kasir/dashboard')->assertOk();
    }

    public function test_unknown_route_uses_custom_404(): void
    {
        $this->get('/route-tidak-tersedia')->assertNotFound()->assertSee('Halaman tidak ditemukan');
    }

    public function test_unexpected_exception_uses_custom_500(): void
    {
        Route::get('/__test/500', fn () => throw new RuntimeException('internal details'));
        $this->get('/__test/500')->assertStatus(500)->assertSee('Terjadi kendala')->assertDontSee('internal details');
    }

    public function test_customer_can_open_valid_table_menu(): void
    {
        $this->get('/menu/meja-01')->assertOk()->assertSee('Jelajahi menu');
    }

    public function test_unknown_table_uses_not_found_page(): void
    {
        $this->get('/menu/meja-xx')->assertNotFound()->assertSee('404');
    }

    public function test_checkout_rejects_empty_cart(): void
    {
        $this->postJson('/menu/meja-01/checkout', ['items' => []])->assertUnprocessable();
    }

    public function test_payment_rejects_unknown_method(): void
    {
        $order = $this->order($this->mejaSatu);
        $this->postJson('/menu/meja-01/bayar', [
            'no_pesanan' => $order->no_pesanan,
            'metode_pembayaran' => 'crypto',
        ])->assertUnprocessable();
    }

    public function test_customer_cannot_view_order_from_another_table(): void
    {
        $order = $this->order($this->mejaSatu);
        $this->get('/menu/meja-02/status/' . $order->no_pesanan)->assertNotFound();
    }

    public function test_customer_cannot_pay_order_from_another_table(): void
    {
        $order = $this->order($this->mejaSatu);
        $this->postJson('/menu/meja-02/bayar', [
            'no_pesanan' => $order->no_pesanan,
            'metode_pembayaran' => 'qris',
        ])->assertNotFound();
    }

    public function test_upload_rejects_non_image_extension(): void
    {
        $this->actingAs($this->admin)->postJson('/admin/menu', [
            'id_kategori' => $this->kategori->id_kategori,
            'nama_menu' => 'Menu uji',
            'harga_dasar' => 10000,
            'stok' => 1,
            'gambar' => UploadedFile::fake()->createWithContent('bukti.pdf', 'not an image'),
        ])->assertUnprocessable()->assertJsonValidationErrors('gambar');
    }

    public function test_bank_transfer_requires_a_valid_bank_channel(): void
    {
        $order = $this->order($this->mejaSatu);
        $this->postJson('/menu/meja-01/bayar', [
            'no_pesanan' => $order->no_pesanan,
            'metode_pembayaran' => 'bank_transfer',
            'kanal_pembayaran' => 'NotABank',
        ])->assertUnprocessable()->assertJsonValidationErrors('kanal_pembayaran');
    }

    public function test_valid_qris_payment_is_saved(): void
    {
        $order = $this->order($this->mejaSatu);
        $this->postJson('/menu/meja-01/bayar', [
            'no_pesanan' => $order->no_pesanan,
            'metode_pembayaran' => 'qris',
        ])->assertOk()->assertJsonPath('status', 'pending');
        $this->assertDatabaseHas('pembayaran', ['id_pesanan' => $order->id_pesanan, 'metode_pembayaran' => 'qris']);
    }

    private function user(string $role): User
    {
        return User::create([
            'nama' => ucfirst($role) . ' Uji',
            'username' => $role . '_' . bin2hex(random_bytes(3)),
            'password_hash' => Hash::make('password-uji'),
            'role' => $role,
            'status_aktif' => true,
        ]);
    }

    private function order(Meja $meja): Pesanan
    {
        return Pesanan::create([
            'id_meja' => $meja->id_meja,
            'no_pesanan' => 'ORD-' . bin2hex(random_bytes(6)),
            'status_pesanan' => 'baru',
            'total_harga' => 25000,
        ]);
    }
}
