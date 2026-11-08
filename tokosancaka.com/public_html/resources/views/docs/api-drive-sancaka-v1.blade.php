@extends('layouts.app')

@section('title', 'Dokumentasi API Driver Sancaka V1')

@section('meta')
    <meta name="description" content="Dokumentasi integrasi API Google Drive Sancaka V1 untuk upload file dari aplikasi dan server eksternal.">
    <meta name="robots" content="index,follow">
@endsection

@push('styles')
<style>
    .drive-docs {
        --docs-ink: #173042;
        --docs-muted: #637381;
        --docs-line: #dce5e9;
        --docs-paper: #f4f7f8;
        --docs-accent: #e87524;
        color: var(--docs-ink);
        background: #fff;
    }
    .drive-docs .docs-hero {
        background: var(--docs-paper);
        border-bottom: 1px solid var(--docs-line);
        padding: 3.25rem 0 2.75rem;
    }
    .drive-docs .docs-kicker {
        color: #a64c12;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .drive-docs .docs-hero h1 {
        max-width: 760px;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 700;
        line-height: 1.15;
    }
    .drive-docs .docs-hero p {
        max-width: 740px;
        color: #516371;
        font-size: 1.05rem;
    }
    .drive-docs .docs-endpoint {
        border-left: 4px solid var(--docs-accent);
        background: #fff;
    }
    .drive-docs .docs-method {
        color: #0b6848;
        background: #e6f4ee;
        font-size: .76rem;
        font-weight: 700;
    }
    .drive-docs .docs-layout {
        padding-top: 2rem;
        padding-bottom: 5rem;
    }
    .drive-docs .docs-toc {
        position: sticky;
        top: 90px;
    }
    .drive-docs .docs-toc a {
        display: block;
        padding: .45rem .7rem;
        border-left: 2px solid transparent;
        color: var(--docs-muted);
        font-size: .9rem;
        text-decoration: none;
    }
    .drive-docs .docs-toc a:hover,
    .drive-docs .docs-toc a:focus {
        border-left-color: var(--docs-accent);
        color: var(--docs-ink);
    }
    .drive-docs .docs-section {
        padding: 0 0 2.4rem;
        margin-bottom: 2.4rem;
        border-bottom: 1px solid var(--docs-line);
        scroll-margin-top: 90px;
    }
    .drive-docs .docs-section h2 {
        margin-bottom: 1rem;
        font-size: 1.45rem;
        font-weight: 700;
    }
    .drive-docs .docs-section h3 {
        margin-top: 1.5rem;
        margin-bottom: .65rem;
        font-size: 1.05rem;
        font-weight: 700;
    }
    .drive-docs .docs-section p,
    .drive-docs .docs-section li {
        color: #435564;
        line-height: 1.75;
    }
    .drive-docs .docs-code {
        overflow: auto;
        padding: 1rem 1.1rem;
        border: 1px solid #263d4b;
        border-radius: .35rem;
        background: #132733;
        color: #e8f0f3;
        font-size: .86rem;
        line-height: 1.65;
        white-space: pre;
    }
    .drive-docs .docs-code code {
        color: inherit;
    }
    .drive-docs .docs-inline-code {
        padding: .12rem .35rem;
        border-radius: .2rem;
        background: #eef2f4;
        color: #8c4316;
        font-size: .9em;
    }
    .drive-docs .docs-alert {
        border-left: 4px solid #d88a21;
        background: #fff8e9;
        color: #654516;
    }
    .drive-docs .docs-table th {
        color: #344b5a;
        font-size: .85rem;
        white-space: nowrap;
    }
    .drive-docs .docs-table td {
        color: #435564;
        vertical-align: top;
    }
    @media (max-width: 991.98px) {
        .drive-docs .docs-toc {
            position: static;
            margin-bottom: 2rem;
        }
        .drive-docs .docs-hero {
            padding-top: 2.25rem;
        }
    }
</style>
@endpush

