<?php
require_once __DIR__ . '/database_bootstrap.php';
require_once __DIR__ . '/preceptor_scope.php';
require_once __DIR__ . '/profesor_scope.php';
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/i18n.php';

\SistemaAdmin\Services\I18nService::init();

// Este header funciona con la nueva arquitectura de autenticación
// pero mantiene el diseño original
$currentUser = [
    'nombre' => $_SESSION['nombre'] ?? 'Usuario',
    'apellido' => $_SESSION['apellido'] ?? '',
    'rol' => $_SESSION['rol'] ?? 'usuario'
];
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

if (empty($GLOBALS['familia_portal_vista']) && ($_SESSION['rol'] ?? '') === 'profesor') {
    $paginasPermitidasProfesor = ['courses.php', 'students.php', 'student_profile.php', 'schedules.php', 'grades.php'];
    $idFichaGet = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $miProfesorId = function_exists('profesor_id_sesion') ? profesor_id_sesion() : null;
    if (
        $currentPage === 'teacher_profile.php'
        && $idFichaGet !== false
        && $idFichaGet > 0
        && $miProfesorId !== null
        && $idFichaGet === $miProfesorId
    ) {
        $paginasPermitidasProfesor[] = 'teacher_profile.php';
    }
    if (!in_array($currentPage, $paginasPermitidasProfesor, true)) {
        $redirProf = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) ? '../courses.php' : 'courses.php';
        if (!headers_sent()) {
            header('Location: ' . $redirProf);
            exit();
        }
    }
}

