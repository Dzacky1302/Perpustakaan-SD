import { usePage } from '@inertiajs/react';

/**
 * True bila pengguna boleh menambah / mengubah / menghapus data.
 * Kepala sekolah (kepsek) hanya boleh membaca.
 */
export function useCanManage() {
    const { auth } = usePage().props;

    return auth?.can_manage !== false;
}

/**
 * Membungkus tombol aksi tulis. Kalau pengguna read-only
 * (kepsek), isinya tidak dirender sama sekali.
 */
export function CanManage({ children, fallback = null }) {
    return useCanManage() ? children : fallback;
}

export default useCanManage;
