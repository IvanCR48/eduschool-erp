/**
 * Sistema Responsive - JavaScript para interacciones móviles
 * Maneja navegación, tablas responsive y mejoras UX
 */

class ResponsiveSystem {
    constructor() {
        this.init();
    }

    init() {
        this.setupStickyHeaderHeightCompensation();
        this.setupMobileNavigation();
        this.setupResponsiveTables();
        this.setupTouchInteractions();
        this.setupAccessibility();
        this.setupPerformanceOptimizations();
    }

    /**
     * Compensar la altura del header sticky para el posicionamiento de la barra lateral (sidebar)
     */
    setupStickyHeaderHeightCompensation() {
        const updateHeight = () => {
            const header = document.querySelector('.main-header');
            if (header) {
                document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
            }
        };

        // Ejecutar inmediatamente
        updateHeight();

        // Escuchar eventos para mantener el valor actualizado
        window.addEventListener('resize', updateHeight);
        window.addEventListener('load', updateHeight);
        
        // También observar posibles cambios de contenido/dimensiones del header
        if (window.ResizeObserver) {
            const header = document.querySelector('.main-header');
            if (header) {
                const observer = new ResizeObserver(() => updateHeight());
                observer.observe(header);
            }
        }
    }

    /**
     * Configurar navegación móvil
     */
    setupMobileNavigation() {
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('.main-nav');
        const sidebar = document.getElementById('sidebar');

        /* El menú real es #sidebar; .main-nav está oculto en layout actual — evitar doble listener en la hamburguesa */
        if (hamburger && sidebar && nav && getComputedStyle(nav).display === 'none') {
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape' || !sidebar.classList.contains('open')) return;
                sidebar.classList.remove('open');
                if (typeof window.__saSyncSidebarDrawer === 'function') {
                    window.__saSyncSidebarDrawer();
                }
            });
            return;
        }

        const overlay = document.querySelector('.nav-overlay') || this.createNavOverlay();

        if (hamburger && nav) {
            hamburger.addEventListener('click', () => {
                this.toggleMobileNav(hamburger, nav, overlay);
            });

            // Cerrar menú al hacer clic en overlay
            if (overlay) {
                overlay.addEventListener('click', () => {
                    this.closeMobileNav(hamburger, nav, overlay);
                });
            }

            // Cerrar menú al hacer clic en enlaces
            const navLinks = nav.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    this.closeMobileNav(hamburger, nav, overlay);
                });
            });

            // Cerrar menú con tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && nav.classList.contains('open')) {
                    this.closeMobileNav(hamburger, nav, overlay);
                }
            });
        }
    }

    /**
     * Crear overlay para navegación móvil
     */
    createNavOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'nav-overlay';
        document.body.appendChild(overlay);
        return overlay;
    }

    /**
     * Alternar navegación móvil
     */
    toggleMobileNav(hamburger, nav, overlay) {
        const isOpen = nav.classList.contains('open');
        
        if (isOpen) {
            this.closeMobileNav(hamburger, nav, overlay);
        } else {
            this.openMobileNav(hamburger, nav, overlay);
        }
    }

    /**
     * Abrir navegación móvil
     */
    openMobileNav(hamburger, nav, overlay) {
        hamburger.classList.add('active');
        nav.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Animación de entrada para enlaces
        const navLinks = nav.querySelectorAll('.nav-link');
        navLinks.forEach((link, index) => {
            link.style.animationDelay = `${index * 0.1}s`;
            link.classList.add('slide-in');
        });
    }

    /**
     * Cerrar navegación móvil
     */
    closeMobileNav(hamburger, nav, overlay) {
        hamburger.classList.remove('active');
        nav.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Limpiar animaciones
        const navLinks = nav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.classList.remove('slide-in');
        });
    }

    /**
     * Configurar tablas responsive
     */
    setupResponsiveTables() {
        const tables = document.querySelectorAll('.table-container');
        
        tables.forEach(table => {
            this.makeTableResponsive(table);
        });
    }

    /**
     * Hacer tabla responsive
     */
    makeTableResponsive(tableContainer) {
        const table = tableContainer.querySelector('.table');
        if (!table) return;

        // Crear versión móvil de la tabla
        const mobileTable = this.createMobileTable(table);
        tableContainer.appendChild(mobileTable);

        // Configurar scroll horizontal suave
        this.setupHorizontalScroll(tableContainer);

        // Detectar cambios de tamaño
        this.setupResizeObserver(tableContainer, table, mobileTable);
    }

    /**
     * Crear tabla móvil
     */
    createMobileTable(originalTable) {
        const mobileTable = document.createElement('div');
        mobileTable.className = 'table-mobile';
        
        const tbody = originalTable.querySelector('tbody');
        if (!tbody) return mobileTable;

        const rows = tbody.querySelectorAll('tr');
        const headers = originalTable.querySelectorAll('th');
        
        rows.forEach(row => {
            const mobileRow = this.createMobileRow(row, headers);
            mobileTable.appendChild(mobileRow);
        });

        return mobileTable;
    }

    /**
     * Crear fila móvil
     */
    createMobileRow(row, headers) {
        const mobileRow = document.createElement('div');
        mobileRow.className = 'mobile-row';

        const cells = row.querySelectorAll('td');
        if (cells.length === 0) return mobileRow;

        // Crear header de la fila
        const rowHeader = document.createElement('div');
        rowHeader.className = 'mobile-row-header';
        
        const title = document.createElement('div');
        title.className = 'mobile-row-title';
        title.textContent = cells[0]?.textContent || 'Registro';
        
        const actions = document.createElement('div');
        actions.className = 'mobile-row-actions';
        
        // Copiar botones de acción
        const actionButtons = cells[cells.length - 1]?.querySelectorAll('a, button');
        if (actionButtons) {
            actionButtons.forEach(btn => {
                const clone = btn.cloneNode(true);
                clone.className = 'btn btn-sm';
                actions.appendChild(clone);
            });
        }
        
        rowHeader.appendChild(title);
        rowHeader.appendChild(actions);
        mobileRow.appendChild(rowHeader);

        // Crear campos
        cells.forEach((cell, index) => {
            if (index === 0 || index === cells.length - 1) return; // Saltar primera y última columna
            
            const field = document.createElement('div');
            field.className = 'mobile-row-field';
            
            const label = document.createElement('div');
            label.className = 'mobile-field-label';
            label.textContent = headers[index]?.textContent || `Campo ${index}`;
            
            const value = document.createElement('div');
            value.className = 'mobile-field-value';
            value.innerHTML = cell.innerHTML;
            
            field.appendChild(label);
            field.appendChild(value);
            mobileRow.appendChild(field);
        });

        return mobileRow;
    }

    /**
     * Configurar scroll horizontal suave
     */
    setupHorizontalScroll(container) {
        let isScrolling = false;
        
        container.addEventListener('scroll', () => {
            if (!isScrolling) {
                window.requestAnimationFrame(() => {
                    // Aquí se pueden agregar efectos de scroll
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });

        // Scroll suave con touch
        container.addEventListener('touchstart', (e) => {
            container.style.scrollBehavior = 'smooth';
        });

        container.addEventListener('touchend', () => {
            container.style.scrollBehavior = '';
        });
    }

    /**
     * Configurar observer de redimensionamiento
     */
    setupResizeObserver(tableContainer, desktopTable, mobileTable) {
        if (!window.ResizeObserver) return;

        const observer = new ResizeObserver(entries => {
            entries.forEach(entry => {
                const width = entry.contentRect.width;
                const isMobile = width <= 768;
                
                desktopTable.style.display = isMobile ? 'none' : 'table';
                mobileTable.style.display = isMobile ? 'block' : 'none';
            });
        });

        observer.observe(tableContainer);
    }

    /**
     * Configurar interacciones táctiles
     */
    setupTouchInteractions() {
        // Mejorar botones para touch
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(btn => {
            btn.addEventListener('touchstart', () => {
                btn.classList.add('touch-active');
            });

            btn.addEventListener('touchend', () => {
                setTimeout(() => {
                    btn.classList.remove('touch-active');
                }, 150);
            });
        });

        // Mejorar cards para touch
        const cards = document.querySelectorAll('.card, .quick-action');
        cards.forEach(card => {
            card.addEventListener('touchstart', () => {
                card.classList.add('touch-active');
            });

            card.addEventListener('touchend', () => {
                setTimeout(() => {
                    card.classList.remove('touch-active');
                }, 150);
            });
        });
    }

    /**
     * Configurar accesibilidad
     */
    setupAccessibility() {
        // Mejorar navegación por teclado
        const focusableElements = document.querySelectorAll(
            'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        focusableElements.forEach(element => {
            element.addEventListener('focus', () => {
                element.classList.add('keyboard-focus');
            });

            element.addEventListener('blur', () => {
                element.classList.remove('keyboard-focus');
            });
        });

        // Skip links - Deshabilitado
        // this.createSkipLinks();

        // ARIA labels para navegación móvil
        const hamburger = document.querySelector('.hamburger');
        if (hamburger) {
            hamburger.setAttribute('aria-label', 'Abrir menú de navegación');
            hamburger.setAttribute('aria-expanded', 'false');
        }
    }

    /**
     * Crear skip links para accesibilidad
     */
    createSkipLinks() {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'skip-link';
        skipLink.textContent = 'Saltar al contenido principal';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            background: var(--primary-color);
            color: white;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 10000;
            transition: top 0.3s;
        `;

        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '6px';
        });

        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });

        document.body.insertBefore(skipLink, document.body.firstChild);
    }

    /**
     * Optimizaciones de rendimiento
     */
    setupPerformanceOptimizations() {
        // Lazy loading para imágenes
        this.setupLazyLoading();

        // Debounce para resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.handleResize();
            }, 250);
        });

        // Intersection Observer para animaciones
        this.setupIntersectionObserver();
    }

    /**
     * Configurar lazy loading
     */
    setupLazyLoading() {
        if (!window.IntersectionObserver) return;

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    }

    /**
     * Configurar Intersection Observer
     */
    setupIntersectionObserver() {
        if (!window.IntersectionObserver) return;

        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1
        });

        const animatedElements = document.querySelectorAll('.card, .stat-card, .quick-action');
        animatedElements.forEach(el => {
            animationObserver.observe(el);
        });
    }

    /**
     * Manejar redimensionamiento
     */
    handleResize() {
        // Actualizar estado de navegación móvil
        const nav = document.querySelector('.main-nav');
        const hamburger = document.querySelector('.hamburger');
        
        if (window.innerWidth > 768 && nav && nav.classList.contains('open')) {
            this.closeMobileNav(hamburger, nav, document.querySelector('.nav-overlay'));
        }
    }

    /**
     * Método público para actualizar tablas
     */
    updateTables() {
        this.setupResponsiveTables();
    }

    /**
     * Método público para cerrar navegación móvil
     */
    closeMobileNavigation() {
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('.main-nav');
        const overlay = document.querySelector('.nav-overlay');
        
        if (hamburger && nav) {
            this.closeMobileNav(hamburger, nav, overlay);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.responsiveSystem = new ResponsiveSystem();
});

// CSS adicional para animaciones
const additionalCSS = `
    .slide-in {
        animation: slideInFromLeft 0.3s ease forwards;
    }

    @keyframes slideInFromLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .touch-active {
        transform: scale(0.98);
        transition: transform 0.1s ease;
    }

    .keyboard-focus {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
    }

    .animate-in {
        animation: fadeInUp 0.6s ease forwards;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .lazy {
        opacity: 0;
        transition: opacity 0.3s;
    }

    .lazy.loaded {
        opacity: 1;
    }
`;

// Inyectar CSS adicional
{
    const style = document.createElement('style');
    style.textContent = additionalCSS;
    document.head.appendChild(style);
}
