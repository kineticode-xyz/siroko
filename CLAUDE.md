# CLAUDE.md — Reglas del proyecto Siroko Carrito & Checkout

Este archivo define cómo debes comportarte al trabajar en este repositorio. Es el complemento del `PLAN.md` (que define QUÉ construir); este define CÓMO trabajar. Respétalo en cada interacción.

---

## Contexto

- Prueba técnica de Siroko: **Opción A — Carrito & Checkout**.
- Stack: **PHP 8.3** (en Docker), **Symfony**, **MySQL** (Doctrine), PHPUnit.
- Arquitectura: **Hexagonal + DDD + CQRS**.
- La especificación completa está en `ai/PLAN.md`. Cuando haya duda de QUÉ construir, consúltalo. Esto es lo que rige CÓMO construir.

---

## Regla de oro: el dominio es PHP puro

La capa de dominio (`Money`, `Quantity`, `CartItem`, `Cart`, `Order`, `OrderLine`, puertos) **no puede conocer el framework**.

- **Prohibido** importar `Symfony\...` o `Doctrine\...` en la capa de dominio.
- **Prohibido** que una entidad de dominio extienda una clase del framework.
- **Prohibido** anotaciones/atributos de Doctrine dentro de entidades de dominio. El mapeo ORM vive en Infraestructura (XML/PHP mapping o clases separadas).
- La dependencia apunta siempre hacia dentro: Infraestructura → Aplicación → Dominio. El dominio no importa de las otras capas.

Si una solución requiere que el dominio sepa de infraestructura, la solución es incorrecta. Detente y replantéala.

---

## Reglas de modelado (no negociables)

- **Dinero = entero en céntimos, SIEMPRE.** Prohibido `float`/`double` para dinero. `19,99 €` es `1999`.
- **Value objects inmutables.** Sin setters que muten estado. Las operaciones devuelven una instancia nueva.
- **Value objects autovalidados:** estado inválido imposible de construir (Money con moneda vacía, Quantity < 1, etc. → excepción en el constructor).
- **El dominio no llama a infraestructura.** Precios y stock se le **entregan** desde la capa de aplicación, no los consulta él.
- **Entidades con métodos de intención de negocio**, no getters/setters anémicos. (`cart.addItem(...)`, no `cart.setItems(...)`.)
- **Acceso al agregado solo por la raíz.** Nadie modifica un `CartItem` directamente; se pide al `Cart`.
- **Carrito y Orden son contextos separados.** No reutilices `CartItem` como `OrderLine`. `OrderLine` lleva precio congelado; `CartItem` no lleva precio.
- **El precio nunca llega del cliente.** Se consulta en el servidor (catálogo).

---

## Prohibido el estilo Laravel/Eloquent

Este proyecto NO usa los patrones de Laravel. En concreto, evita por inercia:

- **Active Record** (entidad que se guarda a sí misma: `$entity->save()`). Usamos **Data Mapper**: la persistencia la hace un repositorio en Infraestructura.
- **Facades** y helpers globales mágicos.
- `Model::all()`, scopes de Eloquent, y similares.
- Lógica de negocio en controllers. Los controllers (Infraestructura) son finos: reciben la petición, invocan un caso de uso (Aplicación), devuelven la respuesta.

---

## Reglas de testing

- **Cada test debe poder fallar.** Si un test pasaría aunque la lógica estuviera rota, no sirve: reescríbelo o elimínalo.
- **Prohibidos los tests vacíos:** los que solo comprueban "no lanza excepción", o que mockean tanto que acaban verificando el mock en lugar del comportamiento real.
- **Dominio: sin mocks.** Es PHP puro; instáncialo y verifica comportamiento y estado.
- **Aplicación: mocks solo en los puertos** (catálogo, pago, repositorios). Verifica efectos observables (estado resultante, orden creada con tal estado), no llamadas internas.
- Cubre los **sad paths** explícitamente: carrito vacío, stock insuficiente, pago rechazado, monedas distintas, cantidad < 1, transición de estado ilegal.

---

## Cómo debes comportarte conmigo

- **Explica tus decisiones.** Cuando elijas un enfoque, di por qué y qué alternativa descartaste. No me des solo el resultado.
- **No inventes librerías ni APIs.** Si propones una dependencia, verifica que existe (Packagist) antes de añadirla. Si no estás seguro de una firma, dilo.
- **Prefiere lo simple a lo sobre-diseñado.** No introduzcas event sourcing, buses de mensajería, ni un sistema de inventario completo. El enunciado no los pide y meterlos es un error de criterio.
- **Trabaja pieza a pieza.** No generes el proyecto entero de golpe. Una pieza, con sus tests, que yo reviso y entiendo, antes de la siguiente. El orden: `Money` → `Quantity` → identificadores → `CartItem` → `Cart` → `OrderLine`/`Order` → puertos → casos de uso → adaptadores.
- **Señala cuándo algo es decisión de negocio** (que decido yo) frente a detalle de implementación (que puedes proponer).
- Si te pido algo que contradice estas reglas o el `PLAN.md`, **avísame** en lugar de obedecer en silencio.

---

## Restricciones de la prueba (recordatorio permanente)

Todo lo que construyas debe servir a uno de estos requisitos. Si no sirve a ninguno, sobra:

- Gestión del carrito (añadir/actualizar/eliminar) + obtención de contenido.
- Checkout que genera orden **persistente**.
- Dominio desacoplado del framework; Hexagonal + DDD + CQRS.
- Testing exhaustivo de casos de uso.
- **Performance medida con datos** (no afirmar "es rápido" sin número).
- Entregables: OpenAPI, modelado documentado, `docker-compose up`, comando de tests, carpeta `/ai`.

---

## Commits y trazabilidad

- Mensajes de commit que reflejen **qué se decidió y por qué**, distinguiendo criterio propio de sugerencia de IA.
- Ejemplo del estilo esperado: `feat(domain): Money como entero en céntimos (IA sugirió float, rechazado por precisión)`.
- Cuando yo rechace una sugerencia tuya por criterio, recuérdame anotarlo en `ai/DECISIONS.md`.
