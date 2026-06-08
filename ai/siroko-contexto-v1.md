# Prueba Siroko — Contexto de Diseño v1

> **Última actualización:** 8 de junio de 2026
> **Sesión:** Calibración de estrategia + modelado completo del dominio (Opción A: Carrito & Checkout). Entorno de desarrollo montado (WSL2 + Ubuntu + Docker). Modelado de dominio cerrado pieza a pieza. Pendiente: redactar PLAN.md y CLAUDE.md, y arrancar implementación en terminal con Claude Code.

---

## 0. Qué es esto y cómo usarlo

Documento de contexto de la prueba técnica de Siroko (Senior PHP Developer). Sirve para tres cosas:
1. **No perder el trabajo de diseño** (la sesión donde nació fue en incógnito, no persistente).
2. **Base del `PLAN.md`** que pide la prueba en `/ai`.
3. **Chuleta para defender el diseño** en la entrevista final.

Estilo deliberadamente igual al de los contextos del homelab: estado + decisiones razonadas + porqués + pendientes.

---

## 1. La prueba: qué piden exactamente

**Opción elegida: A — Carrito & Checkout (Core Business).**

### Requisitos funcionales de la Opción A
- Gestión de productos en el carrito: **añadir, actualizar, eliminar**.
- **Obtención del contenido** del carrito.
- **Procesar el pago (checkout)** y generar una **orden persistente** al confirmar la compra.

### Requisitos comunes obligatorios (el contrato técnico)
- Dominio **desacoplado del framework** (Symfony).
- **Arquitectura Hexagonal + DDD** (entidades, value objects, agregados).
- Soltura con **CQRS**.
- **Testing exhaustivo**: máxima cobertura de casos de uso.
- **Performance justificada y medida con datos** (NO basta decir "es rápido"; hay que enseñar un número).

### Uso de IA (pesa igual que el código) — Carpeta `/ai`
- `PLAN.md`: especificación funcional y técnica para guiar la implementación con IA.
- Skills y reglas: `CLAUDE.md` u otros archivos de configuración del agente.
- `DECISIONS.md`: **4-6 entradas** donde la IA propuso algo y se decidió lo contrario por criterio senior.
- **3-5 prompts clave** del proceso.
- **Trazabilidad en commits**: que se distinga colaboración con IA de criterio propio.

### Qué evalúan detrás de la IA
- **Dirección vs. dejarse dirigir** (iteración con criterio, no el primer output).
- **Coherencia arquitectónica** (dominio puro pese a sugerencias genéricas de la IA).
- **Detección de errores** (cazar alucinaciones, APIs inventadas, tests que no testean nada).
- **Honestidad** (reconocer qué hizo la IA; lo que penalizan es no entender lo generado).

### Entrega
- Repo **público** + README con: descripción y opción elegida, **OpenAPI**, modelado del dominio, instrucciones `docker-compose up`, comando de tests, enlace a `/ai`.

---

## 2. Decisiones de estrategia (ya tomadas)

