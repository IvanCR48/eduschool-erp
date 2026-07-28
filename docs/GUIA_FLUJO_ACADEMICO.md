# 📘 Guía Profesional de Puesta en Marcha Académica  
## Especialización → Curso → Materia → Profesor → Estudiante → Horario

> **Propósito**  
> Este documento está diseñado como una guía exhaustiva para responsables académicos y administrativos que necesitan configurar desde cero el ecosistema educativo en el **Sistema Administrativo E.E.S.T. N°2**. El flujo recomendado garantiza coherencia entre especialidades, cursos, asignaturas, docentes, estudiantes y horarios.  

---

## 🧭 Visión General del Proceso

| Etapa | Objetivo central | Resultado esperado |
|-------|------------------|--------------------|
| 1. Especialización | Definir orientaciones técnicas | Base para cursos superiores técnicos |
| 2. Curso | Crear estructuras de año/división/turno | Contenedor académico para estudiantes |
| 3. Materia | Registrar asignaturas generales y técnicas | Ofertas educativas vinculadas a cursos |
| 4. Profesor | Incorporar docentes al sistema | Recurso humano disponible por materia |
| 5. Estudiante | Inscribir alumnos en cursos | Matrícula activa y clasificable |
| 6. Horario | Planificar distribuciones semanales | Cronograma oficial por curso y materia |

> ⚠️ **Importante:** El orden es intencional. Saltar pasos compromete la integridad de datos y dificulta corregir inconsistencias más adelante.

---

## 1️⃣ Especialización: Definir la oferta técnica

### ¿Por qué primero?
- Las especializaciones (orientaciones) son requisito para crear cursos superiores (4°, 5°, 6°).
- Determinan qué materias técnicas estarán disponibles.

### Ruta en el sistema  
`Menú principal → Especialidades`

### Procedimiento detallado
1. Pulsá **“Nueva Especialidad”**.
2. Completá:
   - **Nombre** (obligatorio): ej. “Técnico en Programación”.
   - **Descripción** (opcional, pero recomendable): ej. “Orientación en desarrollo de software y bases de datos”.
3. Guardá la especialidad.
4. Repetí para cada orientación existente en la escuela.

### Buenas prácticas
- Utilizá denominaciones oficiales del plan de estudios.
- Diferenciá especialidades diurnas de nocturnas solo si ofrecen contenidos distintos.
- Documentá internamente quién autorizó cada creación (útil para auditorías).

---

## 2️⃣ Curso: Crear la estructura académica

### Requisitos previos
- Especialidades creadas (para cursos superiores).
- Turnos verificados (Mañana, Tarde, Vespertino, Noche).

### Ruta en el sistema  
`Menú principal → Cursos`

### Procedimiento detallado
1. Hacé clic en **“Nuevo Curso”**.
2. Completá:
   - **Año**: 1 a 6 (o 7 según plan).
   - **División**: puede ser letra (`A`, `B`) o número (`1`, `2`) según política institucional.
   - **Turno**: seleccioná el turno habilitado.
   - **Especialidad**:
     - **Ciclo Básico (1° a 3°)**: elegir “Sin especialidad”.
     - **Ciclo Superior (4° a 6°)**: asociar a la especialidad correspondiente.
3. Guardá.
4. Repetí el proceso hasta cubrir todas las divisiones del ciclo lectivo.

### Checklist de control
- [ ] Turno coherente con disponibilidad del edificio.
- [ ] Especialidad asignada solo a años superiores.
- [ ] División única (evitar duplicados en el mismo año/turno).

---

## 3️⃣ Materia: Registrar el plan de asignaturas

### Rol de la etapa
- Define el “qué se dicta” y en “qué cursos”.
- Es la base para asignar docentes y horarios.

### Ruta en el sistema  
`Menú principal → Materias`

### Procedimiento detallado
1. Presioná **“Nueva Materia”**.
2. Completá:
   - **Nombre** (obligatorio): ej. “Matemática I”, “Programación Avanzada”.
   - **Carga horaria** (opcional, pero útil para reportes).
   - **Especialidad**:
     - Vacío = materia general/común.
     - Seleccionada = materia técnica específica.
3. **Asigná cursos**:
   - Marcá todos los cursos donde se dicta.
   - Para materias técnicas solo aparecerán cursos de la especialidad correspondiente (desde esta actualización siempre verás también los cursos ya vinculados, aunque sean de otra especialidad, para no perder asignaciones previas).
4. Guardá.

### Gestión posterior: “Gestionar Cursos”
- Accedé desde el listado de materias.
- Permite agregar o quitar cursos asociados.
- El sistema muestra solo cursos compatibles con la especialidad más los que ya tenían la materia asignada.

### Recomendaciones
- Documentá qué materias son anuales, cuatrimestrales o de taller.
- Creá un convenio de nombres (“Matemática 1º” vs “Matemática Primera”).

---

## 4️⃣ Profesor: Alta del cuerpo docente

