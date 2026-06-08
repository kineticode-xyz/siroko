# PLAN — Siroko Carrito & Checkout

> Especificación funcional y técnica para guiar la implementación (humano + agente de IA).
> **Opción elegida:** A — Carrito & Checkout (Core Business).
> Este documento es la fuente de verdad del diseño. Cualquier código debe poder rastrearse a una sección de aquí. Lo que no sirva a ningún requisito, sobra.

---

## 1. Objetivo y alcance

Diseñar una cesta de compra que permita añadir, actualizar y eliminar productos y obtener su contenido; y un proceso de checkout que, al confirmar, genere una **orden persistente**.

### Dentro de alcance
- Gestión de líneas del carrito: añadir, actualizar cantidad, eliminar.
- Obtención del contenido del carrito.
- Checkout: validación de stock, congelado de precios, creación de orden persistente, procesamiento de pago (simulado vía puerto), transición de estado de la orden.

### Fuera de alcance (límites conscientes)
- **Devoluciones / postventa:** ocurren tras existir la orden; otro contexto.
- **Aviso de cambio de precio al cliente:** es UX/presentación. El dominio contempla el cambio; la notificación visual queda fuera.
- **Validación de stock "blanda" en el carrito:** el rigor se concentra en el checkout.
- **Pasarela de pago real:** se modela como puerto con adaptador fake.
- **Promociones complejas / motor de descuentos:** el modelo lo admite (Money negativo, total con suelo 0) pero no se implementa un motor.
- **Autenticación/usuarios:** no es el foco del reto.

Dejar algo fuera es deliberado: acota el esfuerzo y evita sobre-ingeniería.

---

## 2. Lenguaje ubicuo (glosario)

| Término | Significado |
|---|---|
| **Money** | Importe en céntimos (entero, con signo) + moneda. Value object. |
| **Quantity** | Número de unidades de un producto en una línea. Entero ≥ 1. Value object. |
| **CartItem** | Línea del carrito: producto + cantidad. NO lleva precio. |
| **Cart** | Agregado raíz. Carrito persistente del servidor, identificado por `CartId`. |
| **OrderLine** | Línea de orden: producto + cantidad + **precio congelado**. |
| **Order** | Agregado raíz. Registro histórico inmutable de una compra. Tiene estado. |
| **OrderStatus** | Estado de la orden: `PENDING`, `PAID`, `FAILED`. |
| **Catálogo / Inventario** | Fuente de verdad de precios vigentes y stock. Fuera del carrito. |
| **PaymentGateway** | Puerto que abstrae el cobro. Implementado por un adaptador (fake en esta entrega). |

---

## 3. Reglas de negocio

### 3.1 Dinero
- El dinero se representa **siempre como entero en céntimos**. Prohibido `float`.
- Todo `Money` lleva una moneda. No se opera dinero de monedas distintas (excepción).
- Un `Money` puede ser **negativo** (representa un descuento que se resta). Es aritmética pura.
- El **precio final a pagar nunca es negativo**: su suelo es 0. Esta regla vive en el cálculo del total, NO en `Money`.

### 3.2 Cantidades
- Una `Quantity` es **≥ 1**. Cero y negativos se rechazan en construcción.
- Eliminar un producto es una **operación explícita**, no el efecto de poner cantidad 0.
- Actualizar cantidad es por **valor absoluto** ("pon N"), no por incremento relativo.

### 3.3 Carrito
- El carrito vive en el **servidor** y es persistente (identificado por `CartId`). Dos dispositivos del mismo usuario ven el mismo carrito.
- **No existen dos líneas del mismo producto:** añadir un producto ya presente **fusiona** (suma cantidad).
- El carrito **no guarda precios**: refleja el **precio vigente** del catálogo en el momento de calcular.
- El carrito **no gestiona stock**: lo consulta del inventario.
- El **total** se calcula con precios entregados desde fuera (capa de aplicación) y aplica el suelo 0.

### 3.4 Precio (transversal)
- **En el carrito:** el precio puede cambiar; se refleja el vigente.
- **En el checkout:** al confirmar, el precio se **congela** en la orden.
- El precio es siempre la fuente de verdad del **servidor**. Nunca se acepta un precio proveniente del cliente.

### 3.5 Stock (transversal)
- **En el carrito:** consulta blanda (fuera de alcance estricto).
- **En el checkout:** validación **dura**. Si no hay stock suficiente, el checkout **falla antes de cobrar**.

### 3.6 Orden
- La orden es **inmutable en su contenido** (líneas, precios congelados, total) desde su creación.
- Lo único que evoluciona es el **estado**.
- La orden **no referencia al carrito** del que vino (el carrito se elimina; sería un puntero a la nada).
- **Transiciones permitidas:** `PENDING → PAID`, `PENDING → FAILED`. Cualquier otra lanza excepción.
- **`FAILED` es terminal.** Reintentar = crear una orden nueva.

