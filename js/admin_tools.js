/**
 * Admin Tools - Sistema de Métricas en Tiempo Real
 */

const SA_ADMIN_TOOLS_API = (() => {
    const p = window.location.pathname || '';
    return p.includes('/admin/') ? '../api/admin_tools_api.php' : 'api/admin_tools_api.php';
})();

class AdminToolsMonitor {
    constructor() {
        this.updateInterval = 30000; // 30 segundos
        this.intervalId = null;
        this.isActive = false;
        this.chart = null;
    }

    /**
     * Iniciar monitoreo en tiempo real
     */
    start() {
        if (this.isActive) return;
        
        this.isActive = true;
        this.updateMetrics();
        
        this.intervalId = setInterval(() => {
            this.updateMetrics();
        }, this.updateInterval);
        
        console.log('[Admin Tools] Monitoreo en tiempo real iniciado');
    }

    /**
     * Detener monitoreo
     */
    stop() {
        if (!this.isActive) return;
        
        this.isActive = false;
        
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        console.log('[Admin Tools] Monitoreo en tiempo real detenido');
    }

    /**
     * Actualizar métricas
     */
    async updateMetrics() {
        try {
            const response = await fetch(`${SA_ADMIN_TOOLS_API}?action=metricas`);
            
            if (!response.ok) {
                throw new Error('Error al obtener métricas');
            }
            
            const data = await response.json();
            
            if (data.success && data.data) {
                this.renderMetrics(data.data);
                this.updateAlerts();
            }
            
        } catch (error) {
            console.error('[Admin Tools] Error actualizando métricas:', error);
        }
    }

    /**
     * Renderizar métricas en la interfaz
     */
    renderMetrics(data) {
        const metricas = data.metricas;
        
        if (!metricas) return;
        
        // Actualizar memoria
        this.updateElement('.metric-value', 
            metricas.sistema?.memoria_php?.uso_actual_formateado || 'N/A', 0);
        
        // Actualizar base de datos
        this.updateElement('.metric-value', 
            metricas.base_datos?.tamaño_db_formateado || 'N/A', 1);
        
        // Actualizar disco
        if (metricas.sistema?.espacio_disco?.disponible) {
            const porcentaje = Math.round(metricas.sistema.espacio_disco.porcentaje_uso * 10) / 10;
            this.updateElement('.metric-value', `${porcentaje}%`, 2);
        }
        
        // Actualizar usuarios
        this.updateElement('.metric-value', 
            metricas.aplicacion?.usuarios?.sesiones_activas || 0, 3);
        
        // Actualizar indicador de salud
        const salud = metricas.rendimiento?.salud;
        if (salud) {
            const healthIndicator = document.querySelector('.health-indicator');
            if (healthIndicator) {
                healthIndicator.className = `health-indicator ${salud.nivel}`;
                healthIndicator.innerHTML = `
                    <i class="fas fa-heartbeat"></i>
                    ${salud.nivel.toUpperCase()}
                `;
            }
        }
    }

    /**
     * Actualizar alertas
     */
    async updateAlerts() {
        try {
            const response = await fetch(`${SA_ADMIN_TOOLS_API}?action=alertas`);
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            if (data.success && data.data?.alertas) {
                this.renderAlerts(data.data.alertas);
            }
            
        } catch (error) {
            console.error('[Admin Tools] Error actualizando alertas:', error);
        }
    }