| # | Decisión | Razón |
|---|---|---|
| E1 | **Opción A** (no B) | Dominio DDD canónico y verificable contra literatura sólida; sin dependencia de APIs externas (la B con embeddings arriesga alucinaciones/APIs inventadas, justo lo que penalizan). Permite lucir el DDD/Hexagonal/CQRS que es donde más aprietan. |
| E2 | **GitHub** como repo de entrega, **espejado en Forgejo** propio (`git.kineticode.xyz`) con deploy vía Coolify | GitHub = siempre disponible, cero fricción para quien evalúa. El espejo en Forgejo se menciona en el README como **capacidad de infraestructura** (no como "backup"). |
| E3 | **Repo público desde el primer commit**, con `.gitignore` puesto ANTES del primer `git add` | Un repo es público en TODA su historia. Secretos en commit 3 borrados en commit 4 siguen visibles. La Opción A no necesita secretos reales (pago = puerto fake), lo que facilita la higiene. |
| E4 | Desarrollo en **WSL2 + Ubuntu**, proyecto en el home de Linux (`~`), NO en `/mnt/c/...` | Evita fricción de Windows nativo (rutas, permisos, CRLF) y el rendimiento pésimo de Docker sobre el FS de Windows montado. |
| E5 | **PHP 8.3 dentro de Docker**, NO usar el PHP 8.5 del host | El host trae PHP 8.5 (Ubuntu Resolute), demasiado nuevo para el ecosistema Symfony/Doctrine. Se encapsula 8.3 en el contenedor: entorno de desarrollo = entorno de ejecución, sin desajustes. No se degrada el host, se aísla. |
| E6 | **Claude Code en terminal** construye; **el chat** piensa | El chat = taller de diseño (qué y porqué). Claude Code = taller de construcción (cómo), con trazabilidad real en commits. Documentos `PLAN.md` y `CLAUDE.md` son la bisagra entre ambos. |
| E7 | Construir **pieza a pieza** con Claude Code, no "construye todo de golpe" | "Todo de golpe" = dejarse dirigir, código no entendido, commits sin historia. Pieza a pieza = criterio demostrable. |

---

## 3. Modelado del dominio (NÚCLEO — cerrado por criterio propio)

### Principio rector que atraviesa todo
**La invariante vive donde tiene sentido, no en todas partes por defecto.** (Surgió al sacar el "no-negativo" fuera de `Money`.) Y: **el dominio es PHP puro; no llama a nadie, se le entregan los datos.** (Como un servicio que escucha en `127.0.0.1`: no sale al exterior, se lo sirven.)

### Las DOS separaciones (ambas pedidas por la prueba)
- **Vertical (capas):** Dominio / Aplicación / Infraestructura → arquitectura hexagonal. La dependencia apunta siempre hacia dentro. Symfony y Doctrine SOLO en Infraestructura.
- **Horizontal (contextos):** Carrito (efímero, mutable) vs Orden (permanente, inmutable). Son módulos separados; NO se reutilizan clases entre ellos.

---

### 3.1 Value Object: `Money`

- **Qué es:** importe + moneda. Value object (se define por su valor, no por identidad; dos Money de 19,99 € son intercambiables).
- **Importe como entero en céntimos.** `19,99 €` = `1999`. **NUNCA `float`** (los float no representan decimales con exactitud → descuadres en totales). Los dos últimos dígitos son siempre los céntimos: `5` = 0,05 €; `500` = 5,00 €; `1999` = 19,99 €.
- **Lleva moneda obligatoria.** Un número suelto no es dinero.
- **Permite importe NEGATIVO.** Es aritmética pura; el negativo representa un descuento que se resta. La regla "no negativo" NO vive aquí.
- **Inmutable.** Sumar/restar/multiplicar devuelve un `Money` nuevo.
- **Autovalidado:** importe entero, moneda no vacía. Estado inválido imposible de construir.
- **Se niega a operar monedas distintas** (lanza excepción). No hay tipo de cambio en el dominio del carrito.
- **NO se formatea a texto** (`"19,99 €"`). El formateo (símbolo, coma/punto, idioma) es de la capa de presentación. El dominio habla en céntimos; la conversión a/desde euros ocurre en el borde.

> **Decisión clave (caso del -10 €):** Artículo 100 € − descuento 30 € que excede... ¿precio −10 € o 0 €? Reveló que hay DOS conceptos distintos: el importe bruto (puede ser negativo, es aritmética) y el **precio final a pagar** (nunca negativo, suelo 0 €). La regla "nunca negativo" pertenece al **cálculo del total**, no a `Money`.

> **Pendiente de modelado anotado:** las **devoluciones** son postventa (ocurren tras existir la orden), NO son del carrito. Fuera de alcance de la prueba. Saber distinguir "carrito" de "postventa" = madurez de fronteras.

