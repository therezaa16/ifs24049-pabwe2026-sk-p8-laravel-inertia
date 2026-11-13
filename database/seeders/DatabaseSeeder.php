<?php

namespace Database\Seeders;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        foreach ($this->samplePosts() as $index => $sample) {
            Post::firstOrCreate(
                ['slug' => $sample['slug']],
                [
                    'user_id' => $index % 3 === 0 ? $admin->id : $member->id,
                    'title' => $sample['title'],
                    'category' => $sample['category'],
                    'excerpt' => $sample['excerpt'],
                    'content' => $sample['content'],
                    'is_published' => $sample['is_published'],
                    'published_at' => $sample['is_published'] ? now()->subDays(count($this->samplePosts()) - $index) : null,
                ]
            );
        }
    }

    /**
     * Sample articles so the public gallery is never empty after seeding.
     *
     * @return array<int, array{slug: string, title: string, category: string, excerpt: string, content: string, is_published: bool}>
     */
    protected function samplePosts(): array
    {
        return [
            [
                'slug' => 'mengenal-arsitektur-feature-first-di-laravel',
                'title' => 'Mengenal Arsitektur Feature-First di Laravel',
                'category' => 'Teknologi',
                'excerpt' => 'Mengorganisir kode berdasarkan fitur, bukan jenis file, membuat aplikasi Laravel lebih mudah dirawat dan dikembangkan tim.',
                'content' => "## Mengapa Feature-First?\n\nPada struktur bawaan Laravel, controller, model, dan service tersebar di banyak folder. Saat aplikasi membesar, satu fitur sederhana bisa menyentuh lima direktori berbeda.\n\nDengan pendekatan **feature-first**, seluruh berkas yang berkaitan dengan satu fitur dikumpulkan dalam satu folder.\n\n## Komponen Utama\n\n- **Controller** menerima request dan mengembalikan response Inertia.\n- **Service** menyimpan logika bisnis.\n- **Repository** mengisolasi akses data melalui interface.\n- **Policy** memastikan otorisasi di tingkat model.\n\n> Kode yang berdekatan secara logika sebaiknya berdekatan pula secara fisik.\n\nHasilnya, anggota tim baru dapat menemukan seluruh kode sebuah fitur hanya dengan membuka satu folder.",
                'is_published' => true,
            ],
            [
                'slug' => 'tips-belajar-react-untuk-pemula',
                'title' => 'Tips Belajar React untuk Pemula',
                'category' => 'Pendidikan',
                'excerpt' => 'Panduan singkat menyusun jalur belajar React dari konsep komponen sampai state management tanpa kewalahan.',
                'content' => "## Mulai dari Dasar\n\nSebelum menyentuh library tambahan, pahami dulu tiga hal: **komponen**, **props**, dan **state**.\n\n1. Buat komponen kecil yang menampilkan data statis.\n2. Teruskan data lewat props.\n3. Tambahkan interaksi menggunakan `useState`.\n\n## Latihan yang Efektif\n\nBangun proyek kecil seperti daftar tugas atau blog sederhana. Proyek nyata memaksa Anda menghadapi masalah yang tidak muncul di tutorial.\n\nJangan lupa membaca dokumentasi resmi di react.dev, karena contohnya selalu diperbarui mengikuti praktik terbaik terbaru.",
                'is_published' => true,
            ],
            [
                'slug' => 'membangun-rutinitas-pagi-yang-produktif',
                'title' => 'Membangun Rutinitas Pagi yang Produktif',
                'category' => 'Gaya Hidup',
                'excerpt' => 'Beberapa kebiasaan kecil di pagi hari yang terbukti membantu menjaga fokus sepanjang hari.',
                'content' => "## Mulai Lebih Tenang\n\nProduktivitas bukan soal bangun sangat pagi, melainkan memulai hari dengan niat yang jelas.\n\n### Kebiasaan yang Bisa Dicoba\n\n- Minum air putih sebelum membuka ponsel.\n- Tulis tiga prioritas utama hari ini.\n- Bergerak ringan selama sepuluh menit.\n\nPilih satu kebiasaan, jalankan selama dua minggu, lalu tambahkan yang berikutnya. Perubahan kecil yang konsisten lebih bertahan daripada perubahan besar yang cepat ditinggalkan.",
                'is_published' => true,
            ],
            [
                'slug' => 'strategi-memulai-bisnis-digital-dengan-modal-kecil',
                'title' => 'Strategi Memulai Bisnis Digital dengan Modal Kecil',
                'category' => 'Bisnis',
                'excerpt' => 'Validasi ide dulu, bangun produk minimum, lalu kembangkan berdasarkan umpan balik pelanggan nyata.',
                'content' => "## Validasi Sebelum Investasi\n\nBanyak usaha gagal bukan karena kurang modal, tetapi karena membangun sesuatu yang tidak dibutuhkan pasar.\n\n## Langkah Praktis\n\n1. Tentukan masalah spesifik yang ingin diselesaikan.\n2. Tawarkan solusi sederhana kepada sepuluh calon pelanggan.\n3. Ukur minat mereka sebelum membangun fitur lengkap.\n\nProduk minimum yang layak (*minimum viable product*) membantu Anda belajar lebih cepat dengan biaya yang jauh lebih kecil.",
                'is_published' => true,
            ],
            [
                'slug' => 'mengapa-menulis-itu-cara-terbaik-untuk-berpikir',
                'title' => 'Mengapa Menulis Adalah Cara Terbaik untuk Berpikir',
                'category' => 'Opini',
                'excerpt' => 'Menulis memaksa kita merapikan gagasan yang samar menjadi argumen yang bisa diperiksa dan diperdebatkan.',
                'content' => "## Gagasan yang Samar\n\nKita sering merasa paham, sampai harus menjelaskannya secara tertulis. Di titik itulah celah pemahaman terlihat.\n\nMenulis memaksa kita memilih kata, menyusun urutan, dan menguji apakah satu kalimat benar-benar mengikuti kalimat sebelumnya.\n\n> Jika Anda tidak bisa menuliskannya dengan jelas, kemungkinan Anda belum memahaminya sepenuhnya.\n\nKarena itu, menulis blog bukan hanya soal berbagi, tetapi juga latihan berpikir yang jujur.",
                'is_published' => true,
            ],
            [
                'slug' => 'selamat-datang-di-blogverse-pro',
                'title' => 'Selamat Datang di BlogVerse Pro',
                'category' => 'Umum',
                'excerpt' => 'Platform publikasi artikel tempat siapa pun dapat membaca gratis dan penulis dapat berbagi gagasan.',
                'content' => "## Tentang BlogVerse Pro\n\nBlogVerse Pro adalah platform blog multi-tingkat. Pengunjung dapat membaca artikel tanpa akun, sedangkan penulis memiliki dashboard untuk mengelola tulisan mereka.\n\n## Fitur Penulis\n\n- Editor Markdown dengan pratinjau.\n- Foto sampul untuk setiap artikel.\n- Ekspor dan impor artikel melalui Excel.\n- Bantuan AI untuk merancang draf artikel.\n\nDaftar sekarang dan mulai menulis artikel pertama Anda.",
                'is_published' => true,
            ],
            [
                'slug' => 'draf-catatan-eksperimen-ai-untuk-penulis',
                'title' => 'Draf: Catatan Eksperimen AI untuk Penulis',
                'category' => 'Teknologi',
                'excerpt' => 'Draf internal yang belum dipublikasikan dan tidak tampil di halaman publik.',
                'content' => "## Catatan Awal\n\nDraf ini sengaja dibiarkan belum dipublikasikan untuk menguji status **Draf** pada dashboard penulis.",
                'is_published' => false,
            ],
        ];
    }
}
