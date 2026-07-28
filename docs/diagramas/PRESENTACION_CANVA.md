# Presentacion Canva - Sistema Administrativo E.E.S.T. N°2 (Mermaid 2026)

Este documento propone una estructura lista para Canva con orden narrativo, titulos y textos breves.
Los diagramas se presentan en Mermaid (sintaxis actual 2026), listos para pegar en mermaid.live.

---

## Diapositiva 1 - Portada
**Titulo:** Sistema Administrativo E.E.S.T. N°2  
**Subtitulo:** Vision general, procesos y arquitectura tecnica  
**Texto corto:** Plataforma digital para gestionar operacion academica, seguridad y seguimiento institucional.

---

## Diapositiva 2 - Objetivo de la presentacion
**Titulo:** Que vas a ver en esta presentacion  
**Texto corto:** Primero una vista simple para toda la comunidad educativa, luego procesos clave y finalmente la arquitectura tecnica para desarrollo y mantenimiento.

---

## Diapositiva 3 - Vista General del Sistema
**Diagrama:** Vista general del sistema (overview)  
**Titulo:** Vista general de arquitectura actual  
**Texto corto:** Muestra como se conectan interfaz web, controladores, middleware, servicios, persistencia y base de datos.

---

## Nivel 1 - Alto nivel (No tecnico)

## Diapositiva 4 - Contexto
**Diagrama:** Diagrama de contexto  
**Titulo:** Quienes interactuan con el sistema  
**Texto corto:** Profesores, preceptores, directivos, familias y administradores interactuan segun permisos; alumnos no inician sesion.

## Diapositiva 5 - Arquitectura por bloques
**Diagrama:** Arquitectura general por bloques  
**Titulo:** Como esta armado el sistema en bloques  
**Texto corto:** El flujo principal conecta frontend, logica de negocio, seguridad, persistencia y servicios externos.

## Diapositiva 6 - Mapa mental de funcionalidades
**Diagrama:** Mapa mental de funcionalidades  
**Titulo:** Todo lo que resuelve la plataforma  
**Texto corto:** Resume funcionalidades academicas, seguridad, reportes, familias y operacion tecnica.

## Diapositiva 7 - User journey
**Diagrama:** User journey — perfiles de usuario  
**Titulo:** Recorrido de uso por perfil  
**Texto corto:** Explica de forma simple como profesor/admin, preceptor, directivo y familia usan el sistema en su rutina diaria.

---

## Nivel 2 - Intermedio (Mixto)

## Diapositiva 8 - Flujo principal del sistema
**Diagrama:** Flujograma general de procesos  
**Titulo:** Del login a la operacion academica  
**Texto corto:** Describe el camino principal: autenticacion, dashboard, gestion por rol, persistencia y auditoria.

## Diapositiva 9 - Casos de uso UML
**Diagrama:** Diagrama de casos de uso  
**Titulo:** Que puede hacer cada actor  
**Texto corto:** Define permisos y responsabilidades de cada perfil dentro del sistema.

## Diapositiva 10 - Actividad (proceso clave)
**Diagrama:** Diagrama de actividad — carga manual de notas  
**Titulo:** Carga manual de notas y validacion  
**Texto corto:** Muestra el flujo operativo entre personal autorizado y sistema con validaciones, persistencia y auditoria.

---

## Nivel 3 - Tecnico (Desarrollo)

## Diapositiva 11 - Diagrama de clases
**Diagrama:** Diagrama de clases (nucleo del sistema)  
**Titulo:** Estructura de clases y responsabilidades  
**Texto corto:** Relaciona controladores, servicios, mappers y entidades para entender mantenimiento y extensibilidad.

## Diapositiva 12 - Diagrama de secuencia
**Diagrama:** Diagrama de secuencia — carga manual de nota  
**Titulo:** Interaccion en tiempo real  
**Texto corto:** Detalla paso a paso como viaja una accion desde la UI hasta la base de datos y la auditoria.

## Diapositiva 13 - Modelo ER
**Diagrama:** Diagrama entidad-relacion (base de datos)  
**Titulo:** Estructura de datos del sistema  
**Texto corto:** Presenta entidades academicas clave y sus relaciones para consistencia y trazabilidad.

## Diapositiva 14 - Componentes UML
**Diagrama:** Diagrama de componentes  
**Titulo:** Modulos de software y dependencias  
**Texto corto:** Enfoca como se organizan los componentes internos y como colaboran entre capas.

## Diapositiva 15 - Despliegue UML
**Diagrama:** Diagrama de despliegue  
**Titulo:** Donde corre cada parte del sistema  
**Texto corto:** Describe clientes, servidor web, base de datos, OAuth externo y almacenamiento de reportes/backups.

---

## Diapositiva 16 - Cierre
**Titulo:** Impacto y proximo paso  
**Texto corto:** El sistema ya tiene base funcional, seguridad y trazabilidad. Proximo paso: roadmap de mejoras por prioridad institucional.

---

## Recomendacion visual para Canva
- Usar una plantilla limpia con fondo claro.
- Mantener siempre el mismo color por nivel: alto, intermedio, tecnico.
- Dejar cada texto en 1-2 lineas maximo.
- Mostrar solo una idea principal por diapositiva.
- Definir tema Mermaid por bloque (`default` o `forest`) para consistencia visual.
- Cerrar cada bloque de diagrama con la leyenda: "→ Listo para pegar en mermaid.live".
- Exportar version final en PDF para directivos y en PPT para equipo tecnico.