> **Pendiente técnico:** redondeo en multiplicaciones con decimales (p.ej. descuento 15%). De quién es la responsabilidad del redondeo — a decidir al implementar. Con enteros y cantidades enteras no aparece, pero hay que tenerlo presente para promociones porcentuales.

---

### 3.2 Value Object: `Quantity`

- **Qué es:** cuántas unidades de un producto en una línea. Value object inmutable que envuelve un entero.
- **Mínimo 1** (≥ 1). Rechaza cero y negativos en el constructor.
  - Negativo: dato corrupto / error de programación, no caso de negocio. Excepción.
  - Cero: NO se usa como "eliminar". Eliminar es operación explícita aparte.
- **Distinción estado vs operación:** la cantidad es un *valor* (1, 2, 3...); añadir/actualizar/eliminar son *operaciones*. No colar el valor 0 como si fuera la operación "borrar".
- **Actualización por SET (absoluto), no DELTA (relativo).** "Pon la cantidad a N" es predecible (no depende del estado previo ni del orden de peticiones). "Réstale 1" es frágil.

> Mapea directo al enunciado: "añadir, actualizar, eliminar" = las tres operaciones explícitas.

---

### 3.3 Entidad (dentro del agregado): `CartItem`

- **Qué es:** una línea del carrito. **Entidad** con identidad = el producto (no value object).
- **Guarda:** identificador de producto + `Quantity`.
- **NO guarda precio** (ni copia muerta). El carrito refleja el precio **vigente** del catálogo (decisión 3.5).
- **Dos líneas del mismo producto NO existen:** si el producto ya está, se **fusiona** (suma cantidad), no se duplica. La inteligencia de fusión vive en el **`Cart`**, no en la línea. La línea es "tonta": solo sabe de su producto y su cantidad.
- **Stock NO es del CartItem ni del carrito.** El stock pertenece al **inventario/catálogo**. El carrito lo **consulta**, no lo gestiona.

---

### 3.4 Agregado raíz: `Cart`

- **Qué es:** agregado raíz que controla sus `CartItem`. Entidad con identidad propia (`CartId`).
- **Regla de oro del agregado:** al exterior SOLO se accede por la raíz. Nadie agarra un `CartItem` y lo modifica directo; se le pide al `Cart`. (Como un reverse proxy: único punto de entrada que aplica las reglas.)
- **Vive en el SERVIDOR, es persistente.** No en el navegador. Por eso dos dispositivos (portátil + móvil) ven el mismo carrito y el mismo precio vigente. Encaja con "obtención del contenido del carrito" (se recupera por `CartId`).
- **Estado:** `CartId` + colección de `CartItem`.
- **Operaciones** (= enunciado): añadir producto (fusiona o crea), actualizar cantidad a valor concreto (set, ≥1), eliminar producto (explícito), calcular total.
- **Invariantes que protege:** nunca dos líneas del mismo producto; ninguna cantidad < 1; **total nunca negativo** (suelo 0).
- **NO conoce el catálogo ni guarda precios.** Para calcular el total, **la capa de aplicación le ENTREGA los precios** (consultados al catálogo vía puerto). El `Cart` solo hace aritmética con lo que recibe → dominio puro + testeable sin mocks.
- **Cálculo del total dentro del `Cart`** (no en servicio aparte). Simple. Solo se extraería a servicio de dominio si las promociones se complicaran mucho (no es el caso).

---

### 3.5 Regla del PRECIO (transversal, innegociable)

**Mientras está en el carrito:** el precio puede cambiar; refleja el **vigente** y se avisa al cliente si cambió ("este artículo ha cambiado de precio").

**Al pulsar pagar (checkout):** el precio se **CONGELA** en la orden.