@section('content')
<main class="drive-docs">
    <header class="docs-hero">
        <div class="container">
            <div class="docs-kicker mb-2">Sancaka API / V1</div>
            <h1 class="mb-3">Dokumentasi API Driver Sancaka</h1>
            <p class="mb-4">Panduan integrasi upload file ke penyimpanan Google Drive Sancaka dari aplikasi atau server eksternal.</p>
            <div class="docs-endpoint rounded-1 shadow-sm p-3 p-md-4">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="docs-method rounded-1 px-2 py-1">POST</span>
                    <span class="text-secondary small">Endpoint upload</span>
                </div>
                <code class="fs-6 text-break">https://tokosancaka.com/api/v1/drive/softlink</code>
            </div>
        </div>
    </header>

    <div class="container docs-layout">
        <div class="row g-4 g-xl-5">
            <aside class="col-lg-3">
                <nav class="docs-toc" aria-label="Daftar isi dokumentasi">
                    <div class="small fw-bold text-uppercase text-secondary mb-2">Di halaman ini</div>
                    <a href="#ringkasan">Ringkasan</a>
                    <a href="#request">Request dan payload</a>
                    <a href="#curl">Contoh cURL</a>
                    <a href="#integrasi">Contoh integrasi</a>
                    <a href="#response">Response</a>
                    <a href="#errors">Error dan troubleshooting</a>
                    <a href="#keamanan">Keamanan dan batasan</a>
                </nav>
            </aside>

            <article class="col-lg-9">
                <section class="docs-section" id="ringkasan">
                    <h2>Ringkasan</h2>
                    <p>API menerima satu file melalui <span class="docs-inline-code">multipart/form-data</span>, menyimpannya ke folder Drive yang dikonfigurasi untuk layanan Sancaka, lalu mengembalikan metadata file dalam JSON.</p>
                    <div class="table-responsive">
                        <table class="table docs-table table-bordered mb-0">
                            <tbody>
                                <tr><th scope="row">Base URL</th><td><code>https://tokosancaka.com</code></td></tr>
                                <tr><th scope="row">Method</th><td><code>POST</code></td></tr>
                                <tr><th scope="row">Path</th><td><code>/api/v1/drive/softlink</code></td></tr>
                                <tr><th scope="row">Content-Type</th><td><code>multipart/form-data</code></td></tr>
                                <tr><th scope="row">Field wajib</th><td><code>file</code>, satu berkas</td></tr>
                                <tr><th scope="row">Autentikasi</th><td>Belum diwajibkan oleh endpoint saat ini.</td></tr>
                                <tr><th scope="row">Batas aplikasi</th><td>Maksimum 20.480 KB per file; batas PHP/server yang lebih kecil tetap berlaku.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="docs-section" id="request">
                    <h2>Request dan payload</h2>
                    <p>Kirim request HTTPS ke endpoint di atas. Gunakan form multipart dengan field bernama <code>file</code>. Jangan mengirim JSON atau Base64, dan jangan mengatur header <code>Content-Type</code> multipart secara manual; biarkan library HTTP membuat boundary-nya.</p>
                    <div class="table-responsive">
                        <table class="table docs-table table-bordered mb-0">
                            <thead><tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr></thead>
                            <tbody><tr><td><code>file</code></td><td>Binary file</td><td>Ya</td><td>Satu file, maksimum 20.480 KB pada validasi aplikasi.</td></tr></tbody>
                        </table>
                    </div>
                    <p class="small mt-3 mb-0">Nama file di Drive diberi awalan waktu dan karakter nama asli yang tidak didukung diganti dengan garis bawah.</p>
                </section>

                <section class="docs-section" id="curl">
                    <h2>Contoh cURL</h2>
                    <p>Pastikan file tersedia di server pemanggil. cURL akan mengirimnya sebagai multipart upload.</p>
                    <pre class="docs-code"><code>curl --request POST \
  --url https://tokosancaka.com/api/v1/drive/softlink \
  --header 'Accept: application/json' \
  --form 'file=@/path/to/document.pdf'</code></pre>
                    <p>Untuk melihat status HTTP dan response body:</p>
                    <pre class="docs-code"><code>curl --silent --show-error \
  --write-out '\nHTTP %{http_code}\n' \
  --request POST \
  --url https://tokosancaka.com/api/v1/drive/softlink \
  --header 'Accept: application/json' \
  --form 'file=@/path/to/document.pdf'</code></pre>
                </section>

                <section class="docs-section" id="integrasi">
                    <h2>Contoh integrasi</h2>
                    <h3>PHP cURL</h3>
                    <pre class="docs-code"><code>$endpoint = 'https://tokosancaka.com/api/v1/drive/softlink';
$filePath = '/path/to/document.pdf';

$curl = curl_init($endpoint);
curl_setopt_array($curl, [
    CURLOPT_POST =&gt; true,
    CURLOPT_RETURNTRANSFER =&gt; true,
    CURLOPT_TIMEOUT =&gt; 120,
    CURLOPT_HTTPHEADER =&gt; ['Accept: application/json'],
    CURLOPT_POSTFIELDS =&gt; [
        'file' =&gt; new CURLFile($filePath),
    ],
]);

$body = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

if ($body === false) {
    throw new RuntimeException('Request gagal: ' . $error);
}

$result = json_decode($body, true);
if ($status !== 200 || ($result['status'] ?? null) !== 'success') {
    throw new RuntimeException('Upload gagal. HTTP ' . $status . ': ' . $body);
}

