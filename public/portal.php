<?php
declare(strict_types=1);

/**
 * Landing pública — Portal familias (acceso con DNI del responsable cargado en el sistema).
 */
require_once __DIR__ . '/../includes/sistema_admin_session.php';
require_once __DIR__ . '/../includes/sistema_admin_http.php';
require_once __DIR__ . '/../includes/csrf_functions.php';
require_once __DIR__ . '/../includes/i18n.php';

\SistemaAdmin\Services\I18nService::init();
sistema_admin_send_html_security_headers();

$csrfToken = getCSRFToken();
$familiaErrorRaw = filter_input(INPUT_GET, 'familia_error', FILTER_DEFAULT);
$familiaErrorMsg = '';
if (is_string($familiaErrorRaw)) {
    $familiaErrorMsg = mb_substr(trim($familiaErrorRaw), 0, 500);
}
$nonce = htmlspecialchars((string) ($GLOBALS['csp_nonce'] ?? ''), ENT_QUOTES, 'UTF-8');
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/portal.php'));
$publicWeb = dirname($scriptName);
$appWebRoot = ($publicWeb !== '' && $publicWeb !== '/') ? dirname($publicWeb) : '';
if ($appWebRoot === '/' || $appWebRoot === '.') {
    $appWebRoot = '';
}
$href = static function (string $path) use ($appWebRoot): string {
    $base = $appWebRoot === '' ? '' : $appWebRoot;
    return htmlspecialchars($base . $path, ENT_QUOTES, 'UTF-8');
};
// Nombre y eslogan de la institución — sincronizado dinámicamente con la base de datos (configuracion_sistema)
$pdoSys = sistema_admin_pdo();
$schoolName = \SistemaAdmin\Services\SchoolConfigService::getSchoolName($pdoSys);
$schoolSlogan = \SistemaAdmin\Services\SchoolConfigService::getSchoolSlogan($pdoSys);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Familias · <?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $href('/css/inicio.css'); ?>">
</head>
<body id="acceso-familias">

<!-- ═══════════ PARTÍCULAS DE FONDO ═══════════ -->
<div class="particles" id="particles" aria-hidden="true"></div>