Tres razones independientes que apuntan a "el precio NO vive en el carrito":
1. **Consistencia:** refleja siempre lo vigente.
2. **Seguridad:** el precio NUNCA viene del cliente. Si el carrito serializa el precio hacia el navegador, un atacante lo cambia (1999 → 1) y compra por un céntimo. El precio es la fuente de verdad del **servidor**, consultada al catálogo. El cliente solo elige qué producto y cuántos.
3. **Pureza arquitectónica:** el dominio no toca infraestructura (catálogo).

> El aviso de cambio de precio al cliente = sad path **consciente fuera de alcance** (es UX). Lo innegociable a implementar: **el checkout congela**.

---

### 3.6 Patrón simétrico PRECIO / STOCK

| | En el carrito | En el checkout |
|---|---|---|
| **Precio** | Blando: refleja vigente, avisa | Duro: **congela** en la orden |
| **Stock** | Blando: consulta, avisa ("quedan 3") | Duro: **valida** o falla limpiamente antes de cobrar |

Misma filosofía: el carrito refleja y avisa; el checkout valida y confirma o falla. Coherencia, no reglas sueltas.

> Para la prueba: validación de stock en carrito = mínima o fuera de alcance. **Foco en la validación dura del checkout** (sad path crítico: "¿qué pasa si al pagar no hay stock?"). Catálogo con stock = repositorio simple, no sistema de inventario completo (sería sobre-ingeniería).

---

### 3.7 Contexto Orden: `OrderLine`, `Order`, `OrderStatus`

**`OrderLine`** — como `CartItem` PERO **con precio congelado**. Guarda producto + cantidad + **precio al que se compró**. Es el registro histórico "esto se vendió a este precio este día". NO se reutiliza la clase `CartItem` (contextos separados).

**`Order`** — agregado, espejo inverso del carrito:

| Eje | Cart | Order |
|---|---|---|
| Mutabilidad | mutable | contenido **inmutable** |
| Duración | efímero | permanente |
| Precio | vigente | **congelado** |
| Destino | desechable | registro histórico |

- **Guarda:** `OrderId` propio, líneas (`OrderLine` con precio congelado), `OrderStatus`, total congelado.
- **Lo único que evoluciona tras crearse: el `OrderStatus`.** El contenido es inmutable desde el nacimiento.
- **Independiente del carrito:** NO guarda referencia al `CartId`. El carrito se borra; apuntar a algo inexistente no aporta. La orden se basta a sí misma.

**`OrderStatus`** — transiciones controladas (el agregado es guardián):
- `PENDING → PAID` (pago OK)
- `PENDING → FAILED` (pago rechazado)
- Prohibidas: `PAID → PENDING/FAILED`, `FAILED → PAID`. Transición ilegal = excepción.
- **`FAILED` es TERMINAL** (no reintentable). Reintentar no soluciona la causa original (tarjeta rechazada, fondos); solo añade complejidad para un caso raro. Reintentar = crear orden nueva. Cada orden = un intento único y trazable.

---

### 3.8 Caso de uso: CHECKOUT (capa de aplicación)

**Orden de pasos (orden ANTES del pago, clave de robustez):**
1. Recupera el carrito por `CartId`.
2. Consulta precios vigentes del catálogo (puerto).
3. **Valida stock duro.** Si no hay → falla limpiamente ANTES de cobrar (sad path).
4. Construye la `Order` en estado **`PENDING`** con líneas y precios **congelados**.
5. Llama al puerto **`PaymentGateway`**.
6. Según respuesta: `PENDING → PAID` o `PENDING → FAILED`.
7. Si `PAID`: elimina el carrito.
8. Persiste la orden (repositorio = puerto).

**Por qué orden antes que pago:** si cobraras primero y crearas la orden después, un fallo entre medias = dinero cobrado sin registro de pedido (desastre de conciliación). Creando la orden en `PENDING` primero, SIEMPRE hay rastro. Nunca cobro sin orden.

**Pago como PUERTO con adaptador FAKE.** El dominio no conoce Stripe/Redsys; conoce una abstracción "algo que cobra y responde OK/KO". Adaptador fake simula resultados → testea happy path (OK → PAID) y sad path (KO → FAILED) sin tocar internet. Blinda contra inventar SDKs (pecado penalizado) + buena testabilidad.

