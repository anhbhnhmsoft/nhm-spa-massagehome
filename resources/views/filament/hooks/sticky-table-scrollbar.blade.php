<style>
    /* 1. Thanh cuộn ngang tinh tế trên container bảng Filament */
    .fi-ta-content-ctn,
    .fi-ta-content {
        overflow-x: auto !important;
        scrollbar-width: thin !important;
        scrollbar-color: #cbd5e1 #f1f5f9 !important;
    }

    .fi-ta-content-ctn::-webkit-scrollbar,
    .fi-ta-content::-webkit-scrollbar {
        height: 10px !important;
        width: 10px !important;
        display: block !important;
    }

    .fi-ta-content-ctn::-webkit-scrollbar-track,
    .fi-ta-content::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
        border-radius: 6px !important;
    }

    .fi-ta-content-ctn::-webkit-scrollbar-thumb,
    .fi-ta-content::-webkit-scrollbar-thumb {
        background: #cbd5e1 !important;
        border-radius: 6px !important;
        min-width: 60px !important;
    }

    .fi-ta-content-ctn::-webkit-scrollbar-thumb:hover,
    .fi-ta-content::-webkit-scrollbar-thumb:hover {
        background: #94a3b8 !important;
    }

    /* Dark mode */
    .dark .fi-ta-content-ctn,
    .dark .fi-ta-content {
        scrollbar-color: #475569 #1e293b !important;
    }

    .dark .fi-ta-content-ctn::-webkit-scrollbar-track,
    .dark .fi-ta-content::-webkit-scrollbar-track {
        background: #1e293b !important;
    }

    .dark .fi-ta-content-ctn::-webkit-scrollbar-thumb,
    .dark .fi-ta-content::-webkit-scrollbar-thumb {
        background: #475569 !important;
    }

    .dark .fi-ta-content-ctn::-webkit-scrollbar-thumb:hover,
    .dark .fi-ta-content::-webkit-scrollbar-thumb:hover {
        background: #64748b !important;
    }

    /* 2. Thanh cuộn nổi cố định ở mép dưới màn hình (Sticky Floating Scrollbar) */
    .fi-sticky-table-scrollbar {
        position: fixed !important;
        bottom: 0 !important;
        height: 12px !important;
        overflow-x: scroll !important;
        overflow-y: hidden !important;
        z-index: 40 !important;
        display: none;
        background: rgba(248, 250, 252, 0.95) !important;
        backdrop-filter: blur(4px) !important;
        border-top: 1px solid #e2e8f0 !important;
        box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.05) !important;
        scrollbar-width: thin !important;
        scrollbar-color: #cbd5e1 #f1f5f9 !important;
    }

    .fi-sticky-table-scrollbar::-webkit-scrollbar {
        height: 10px !important;
        display: block !important;
    }

    .fi-sticky-table-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9 !important;
    }

    .fi-sticky-table-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1 !important;
        border-radius: 6px !important;
    }

    .fi-sticky-table-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8 !important;
    }

    .dark .fi-sticky-table-scrollbar {
        background: rgba(15, 23, 42, 0.95) !important;
        border-top-color: #334155 !important;
        scrollbar-color: #475569 #1e293b !important;
    }

    .dark .fi-sticky-table-scrollbar::-webkit-scrollbar-track {
        background: #1e293b !important;
    }

    .dark .fi-sticky-table-scrollbar::-webkit-scrollbar-thumb {
        background: #475569 !important;
    }

    .dark .fi-sticky-table-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #64748b !important;
    }
</style>

<script>
    (function () {
        function initStickyScrollbars() {
            const containers = document.querySelectorAll('.fi-ta-content-ctn, .fi-ta-content');
            containers.forEach((container) => {
                if (container.dataset.stickyScrollbarReady === 'true') {
                    if (container._updateStickyScrollbar) {
                        container._updateStickyScrollbar();
                    }
                    return;
                }

                container.dataset.stickyScrollbarReady = 'true';

                const floatingBar = document.createElement('div');
                floatingBar.className = 'fi-sticky-table-scrollbar';
                floatingBar.setAttribute('aria-hidden', 'true');

                const dummyInner = document.createElement('div');
                dummyInner.style.height = '1px';
                floatingBar.appendChild(dummyInner);
                document.body.appendChild(floatingBar);

                let isSyncingFloating = false;
                let isSyncingContainer = false;

                floatingBar.addEventListener('scroll', function () {
                    if (!isSyncingContainer) {
                        isSyncingFloating = true;
                        container.scrollLeft = floatingBar.scrollLeft;
                        isSyncingFloating = false;
                    }
                });

                container.addEventListener('scroll', function () {
                    if (!isSyncingFloating) {
                        isSyncingContainer = true;
                        floatingBar.scrollLeft = container.scrollLeft;
                        isSyncingContainer = false;
                    }
                });

                const update = function () {
                    if (!document.body.contains(container)) {
                        floatingBar.remove();
                        window.removeEventListener('scroll', update);
                        window.removeEventListener('resize', update);
                        return;
                    }

                    const rect = container.getBoundingClientRect();
                    const isOverflowing = container.scrollWidth > container.clientWidth + 5;
                    const isBottomOutOfView = rect.bottom > window.innerHeight;
                    const isTopInView = rect.top < window.innerHeight - 60;

                    if (isOverflowing && isBottomOutOfView && isTopInView) {
                        floatingBar.style.display = 'block';
                        floatingBar.style.left = Math.max(0, rect.left) + 'px';
                        floatingBar.style.width = rect.width + 'px';
                        dummyInner.style.width = container.scrollWidth + 'px';
                        if (Math.abs(floatingBar.scrollLeft - container.scrollLeft) > 1) {
                            floatingBar.scrollLeft = container.scrollLeft;
                        }
                    } else {
                        floatingBar.style.display = 'none';
                    }
                };

                container._updateStickyScrollbar = update;

                window.addEventListener('scroll', update, { passive: true });
                window.addEventListener('resize', update, { passive: true });

                if (window.ResizeObserver) {
                    new ResizeObserver(update).observe(container);
                }

                // Chạy cập nhật ban đầu
                setTimeout(update, 100);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initStickyScrollbars);
        } else {
            initStickyScrollbars();
        }

        document.addEventListener('livewire:initialized', function () {
            initStickyScrollbars();
            if (window.Livewire) {
                Livewire.hook('morph.updated', function () {
                    setTimeout(initStickyScrollbars, 100);
                });
                Livewire.hook('commit', function () {
                    setTimeout(initStickyScrollbars, 150);
                });
            }
        });

        // Bắt thêm sau khi toàn bộ window load xong
        window.addEventListener('load', initStickyScrollbars);
    })();
</script>