<!-- ═══════════ NAVBAR ═══════════ -->
<header class="navbar" id="navbar">
    <div class="navbar__inner">
        <a href="#inicio" class="navbar__brand">
            <img src="<?php echo $href('/img/logo-school.png'); ?>" alt="Logo <?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?>" class="navbar__logo">
            <div class="navbar__brand-text">
                <span class="navbar__name"><?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="navbar__sub"><?php echo htmlspecialchars($schoolSlogan, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </a>
        <nav class="navbar__links">
            <a href="#funciones"><?php echo htmlspecialchars(__('inicio.nav_what_see'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="#como-funciona"><?php echo htmlspecialchars(__('inicio.nav_how_it_works'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="#preguntas"><?php echo htmlspecialchars(__('auto.preguntas'), ENT_QUOTES, 'UTF-8'); ?></a>
        </nav>
        <div class="lang-switcher" style="display: flex; gap: 0.2rem; background: rgba(255,255,255,0.1); padding: 0.25rem 0.5rem; border-radius: 20px; font-size: 0.8rem; border: 1px solid rgba(255,255,255,0.2); margin-right: 0.5rem;">
            <a href="?lang=en" title="English" style="text-decoration:none; padding: 0.15rem 0.5rem; border-radius: 12px; color: #fff; <?php echo current_lang() === 'en' ? 'background: #2563eb; font-weight: 700;' : 'opacity: 0.75;'; ?>">🇺🇸 EN</a>
            <a href="?lang=es" title="Español" style="text-decoration:none; padding: 0.15rem 0.5rem; border-radius: 12px; color: #fff; <?php echo current_lang() === 'es' ? 'background: #2563eb; font-weight: 700;' : 'opacity: 0.75;'; ?>">🇪🇸 ES</a>
        </div>
        <button class="btn-hero btn-hero--outline btn-login-open" data-modal="modal-login">
            <i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(__('auto.acceder'), ENT_QUOTES, 'UTF-8'); ?></button>
        <button class="navbar__hamburger" id="nav-hamburger" aria-label="Menú">
            <span></span><span></span><span></span>
        </button>
    </div>
    <nav class="navbar__mobile" id="navbar-mobile">
        <a href="#funciones"><?php echo htmlspecialchars(__('inicio.nav_what_see'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="#como-funciona"><?php echo htmlspecialchars(__('inicio.nav_how_it_works'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="#preguntas"><?php echo htmlspecialchars(__('auto.preguntas'), ENT_QUOTES, 'UTF-8'); ?></a>
        <button class="btn-hero btn-hero--outline btn-login-open" data-modal="modal-login">
            <i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(__('auto.acceder'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </nav>
</header>

<!-- ═══════════ HERO ═══════════ -->
<section class="hero" id="inicio">
    <div class="hero__bg-shapes" aria-hidden="true">
        <div class="shape shape--1"></div>
        <div class="shape shape--2"></div>
        <div class="shape shape--3"></div>
        <div class="shape shape--4"></div>
    </div>
    <div class="hero__wrap">
    <div class="hero__content reveal">
        <div class="hero__tag">
            <i class="fas fa-star"></i><?php echo htmlspecialchars(__('auto.portal_de_familias'), ENT_QUOTES, 'UTF-8'); ?></div>
        <h1 class="hero__title"><?php echo htmlspecialchars(__('auto.segu_de_cerca'), ENT_QUOTES, 'UTF-8'); ?><br>
            <span class="gradient-text"><?php echo htmlspecialchars(__('inicio.hero_gradient'), ENT_QUOTES, 'UTF-8'); ?></span><br>
            <?php echo htmlspecialchars(__('inicio.hero_suffix'), ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="hero__desc">
            <?php echo htmlspecialchars(__('inicio.hero_desc'), ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <div class="hero__actions">
            <button type="button" class="btn-hero btn-hero--primary btn-login-open" data-modal="modal-login">
                <i class="fas fa-sign-in-alt"></i><?php echo htmlspecialchars(__('auto.ingresar_con_dni'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
        <div class="hero__trust">
            <div class="trust-item"><i class="fas fa-shield-alt"></i><?php echo htmlspecialchars(__('auto.datos_protegidos'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="trust-sep">·</div>
            <div class="trust-item"><i class="fas fa-mobile-alt"></i><?php echo htmlspecialchars(__('auto.100_responsivo'), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="trust-sep">·</div>
            <div class="trust-item"><i class="fas fa-lock"></i><?php echo htmlspecialchars(__('auto.acceso_seguro'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>
    <div class="hero__mockup reveal-right" aria-hidden="true">
        <div class="mockup-browser">
            <div class="mockup-bar">
                <span class="dot dot--red"></span>
                <span class="dot dot--yellow"></span>
                <span class="dot dot--green"></span>
                <span class="mockup-url"><i class="fas fa-lock"></i><?php echo htmlspecialchars((string)($_ENV['APP_URL'] ?? __('auto.portal_escolar')), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="mockup-screen mockup-screen--sistema">
                <?php /* Misma estructura que includes/header.php → familia-portal-header */ ?>
                <header class="mockup-familia-header" aria-hidden="true">
                    <div class="mockup-familia-header__top">
                        <div class="mockup-familia-logo-section">
                            <img src="<?php echo $href('/img/logo-school.png'); ?>" alt="" class="mockup-familia-logo" width="40" height="40" decoding="async">
                            <div class="mockup-familia-school-info">
                                <h1 class="mockup-familia-brand-title"><?php echo htmlspecialchars(__('auto.portal_de_familias'), ENT_QUOTES, 'UTF-8'); ?></h1>
                                <h2 class="mockup-familia-subtitle"><?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                        </div>
                        <div class="mockup-familia-actions">
                            <span class="mockup-familia-link"><i class="fas fa-users" aria-hidden="true"></i><?php echo htmlspecialchars(__('auto.elegir_estudiante'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="mockup-familia-link"><i class="fas fa-home" aria-hidden="true"></i><?php echo htmlspecialchars(__('auto.inicio_portal'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="mockup-familia-logout" title="Salir del portal familias"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </header>
                <div class="mockup-sys-body">
                    <div class="mockup-ficha-bar">
                        <div>
                            <span class="mockup-ficha-bar__kicker"><?php echo htmlspecialchars(__('auto.ficha_del_estudiante'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <strong class="mockup-ficha-bar__nombre"><?php echo htmlspecialchars(__('auto.garc_a_juan'), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="mockup-ficha-bar__meta">3° 2°</span>
                            <span class="mockup-ficha-estado"><i class="fas fa-check-circle" aria-hidden="true"></i><?php echo htmlspecialchars(__('auto.al_d_a'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                    <div class="mockup-kpi-row">
                        <div class="mockup-kpi">
                            <span class="mockup-kpi__val">8,7</span>
                            <span class="mockup-kpi__lbl"><?php echo htmlspecialchars(__('auto.prom_general'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="mockup-kpi">
                            <span class="mockup-kpi__val mockup-kpi__val--ok">95%</span>
                            <span class="mockup-kpi__lbl"><?php echo htmlspecialchars(__('auto.asistencia'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="mockup-kpi">
                            <span class="mockup-kpi__val">12</span>
                            <span class="mockup-kpi__lbl"><?php echo htmlspecialchars(__('auto.materias_cursando'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                    <div class="mockup-boletin">
                        <div class="mockup-boletin__title">📊 <?php echo htmlspecialchars(__('grades.report_card'), ENT_QUOTES, 'UTF-8'); ?> — 3° 2°</div>
                        <div class="mockup-boletin__frame">
                            <table class="mockup-boletin-table">
                                <thead>
                                    <tr>
                                        <th class="mockup-boletin-table__mat"><?php echo htmlspecialchars(__('auto.materia'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th>Av.&nbsp;1</th>
                                        <th>1°&nbsp;Trim.</th>
                                        <th>Av.&nbsp;2</th>
                                        <th>2°&nbsp;Trim.</th>
                                        <th class="mockup-boletin-table__prom"><?php echo htmlspecialchars(__('grades.average'), ENT_QUOTES, 'UTF-8'); ?></th>
                                        <th><?php echo htmlspecialchars(__('auto.estado'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="mockup-boletin-table__matcell"><?php echo htmlspecialchars(__('auto.matem_tica'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td><span class="mockup-nota mockup-nota--dest">9</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td class="mockup-boletin-table__promcell"><span class="mockup-prom">9</span></td>
                                        <td><span class="mockup-estado mockup-estado--ok"><?php echo htmlspecialchars(__('auto.aprobado'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="mockup-boletin-table__matcell"><?php echo htmlspecialchars(__('auto.lengua'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="mockup-nota">P</span></td>
                                        <td><span class="mockup-nota mockup-nota--dest">8</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td class="mockup-boletin-table__promcell"><span class="mockup-prom">8</span></td>
                                        <td><span class="mockup-estado mockup-estado--ok"><?php echo htmlspecialchars(__('auto.aprobado'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="mockup-boletin-table__matcell"><?php echo htmlspecialchars(__('auto.historia'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td><span class="mockup-nota mockup-nota--dest mockup-nota--warn">6</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td class="mockup-boletin-table__promcell"><span class="mockup-prom mockup-prom--warn">6</span></td>
                                        <td><span class="mockup-estado mockup-estado--mid"><?php echo htmlspecialchars(__('auto.en_seguimiento'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td class="mockup-boletin-table__matcell"><?php echo htmlspecialchars(__('auto.f_sica'), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td><span class="mockup-nota mockup-nota--dest">10</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td><span class="mockup-nota">—</span></td>
                                        <td class="mockup-boletin-table__promcell"><span class="mockup-prom">10</span></td>
                                        <td><span class="mockup-estado mockup-estado--ok">Aprobado</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.hero__mockup -->
    </div><!-- /.hero__wrap -->
</section>

<!-- ═══════════ FUNCIONES ═══════════ -->
<section class="section funciones" id="funciones">
    <div class="container">
        <div class="section-label reveal"><?php echo htmlspecialchars(__('inicio.nav_what_see'), ENT_QUOTES, 'UTF-8'); ?></div>
        <h2 class="section-title reveal"><?php echo htmlspecialchars(__('inicio.everything_in_one_place'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="features-grid">
            <div class="feat-card reveal">
                <div class="feat-icon feat-icon--blue"><i class="fas fa-chart-line"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.notas_y_calificaciones'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.consult_el_rendimiento_acad_mico_de_tu_hij'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon feat-icon--sky"><i class="fas fa-calendar-check"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.asistencia_actualizada'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.revis_presencias_ausencias_y_llegadas_tard'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon feat-icon--indigo"><i class="fas fa-bell"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.comunicados_escolares'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.recib_avisos_importantes_llamados_de_atenc'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon feat-icon--teal"><i class="fas fa-clock"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.horarios_de_cursada'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.acced_a_la_grilla_semanal_de_materias_doce'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon feat-icon--blue"><i class="fas fa-user-tie"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.equipo_docente'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.conoc_el_nombre_y_contacto_de_cada_profesor'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="feat-card reveal">
                <div class="feat-icon feat-icon--sky"><i class="fas fa-file-alt"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.bolet_n_digital'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.descarg_o_imprim_el_bolet_n_de_notas_de'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ CÓMO FUNCIONA ═══════════ -->
<section class="section como-funciona" id="como-funciona">
    <div class="container">
        <div class="section-label reveal"><?php echo htmlspecialchars(__('auto.f_cil_y_r_pido'), ENT_QUOTES, 'UTF-8'); ?></div>
        <h2 class="section-title reveal"><?php echo htmlspecialchars(__('auto.tres_pasos_para_estar_siempre_informado'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="steps">
            <div class="step reveal">
                <div class="step-icon"><i class="fas fa-id-card"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.dni_en_la_instituci_n'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.al_dar_de_alta_o_actualizar_la_ficha_la_escu'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="step-arrow reveal" aria-hidden="true"><i class="fas fa-chevron-right"></i></div>
            <div class="step reveal">
                <div class="step-icon"><i class="fas fa-sign-in-alt"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.ingres_con_ese_dni'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('inicio.step2_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="step-arrow reveal" aria-hidden="true"><i class="fas fa-chevron-right"></i></div>
            <div class="step reveal">
                <div class="step-icon"><i class="fas fa-eye"></i></div>
                <h3><?php echo htmlspecialchars(__('auto.consult_la_informaci_n'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(__('auto.visualiz_la_ficha_del_o_los_estudiantes_vin'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ STATS ═══════════ -->
<section class="stats-band reveal">
    <div class="container">
        <div class="stats-row">
            <div class="stat-band-item">
                <span class="stat-band-val" data-count="1200">0</span>
                <span class="stat-band-lbl"><?php echo htmlspecialchars(__('auto.estudiantes'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="stat-band-item">
                <span class="stat-band-val" data-count="85">0</span>
                <span class="stat-band-lbl"><?php echo htmlspecialchars(__('auto.docentes'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="stat-band-item">
                <span class="stat-band-val" data-count="32">0</span>
                <span class="stat-band-lbl"><?php echo htmlspecialchars(__('auto.cursos_activos'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="stat-band-item">
                <span class="stat-band-val" data-count="98">0</span>
                <span class="stat-band-lbl"><?php echo htmlspecialchars(__('auto.satisfacci_n'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ TESTIMONIOS ═══════════ -->
<section class="section testimonios" id="testimonios">
    <div class="container">
        <div class="section-label reveal"><?php echo htmlspecialchars(__('auto.familias_que_ya_lo_usan'), ENT_QUOTES, 'UTF-8'); ?></div>
        <h2 class="section-title reveal"><?php echo htmlspecialchars(__('auto.lo_que_dicen_los_padres'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="testi-grid">
            <div class="testi-card reveal">
                <div class="testi-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p><?php echo htmlspecialchars(__('inicio.testi1_quote'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="testi-author">
                    <div class="testi-avatar">ML</div>
                    <div>
                        <strong>María Laura S.</strong>
                        <span><?php echo htmlspecialchars(__('inicio.testi1_role'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
            <div class="testi-card reveal">
                <div class="testi-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                </div>
                <p><?php echo htmlspecialchars(__('inicio.testi2_quote'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="testi-author">
                    <div class="testi-avatar">RG</div>
                    <div>
                        <strong>Roberto G.</strong>
                        <span><?php echo htmlspecialchars(__('inicio.testi2_role'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
            <div class="testi-card reveal">
                <div class="testi-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
                <p><?php echo htmlspecialchars(__('inicio.testi3_quote'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="testi-author">
                    <div class="testi-avatar">CA</div>
                    <div>
                        <strong>Claudia A.</strong>
                        <span><?php echo htmlspecialchars(__('inicio.testi3_role'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════ FAQ ═══════════ -->
<section class="section faq" id="preguntas">
    <div class="container faq__inner">
        <div class="faq__side reveal">
            <div class="section-label"><?php echo htmlspecialchars(__('auto.preguntas_frecuentes'), ENT_QUOTES, 'UTF-8'); ?></div>
            <h2 class="section-title"><?php echo htmlspecialchars(__('inicio.faq_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(__('auto.si_tu_pregunta_no_est_ac_pod_s_escribir'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="faq__list">
            <details class="faq-item reveal">
                <summary><?php echo htmlspecialchars(__('inicio.faq1_q'), ENT_QUOTES, 'UTF-8'); ?></summary>
                <p><?php echo htmlspecialchars(__('auto.no_el_portal_funciona_desde_cualquier_navega'), ENT_QUOTES, 'UTF-8'); ?></p>
            </details>
            <details class="faq-item reveal">
                <summary><?php echo htmlspecialchars(__('inicio.faq2_q'), ENT_QUOTES, 'UTF-8'); ?></summary>
                <p><?php echo htmlspecialchars(__('inicio.faq2_a'), ENT_QUOTES, 'UTF-8'); ?></p>
            </details>
            <details class="faq-item reveal">
                <summary><?php echo htmlspecialchars(__('inicio.faq3_q'), ENT_QUOTES, 'UTF-8'); ?></summary>
                <p><?php echo htmlspecialchars(__('auto.s_si_varios_hijos_comparten_el_mismo_dni_d'), ENT_QUOTES, 'UTF-8'); ?></p>
            </details>
            <details class="faq-item reveal">
                <summary><?php echo htmlspecialchars(__('inicio.faq4_q'), ENT_QUOTES, 'UTF-8'); ?></summary>
                <p><?php echo htmlspecialchars(__('inicio.faq4_a'), ENT_QUOTES, 'UTF-8'); ?></p>
            </details>
            <details class="faq-item reveal">
                <summary><?php echo htmlspecialchars(__('inicio.faq5_q'), ENT_QUOTES, 'UTF-8'); ?></summary>
                <p><?php echo htmlspecialchars(__('inicio.faq5_a'), ENT_QUOTES, 'UTF-8'); ?></p>
            </details>
        </div>
    </div>
</section>

<!-- ═══════════ CTA FINAL ═══════════ -->
<section class="section cta-final reveal">
    <div class="cta-card">
        <div class="cta-deco" aria-hidden="true"></div>
        <h2><?php echo htmlspecialchars(__('inicio.cta_title'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars(__('inicio.cta_desc'), ENT_QUOTES, 'UTF-8'); ?></p>
        <button type="button" class="btn-hero btn-hero--primary btn-login-open" data-modal="modal-login">
            <i class="fas fa-sign-in-alt"></i><?php echo htmlspecialchars(__('auto.acceder_al_portal'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>
</section>

<!-- ═══════════ FOOTER ═══════════ -->
<footer class="landing-footer">
    <div class="container">
        <div class="footer-top">
            <div class="footer-brand">
                <img src="<?php echo $href('/img/logo-school.png'); ?>" alt="Logo" class="footer-logo">
                <div>
                    <strong><?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span><?php echo htmlspecialchars($schoolSlogan, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <div class="footer-links">
                <a href="<?php echo $href('/public/login.php'); ?>"><i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(__('auto.acceso_personal_docente'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </div>
        <div class="footer-disclaimer" style="font-size: 0.72rem; color: rgba(255, 255, 255, 0.4); border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 1rem; margin-top: 0.5rem; margin-bottom: 1.5rem; line-height: 1.5; text-align: justify;">
            <p style="margin: 0;"><i class="fas fa-info-circle" style="color: #38bdf8; margin-right: 0.35rem;"></i> <strong><?php echo htmlspecialchars(__('auto.informaci_n_importante_sobre_validez_de_dato'), ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars(__('inicio.footer_disclaimer'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo htmlspecialchars(__('inicio.footer_rights'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span><?php echo htmlspecialchars(__('auto.hecho_con'), ENT_QUOTES, 'UTF-8'); ?><i class="fas fa-heart" style="color:#f87171;"></i><?php echo htmlspecialchars(__('auto.para_las_familias'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>
</footer>

<!-- ═══════════ MODAL ACCEDER (DNI responsable) ═══════════ -->
<div class="modal-overlay" id="modal-login" role="dialog" aria-modal="true" aria-labelledby="modal-login-title">
    <div class="modal-box">
        <button type="button" class="modal-close" data-close="modal-login" aria-label="<?php echo htmlspecialchars(__('auto.cerrar'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <div class="modal-icon modal-icon--blue"><i class="fas fa-sign-in-alt"></i></div>
            <h2 id="modal-login-title"><?php echo htmlspecialchars(__('auto.acceder_al_portal'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo htmlspecialchars(__('auto.ingres_el_dni_del_padre_madre_o_tutor_regi'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <?php if ($familiaErrorMsg !== ''): ?>
        <div class="modal-alert modal-alert--error" role="alert"><?php echo htmlspecialchars($familiaErrorMsg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form class="modal-form" id="form-login" method="post" action="familia_login.php" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="modal-field">
                <label for="login-dni"><i class="fas fa-id-card"></i><?php echo htmlspecialchars(__('auto.dni_del_responsable'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="login-dni" name="dni_responsable" inputmode="numeric" pattern="[0-9.\s-]{7,12}" maxlength="14" placeholder="<?php echo htmlspecialchars(__('inicio.modal_dni_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" required autocomplete="off">
            </div>
            <button type="submit" class="btn-modal-submit">
                <i class="fas fa-sign-in-alt"></i><?php echo htmlspecialchars(__('auto.entrar'), ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
        <div class="modal-separator"><span><?php echo htmlspecialchars(__('inicio.modal_or'), ENT_QUOTES, 'UTF-8'); ?></span></div>
        <a href="<?php echo $href('/public/login.php'); ?>" class="btn-modal-staff">
            <i class="fas fa-user-shield"></i><?php echo htmlspecialchars(__('auto.soy_docente_personal_del_colegio'), ENT_QUOTES, 'UTF-8'); ?></a>
        <div class="modal-disclaimer" style="margin-top: 1.25rem; font-size: 0.72rem; color: #64748b; text-align: center; line-height: 1.45; border-top: 1px dashed rgba(148, 163, 184, 0.25); padding-top: 0.85rem;">
            <p style="margin-bottom: 0.25rem; font-weight: 600; color: #2563eb; display: flex; align-items: center; justify-content: center; gap: 0.25rem;"><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars(__('auto.seguridad_y_privacidad'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p style="margin: 0;"><?php echo htmlspecialchars(__('inicio.modal_disclaimer'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
</div>

<!-- ═══════════ TOAST ═══════════ -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script type="application/json" id="inicio-page-data"><?php echo json_encode([
    'familiaError' => $familiaErrorMsg,
    'openLoginModal' => $familiaErrorMsg !== '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

<script nonce="<?php echo $nonce; ?>">
/* ─── Partículas flotantes ─── */
(function () {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const container = document.getElementById('particles');
    container.appendChild(canvas);
    let W, H, particles;

    function resize() {
        W = canvas.width = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    function init() {
        resize();
        particles = Array.from({ length: 55 }, () => ({
            x: Math.random() * W,
            y: Math.random() * H,
            r: Math.random() * 3 + 1,
            dx: (Math.random() - 0.5) * 0.4,
            dy: (Math.random() - 0.5) * 0.4,
            alpha: Math.random() * 0.4 + 0.1,
            color: ['#38bdf8', '#7dd3fc', '#bae6fd', '#60a5fa', '#93c5fd'][Math.floor(Math.random() * 5)],
        }));
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);
        particles.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = p.color;
            ctx.globalAlpha = p.alpha;
            ctx.fill();
            p.x += p.dx;
            p.y += p.dy;
            if (p.x < 0 || p.x > W) p.dx *= -1;
            if (p.y < 0 || p.y > H) p.dy *= -1;
        });
        ctx.globalAlpha = 1;
        requestAnimationFrame(draw);
    }

    init();
    draw();
    window.addEventListener('resize', () => { resize(); });
})();

/* ─── Navbar scroll ─── */
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 40);
});

/* ─── Hamburger ─── */
document.getElementById('nav-hamburger').addEventListener('click', () => {
    document.getElementById('navbar-mobile').classList.toggle('open');
});

/* ─── Smooth scroll ─── */
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const t = document.querySelector(a.getAttribute('href'));
        if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('navbar-mobile').classList.remove('open');
    });
});

/* ─── Reveal on scroll ─── */
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal, .reveal-right').forEach(el => observer.observe(el));

/* ─── Modales ─── */
function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('active');
    document.body.style.overflow = '';
}

document.querySelectorAll('.btn-login-open').forEach(b => b.addEventListener('click', () => openModal(b.dataset.modal)));
document.querySelectorAll('.modal-close').forEach(b => b.addEventListener('click', () => closeModal(b.dataset.close)));
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); }));
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(m => closeModal(m.id));
});

/* ─── Hash #acceso-familias / #modal-login → modal de acceso ─── */
(function () {
    function openFromHash() {
        const raw = (location.hash || '').replace(/^#/, '').toLowerCase();
        if (raw === 'acceso-familias' || raw === 'modal-login' || raw === 'login-familias') {
            openModal('modal-login');
            history.replaceState(null, '', location.pathname + location.search);
        }
    }
    openFromHash();
    window.addEventListener('hashchange', openFromHash);
})();

(function () {
    var el = document.getElementById('inicio-page-data');
    if (!el || !el.textContent) return;
    try {
        var data = JSON.parse(el.textContent);
        if (data.openLoginModal) {
            openModal('modal-login');
            if (window.history && window.history.replaceState) {
                try {
                    window.history.replaceState(null, '', window.location.pathname + window.location.hash);
                } catch (e2) {}
            }
        }
    } catch (e) {}
})();

/* ─── Toast ─── */
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast--' + type + ' show';
    setTimeout(() => t.classList.remove('show'), 4000);
}

/* ─── Contador animado ─── */
function animateCount(el, target, duration = 1800) {
    let start = 0;
    const step = (timestamp) => {
        if (!start) start = timestamp;
        const progress = Math.min((timestamp - start) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(ease * target).toLocaleString('es');
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}
const countObserver = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            const val = parseInt(e.target.dataset.count, 10);
            animateCount(e.target, val);
            countObserver.unobserve(e.target);
        }
    });
}, { threshold: 0.5 });
document.querySelectorAll('.stat-band-val').forEach(el => countObserver.observe(el));
</script>
</body>
</html>