**El dominio sigue puro:** el checkout (aplicación) toca varios puertos (catálogo, pago, repos de orden y carrito); `Order` y `Cart` no llaman a nadie. El dominio define reglas; la aplicación las orquesta con el exterior.

---

## 4. Mapa de capas (hexagonal)

```
Dominio (PHP puro, sin Symfony/Doctrine)
  Cart/        Money, Quantity, CartItem, Cart (agg root), CartId, eventos, excepciones
  Order/       Order (agg root), OrderLine, OrderStatus, OrderId
  Puertos (interfaces): CartRepository, OrderRepository, ProductCatalog, PaymentGateway

Aplicación (casos de uso, CQRS)
  Commands (mutan): AddItem, UpdateItem, RemoveItem, Checkout
  Queries (leen): GetCart  → devuelven DTOs de lectura, no exponen el agregado

Infraestructura (adaptadores)
  Doctrine (repos), controllers Symfony, adaptador PaymentGateway fake, catálogo
```

**CQRS ligero y honesto:** comandos que mutan (void o id), queries que leen (DTOs). NO event sourcing ni buses complejos (sería sobre-ingeniería = dejarse dirigir). Proporcionado = criterio senior.

---

## 5. Reglas para el `CLAUDE.md` (a redactar)

Derivadas del modelado. El agente DEBE respetar:
- Dinero SIEMPRE entero en céntimos. **Prohibido `float` para dinero.**
- Dominio = PHP puro. **Prohibido** importar Symfony/Doctrine en la capa de dominio; sin `extends` de clases de framework; sin anotaciones Doctrine en entidades de dominio.
- Value objects inmutables y autovalidados.
- Entidades con métodos de intención de negocio, **NO getters/setters anémicos**.
- **Prohibido estilo Laravel/Eloquent:** sin Active Record (entidad que se guarda a sí misma), sin facades, sin `Model::all()`. Persistencia vía repositorio (Data Mapper), en Infraestructura.
- Acceso al agregado SOLO por la raíz.
- Tests: **cada test debe poder fallar.** Nada de tests que solo verifican "no lanza excepción" ni que mockean tanto que comprueban el mock. Dominio se testea **sin mocks**; mocks SOLO en puertos.
- No inventar librerías: verificar en Packagist antes de añadir.
- Preferir lo simple a lo sobre-diseñado. Respetar el alcance del enunciado.
- Recordar los requisitos de la prueba como restricciones duras (sección 1).

---

## 6. Candidatos para `DECISIONS.md` (se rellenan en caliente al implementar)

Momentos donde la IA propondrá X y se decide Y:
1. **Dinero:** IA propondrá `float` → rechazado, enteros en céntimos.
2. **Negativo en Money:** decisión razonada de permitirlo (descuentos) y poner "no negativo" en el total, no en `Money`.
3. **Getters/setters anémicos** → rechazados, métodos con intención.
4. **Lógica en el controller** → rechazada, va al handler (aplicación).
5. **Reutilizar `CartItem` en la orden** → rechazado, `OrderLine` separada (contextos).
6. **Test que no testea nada** (solo "no lanza excepción" o mock que se comprueba a sí mismo) → rechazado/reescrito.
7. **Sobre-ingeniería** (event sourcing, bus complejo, sistema de inventario completo) → rechazada, proporcionalidad.

(Elegir las 4-6 reales que de verdad ocurran. Auténticas, no reconstruidas.)

---

## 7. Estado del entorno (montado hoy)

