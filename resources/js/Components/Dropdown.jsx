import { Transition } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { cloneElement, createContext, isValidElement, useContext, useEffect, useId, useRef, useState } from 'react';

const DropDownContext = createContext();

const Dropdown = ({ children }) => {
    const [open, setOpen] = useState(false);
    const contentId = useId();
    const wrapperRef = useRef(null);

    const toggleOpen = () => {
        setOpen((previousState) => !previousState);
    };

    // Esc menutup menu dan mengembalikan fokus ke tombol pemicunya.
    // Tanpa ini, pengguna keyboard hanya bisa keluar lewat Tab membabi-buta.
    useEffect(() => {
        if (!open) return;

        const onKeyDown = (event) => {
            if (event.key !== 'Escape') return;

            setOpen(false);
            wrapperRef.current?.querySelector('button, a, input, [tabindex]')?.focus();
        };

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open]);

    return (
        <DropDownContext.Provider value={{ open, setOpen, toggleOpen, contentId, wrapperRef }}>
            <div className="relative">{children}</div>
        </DropDownContext.Provider>
    );
};

const Trigger = ({ children }) => {
    const { open, toggleOpen, contentId, wrapperRef } = useContext(DropDownContext);

    // Pemicu sudah berupa <button> asli di halaman pemanggil, jadi status
    // terbuka tinggal ditempel ke tombol itu — tidak perlu (dan tidak boleh)
    // membungkusnya dengan tombol kedua.
    const trigger = isValidElement(children)
        ? cloneElement(children, {
              'aria-expanded': open,
              'aria-controls': open ? contentId : undefined,
          })
        : children;

    return (
        <>
            <div ref={wrapperRef} onClick={toggleOpen}>
                {trigger}
            </div>

            {open && (
                <div
                    aria-hidden="true"
                    className="fixed inset-0 z-40"
                    onClick={() => setOpen(false)}
                ></div>
            )}
        </>
    );
};

const Content = ({
    align = 'right',
    width = '48',
    contentClasses = 'py-1 bg-white',
    children,
}) => {
    const { open, setOpen, contentId } = useContext(DropDownContext);

    let alignmentClasses = 'origin-top';

    if (align === 'left') {
        alignmentClasses = 'ltr:origin-top-left rtl:origin-top-right start-0';
    } else if (align === 'right') {
        alignmentClasses = 'ltr:origin-top-right rtl:origin-top-left end-0';
    }

    let widthClasses = '';

    if (width === '48') {
        widthClasses = 'w-48';
    }

    return (
        <>
            <Transition
                show={open}
                enter="transition ease-out duration-200"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="transition ease-in duration-75"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
            >
                <div
                    id={contentId}
                    className={`absolute z-50 mt-2 rounded-xl border border-slate-200 bg-white shadow-pop ${alignmentClasses} ${widthClasses}`}
                    onClick={() => setOpen(false)}
                >
                    <div
                        className={
                            `rounded-xl ` +
                            contentClasses
                        }
                    >
                        {children}
                    </div>
                </div>
            </Transition>
        </>
    );
};

const DropdownLink = ({ className = '', children, ...props }) => {
    return (
        <Link
            {...props}
            className={
                'block w-full px-4 py-2 text-start text-sm leading-5 text-slate-700 transition duration-150 ease-in-out hover:bg-slate-100 focus:bg-slate-100 focus:outline-none ' +
                className
            }
        >
            {children}
        </Link>
    );
};

Dropdown.Trigger = Trigger;
Dropdown.Content = Content;
Dropdown.Link = DropdownLink;

export default Dropdown;
