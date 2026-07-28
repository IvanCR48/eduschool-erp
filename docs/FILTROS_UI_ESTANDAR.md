# Estandar Unificado de Filtros (UI + Backend)

Este documento define el comportamiento y convenciones para filtros en todo el sistema.

## 1) Objetivo

Unificar la experiencia de filtrado para que:

- cambiar un selector dependiente (por ejemplo, curso) actualice opciones relacionadas (por ejemplo, estudiantes/materias) sin recargar;
- la busqueda de datos se ejecute solo al presionar el boton de accion;
- nombres de campos y estructura sean consistentes entre modulos;
- los filtros preserven alcance por rol (admin, preceptor, profesor, etc.).

## 2) Convenciones de nombres

### 2.1 Filtros de listado (GET)

- `curso`
- `estudiante`
- `materia`
- `fecha_desde`
- `fecha_hasta`
- `trimestre` (nombre de parámetro conservado por compatibilidad con URLs y formularios; en pantallas y textos se usa **cuatrimestre**.)

Notas:

- Usar estos nombres para filtros de reportes/listados.
- Evitar variantes mezcladas (`curso_id`, `estudiante_id`) salvo casos legacy puntuales.

### 2.2 Formularios de alta/edicion (POST)

- En formularios de registro se permite `*_id` para entidades (por ejemplo `estudiante_id`, `materia_id`).
- En UI, si hay selector auxiliar sin envio (por ejemplo `curso_form`), aclarar que es solo para filtro visual.

### 2.3 IDs de elementos HTML

Para filtros de listado:

- `id="curso"`
- `id="estudiante"`
- `id="materia"`

Para formularios de alta:

- `id="curso_form"` (selector visual)
- `id="estudiante_id"` (valor enviado)

## 3) Reglas de UX

- **Dependencia visual automatica:** al cambiar curso se filtran las opciones de estudiante/materia visibles.
- **Busqueda manual:** no auto-submit en cambio de select; aplicar solo con boton.
- **Botones estandar:**
  - accion principal: `Aplicar filtros` o `Buscar reporte` (segun contexto);
  - secundario: `Limpiar filtros`.
- **Estado invalido:** si una opcion seleccionada deja de pertenecer al curso actual, resetear a opcion vacia o "todos".

## 4) Reglas backend

- Normalizar GET con `trim`, validaciones de tipo y valores permitidos.
- Aplicar alcance de rol de forma consistente (curso/materia/estudiante visibles y consultables).
- Mantener PRG (Post/Redirect/Get) en acciones POST y preservar filtros de listado cuando corresponda.

## 5) Reglas frontend

- Usar `data-curso-id` en `option` para dependencias por curso.
- Reusar helper comun `js/filtros_dependientes.js`.
- Evitar logica duplicada ad-hoc por modulo.

## 6) Checklist de aceptacion por modulo

- [ ] Al cambiar curso, estudiantes/materias se actualizan visualmente sin recargar.
- [ ] No se dispara busqueda automaticamente.
- [ ] Boton de aplicar ejecuta el filtrado de datos.
- [ ] Boton limpiar restablece estado base.
- [ ] Scope por rol respetado en UI y backend.
- [ ] Filtros se preservan tras POST cuando aplique.

## 7) Uso recomendado del helper comun

- Cargar `js/filtros_dependientes.js`.
- Configurar reglas en `DOMContentLoaded`.
- Ejemplo:

```javascript
window.FiltrosDependientes.init({
  sourceSelectId: 'curso',
  targets: [
    { selectId: 'estudiante', emptyValue: '', dataAttr: 'data-curso-id' },
    { selectId: 'materia', emptyValue: '', dataAttr: 'data-curso-id' }
  ]
});
```

## 8) Compatibilidad

- Si un modulo legacy no puede migrarse en una sola iteracion, mantener comportamiento actual y registrar deuda tecnica.
- No romper formularios existentes ni nombres de parametros en rutas criticas sin migracion controlada.

## 9) Estado de migracion (etapa actual)

Modulos migrados al estandar:

- `materias_previas.php`
  - Listado GET: `curso -> estudiante` con filtro visual dependiente.
  - Formulario POST: `previa_curso_id -> previa_estudiante_id` con filtro visual dependiente.
  - Implementacion con helper comun `js/filtros_dependientes.js`.
- `grades.php`
  - Formulario "Cargar Nueva Nota Individual": `curso_id_nota -> estudiante_id` con helper comun.
  - Filtro de materias mantenido con logica especifica del modulo (multi-curso por materia).
  - Cache-busting en scripts para evitar problemas de version en navegador.
- `schedules.php`
  - Filtro GET: `curso -> profesor` con actualizacion visual y submit manual.
  - Correccion de consistencia en valor del filtro de profesor (usa `id`).
  - Cache-busting en `js/horarios.js`.
- `discipline.php`
  - Listado GET: `curso -> estudiante` con helper comun y submit manual (`Buscar reporte`).
  - Formulario POST (nuevo llamado): `curso_form -> estudiante_id` con helper comun.
  - Sin auto-submit en cambios de select; solo actualizacion visual.
- `attendance.php`
  - Filtro GET: `curso_id -> materia_id` y `curso_id -> estudiante_id` con helper comun.
  - Se conserva boton manual `Aplicar filtros` para recargar datos.
  - Cache-busting en scripts del modulo.

## 10) Criterios de cierre de etapa

- [x] Se definio y documento el estandar unificado de filtros.
- [x] Existe helper comun reutilizable para dependencias visuales.
- [x] Se migro al menos un modulo piloto de complejidad media.
- [x] Se migro `grades.php` con compatibilidad funcional.
- [x] Se migro `schedules.php` con compatibilidad funcional.
- [x] Se migro `discipline.php` al helper comun manteniendo UX esperada.
- [x] Se migro `attendance.php` al helper comun manteniendo UX esperada.
- [ ] Pendiente: extender el estandar al resto de modulos legacy fuera de esta etapa.