- ✅ WSL2 con **Ubuntu** (Resolute, PHP 8.5 de fábrica — NO se usa para el proyecto).
- ✅ `git` 2.53.0.
- ✅ **Docker** operativo desde Ubuntu sin `sudo` (usuario en grupo `docker`; integración WSL de Docker Desktop activada). Docker 28.3.2, Compose v2.38.2.
- ⏳ PHP/Composer del proyecto: irán **en el contenedor** (PHP 8.3), no en el host.
- ⏳ Claude Code: instalar en Ubuntu (`curl -fsSL https://claude.ai/install.sh | bash`). Requiere cuenta de pago (Pro/Max/Console). Arrancar con `claude` DENTRO de la carpeta del repo.

### Gotchas del entorno (lecciones de hoy)
- `wsl --shutdown` apaga TODAS las distros, incluida la de Docker Desktop → Docker protesta. Para refrescar solo Ubuntu: `wsl --terminate Ubuntu`.
- Cambio de grupo (`usermod -aG docker`) no aplica a la sesión actual → requiere reiniciar WSL.
- Proyecto en `~` (FS Linux), NUNCA en `/mnt/c/...` (Docker lentísimo ahí).
- El host trae PHP 8.5; no clavar `php8.3-*` en apt — usar metapaquetes o, mejor, todo en Docker.

---

## 8. Pendientes / próximos pasos

### Inmediato (en el chat, son diseño)
- [ ] Redactar **`PLAN.md`** completo (spec funcional+técnica para `/ai`): reglas de negocio, modelado, casos de uso, happy/sad paths, decisiones arquitectónicas, restricciones.
- [ ] Redactar **`CLAUDE.md`** (sección 5 desarrollada).

### En terminal (Claude Code, es construcción)
- [ ] Crear repo público en GitHub con `.gitignore` ANTES del primer add.
- [ ] Primer commit = `CLAUDE.md`.
- [ ] Infraestructura: `docker-compose.yml` + `Dockerfile` (PHP 8.3) + Symfony + BD (MySQL).
- [ ] Dominio pieza a pieza (orden de diseño): `Money` → `Quantity` → `CartItem` → `Cart` → `OrderLine`/`Order` → checkout. Cada pieza con sus tests, revisada y entendida, commit con mensaje que refleje criterio propio.
- [ ] Casos de uso CQRS + adaptadores (Doctrine, controllers, PaymentGateway fake, catálogo).
- [ ] **OpenAPI** spec.
- [ ] **Performance medida con datos** (requisito duro; pensar qué medir: p.ej. tiempo de checkout, carga del carrito).
- [ ] README completo (descripción, opción, OpenAPI, modelado, `docker-compose up`, comando tests, enlace `/ai`).
- [ ] `/ai`: `PLAN.md`, `CLAUDE.md`, `DECISIONS.md` (4-6), 3-5 prompts clave, agentes custom si los hay.

### Recordatorios de método
- Pasar a **chat normal (no incógnito)** para conservar el hilo del diseño entre sesiones.
- Pieza a pieza, no "construye todo". Rechazos → `DECISIONS.md` en caliente.
- Todo lo que se diseñe debe rastrearse a un requisito de la sección 1. Si no sirve a ninguno, sobra.

---

## 9. Frases-resumen para la entrevista (defensa del diseño)

- *"El precio no vive en el carrito por tres razones independientes: consistencia, seguridad y pureza arquitectónica. Cuando una decisión se sostiene desde tres ángulos, ya no es opinión, es diseño."*
- *"Carrito y orden son contextos separados: el carrito refleja el precio vigente, la orden lo congela. El checkout es el instante del congelado."*
- *"Creo la orden en PENDING antes de cobrar, no después: así nunca cobro sin dejar rastro del pedido."*
- *"El dominio no llama a nadie; se le entregan los datos. Por eso es PHP puro y testeable sin mocks."*
- *"Precio y stock siguen el mismo patrón: blandos en el carrito, duros en el checkout. Coherencia, no reglas sueltas."*
- *"La invariante vive donde tiene sentido: el 'no negativo' no está en Money, está en el cálculo del total."*

---

**Fin del documento de contexto v1.**
