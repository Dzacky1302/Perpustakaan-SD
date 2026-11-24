/** Tahun ajaran berjalan: Juli–Desember = 2026/2027, Januari–Juni = 2025/2026. */
export function academicYear(date = new Date()) {
    const year = date.getFullYear();
    const month = date.getMonth() + 1;

    return month >= 7 ? `${year}/${year + 1}` : `${year - 1}/${year}`;
}