### 3.7 Checkout (secuencia)
1. Recuperar el carrito por `CartId`.
2. Consultar precios vigentes del catálogo.
3. **Validar stock duro.** Si falla → error de dominio, sin cobrar.
4. Crear la `Order` en **`PENDING`** con líneas y precios **congelados** y total calculado.
5. Invocar `PaymentGateway`.
6. Pago OK → `PAID`; pago KO → `FAILED`.
7. Si `PAID` → eliminar el carrito.
8. Persistir la orden.

> **Por qué la orden se crea antes del pago:** garantiza que nunca se cobra sin dejar rastro del pedido. Si el proceso se interrumpe tras cobrar, la orden ya existe en `PENDING` y es conciliable.

---

## 4. Modelo de dominio

### 4.1 Value Objects

**`Money`**
- Datos: `amount` (int, céntimos, con signo), `currency`.
- Inmutable. Autovalidado (amount entero; currency no vacía).
- Operaciones: `add`, `subtract`, `multiply(int factor)`, comparaciones. Todas devuelven nuevo `Money`.
- `add`/`subtract` con monedas distintas → excepción de dominio.
- No formatea a texto (responsabilidad de presentación).

**`Quantity`**
- Dato: `value` (int ≥ 1). Inmutable. Autovalidado.

**Identificadores** (`CartId`, `OrderId`, `ProductId`)
- Value objects que envuelven el identificador. Inmutables.

### 4.2 Contexto Carrito

**`CartItem`** (entidad dentro del agregado)
- Datos: `productId`, `quantity`. **Sin precio.**
- Identidad = producto.
- Método para cambiar su `quantity` (lo orquesta el `Cart`).

**`Cart`** (agregado raíz)
- Datos: `cartId`, colección de `CartItem`.
- Operaciones:
  - `addItem(productId, quantity)` → fusiona si existe, crea si no.
  - `updateItem(productId, quantity)` → set absoluto, valida ≥ 1.
  - `removeItem(productId)` → elimina la línea.
  - `total(prices)` → recibe los precios vigentes, suma subtotales, aplica suelo 0.
- Invariantes: sin líneas duplicadas; cantidades ≥ 1; total ≥ 0.
- No conoce catálogo ni infraestructura.

### 4.3 Contexto Orden

**`OrderLine`** (entidad dentro del agregado)
- Datos: `productId`, `quantity`, **`unitPrice` (Money, congelado)**.

**`Order`** (agregado raíz)
- Datos: `orderId`, colección de `OrderLine`, `status`, `total` (congelado).
- Creación: a partir de las líneas del carrito + precios vigentes (congelados en ese instante), estado inicial `PENDING`.
- Operaciones de estado: `markPaid()`, `markFailed()` con validación de transición.
- Invariantes: contenido inmutable tras crear; transiciones de estado controladas.

**`OrderStatus`** — enum/value object: `PENDING`, `PAID`, `FAILED`.

### 4.4 Puertos (interfaces de dominio)
- `CartRepository` — `save(Cart)`, `get(CartId)`, `remove(CartId)`.
- `OrderRepository` — `save(Order)`, `get(OrderId)`.
- `ProductCatalog` — `priceOf(ProductId): Money`, `stockOf(ProductId): int` (o equivalente para consulta batch).
- `PaymentGateway` — `charge(Order): PaymentResult` (OK/KO).

### 4.5 Eventos de dominio (opcional, si aporta)
- `OrderPlaced`, `OrderPaid`, `OrderFailed`. Solo si se usan; no introducir infraestructura de eventos por moda.

---

## 5. Casos de uso (CQRS)

### Comandos (mutan estado)
| Comando | Entrada | Efecto | Sad paths |
|---|---|---|---|
| `AddItemToCart` | cartId, productId, quantity | Añade/fusiona línea | Producto inexistente; quantity < 1 |
| `UpdateCartItem` | cartId, productId, quantity | Set cantidad | Línea inexistente; quantity < 1 |
| `RemoveCartItem` | cartId, productId | Elimina línea | Línea inexistente |
| `Checkout` | cartId | Crea orden, cobra, transiciona | Carrito vacío; stock insuficiente; pago rechazado |

### Queries (leen estado)
| Query | Entrada | Devuelve |
|---|---|---|
| `GetCart` | cartId | DTO de lectura del carrito (líneas, cantidades, precios vigentes, total). NO expone el agregado. |

> Comandos devuelven void o un identificador. Queries devuelven DTOs de lectura.

---

## 6. Flujos: happy paths y sad paths