### Rol de la etapa
- Registra cada docente con sus datos personales.
- Permite asignarlos a materias y horarios.

### Ruta en el sistema  
`Menú principal → Profesores`

### Procedimiento detallado
1. Elegí **“Nuevo Profesor”**.
2. Completá obligatorios:
   - **DNI** (7-8 dígitos, sin puntos).
   - **Apellido** / **Nombre**.
3. Completá opcionales (altamente recomendado):
   - Fechas (nacimiento e ingreso).
   - Contacto (teléfono/s, email).
   - Domicilio.
   - Título.
   - Especialidad principal.
4. Guardá.  
   > Nota: cualquier validación fallida (“DNI duplicado”, “Email inválido”, “Teléfono inválido”) ahora se mostrará con mensaje específico.

### Asociaciones docentes
- Desde la ficha del profesor podés asignarlo a materias y cursos.
- Solo aparecerán materias correspondientes al curso seleccionado (según la etapa anterior).

### Buenas prácticas
- Registrar fechas para calcular antigüedad.
- Verificar que el DNI no exista como usuario o preceptor antes de cargarlo.

---

## 5️⃣ Estudiante: Ingreso de matrícula

### Ruta en el sistema  
`Menú principal → Estudiantes`

### Procedimiento detallado
1. Clic en **“Registrar Estudiante”**.
2. Completá:
   - **DNI**, **Apellido**, **Nombre** (obligatorios).
   - Información adicional: fecha de nacimiento, contacto, salud (según política institucional).
3. Asigná el **Curso** (ya debe existir desde el Paso 2).
4. Guardá.

### Recomendaciones
- Si vas a importar muchos alumnos, subdividí la tarea por curso.
- Revisá la sección “Estudiantes por Curso” al finalizar para confirmar asignaciones.

---

## 6️⃣ Horario: Planificación semanal

### Prerrequisitos
- Materias asignadas a cursos (Paso 3).
- Al menos un profesor registrado y vinculado a esas materias.

### Ruta en el sistema  
`Menú principal → Horarios`

### Nueva lógica implementada (2025-11)
- **Materias filtradas por curso**: Al seleccionar un curso, el sistema carga únicamente las materias asociadas a ese curso (`materia_curso`).
- **Profesores filtrados por materia**: Una vez elegida la materia, solo se listan docentes que dictan esa combinación curso-materia.
- **Edición segura**: Al modificar un horario, se precarga la materia y el docente actuales, conservando coherencia.

### Procedimiento para “Nuevo Horario”
1. Seleccioná el **Curso**.
2. Esperá que se habilite el selector de **Materia** y elegí entre las materias del curso.
3. Elegí el **Día de la semana**, **Hora de inicio y fin** (entre las predefinidas).
4. Opcionalmente completá **Aula**.
5. Asigná el **Docente** (solo mostrará quienes dictan esa materia en ese curso).
6. Guardá.

### Consejos de gestión
- Cargá primero horarios prácticos (talleres) y luego materias generales.
- Utilizá la visual semanal para detectar superposiciones.
- Recordá que los campos horario usan un set fijo de horas institucionales; si necesitás más franjas, gestioná la ampliación en Admin Tools.

---

## 📑 Anexo: Herramientas de verificación rápida

| Verificación | Ruta recomendada | Qué revisar |
|--------------|------------------|-------------|
| Cursos vs Especialidad | `Materias → Gestionar Cursos` | ¿Los cursos asignados corresponden a la especialidad correcta? |
| Profesores vs Materias | `Profesores → Ficha → Materias` | ¿Cada docente figura solo en materias que dicta realmente? |
| Estudiantes vs Cursos | `Estudiantes → Filtros por Curso` | ¿Se cargaron todos los alumnos previstos? |
| Horarios completos | `Horarios → Visualización semanal` | ¿Existen huecos inesperados o superposiciones? |

---

## 🛠️ Herramientas administrativas complementarias

- **Admin Tools → Monitoreo**: confirma el estado del sistema, sesiones activas y alertas de seguridad.
- **Admin Tools → Backups**: realizá un respaldo completo después de finalizar la carga masiva de datos.
- **Documentación en línea**: revisá `GUIA_CARGA_DATOS.md` para obtener un panorama general y checklists.

---

## ✅ Resumen ejecutivo para directivos

1. **Especialidad → Curso → Materia** definen la malla curricular.  
2. **Profesor → Estudiante** completan la nómina humana vinculada a esa malla.  
3. **Horario** operacionaliza la planificación semanal.

> Al finalizar estos pasos, el sistema queda listo para registrar notas, generar boletines, controlar asistencia y producir reportes académicos y administrativos.

---

## 📝 Registro de cambios

| Fecha | Versión | Modificaciones |
|-------|---------|----------------|
| 13/11/2025 | 1.0 | Creación del documento |

---

**Contacto de soporte interno:**  
- Mesa de Ayuda TIC – anexos 101/102  
- Correo institucional: `soporte@sistema-eest2.edu`

**Fin del documento**

