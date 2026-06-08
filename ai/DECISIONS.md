# DECISIONS — Siroko Carrito & Checkout

> Registro de decisiones de proyecto: qué se decidió, por qué, y qué alternativa se descartó.
> Distingue criterio propio (humano) de sugerencia de IA. Complementa a `PLAN.md` (el QUÉ) y `CLAUDE.md` (el CÓMO).

---

## D-001 — Los tests se verifican siempre en PHP 8.3

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Criterio propio (impuesto por el humano).

**Decisión:** La suite de tests debe pasar y verificarse en **PHP 8.3**, que es la versión de entrega de la prueba. No basta con que pase en otra versión.

**Contexto:** Al arrancar, el entorno local no tenía PHP ni Composer, solo Docker. La imagen oficial `composer` corre PHP 8.5, así que la primera ejecución de los tests de `Money` se hizo en 8.5 (20 tests en verde). El humano señaló que eso no garantiza nada: una función o una firma puede existir en 8.5 pero no en 8.3, y la entrega corre en 8.3.

**Por qué:** No dejar nada al azar. Validar contra una versión que no es la de entrega puede ocultar incompatibilidades (funciones nuevas, cambios de firma o de comportamiento).

**Cómo se aplica:**
- Para cada pieza, correr la suite con la imagen de la versión objetivo:
  `docker run --rm -v "$PWD":/app -w /app php:8.3-cli php vendor/bin/phpunit`
- La imagen `composer` se usa solo para `composer install`, no como runtime de tests.
- El servicio PHP del `docker-compose` final irá fijado a **8.3**.

**Verificación:** Tras la decisión, los 20 tests de `Money` se re-ejecutaron en `php:8.3-cli` → PHP 8.3.31, todos en verde.

---

## D-002 — Una sola moneda en uso, pero el modelo admite multi-moneda

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Decisión de negocio (humano), con detalle de implementación propuesto por IA.

**Decisión:** Por ahora el carrito y el checkout operan con **una sola moneda**. No se implementa soporte multi-moneda (ni catálogo, ni casos de uso, ni conversión). Pero queda **decidido y soportado por el modelo** poder usar cualquier moneda: el value object `Money` lleva siempre su moneda y rechaza operar entre monedas distintas.

**Contexto:** Al confirmar el entendimiento del diseño se preguntó si la prueba asume una sola moneda o varias de verdad. El humano respondió: una sola por ahora.

**Por qué:** El enunciado no pide multi-moneda; implementar un motor de conversión o catálogos multi-divisa sería sobre-ingeniería. A la vez, modelar la moneda dentro de `Money` es una invariante barata que deja la puerta abierta sin reescribir nada el día que se necesite.

**Cómo se aplica:**
- `Money` guarda `amount` (céntimos) **y** `currency`; `add`/`subtract`/comparaciones lanzan `CurrencyMismatch` si las monedas difieren.
- NO se construyen casos de uso, catálogo ni conversión con varias monedas.
- La mezcla de monedas se cubre como *sad path* unitario de `Money`, no como funcionalidad del carrito/checkout.

**Alternativa descartada:** Quitar la moneda de `Money` y asumir un único símbolo implícito. Se descartó porque ataría el modelo a una sola divisa y haría costoso el cambio futuro, sin ahorrar complejidad real ahora.

---

## D-003 — Toda excepción de dominio extiende `\DomainException`

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Criterio propio (impuesto por el humano), por coherencia.

**Decisión:** Todas las excepciones de la **capa de dominio** extienden `\DomainException`. Se prohíbe mezclar bases (`\InvalidArgumentException`, etc.) dentro del dominio.

**Contexto:** Al crear `Quantity`, la IA propuso `InvalidQuantity extends \InvalidArgumentException`. El humano detectó la incoherencia: en `Money`, `CurrencyMismatch` ya extendía `\DomainException`. Al revisar, se vio que además `InvalidCurrency` también extendía `\InvalidArgumentException`, así que la incoherencia ya existía dentro de `Money`.

**Por qué:** Una única raíz de excepción para el dominio hace el modelo predecible y permite, si hiciera falta, capturar "cualquier error de dominio" en una sola cláusula. Semánticamente, un estado de dominio inválido (cantidad < 1, moneda vacía) es una violación de invariante del dominio, no un mero argumento mal pasado.

**Cómo se aplica:**
- `InvalidQuantity`, `InvalidCurrency` y `CurrencyMismatch` extienden `\DomainException`.
- Toda excepción futura de la capa de dominio debe extender `\DomainException`.

**Nota técnica:** `\DomainException` e `\InvalidArgumentException` son ambas hijas de `\LogicException`, por lo que el cambio no rompió los tests que afirman la clase exacta de la excepción. Suite re-ejecutada en PHP 8.3: 28 tests en verde.

---

## D-004 — AddItemToCart crea el carrito al vuelo si no existe (get-or-create)

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Decisión de negocio (humano).

**Decisión:** Cuando `AddItemToCart` recibe un `cartId` que aún no existe en BD, **crea el carrito** con ese `cartId` y añade la línea. No exige que el carrito preexista.