    /**
     * Renderizar alertas
     */
    renderAlerts(alertas) {
        const container = document.querySelector('.alerts-container');
        
        if (!container) return;
        
        if (alertas.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        
        let html = '<h3 style="margin-bottom: 1rem;"><i class="fas fa-exclamation-triangle"></i> Alertas Activas</h3>';
        
        alertas.forEach(alerta => {
            html += `
                <div class="alert-item ${alerta.tipo}">
                    <i class="fas fa-${alerta.tipo === 'error' ? 'times-circle' : 'exclamation-triangle'}"></i>
                    <div>
                        <strong>${this.escapeHtml(alerta.mensaje)}</strong>
                        ${alerta.detalles ? `<br><small>${this.escapeHtml(alerta.detalles)}</small>` : ''}
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    /**
     * Actualizar elemento específico
     */
    updateElement(selector, value, index = 0) {
        const elements = document.querySelectorAll(selector);
        if (elements[index]) {
            elements[index].textContent = value;
        }
    }

    /**
     * Escapar HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Cargar historial de métricas
     */
    async loadHistorial(horas = 24) {
        try {
            const response = await fetch(`${SA_ADMIN_TOOLS_API}?action=historial&horas=${horas}`);
            
            if (!response.ok) {
                throw new Error('Error al cargar historial');
            }
            
            const data = await response.json();
            
            if (data.success && data.data?.historial) {
                return data.data.historial;
            }
            
            return [];
            
        } catch (error) {
            console.error('[Admin Tools] Error cargando historial:', error);
            return [];
        }
    }

    /**
     * Formatear bytes
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Actualizar timestamp
     */
    updateTimestamp() {
        const timestamp = document.querySelector('.last-update');
        if (timestamp) {
            const now = new Date();
            const time = now.toLocaleTimeString('es-AR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            timestamp.textContent = `Última actualización: ${time}`;
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Solo iniciar en la página de herramientas administrativas
    if (window.location.pathname.includes('admin_tools.php')) {
        const monitor = new AdminToolsMonitor();
        
        // Iniciar monitoreo solo en la pestaña de monitoreo
        const monitoringTab = document.querySelector('[data-tab="monitoring"]');
        if (monitoringTab) {
            monitoringTab.addEventListener('click', function() {
                monitor.start();
            });
        }
        
        // Detener monitoreo al cambiar de pestaña
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                if (targetTab !== 'monitoring') {
                    monitor.stop();
                }
            });
        });
        
        // Iniciar si ya estamos en la pestaña de monitoreo
        const activeTab = document.querySelector('.tab-content.active');
        if (activeTab && activeTab.id === 'tab-monitoring') {
            monitor.start();
        }
        
        // Detener al salir de la página
        window.addEventListener('beforeunload', function() {
            monitor.stop();
        });
        
        // Exponer monitor globalmente para debugging
        window.adminMonitor = monitor;
    }
});

/**
 * Animación de valores numéricos
 */
function animateValue(element, start, end, duration = 1000) {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(function() {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            element.textContent = Math.round(end);
            clearInterval(timer);
        } else {
            element.textContent = Math.round(current);
        }
    }, 16);
}

/**
 * Confirmación para acciones peligrosas
 */
function confirmarAccion(mensaje) {
    return confirm(mensaje);
}

/**
 * Mostrar notificación toast
 */
function mostrarToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.textContent = mensaje;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${tipo === 'success' ? '#28a745' : tipo === 'error' ? '#dc3545' : '#007bff'};
        color: white;
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Estilos para animaciones
{
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Cargar y mostrar logs del servidor vía AJAX
async function cargarLogs() {
    const logTypeSelect = document.getElementById('log-type');
    const logLimitSelect = document.getElementById('log-limit');
    const logFilterInput = document.getElementById('log-filter');
    const viewer = document.getElementById('log-viewer-content');
    const stats = document.getElementById('log-stats');
    const clearTypeInput = document.getElementById('clear-log-type');
    const clearBtn = document.getElementById('btn-clear-log');

    if (!viewer) return;

    const logType = logTypeSelect ? logTypeSelect.value : 'error';
    const logLimit = logLimitSelect ? logLimitSelect.value : '100';
    const logFilter = logFilterInput ? logFilterInput.value : '';

    // Actualizar tipo en formulario de vaciado
    if (clearTypeInput) clearTypeInput.value = logType;
    if (clearBtn) {
        if (logType === 'audit') {
            clearBtn.disabled = true;
            clearBtn.style.opacity = '0.5';
            clearBtn.title = 'Los logs de auditoría legal no pueden ser vaciados';
        } else {
            clearBtn.disabled = false;
            clearBtn.style.opacity = '1';
            clearBtn.title = 'Vaciar este archivo de log';
        }
    }

    viewer.innerHTML = '<div style="color: #94a3b8; text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i> Cargando logs...</div>';
    stats.textContent = 'Cargando...';

    try {
        let url = SA_ADMIN_TOOLS_API + `?action=logs&tipo=${encodeURIComponent(logType)}&limite=${encodeURIComponent(logLimit)}`;
        if (logFilter) {
            url += `&filtro=${encodeURIComponent(logFilter)}`;
        }

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Error al consultar logs');
        }

        const res = await response.json();
        if (res.success && Array.isArray(res.data)) {
            const logs = res.data;
            stats.textContent = `${logs.length} entradas encontradas`;

            if (logs.length === 0) {
                viewer.innerHTML = '<div style="color: #64748b; text-align: center; padding: 2rem;">No hay registros que coincidan con la búsqueda.</div>';
                return;
            }

            let html = '';
            logs.forEach(entry => {
                let badgeColor = '#475569';
                let rowBg = 'transparent';
                const tipo = (entry.tipo || entry.type || 'INFO').toUpperCase();

                if (tipo.includes('ERROR') || tipo.includes('FAILED')) {
                    badgeColor = '#ef4444';
                    rowBg = 'rgba(239, 68, 68, 0.08)';
                } else if (tipo.includes('WARNING') || tipo.includes('SUSPICIOUS')) {
                    badgeColor = '#f59e0b';
                    rowBg = 'rgba(245, 158, 11, 0.08)';
                } else if (tipo.includes('SUCCESS') || tipo.includes('LOGIN_SUCCESS')) {
                    badgeColor = '#10b981';
                    rowBg = 'rgba(16, 185, 129, 0.08)';
                } else if (tipo.includes('AUDIT')) {
                    badgeColor = '#8b5cf6';
                    rowBg = 'rgba(139, 92, 246, 0.08)';
                }

                // Generar descripción legible
                let desc = entry.descripcion || entry.mensaje || '';
                let extraHtml = '';
                
                // Mostrar datos adicionales si existen
                const datos = entry.datos || entry.contexto || null;
                if (datos && Object.keys(datos).length > 0) {
                    extraHtml = `<details style="margin-top: 0.5rem; color: #94a3b8; font-size: 0.85rem;"><summary style="cursor: pointer; color: #38bdf8; user-select: none;">Ver detalles JSON</summary><pre style="background: #1e293b; padding: 0.5rem; border-radius: 4px; margin-top: 0.25rem; overflow-x: auto; max-width: 100%; white-space: pre-wrap;">${escapeHtml(JSON.stringify(datos, null, 2))}</pre></details>`;
                }

                const timestamp = entry.timestamp || 'N/A';
                const ip = entry.ip || 'N/A';
                const userId = entry.usuario_id ? `Usuario ID: ${entry.usuario_id}` : 'Sesión anónima';

                html += `
                    <div style="padding: 1rem; border-bottom: 1px solid #1e293b; background: ${rowBg}; display: flex; flex-direction: column; gap: 0.25rem;">
                        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; font-size: 0.85rem;">
                            <span style="color: #64748b;">[${escapeHtml(timestamp)}]</span>
                            <span style="background: ${badgeColor}; color: white; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: bold; font-size: 0.75rem;">${escapeHtml(tipo)}</span>
                            <span style="color: #94a3b8;">IP: <code>${escapeHtml(ip)}</code></span>
                            <span style="color: #64748b;">${escapeHtml(userId)}</span>
                        </div>
                        <div style="color: #f1f5f9; font-weight: 500; word-break: break-all; margin-top: 0.25rem;">
                            ${escapeHtml(desc)}
                        </div>
                        ${extraHtml}
                    </div>
                `;
            });

            viewer.innerHTML = html;
        } else {
            viewer.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 2rem;">Error: ${escapeHtml(res.mensaje || 'Respuesta no válida')}</div>`;
        }
    } catch (e) {
        viewer.innerHTML = `<div style="color: #ef4444; text-align: center; padding: 2rem;">Error al cargar los logs: ${escapeHtml(e.message)}</div>`;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Funcionalidad de pestañas
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remover clase active de todas las pestañas
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));
            
            // Agregar clase active a la pestaña clickeada
            this.classList.add('active');
            
            // Mostrar el contenido correspondiente
            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }

            // Si es la pestaña de logs, cargar automáticamente
            if (targetTab === 'logs') {
                cargarLogs();
            }
        });
    });

    // Eventos para el visor de logs
    const logTypeSelect = document.getElementById('log-type');
    const logLimitSelect = document.getElementById('log-limit');
    const logFilterInput = document.getElementById('log-filter');
    const btnRefreshLogs = document.getElementById('btn-refresh-logs');

    if (logTypeSelect) {
        logTypeSelect.addEventListener('change', cargarLogs);
    }
    if (logLimitSelect) {
        logLimitSelect.addEventListener('change', cargarLogs);
    }
    if (logFilterInput) {
        let timeout = null;
        logFilterInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(cargarLogs, 400); // Debounce de 400ms
        });
    }
    if (btnRefreshLogs) {
        btnRefreshLogs.addEventListener('click', cargarLogs);
    }

    // Si la pestaña de logs ya está activa de inicio, cargar logs
    const activeTab = document.querySelector('.tab.active');
    if (activeTab && activeTab.getAttribute('data-tab') === 'logs') {
        cargarLogs();
    }
});