echo $result['data']['file_name'];</code></pre>

                    <h3>Python requests</h3>
                    <pre class="docs-code"><code>import requests

endpoint = "https://tokosancaka.com/api/v1/drive/softlink"
with open("document.pdf", "rb") as file:
    response = requests.post(
        endpoint,
        headers={"Accept": "application/json"},
        files={"file": ("document.pdf", file, "application/pdf")},
        timeout=120,
    )

response.raise_for_status()
result = response.json()
if result.get("status") != "success":
    raise RuntimeError(result)

print(result["data"]["file_name"])</code></pre>

                    <h3>Node.js 18+</h3>
                    <pre class="docs-code"><code>import { readFile } from "node:fs/promises";

const endpoint = "https://tokosancaka.com/api/v1/drive/softlink";
const bytes = await readFile("document.pdf");
const form = new FormData();
form.append(
  "file",
  new Blob([bytes], { type: "application/pdf" }),
  "document.pdf",
);

const response = await fetch(endpoint, {
  method: "POST",
  headers: { Accept: "application/json" },
  body: form,
});
const result = await response.json();

if (!response.ok || result.status !== "success") {
  throw new Error(JSON.stringify(result));
}

console.log(result.data.file_name);</code></pre>
                </section>

                <section class="docs-section" id="response">
                    <h2>Response</h2>
                    <h3>Upload berhasil</h3>
                    <pre class="docs-code"><code>{
  "status": "success",
  "message": "File berhasil diupload ke server Google Drive Sancaka.",
  "data": {
    "file_name": "1794083383_document.pdf",
    "original_name": "document.pdf",
    "mime_type": "application/pdf",
    "size_bytes": 24576,
    "softlink_url": ""
  }
}</code></pre>
                    <div class="docs-alert rounded-1 p-3 mt-3">
                        <strong>Catatan softlink:</strong> upload dapat berstatus sukses walaupun <code>softlink_url</code> kosong. Saat ini gunakan <code>status</code> dan metadata file sebagai konfirmasi upload; jangan mengasumsikan URL selalu tersedia.
                    </div>
                    <h3>Validasi gagal</h3>
                    <pre class="docs-code"><code>{
  "status": "error",
  "message": "Validasi file gagal.",
  "errors": {
    "file": ["The file field is required."]
  }
}</code></pre>
                </section>

                <section class="docs-section" id="errors">
                    <h2>Error dan troubleshooting</h2>
                    <div class="table-responsive">
                        <table class="table docs-table table-bordered">
                            <thead><tr><th>HTTP</th><th>Makna</th><th>Tindakan</th></tr></thead>
                            <tbody>
                                <tr><td><code>200</code></td><td>File berhasil diunggah. Periksa <code>status</code> dan <code>data</code>.</td><td>Simpan <code>file_name</code> dan metadata response.</td></tr>
                                <tr><td><code>422</code></td><td>Field file tidak ada atau gagal validasi, termasuk batas ukuran.</td><td>Kirim ulang sebagai multipart dengan field <code>file</code> dan ukuran sesuai batas.</td></tr>
                                <tr><td><code>500</code></td><td>Gangguan saat otorisasi atau komunikasi dengan Google Drive.</td><td>Catat waktu dan HTTP response; hubungi administrator Sancaka. Jangan retry tanpa batas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="small mb-0">Ukuran upload efektif juga dibatasi oleh konfigurasi PHP dan web server. Jika koneksi terputus, periksa status file sebelum mengulang agar tidak membuat duplikat.</p>
                </section>

                <section class="docs-section border-0 mb-0 pb-0" id="keamanan">
                    <h2>Keamanan dan batasan</h2>
                    <div class="docs-alert rounded-1 p-3 mb-3">
                        <strong>Endpoint saat ini publik tanpa API key.</strong> Siapa pun yang mengetahui URL dapat mencoba mengunggah file hingga batas aplikasi. Untuk integrasi terbatas pada server mitra, minta API key atau pembatasan IP sebelum mengirim data sensitif.
                    </div>
                    <ul class="mb-0">
                        <li>Gunakan HTTPS dan kirim hanya file yang memang boleh disimpan di Drive Sancaka.</li>
                        <li>Jangan mengirim password, access token, atau credential Google melalui payload.</li>
                        <li>Jangan menganggap <code>softlink_url</code> sebagai tautan publik yang siap dibagikan jika nilainya kosong.</li>
                        <li>Respons error dapat memuat detail teknis. Simpan log integrasi di server Anda dan jangan tampilkan detail internal kepada pengguna akhir.</li>
                    </ul>
                </section>
            </article>
        </div>
    </div>
</main>
@endsection
