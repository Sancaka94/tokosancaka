@component('mail::message')
# Rekapan Notifikasi Harian

Halo Admin,

Berikut adalah rekapan aktivitas yang terjadi dalam 24 jam terakhir di platform Anda.

@component('mail::table')
| Waktu | Notifikasi |
|:-----------|:----------------|
@foreach ($notifications as $notification)
| {{ $notification->created_at->format('H:i') }} | **{{ $notification->data['title'] ?? 'Update' }}:** {{ $notification->data['message'] ?? 'Tidak ada detail.' }} |
@endforeach
@endcomponent

Silakan login ke dashboard untuk melihat detail lebih lanjut.

@component('mail::button', ['url' => url('/admin/dashboard')])
Buka Dashboard
@endcomponent

Terima kasih,<br>
Sistem {{ config('app.name') }}
@endcomponent
