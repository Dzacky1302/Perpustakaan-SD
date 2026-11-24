import { useEffect, useId, useRef } from 'react';

const MAX_WIDTHS = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};

/**
 * Deteksi dukungan exit animasi top layer.
 *
 * Firefox menyatakan mendukung `transition-behavior` lewat CSS.supports()
 * padahal transisi `display`-nya tidak jalan, jadi dites dengan probe nyata:
 * elemen sementara yang display-nya di-set none saat transisi berjalan.
 */
let displayTransitionSupport;
function supportsTopLayerExit() {
    if (!window.CSS?.supports?.('overlay', 'auto')) return false;
    if (displayTransitionSupport !== undefined) return displayTransitionSupport;
    if (!document.body || !window.CSS?.supports?.('transition-behavior', 'allow-discrete')) {
        return false;
    }

    const probe = document.createElement('div');
    // Shorthand disengaja: browser yang tidak mengenali allow-discrete akan
    // menjatuhkan seluruh deklarasi, sehingga display langsung none dan probe
    // dengan benar melaporkan false.
    probe.style.cssText = 'transition: display 1s allow-discrete !important; display: block;';
    document.body.appendChild(probe);
    getComputedStyle(probe).display;
    probe.style.display = 'none';
    displayTransitionSupport = getComputedStyle(probe).display === 'block';
    probe.remove();

    return displayTransitionSupport;
}

/**
 * Modal berbasis `<dialog>` native.
 *
 * Browser yang menyediakan: halaman jadi inert, fokus terkunci, top layer,
 * ::backdrop, tutup dengan Esc / gestur back, dan pengembalian fokus ke
 * pemicu. Karena itu komponen ini tidak lagi menulis focus trap, handler Esc,
 * atau backdrop sendiri seperti versi Headless UI sebelumnya.
 */
export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}) {
    const dialogRef = useRef(null);
    const onCloseRef = useRef(onClose);
    const closeableRef = useRef(closeable);
    const titleId = `${useId()}-title`;

    onCloseRef.current = onClose;
    closeableRef.current = closeable;

    /** Tutup sambil menghormati animasi keluar (lihat panduan top layer). */
    const closeAnimated = async () => {
        const dialog = dialogRef.current;
        if (!dialog || !dialog.open) return;

        if (!supportsTopLayerExit()) {
            dialog.setAttribute('data-closing', '');

            const animations = [
                ...(dialog.getAnimations?.({ subtree: true }) ?? []),
                ...(dialog.getAnimations?.({ pseudoElement: '::backdrop' }) ?? []),
            ];

            if (animations.length > 0) {
                await Promise.race([
                    Promise.allSettled(animations.map((animation) => animation.finished)),
                    new Promise((resolve) => setTimeout(resolve, 2000)),
                ]);
            }

            // Dibuka lagi selama animasi keluar: batalkan, jangan ditutup.
            if (!dialog.hasAttribute('data-closing')) return;
            dialog.removeAttribute('data-closing');
        }

        dialog.close();
    };

    // 1. Status terbuka selalu mengikuti prop `show`.
    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) return;

        if (show) {
            dialog.removeAttribute('data-closing');
            if (!dialog.open) dialog.showModal();
        } else if (dialog.open) {
            void closeAnimated();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show]);

    // 2. Kabari induk setiap kali dialog benar-benar tertutup (Esc, klik
    //    di luar, tombol, maupun perubahan `show`).
    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) return;

        const handleClose = () => onCloseRef.current();
        const handleCancel = (event) => {
            if (!closeableRef.current) event.preventDefault();
        };

        dialog.addEventListener('close', handleClose);
        dialog.addEventListener('cancel', handleCancel);

        return () => {
            dialog.removeEventListener('close', handleClose);
            dialog.removeEventListener('cancel', handleCancel);
        };
    }, []);

    // 3. Klik di luar panel untuk browser yang belum mengenal `closedby`
    //    (Safari & Firefox). Tanda tekan diperiksa di pointerdown dan
    //    pointerup supaya drag teks yang berakhir di backdrop tidak ikut
    //    menutup modal.
    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog || 'closedBy' in HTMLDialogElement.prototype) return;

        const isOutside = (event) => {
            const rect = dialog.getBoundingClientRect();
            return (
                event.clientX < rect.left ||
                event.clientX > rect.right ||
                event.clientY < rect.top ||
                event.clientY > rect.bottom
            );
        };

        let pressedOutside = false;
        const handleDown = (event) => {
            pressedOutside = isOutside(event);
        };
        const handleUp = (event) => {
            if (pressedOutside && isOutside(event) && closeableRef.current) {
                void closeAnimated();
            }
        };

        dialog.addEventListener('pointerdown', handleDown);
        dialog.addEventListener('pointerup', handleUp);

        return () => {
            dialog.removeEventListener('pointerdown', handleDown);
            dialog.removeEventListener('pointerup', handleUp);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // 4. Nama dialog otomatis diambil dari heading pertama di dalamnya.
    //    Dilakukan setiap render supaya judul yang berganti (mis. "Tambah"
    //    vs "Ubah") tetap ikut berubah.
    useEffect(() => {
        const dialog = dialogRef.current;
        if (!dialog) return;

        const heading = dialog.querySelector('h1, h2, h3, h4');

        if (heading) {
            if (!heading.id) heading.id = titleId;
            dialog.setAttribute('aria-labelledby', heading.id);
        } else {
            dialog.removeAttribute('aria-labelledby');
        }
    });

    return (
        <dialog
            ref={dialogRef}
            closedby={closeable ? 'any' : 'none'}
            className={`modal-dialog m-auto w-[calc(100%-2rem)] sm:w-full ${
                MAX_WIDTHS[maxWidth] ?? MAX_WIDTHS['2xl']
            }`}
        >
            {children}
        </dialog>
    );
}