// Función para obtener la ruta base correcta
function getBasePath() {
    return (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '';
}

$familiaPortalHeader = !empty($GLOBALS['familia_portal_vista']);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php
        $saTitleDefault = class_exists(\SistemaAdmin\Bootstrap\AppRequestInit::class, false)
            ? \SistemaAdmin\Bootstrap\AppRequestInit::systemName()
            : 'EduSchool ERP';
        $saSubtitleDefault = class_exists(\SistemaAdmin\Bootstrap\AppRequestInit::class, false)
            ? \SistemaAdmin\Bootstrap\AppRequestInit::systemSubtitle()
            : 'School Management System';
        $saLogoDefault = class_exists(\SistemaAdmin\Bootstrap\AppRequestInit::class, false)
            ? \SistemaAdmin\Bootstrap\AppRequestInit::systemLogo()
            : 'img/logo.png';
        echo htmlspecialchars($pageTitle ?? $saTitleDefault, ENT_QUOTES, 'UTF-8');
    ?></title>
    <link rel="stylesheet" href="<?php echo $GLOBALS['css_path'] ?? (getBasePath() . 'css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isset($GLOBALS['extra_css'])) echo $GLOBALS['extra_css']; ?>
<?php $nonce = $GLOBALS['csp_nonce'] ?? ''; ?>
<script src="<?php echo getBasePath(); ?>js/responsive.js" defer nonce="<?php echo htmlspecialchars($nonce); ?>"></script>
<script src="<?php echo getBasePath(); ?>js/csp-safe-handlers.js" defer nonce="<?php echo htmlspecialchars($nonce); ?>"></script>
</head>
<body<?php
$bodyClasses = [];
if (isset($bodyClass) && $bodyClass !== '') {
    $bodyClasses[] = $bodyClass;
}
if ($familiaPortalHeader) {
    $bodyClasses[] = 'portal-familia';
}
echo $bodyClasses !== [] ? ' class="' . htmlspecialchars(implode(' ', $bodyClasses), ENT_QUOTES, 'UTF-8') . '"' : '';
?>>
<?php if ($familiaPortalHeader): ?>
    <header class="main-header familia-portal-header">
        <div class="header-top">
            <div class="logo-section">
                <img src="<?php echo getBasePath() . htmlspecialchars($saLogoDefault, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" class="logo">
                <div class="school-info">
                    <h1 class="brand-title">Student <span>Portal</span></h1>
                    <h2><?php echo htmlspecialchars($saTitleDefault, ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
            </div>
            <div class="user-section familia-portal-header__actions">
                <?php if (!empty($familia_multihijo)): ?>
                <a href="<?php echo htmlspecialchars(getBasePath() . 'public/familia_seleccion.php', ENT_QUOTES, 'UTF-8'); ?>" class="familia-portal-header__link"><i class="fas fa-users"></i><?php echo htmlspecialchars(__('auto.elegir_estudiante'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars(getBasePath() . 'public/portal.php', ENT_QUOTES, 'UTF-8'); ?>" class="familia-portal-header__link"><i class="fas fa-home"></i><?php echo htmlspecialchars(__('auto.inicio_portal'), ENT_QUOTES, 'UTF-8'); ?></a>
                <a href="<?php echo htmlspecialchars(getBasePath() . 'public/familia_logout.php', ENT_QUOTES, 'UTF-8'); ?>" class="logout-btn" title="Salir del portal familias"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>
    <div class="app-layout app-layout--familia-portal">
<?php else: ?>
    <header class="main-header">
        <div class="header-top">
            <div class="logo-section">
                <img src="<?php echo getBasePath() . htmlspecialchars($saLogoDefault, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" class="logo">
                <div class="school-info">
                    <h1 class="brand-title"><?php echo htmlspecialchars($saTitleDefault, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <h2><?php echo htmlspecialchars($saSubtitleDefault, ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
            </div>
            <button class="hamburger" id="hamburger-menu" aria-label="Abrir menú" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="user-section">
                <div class="lang-switcher" style="margin-right: 0.75rem; display: flex; gap: 0.2rem; background: rgba(255,255,255,0.15); padding: 0.2rem 0.4rem; border-radius: 20px; font-size: 0.78rem;">
                    <a href="?lang=en" title="English" style="text-decoration:none; padding: 0.15rem 0.45rem; border-radius: 12px; color: #fff; <?php echo current_lang() === 'en' ? 'background: #2563eb; font-weight: 700;' : 'opacity: 0.75;'; ?>">🇺🇸 EN</a>
                    <a href="?lang=es" title="Español" style="text-decoration:none; padding: 0.15rem 0.45rem; border-radius: 12px; color: #fff; <?php echo current_lang() === 'es' ? 'background: #2563eb; font-weight: 700;' : 'opacity: 0.75;'; ?>">🇪🇸 ES</a>
                </div>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($currentUser['nombre'] . ' ' . $currentUser['apellido']); ?></span>
                    <span class="role">(<?php echo ucfirst($currentUser['rol']); ?>)</span>
                </div>
                <a href="<?php echo getBasePath(); ?>public/logout.php" class="logout-btn" title="<?php echo htmlspecialchars(__('nav.logout'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        
        <!-- Navegación principal responsive -->
        <nav class="main-nav" id="main-nav">
            <ul class="nav-menu">
                <?php if (!hasRole('profesor')): ?>
                <li><a href="<?php echo getBasePath(); ?>index.php" class="nav-link <?php echo $currentPage==='index.php'?'active':''; ?>">
                    <i class="fas fa-home"></i><span><?php echo htmlspecialchars(__('nav.dashboard'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php endif; ?>
                <li><a href="<?php echo getBasePath(); ?>students.php" class="nav-link <?php echo $currentPage==='students.php'?'active':''; ?>">
                    <i class="fas fa-users"></i><span><?php echo htmlspecialchars(__('nav.students'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php if (!hasRole('profesor')): ?>
                <li><a href="<?php echo getBasePath(); ?>teachers.php" class="nav-link <?php echo $currentPage==='teachers.php'?'active':''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i><span><?php echo htmlspecialchars(__('nav.teachers'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php endif; ?>
                <?php if (puedeAccesoPestanaPreceptor()): ?>
                <li><a href="<?php echo getBasePath(); ?>advisors.php" class="nav-link <?php echo $currentPage==='advisors.php'?'active':''; ?>">
                    <i class="fas fa-user-graduate"></i><span><?php echo htmlspecialchars(__('nav.advisors'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php endif; ?>
                <li><a href="<?php echo getBasePath(); ?>courses.php" class="nav-link <?php echo $currentPage==='courses.php'?'active':''; ?>">
                    <i class="fas fa-graduation-cap"></i><span><?php echo htmlspecialchars(__('nav.courses'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <li><a href="<?php echo getBasePath(); ?>schedules.php" class="nav-link <?php echo $currentPage==='schedules.php'?'active':''; ?>">
                    <i class="fas fa-clock"></i><span><?php echo htmlspecialchars(__('nav.schedules'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php if (hasRole(['admin', 'preceptor', 'directivo']) || puedeGestionarPreceptoresCurso()): ?>
                <li><a href="<?php echo getBasePath(); ?>attendance.php" class="nav-link <?php echo $currentPage==='attendance.php'?'active':''; ?>">
                    <i class="fas fa-calendar-check"></i><span><?php echo htmlspecialchars(__('nav.attendance'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php endif; ?>
                <?php if (hasRole('profesor') || hasRole('preceptor')): ?>
                <li><a href="<?php echo getBasePath(); ?>grades.php" class="nav-link <?php echo $currentPage==='grades.php'?'active':''; ?>">
                    <i class="fas fa-clipboard-check"></i><span><?php echo htmlspecialchars(__('nav.grades'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php if (hasRole('preceptor')): ?>
                <li><a href="<?php echo getBasePath(); ?>admin/calendario_escolar.php" class="nav-link <?php echo $currentPage==='calendario_escolar.php'?'active':''; ?>">
                    <i class="fas fa-calendar-alt"></i><span><?php echo htmlspecialchars(__('nav.calendar'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (hasRole(['admin', 'directivo'])): ?>
                <li><a href="<?php echo getBasePath(); ?>grades.php" class="nav-link <?php echo $currentPage==='grades.php'?'active':''; ?>">
                    <i class="fas fa-clipboard-check"></i><span><?php echo htmlspecialchars(__('nav.grades'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <li><a href="<?php echo getBasePath(); ?>subjects.php" class="nav-link <?php echo $currentPage==='subjects.php'?'active':''; ?>">
                    <i class="fas fa-book"></i><span><?php echo htmlspecialchars(__('nav.subjects'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <li><a href="<?php echo getBasePath(); ?>admin/calendario_escolar.php" class="nav-link <?php echo $currentPage==='calendario_escolar.php'?'active':''; ?>">
                    <i class="fas fa-calendar-alt"></i><span><?php echo htmlspecialchars(__('nav.calendar'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>

                <?php endif; ?>

                <?php if (hasRole('admin')): ?>
                <li><a href="<?php echo getBasePath(); ?>users.php" class="nav-link <?php echo $currentPage==='users.php'?'active':''; ?>">
                    <i class="fas fa-users-cog"></i><span><?php echo htmlspecialchars(__('nav.users'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <li><a href="<?php echo getBasePath(); ?>admin/admin_tools.php?tab=configuration" class="nav-link <?php echo $currentPage==='admin_tools.php'?'active':''; ?>">
                    <i class="fas fa-sliders-h"></i><span><?php echo htmlspecialchars(__('nav.settings'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <li><a href="<?php echo getBasePath(); ?>admin/admin_tools.php" class="nav-link <?php echo $currentPage==='admin_tools.php'?'active':''; ?>">
                    <i class="fas fa-tools"></i><span><?php echo htmlspecialchars(__('nav.admin_tools'), ENT_QUOTES, 'UTF-8'); ?></span>
                </a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="app-layout">
        <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true" hidden></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-school"></i>
                <span><?php echo htmlspecialchars(__('nav.school'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <nav class="sidebar-nav">
                <ul class="menu">
                    <?php if (!hasRole('profesor')): ?>
                    <li><a href="<?php echo getBasePath(); ?>index.php" class="menu-link <?php echo $currentPage==='index.php'?'active':''; ?>"><i class="fas fa-home"></i><span><?php echo htmlspecialchars(__('nav.dashboard'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo getBasePath(); ?>students.php" class="menu-link <?php echo $currentPage==='students.php'?'active':''; ?>"><i class="fas fa-users"></i><span><?php echo htmlspecialchars(__('nav.students'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php if (!hasRole('profesor')): ?>
                    <li><a href="<?php echo getBasePath(); ?>teachers.php" class="menu-link <?php echo $currentPage==='teachers.php'?'active':''; ?>"><i class="fas fa-chalkboard-teacher"></i><span><?php echo htmlspecialchars(__('nav.teachers'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php endif; ?>
                    <?php if (puedeAccesoPestanaPreceptor()): ?>
                    <li><a href="<?php echo getBasePath(); ?>advisors.php" class="menu-link <?php echo $currentPage==='advisors.php'?'active':''; ?>"><i class="fas fa-user-graduate"></i><span><?php echo htmlspecialchars(__('nav.advisors'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo getBasePath(); ?>courses.php" class="menu-link <?php echo $currentPage==='courses.php'?'active':''; ?>"><i class="fas fa-graduation-cap"></i><span><?php echo htmlspecialchars(__('nav.courses'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <li><a href="<?php echo getBasePath(); ?>schedules.php" class="menu-link <?php echo $currentPage==='schedules.php'?'active':''; ?>"><i class="fas fa-clock"></i><span><?php echo htmlspecialchars(__('nav.schedules'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php if (hasRole(['admin', 'preceptor', 'directivo']) || puedeGestionarPreceptoresCurso()): ?>
                    <li><a href="<?php echo getBasePath(); ?>attendance.php" class="menu-link <?php echo $currentPage==='attendance.php'?'active':''; ?>"><i class="fas fa-calendar-check"></i><span><?php echo htmlspecialchars(__('nav.attendance'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php endif; ?>
                    <?php if (hasRole('profesor') || hasRole('preceptor')): ?>
                    <li class="menu-section"><?php echo htmlspecialchars(__('nav.academic'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><a href="<?php echo getBasePath(); ?>grades.php" class="menu-link <?php echo $currentPage==='grades.php'?'active':''; ?>"><i class="fas fa-clipboard-check"></i><span><?php echo htmlspecialchars(__('nav.grades'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php if (hasRole('preceptor')): ?>
                    <li><a href="<?php echo getBasePath(); ?>admin/calendario_escolar.php" class="menu-link <?php echo $currentPage==='calendario_escolar.php'?'active':''; ?>"><i class="fas fa-calendar-alt"></i><span><?php echo htmlspecialchars(__('nav.calendar'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if (hasRole(['admin', 'directivo'])): ?>
                    <li class="menu-section"><?php echo htmlspecialchars(__('nav.academic'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><a href="<?php echo getBasePath(); ?>grades.php" class="menu-link <?php echo $currentPage==='grades.php'?'active':''; ?>"><i class="fas fa-clipboard-check"></i><span><?php echo htmlspecialchars(__('nav.grades'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <li><a href="<?php echo getBasePath(); ?>subjects.php" class="menu-link <?php echo $currentPage==='subjects.php'?'active':''; ?>"><i class="fas fa-book"></i><span><?php echo htmlspecialchars(__('nav.subjects'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <li><a href="<?php echo getBasePath(); ?>admin/calendario_escolar.php" class="menu-link <?php echo $currentPage==='calendario_escolar.php'?'active':''; ?>"><i class="fas fa-calendar-alt"></i><span><?php echo htmlspecialchars(__('nav.calendar'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>

                    <li><a href="<?php echo getBasePath(); ?>staff.php" class="menu-link <?php echo $currentPage==='staff.php'?'active':''; ?>"><i class="fas fa-user-tie"></i><span><?php echo htmlspecialchars(__('nav.executive_board'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    

                    
                    <?php if (hasRole('admin')): ?>
                    <li class="menu-section"><?php echo htmlspecialchars(__('nav.administration'), ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><a href="<?php echo getBasePath(); ?>users.php" class="menu-link <?php echo $currentPage==='users.php'?'active':''; ?>"><i class="fas fa-users-cog"></i><span><?php echo htmlspecialchars(__('nav.users'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <li><a href="<?php echo getBasePath(); ?>admin/admin_tools.php" class="menu-link <?php echo $currentPage==='admin_tools.php'?'active':''; ?>"><i class="fas fa-tools"></i><span><?php echo htmlspecialchars(__('nav.admin_tools'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                    <?php endif; ?>
                    <?php endif; ?>

                    <li><a href="<?php echo getBasePath(); ?>public/logout.php" class="menu-link"><i class="fas fa-sign-out-alt"></i><span><?php echo htmlspecialchars(__('nav.logout'), ENT_QUOTES, 'UTF-8'); ?></span></a></li>
                </ul>
            </nav>
        </aside>
<?php endif; ?>
        <main class="main-content<?php echo (isset($mainContentExtraClass) && $mainContentExtraClass !== '') ? ' ' . htmlspecialchars($mainContentExtraClass, ENT_QUOTES, 'UTF-8') : ''; ?>">
            <div class="breadcrumb-bar">
                <div class="crumbs-left">
                    <i class="fas fa-home"></i>
                    <a href="<?php echo getBasePath() . (hasRole('profesor') ? 'courses.php' : 'index.php'); ?>">Inicio</a>
                    <span>/</span>
                    <strong><?php echo htmlspecialchars($pageTitle ?? ''); ?></strong>
                </div>
                <div class="crumbs-right">
                    <i class="far fa-clock"></i>
                    <span id="clock-time"></span>
                    <i class="far fa-calendar-alt" style="margin-left:.75rem;"></i>
                    <span id="clock-date"></span>
                </div>
            </div>
        <script nonce="<?php echo htmlspecialchars($nonce); ?>">
        document.addEventListener('DOMContentLoaded', function() {
            var hamburger = document.getElementById('hamburger-menu');
            var sidebar = document.getElementById('sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');
            function syncSidebarDrawer() {
                var open = sidebar && sidebar.classList.contains('open');
                var mq = window.matchMedia('(max-width: 992px)');
                if (sidebar) {
                    document.body.classList.toggle('sidebar-drawer-open', !!open && mq.matches);
                }
                if (backdrop) {
                    if (open && mq.matches) {
                        backdrop.hidden = false;
                        backdrop.setAttribute('aria-hidden', 'false');
                    } else {
                        backdrop.hidden = true;
                        backdrop.setAttribute('aria-hidden', 'true');
                    }
                }
                if (hamburger) {
                    hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                document.body.style.overflow = (open && mq.matches) ? 'hidden' : '';
            }
            window.__saSyncSidebarDrawer = syncSidebarDrawer;

            /* Tras navegar con “modo dispositivo” del inspector, a veces el primer pintado no aplica bien los @media hasta que hay un resize; esto lo fuerza sin que el usuario toque el toggle. */
            function refreshLayoutAfterPaint() {
                syncSidebarDrawer();
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        syncSidebarDrawer();
                        try {
                            window.dispatchEvent(new Event('resize'));
                        } catch (e) {}
                    });
                });
            }

            if (hamburger && sidebar) {
                hamburger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('open');
                    syncSidebarDrawer();
                });
                if (backdrop) {
                    backdrop.addEventListener('click', function(e) {
                        e.stopPropagation();
                        sidebar.classList.remove('open');
                        syncSidebarDrawer();
                    });
                }
                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                        sidebar.classList.remove('open');
                        syncSidebarDrawer();
                    }
                    if (e.target.closest && e.target.closest('.menu-link')) {
                        sidebar.classList.remove('open');
                        syncSidebarDrawer();
                    }
                });
                window.addEventListener('resize', function() {
                    if (!window.matchMedia('(max-width: 992px)').matches) {
                        sidebar.classList.remove('open');
                    }
                    syncSidebarDrawer();
                });
                var mq992 = window.matchMedia('(max-width: 992px)');
                function onViewportBucketChange() {
                    if (!mq992.matches) {
                        sidebar.classList.remove('open');
                    }
                    refreshLayoutAfterPaint();
                }
                if (mq992.addEventListener) {
                    mq992.addEventListener('change', onViewportBucketChange);
                } else if (mq992.addListener) {
                    mq992.addListener(onViewportBucketChange);
                }
            }

            // Reloj local (hora del sistema del usuario)
            function updateClock() {
                try {
                    var now = new Date();
                    var time = new Intl.DateTimeFormat('es-AR', { hour: '2-digit', minute: '2-digit' }).format(now);
                    var date = new Intl.DateTimeFormat('es-AR', { day: '2-digit', month: 'short', year: 'numeric' }).format(now);
                    var tEl = document.getElementById('clock-time');
                    var dEl = document.getElementById('clock-date');
                    if (tEl) tEl.textContent = time;
                    if (dEl) dEl.textContent = date;
                } catch (e) {}
            }
            updateClock();
            setInterval(updateClock, 60000);

            refreshLayoutAfterPaint();
        });
        window.addEventListener('pageshow', function() {
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    if (typeof window.__saSyncSidebarDrawer === 'function') {
                        window.__saSyncSidebarDrawer();
                    }
                    try {
                        window.dispatchEvent(new Event('resize'));
                    } catch (e) {}
                });
            });
        });
        </script>

<?php
// Las funciones CSRF están definidas en includes/csrf_functions.php
?>