**Contexto:** La API es `POST /cart/{cartId}/items`: el `cartId` llega en la ruta (lo aporta el cliente, p. ej. cookie/sesión) y el PLAN no contempla un comando "crear carrito". La IA preguntó si añadir a un carrito inexistente debía crearlo o lanzar `CartNotFound`.

**Por qué:** Es la UX natural (no se pre-crean carritos) y evita añadir un endpoint/flujo de creación que el PLAN no pide. La identidad del carrito la fija el cliente vía la ruta.

**Cómo se aplica:**
- El handler de `AddItemToCart` hace get-or-create: intenta `CartRepository::get($cartId)` y, si lanza `CartNotFound`, construye un `Cart` nuevo con ese id.
- Capturar `CartNotFound` aquí es control de flujo legítimo del patrón get-or-create; NO contradice la regla "get siempre lanza" ([[never-return-null-on-lookup]] / preferencia del usuario), que sigue vigente para el resto de lookups.
- **Solo aplica a AddItem.** `UpdateCartItem` y `RemoveCartItem` sobre un carrito inexistente SÍ lanzan `CartNotFound` (no tiene sentido actualizar/eliminar en un carrito que no existe).

---

## D-005 — El OrderId lo genera el servidor vía puerto OrderIdGenerator

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Decisión de negocio (humano) + diseño hexagonal.

**Decisión:** El `OrderId` lo genera el **servidor** durante el checkout, a través de un puerto de dominio `OrderIdGenerator::nextIdentity(): OrderId`. La implementación (infra) usará `symfony/uid` (el stack ya es Symfony). En tests se usa un generador determinista.

**Contexto:** Se aplazó la generación de ids al definir los identificadores; el checkout la necesita. A diferencia del `CartId` (que llega en la ruta), la orden la identifica el servidor.

**Por qué:** Mantiene el dominio puro (no mete aleatoriedad ni librerías en el VO) y el handler 100% testeable con un fake. Se descartó `OrderId::generate()` en PHP puro (aleatoriedad en el dominio, tests no deterministas) y pasar el id dentro del comando (el cliente no debe poder fijarlo).

---

## D-006 — Checkout devuelve CheckoutResult; el pago rechazado es resultado, no excepción

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Decisión de negocio (humano).

**Decisión:** `Checkout` devuelve un DTO `CheckoutResult` (orderId, estado PAID/FAILED, total, moneda, motivo de fallo). El **pago rechazado NO lanza excepción**: la orden queda `FAILED` (persistida) y el resultado lo refleja.

**Por qué:** El controller necesita el desenlace en una sola llamada para mapearlo a HTTP (p. ej. PAID→201, FAILED→402 con motivo). El rechazo de pago es un desenlace de negocio válido, no un error de programa. Las excepciones se reservan para lo que aborta antes de cobrar (carrito inexistente/vacío, stock insuficiente).

**Nota:** Es la excepción a "los comandos devuelven void" (los comandos de carrito sí son void): el checkout produce un id de servidor y un estado que el caller debe conocer.

---

## D-007 — La orden se persiste en PENDING antes de cobrar

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Criterio propio (IA), resolviendo una contradicción del PLAN.

**Decisión:** En el checkout, la orden se **persiste en `PENDING` ANTES** de invocar la pasarela. Secuencia: validar stock → crear Order PENDING → **save** → charge → markPaid/markFailed → **save**.

**Contexto:** El PLAN §3.7 lista "Persistir la orden" como paso 8 (al final), pero su propia nota justifica crear la orden antes del pago "para que si el proceso se interrumpe tras cobrar, la orden ya exista en PENDING y sea conciliable". Eso solo se cumple si la orden está **persistida** antes del cobro, no solo creada en memoria.

**Por qué:** Honra la razón declarada en el PLAN: nunca cobrar sin dejar rastro persistente del pedido. El coste son dos `save` (insert PENDING + update estado), asumible.

---

## D-008 — AddItem valida existencia del producto con ProductCatalog::exists(), no con pricesFor descartado

**Fecha:** 2026-06-08
**Estado:** Vigente
**Tipo:** Criterio propio (impuesto por el humano).

**Decisión:** `AddItemToCart` valida que el producto existe en el catálogo mediante un método explícito `ProductCatalog::exists(ProductId): bool`; si no existe, el handler lanza `ProductNotInCatalog` antes de tocar el carrito. No se usa `pricesFor()` para esto.

**Contexto:** La primera versión del handler llamaba a `pricesFor([$productId])` solo para validar existencia, trayendo un precio que descartaba. El humano pidió un método explícito en el puerto.

**Por qué:** `exists()` es una query pura de intención clara (¿existe?) que devuelve `bool` sin ambigüedad, y evita traer precio/stock innecesarios solo para comprobar existencia. `pricesFor()` queda para cuando de verdad se necesitan los precios (GetCart, Checkout).

**Nota de orden:** Cronológicamente esta decisión es anterior a las del checkout (D-005…D-007), pero se registra como D-008 para no desplazar números ya referenciados en el código (`OrderIdGenerator` cita D-005, `CheckoutResult` cita D-006) ni en commits previos. El número es un identificador estable, no un orden cronológico estricto.

---
