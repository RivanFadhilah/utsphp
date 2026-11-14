/**
 * Sub-CPMK 7: CRUD Part 4 - Data Deletion & Confirmation Alerts
 * Memberikan notifikasi konfirmasi sebelum menghapus record secara permanen
 */
function confirmDeleteAbsensi(id, nama, tanggal) {
    const pesan = `PERINGATAN PENGHAPUSAN DATA!\n\nApakah Anda yakin ingin menghapus data absensi berikut secara permanen?\n- ID Absensi : #${id}\n- Nama       : ${nama}\n- Tanggal    : ${tanggal}\n\nData yang dihapus tidak dapat dikembalikan.`;
    return confirm(pesan);
}

function confirmDeleteAnggota(id, nama, nim) {
    const pesan = `PERINGATAN PENGHAPUSAN ANGGOTA!\n\nMenghapus anggota "${nama}" (${nim}) juga akan menghapus seluruh riwayat absensinya.\nApakah Anda yakin ingin melanjutkan?`;
    return confirm(pesan);
}

// Jam Digital Real-time pada Status Bar
document.addEventListener('DOMContentLoaded', () => {
    const clockEl = document.getElementById('live-clock');
    if (clockEl) {
        const updateClock = () => {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }) + ' WIB';
        };
        updateClock();
        setInterval(updateClock, 1000);
    }
});