### Añadir producto (happy)
Carrito existe → producto existe → quantity ≥ 1 → línea creada o fusionada → carrito persistido.

### Añadir producto (sad)
- Producto no existe en catálogo → error, no se modifica el carrito.
- Quantity < 1 → excepción de dominio en construcción de `Quantity`.

### Checkout (happy)
Carrito no vacío → stock suficiente → orden creada en `PENDING` con precios congelados → pago OK → orden `PAID` → carrito eliminado → orden persistida.

### Checkout (sad)
- **Carrito vacío:** no se crea orden. Error claro.
- **Stock insuficiente:** validación dura falla ANTES de crear orden / cobrar. Error claro.
- **Pago rechazado:** la orden queda en `FAILED` (ya persistida en `PENDING` antes del intento). El carrito NO se elimina (no hubo compra exitosa). Reintentar = nuevo checkout / nueva orden.

---

## 7. Decisiones arquitectónicas (el agente DEBE respetarlas)

1. **Dominio puro:** sin Symfony ni Doctrine en la capa de dominio. Sin `extends` de framework, sin anotaciones de ORM en entidades de dominio.
2. **Regla de dependencia:** Infraestructura → Aplicación → Dominio. El dominio no conoce a nadie.
3. **El dominio no llama a infraestructura:** los datos externos (precios, stock) se le **entregan** desde la capa de aplicación vía puertos.
4. **Patrón Data Mapper**, no Active Record. Las entidades no se persisten a sí mismas; lo hace un repositorio en Infraestructura.
5. **Dos contextos separados:** Carrito y Orden no comparten clases (`CartItem` ≠ `OrderLine`).
6. **CQRS ligero:** comandos/queries separados, sin event sourcing ni buses complejos salvo que aporten.
7. **Pago como puerto** con adaptador fake. Nada de SDK real.
8. **Persistencia real para la orden** (requisito: "orden persistente"). MySQL vía Doctrine en Infraestructura.

---

## 8. Restricciones técnicas (evitar soluciones incorrectas/genéricas)

- Prohibido `float`/`double` para dinero. Enteros en céntimos.
- Value objects inmutables; sin setters que muten estado.
- Entidades con métodos de intención de negocio, no getters/setters anémicos.
- El precio NUNCA llega desde el cliente; se consulta en el servidor.
- No introducir dependencias sin verificar que existen (Packagist).
- No inflar el diseño con patrones que el enunciado no necesita.

---

## 9. Testing

- **Dominio:** tests unitarios **sin mocks** (es PHP puro). Cubrir invariantes: Money no mezcla monedas, Quantity ≥ 1, fusión de líneas, total con suelo 0, transiciones de OrderStatus válidas e inválidas.
- **Aplicación (casos de uso):** mocks **solo en los puertos** (catálogo, pago, repos). Verificar efectos observables y estados resultantes, no llamadas internas.
- **Regla dura:** cada test debe poder fallar. Nada de tests que solo comprueban "no lanza excepción" ni que acaban verificando el mock en lugar del comportamiento.
- Cubrir explícitamente los **sad paths** de la sección 6 (carrito vacío, sin stock, pago rechazado).
- Objetivo: máxima cobertura de **casos de uso**.

---

## 10. Performance (requisito: justificada y medida con datos)

- Identificar las operaciones a medir: carga del carrito (`GetCart`) y `Checkout`.
- Medir con datos reales (p. ej. carrito con N líneas; tiempo de respuesta del endpoint).
- Evitar el problema N+1 al consultar precios/stock: consulta **batch** de precios para todas las líneas, no una por línea.
- Documentar el número, no afirmar "es rápido" sin dato.

---

## 11. API (orientación para OpenAPI)

| Método | Ruta | Acción |
|---|---|---|
| `POST` | `/cart/{cartId}/items` | Añadir producto (body: productId, quantity) |
| `PATCH` | `/cart/{cartId}/items/{productId}` | Actualizar cantidad (body: quantity) |
| `DELETE` | `/cart/{cartId}/items/{productId}` | Eliminar producto |
| `GET` | `/cart/{cartId}` | Obtener contenido del carrito |
| `POST` | `/cart/{cartId}/checkout` | Procesar checkout → devuelve orden / estado |

> Rutas orientativas; ajustar al implementar. Documentar en OpenAPI con esquemas de request/response y los códigos de error de los sad paths.

---

## 12. Entrega (checklist del README)

- [ ] Descripción del proyecto y opción elegida.
- [ ] OpenAPI Specification.
- [ ] Modelado del dominio documentado.
- [ ] Instrucciones `docker-compose up`.
- [ ] Comando para lanzar tests.
- [ ] Enlace a la carpeta `/ai`.

---

**Fin del PLAN.**
